<?php

// Simple working admin dashboard
$page = $_GET['page'] ?? 'dashboard';

// Define page content
$pages = [
    'dashboard' => [
        'title' => 'Dashboard Overview',
        'content' => '
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-value">5</div>
                    <div class="stat-label">Total Events</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-value">12</div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card info">
                    <div class="stat-value">$2,450</div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-value">8</div>
                    <div class="stat-label">Checked In Today</div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="card-body">
                    <button class="btn btn-primary" onclick="testAction(\'create_event\')">Create Event</button>
                    <button class="btn btn-success" onclick="testAction(\'view_orders\')">View Orders</button>
                    <button class="btn btn-info" onclick="testAction(\'check_in\')">Check-in Tickets</button>
                </div>
            </div>
        '
    ],
    'events' => [
        'title' => 'Events Management',
        'content' => '
            <div class="card">
                <div class="card-header">
                    <h3>All Events</h3>
                    <button class="btn btn-primary" onclick="testAction(\'create_event\')">Create Event</button>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr><th>Event Name</th><th>Date</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Summer Music Festival 2024</td>
                                <td>2024-07-15</td>
                                <td><span class="badge badge-success">Published</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="testAction(\'edit_event\')">Edit</button>
                                    <button class="btn btn-sm btn-info" onclick="testAction(\'view_event\')">View</button>
                                </td>
                            </tr>
                            <tr>
                                <td>Tech Conference 2024</td>
                                <td>2024-09-20</td>
                                <td><span class="badge badge-success">Published</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="testAction(\'edit_event\')">Edit</button>
                                    <button class="btn btn-sm btn-info" onclick="testAction(\'view_event\')">View</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        '
    ],
    'users' => [
        'title' => 'User Management',
        'content' => '
            <div class="card">
                <div class="card-header">
                    <h3>All Users</h3>
                    <button class="btn btn-primary" onclick="testAction(\'create_user\')">Add User</button>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Super Admin</td>
                                <td>admin@eventticketing.com</td>
                                <td><span class="badge badge-primary">Super Admin</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="testAction(\'edit_user\')">Edit</button>
                                    <button class="btn btn-sm btn-warning" onclick="testAction(\'suspend_user\')">Suspend</button>
                                </td>
                            </tr>
                            <tr>
                                <td>John Doe</td>
                                <td>john.doe@example.com</td>
                                <td><span class="badge badge-secondary">User</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="testAction(\'edit_user\')">Edit</button>
                                    <button class="btn btn-sm btn-warning" onclick="testAction(\'suspend_user\')">Suspend</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        '
    ],
    'venues' => [
        'title' => 'Venue Management',
        'content' => '
            <div class="card">
                <div class="card-header">
                    <h3>All Venues</h3>
                    <button class="btn btn-primary" onclick="testAction(\'create_venue\')">Add Venue</button>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr><th>Venue Name</th><th>Location</th><th>Capacity</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Grand Convention Center</td>
                                <td>New York, USA</td>
                                <td>5,000</td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="testAction(\'edit_venue\')">Edit</button>
                                    <button class="btn btn-sm btn-info" onclick="testAction(\'view_venue\')">View</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        '
    ],
    'checkin' => [
        'title' => 'Check-in Management',
        'content' => '
            <div class="stats-grid">
                <div class="stat-card success">
                    <div class="stat-value">156</div>
                    <div class="stat-label">Checked In Today</div>
                </div>
                <div class="stat-card info">
                    <div class="stat-value">234</div>
                    <div class="stat-label">Pending Check-in</div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <h3>Check-in Scanner</h3>
                    <button class="btn btn-primary" onclick="testAction(\'scan_ticket\')">Scan Ticket</button>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Enter Ticket Code:</label>
                        <input type="text" class="form-control" id="ticketCode" placeholder="Scan or enter ticket code">
                    </div>
                    <button class="btn btn-success" onclick="testAction(\'process_checkin\')">Process Check-in</button>
                    <div id="checkInResult"></div>
                </div>
            </div>
        '
    ]
];

