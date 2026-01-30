<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Event Ticketing System</title>
    <link rel="stylesheet" href="/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>Event Tickets</h1>
                <p>Admin Dashboard</p>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="#" class="active">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="#events">
                            <i class="fas fa-calendar-alt"></i>
                            Events
                        </a>
                    </li>
                    <li>
                        <a href="#orders">
                            <i class="fas fa-shopping-cart"></i>
                            Orders
                        </a>
                    </li>
                    <li>
                        <a href="#tickets">
                            <i class="fas fa-ticket-alt"></i>
                            Tickets
                        </a>
                    </li>
                    <li>
                        <a href="#venues">
                            <i class="fas fa-map-marker-alt"></i>
                            Venues
                        </a>
                    </li>
                    <li>
                        <a href="#users">
                            <i class="fas fa-users"></i>
                            Users
                        </a>
                    </li>
                    <li>
                        <a href="#checkin">
                            <i class="fas fa-qrcode"></i>
                            Check-in
                        </a>
                    </li>
                    <li>
                        <a href="#analytics">
                            <i class="fas fa-chart-line"></i>
                            Analytics
                        </a>
                    </li>
                    <li>
                        <a href="#reports">
                            <i class="fas fa-file-alt"></i>
                            Reports
                        </a>
                    </li>
                    <li>
                        <a href="#settings">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <h2>Dashboard Overview</h2>
                <div class="header-actions">
                    <button class="btn btn-outline">
                        <i class="fas fa-download"></i>
                        Export Report
                    </button>
                    <button class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        New Event
                    </button>
                </div>
            </header>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-value" id="totalEvents">0</div>
                    <div class="stat-label">Total Events</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>12% from last month</span>
                    </div>
                </div>
                <div class="stat-card success">
                    <div class="stat-value" id="totalOrders">0</div>
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>8% from last month</span>
                    </div>
                </div>
                <div class="stat-card info">
                    <div class="stat-value" id="totalRevenue">$0</div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>23% from last month</span>
                    </div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-value" id="checkedInTickets">0</div>
                    <div class="stat-label">Checked In Today</div>
                    <div class="stat-change negative">
                        <i class="fas fa-arrow-down"></i>
                        <span>5% from yesterday</span>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="card">
                <div class="card-header">
                    <h3>Revenue Overview</h3>
                    <select class="form-control" style="width: 150px;">
                        <option>Last 7 days</option>
                        <option>Last 30 days</option>
                        <option>Last 3 months</option>
                        <option>Last year</option>
                    </select>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Orders & Upcoming Events -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header">
                        <h3>Recent Orders</h3>
                        <a href="#orders" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="recentOrders">
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <div class="spinner"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <div class="card">
                    <div class="card-header">
                        <h3>Upcoming Events</h3>
                        <a href="#events" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Event</th>
                                        <th>Date</th>
                                        <th>Tickets Sold</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="upcomingEvents">
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <div class="spinner"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <button class="btn btn-primary" onclick="createEvent()">
                            <i class="fas fa-plus"></i>
                            Create Event
                        </button>
                        <button class="btn btn-success" onclick="checkInTicket()">
                            <i class="fas fa-qrcode"></i>
                            Check-in Ticket
                        </button>
                        <button class="btn btn-info" onclick="viewReports()">
                            <i class="fas fa-chart-bar"></i>
                            View Reports
                        </button>
                        <button class="btn btn-warning" onclick="manageUsers()">
                            <i class="fas fa-users-cog"></i>
                            Manage Users
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <div id="createEventModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Create New Event</h3>
                <button class="modal-close" onclick="closeModal('createEventModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="createEventForm">
                    <div class="form-group">
                        <label class="form-label">Event Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Event Type</label>
                        <select class="form-control" name="event_type" required>
                            <option value="">Select Type</option>
                            <option value="concert">Concert</option>
                            <option value="conference">Conference</option>
                            <option value="sports">Sports</option>
                            <option value="theater">Theater</option>
                            <option value="festival">Festival</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Start Date</label>
                            <input type="datetime-local" class="form-control" name="starts_at" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">End Date</label>
                            <input type="datetime-local" class="form-control" name="ends_at" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Venue</label>
                        <select class="form-control" name="venue_id" required>
                            <option value="">Select Venue</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('createEventModal')">Cancel</button>
                <button class="btn btn-primary" onclick="saveEvent()">Create Event</button>
            </div>
        </div>
    </div>

    <div id="checkInModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Check-in Ticket</h3>
                <button class="modal-close" onclick="closeModal('checkInModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Scan QR Code / Enter Ticket Number</label>
                    <input type="text" class="form-control" id="ticketCode" placeholder="Scan or enter ticket code">
                </div>
                <div id="checkInResult"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('checkInModal')">Close</button>
                <button class="btn btn-primary" onclick="processCheckIn()">Check In</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/js/admin.js"></script>
    <script>
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            initializeRevenueChart();
            loadRecentOrders();
            loadUpcomingEvents();
        });

        // Load dashboard statistics
        async function loadDashboardData() {
            try {
                const response = await fetch('/api/analytics/dashboard');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('totalEvents').textContent = data.data.overview.total_events || 0;
                    document.getElementById('totalOrders').textContent = data.data.overview.total_orders || 0;
                    document.getElementById('totalRevenue').textContent = '$' + (data.data.overview.total_revenue || 0).toLocaleString();
                    document.getElementById('checkedInTickets').textContent = data.data.overview.checked_in_tickets || 0;
                }
            } catch (error) {
                console.error('Error loading dashboard data:', error);
            }
        }

        // Initialize revenue chart
        function initializeRevenueChart() {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Revenue',
                        data: [1200, 1900, 3000, 2500, 4200, 3800, 5100],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // Load recent orders
        async function loadRecentOrders() {
            try {
                const response = await fetch('/api/orders?limit=5');
                const data = await response.json();
                
                const tbody = document.getElementById('recentOrders');
                tbody.innerHTML = '';
                
                if (data.success && data.data.data.length > 0) {
                    data.data.data.forEach(order => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${order.order_number}</td>
                            <td>${order.guest_name || 'Guest'}</td>
                            <td>$${order.total_amount}</td>
                            <td><span class="badge badge-${getStatusColor(order.status)}">${order.status}</span></td>
                        `;
                        tbody.appendChild(row);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center">No recent orders</td></tr>';
                }
            } catch (error) {
                console.error('Error loading recent orders:', error);
                document.getElementById('recentOrders').innerHTML = '<tr><td colspan="4" class="text-center">Error loading orders</td></tr>';
            }
        }

        // Load upcoming events
        async function loadUpcomingEvents() {
            try {
                const response = await fetch('/api/events?upcoming&limit=5');
                const data = await response.json();
                
                const tbody = document.getElementById('upcomingEvents');
                tbody.innerHTML = '';
                
                if (data.success && data.events.length > 0) {
                    data.events.forEach(event => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${event.title}</td>
                            <td>${new Date(event.starts_at).toLocaleDateString()}</td>
                            <td>${event.total_tickets || 0}</td>
                            <td><span class="badge badge-${getStatusColor(event.status)}">${event.status}</span></td>
                        `;
                        tbody.appendChild(row);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center">No upcoming events</td></tr>';
                }
            } catch (error) {
                console.error('Error loading upcoming events:', error);
                document.getElementById('upcomingEvents').innerHTML = '<tr><td colspan="4" class="text-center">Error loading events</td></tr>';
            }
        }

        // Helper functions
        function getStatusColor(status) {
            const colors = {
                'paid': 'success',
                'pending': 'warning',
                'cancelled': 'danger',
                'published': 'success',
                'draft': 'secondary',
                'live': 'primary'
            };
            return colors[status] || 'secondary';
        }

        function createEvent() {
            document.getElementById('createEventModal').classList.add('show');
        }

        function checkInTicket() {
            document.getElementById('checkInModal').classList.add('show');
            document.getElementById('ticketCode').focus();
        }

        function viewReports() {
            window.location.href = '#reports';
        }

        function manageUsers() {
            window.location.href = '#users';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        async function saveEvent() {
            const form = document.getElementById('createEventForm');
            const formData = new FormData(form);
            
            try {
                const response = await fetch('/api/events', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + localStorage.getItem('authToken')
                    },
                    body: JSON.stringify(Object.fromEntries(formData))
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeModal('createEventModal');
                    loadUpcomingEvents();
                    showAlert('Event created successfully!', 'success');
                } else {
                    showAlert(result.error || 'Error creating event', 'danger');
                }
            } catch (error) {
                console.error('Error creating event:', error);
                showAlert('Error creating event', 'danger');
            }
        }

        async function processCheckIn() {
            const code = document.getElementById('ticketCode').value;
            const resultDiv = document.getElementById('checkInResult');
            
            if (!code) {
                showAlert('Please enter a ticket code', 'warning');
                return;
            }
            
            try {
                const response = await fetch('/api/tickets/check-in', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + localStorage.getItem('authToken')
                    },
                    body: JSON.stringify({
                        code: code,
                        gate_id: 1,
                        device_id: 'web',
                        operator_id: 1
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            ${result.message}
                            <br><small>${result.ticket_info.attendee_name} - ${result.ticket_info.event_title}</small>
                        </div>
                    `;
                    document.getElementById('ticketCode').value = '';
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            ${result.error}
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error processing check-in:', error);
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Error processing check-in
                    </div>
                `;
            }
        }

        function showAlert(message, type) {
            // Create and show alert
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;
            
            document.querySelector('.main-content').insertBefore(alert, document.querySelector('.main-content').firstChild);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
    </script>
</body>
</html>
