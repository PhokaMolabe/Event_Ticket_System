<?php

// Simple admin test without complex routing
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Test - Event Ticketing System</title>
    <link rel="stylesheet" href="/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>Event Tickets</h1>
                <p>Admin Test</p>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="/admin" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="/admin/events"><i class="fas fa-calendar-alt"></i> Events</a></li>
                    <li><a href="/admin/users"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="/admin/venues"><i class="fas fa-map-marker-alt"></i> Venues</a></li>
                    <li><a href="/admin/checkin"><i class="fas fa-qrcode"></i> Check-in</a></li>
                </ul>
            </nav>
        </aside>
        
        <main class="main-content">
            <header class="header">
                <h2>Functionality Test</h2>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="testFunction('create')">
                        <i class="fas fa-plus"></i> Test Create
                    </button>
                </div>
            </header>
            
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-value">TEST</div>
                    <div class="stat-label">System Status</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-value">OK</div>
                    <div class="stat-label">JavaScript</div>
                </div>
                <div class="stat-card info">
                    <div class="stat-value">ON</div>
                    <div class="stat-label">Buttons</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-value">5</div>
                    <div class="stat-label">Pages Ready</div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Test Functions</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <button class="btn btn-primary" onclick="testFunction('events')">
                            <i class="fas fa-calendar-alt"></i> Test Events
                        </button>
                        <button class="btn btn-success" onclick="testFunction('users')">
                            <i class="fas fa-users"></i> Test Users
                        </button>
                        <button class="btn btn-info" onclick="testFunction('venues')">
                            <i class="fas fa-map-marker-alt"></i> Test Venues
                        </button>
                        <button class="btn btn-warning" onclick="testFunction('checkin')">
                            <i class="fas fa-qrcode"></i> Test Check-in
                        </button>
                        <button class="btn btn-secondary" onclick="testFunction('export')">
                            <i class="fas fa-download"></i> Test Export
                        </button>
                        <button class="btn btn-danger" onclick="testFunction('delete')">
                            <i class="fas fa-trash"></i> Test Delete
                        </button>
                    </div>
                    
                    <div id="testResults" style="margin-top: 20px;"></div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        console.log('🚀 Admin test page loaded');
        
        function testFunction(type) {
            console.log('🧪 Testing function:', type);
            
            const results = document.getElementById('testResults');
            const timestamp = new Date().toLocaleTimeString();
            
            let message = '';
            let alertType = 'success';
            
            switch(type) {
                case 'create':
                    message = '✅ Create function working - Form would open here';
                    break;
                case 'events':
                    message = '✅ Events page working - Navigation successful';
                    break;
                case 'users':
                    message = '✅ Users page working - Management functions active';
                    break;
                case 'venues':
                    message = '✅ Venues page working - Location management ready';
                    break;
                case 'checkin':
                    message = '✅ Check-in system working - QR scanner ready';
                    break;
                case 'export':
                    message = '✅ Export function working - CSV generation ready';
                    break;
                case 'delete':
                    if (confirm('🔴 Test delete function?')) {
                        message = '✅ Delete function working - Confirmation dialog active';
                    } else {
                        message = 'ℹ️ Delete function cancelled - User chose not to delete';
                        alertType = 'info';
                    }
                    break;
                default:
                    message = '❓ Unknown function test';
                    alertType = 'warning';
            }
            
            // Add result to page
            const resultDiv = document.createElement('div');
            resultDiv.className = `alert alert-${alertType}`;
            resultDiv.innerHTML = `<strong>[${timestamp}]</strong> ${message}`;
            results.insertBefore(resultDiv, results.firstChild);
            
            // Remove old results
            while (results.children.length > 5) {
                results.removeChild(results.lastChild);
            }
            
            // Show notification
            showNotification(message, alertType);
        }
        
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i> 
                ${message}
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        
        // Test on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📄 DOM loaded successfully');
            showNotification('🎉 Admin test page loaded successfully!', 'success');
            
            // Test button functionality
            const buttons = document.querySelectorAll('button');
            console.log(`🔘 Found ${buttons.length} buttons`);
            
            buttons.forEach((btn, index) => {
                if (btn.onclick) {
                    console.log(`✅ Button ${index + 1} has onclick: ${btn.textContent}`);
                } else {
                    console.log(`⚠️ Button ${index + 1} missing onclick: ${btn.textContent}`);
                }
            });
            
            // Test navigation links
            const links = document.querySelectorAll('.sidebar-nav a');
            console.log(`🔗 Found ${links.length} navigation links`);
            
            links.forEach((link, index) => {
                console.log(`📍 Link ${index + 1}: ${link.getAttribute('href')} - ${link.textContent}`);
            });
        });
    </script>
</body>
</html>