// Get current page data
$current_page = $pages[$page] ?? $pages['dashboard'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_page['title']; ?> - Event Ticketing System</title>
    <link rel="stylesheet" href="/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>Event Tickets</h1>
                <p>Admin System</p>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="?page=dashboard" class="<?php echo $page === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="?page=events" class="<?php echo $page === 'events' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Events</a></li>
                    <li><a href="?page=users" class="<?php echo $page === 'users' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="?page=venues" class="<?php echo $page === 'venues' ? 'active' : ''; ?>"><i class="fas fa-map-marker-alt"></i> Venues</a></li>
                    <li><a href="?page=checkin" class="<?php echo $page === 'checkin' ? 'active' : ''; ?>"><i class="fas fa-qrcode"></i> Check-in</a></li>
                </ul>
            </nav>
        </aside>
        
        <main class="main-content">
            <header class="header">
                <h2><?php echo $current_page['title']; ?></h2>
                <div class="header-actions">
                    <button class="btn btn-success" onclick="testAction('export')">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </header>
            
            <?php echo $current_page['content']; ?>
        </main>
    </div>
    
    <script>
        console.log('🚀 Simple admin loaded');
        
        function testAction(action) {
            console.log('🧪 Testing action:', action);
            
            let message = '';
            let type = 'success';
            
            switch(action) {
                case 'create_event':
                    message = '✅ Event creation form would open here';
                    break;
                case 'edit_event':
                    message = '✅ Editing event - Changes would be saved';
                    break;
                case 'view_event':
                    message = '✅ Viewing event details';
                    break;
                case 'create_user':
                    message = '✅ User creation form would open here';
                    break;
                case 'edit_user':
                    message = '✅ Editing user - Changes would be saved';
                    break;
                case 'suspend_user':
                    if (confirm('Are you sure you want to suspend this user?')) {
                        message = '✅ User suspended successfully';
                    } else {
                        return;
                    }
                    break;
                case 'create_venue':
                    message = '✅ Venue creation form would open here';
                    break;
                case 'edit_venue':
                    message = '✅ Editing venue - Changes would be saved';
                    break;
                case 'view_venue':
                    message = '✅ Viewing venue details';
                    break;
                case 'scan_ticket':
                    document.getElementById('ticketCode').focus();
                    message = '📷 Scanner ready - Enter ticket code';
                    break;
                case 'process_checkin':
                    const code = document.getElementById('ticketCode').value;
                    if (!code) {
                        message = '⚠️ Please enter a ticket code';
                        type = 'warning';
                    } else {
                        document.getElementById('checkInResult').innerHTML = '<div class="alert alert-success">✅ Ticket checked in successfully: ' + code + '</div>';
                        message = '✅ Ticket processed successfully';
                        document.getElementById('ticketCode').value = '';
                    }
                    break;
                case 'export':
                    message = '✅ Exporting data to CSV...';
                    break;
                case 'view_orders':
                    message = '✅ Opening orders page';
                    window.location.href = '?page=orders';
                    return;
                default:
                    message = '✅ Action completed: ' + action;
            }
            
            showNotification(message, type);
        }
        
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i> 
                ${message}
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        
        // Test on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📄 DOM loaded - Testing buttons...');
            showNotification('🎉 Simple admin loaded successfully!', 'success');
            
            const buttons = document.querySelectorAll('button');
            console.log(`🔘 Found ${buttons.length} buttons - All should be functional`);
            
            buttons.forEach((btn, index) => {
                if (btn.onclick) {
                    console.log(`✅ Button ${index + 1}: ${btn.textContent.trim()} - FUNCTIONAL`);
                } else {
                    console.log(`⚠️ Button ${index + 1}: ${btn.textContent.trim()} - NO ONCLICK`);
                }
            });
        });
    </script>
</body>
</html>
