<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Time Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .timer-display {
            font-size: 2rem;
            font-weight: bold;
        }

        .timer-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .timer-buttons {
            margin-top: 15px;
        }

        .timer-status {
            color: #28a745;
            font-weight: bold;
        }

        .timer-inactive {
            color: #dc3545;
        }

        .chart-container {
            height: 400px;
            margin-top: 30px;
        }
        #project{
            background-color: orange !important;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Time Tracker</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" id="userDropdown"
                            data-bs-toggle="dropdown">
                            {{ Auth::user()->name }}
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card timer-card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex flex-column">
                                    <div class="text-muted">Since last task resume</div>
                                    <div class="timer-display" id="since-last-activity">{{ $lastActivityFormatted }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex flex-column">
                                    <div class="text-muted">Total current working day</div>
                                    <div class="timer-display" id="total-today">{{ $totalTodayFormatted }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <div class="timer-status" id="timer-status-text">
                                @if($timerActive)
                                    Active
                                @else
                                    Inactive
                                @endif
                            </div>
                        </div>

                        <div class="bg-success text-white p-3 rounded mt-3" id="project">
                            <div class="text-center">Default Project</div>
                            <div class="text-center">Default Task</div>
                        </div>

                        <div class="d-flex justify-content-center timer-buttons mt-3">
                            <button id="start-timer-btn" class="btn btn-success me-2" @if($timerActive)
                            style="display: none;" @endif>
                                Start Timer
                            </button>
                            <button id="stop-timer-btn" class="btn btn-danger" @if(!$timerActive) style="display: none;"
                            @endif>
                                Stop Timer
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Monthly Time Log</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Tasks</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="tasksTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="recent-tab" data-bs-toggle="tab"
                                    data-bs-target="#recent" type="button">Recent Tasks</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="all-tab" data-bs-toggle="tab" data-bs-target="#all"
                                    type="button">All Tasks</button>
                            </li>
                        </ul>
                        <div class="tab-content mt-3" id="tasksTabContent">
                            <div class="tab-pane fade show active" id="recent" role="tabpanel">
                                <div class="text-center py-4">
                                    This tab only shows recent tasks.
                                    <div><a href="#">Click here to go to a tab that shows all tasks.</a></div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="all" role="tabpanel">
                                <div class="text-center py-4">
                                    All tasks will be displayed here.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            let timerInterval;
            let chartInstance;
            let timerActive = {{ $timerActive ? 'true' : 'false' }};

            // Initial load of the chart
            loadMonthlyStats();

            // Start timer button
            document.getElementById('start-timer-btn').addEventListener('click', function () {
                fetch('{{ route("timer.start") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            document.getElementById('start-timer-btn').style.display = 'none';
                            document.getElementById('stop-timer-btn').style.display = 'inline-block';
                            document.getElementById('timer-status-text').textContent = 'Active';
                            document.getElementById('timer-status-text').classList.remove('timer-inactive');
                            document.getElementById('timer-status-text').classList.add('timer-status');
                            timerActive = true;
                            startTimerUpdates();
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });

            // Stop timer button
            document.getElementById('stop-timer-btn').addEventListener('click', function () {
                fetch('{{ route("timer.stop") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            document.getElementById('stop-timer-btn').style.display = 'none';
                            document.getElementById('start-timer-btn').style.display = 'inline-block';
                            document.getElementById('timer-status-text').textContent = 'Inactive';
                            document.getElementById('timer-status-text').classList.remove('timer-status');
                            document.getElementById('timer-status-text').classList.add('timer-inactive');
                            timerActive = false;
                            clearInterval(timerInterval);
                            loadMonthlyStats(); // Refresh the chart
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });

            function startTimerUpdates() {
                // Clear any existing interval
                if (timerInterval) {
                    clearInterval(timerInterval);
                }

                // Update timer every second
                timerInterval = setInterval(updateTimerDisplay, 1000);
            }

            function updateTimerDisplay() {
                fetch('{{ route("timer.status") }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.is_active) {
                            const totalSeconds = data.duration;

                            // Update last activity timer
                            const hours = Math.floor(totalSeconds / 3600);
                            const minutes = Math.floor((totalSeconds % 3600) / 60);
                            document.getElementById('since-last-activity').textContent =
                                `${String(hours).padStart(2, '0')} hours ${String(minutes).padStart(2, '0')} minutes`;

                            // Also update the total for today
                            updateTotalTodayDisplay();
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }

            function updateTotalTodayDisplay() {
                // This is a simplified version - in a real app, you'd calculate the total
                // for all logs today including the current one
                fetch('{{ route("timer.status") }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.is_active) {
                            const now = new Date();
                            const startedAt = new Date(data.started_at);
                            const diffSeconds = Math.floor((now - startedAt) / 1000);

                            // For demo purposes, we're just showing the current timer
                            // In a real app, you'd add up all timers for today
                            const hours = Math.floor(diffSeconds / 3600);
                            const minutes = Math.floor((diffSeconds % 3600) / 60);
                            document.getElementById('total-today').textContent =
                                `${String(hours).padStart(2, '0')} hours ${String(minutes).padStart(2, '0')} minutes`;
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }

            function loadMonthlyStats() {
                fetch('{{ route("timer.monthly-stats") }}')
                    .then(response => response.json())
                    .then(data => {
                        renderMonthlyChart(data);
                    })
                    .catch(error => console.error('Error:', error));
            }

            function renderMonthlyChart(data) {
                const ctx = document.getElementById('monthlyChart').getContext('2d');

                // Destroy the previous chart if it exists
                if (chartInstance) {
                    chartInstance.destroy();
                }

                // Extract data for the chart
                const labels = data.map(item => item.date);
                const hours = data.map(item => item.hours);

                // Create the chart
                chartInstance = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Hours Worked',
                            data: hours,
                            backgroundColor: 'rgba(0, 123, 255, 0.5)',
                            borderColor: 'rgba(0, 123, 255, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 8,
                                title: {
                                    display: true,
                                    text: 'Hours'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Date'
                                }
                            }
                        }
                    }
                });
            }

            // If timer is active, start updates
            if (timerActive) {
                startTimerUpdates();
            }
        });
    </script>
</body>

</html>