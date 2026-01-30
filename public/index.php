<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Load models
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Ticket.php';

// Load controllers
require_once __DIR__ . '/../controllers/EventController.php';

// Initialize router
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string from URI
$uri = parse_url($requestUri, PHP_URL_PATH);
$uri = rtrim($uri, '/');

// API routes
if (strpos($uri, '/api/') === 0) {
    handleApiRequest($uri, $requestMethod);
} elseif (strpos($uri, '/admin') === 0) {
    // Serve admin dashboard with routing
    $adminPath = str_replace('/admin', '', $uri);
    $adminPath = $adminPath ?: '/';
    serveAdminDashboard($adminPath);
} else {
    // Serve frontend or show API documentation
    serveFrontend();
}

function handleApiRequest($uri, $method) {
    // Remove /api prefix
    $path = substr($uri, 4);
    $path = ltrim($path, '/');
    
    $segments = explode('/', $path);
    $resource = $segments[0] ?? '';
    $id = $segments[1] ?? null;
    $action = $segments[2] ?? null;
    
    try {
        switch ($resource) {
            case 'events':
                handleEventRoutes($method, $id, $action);
                break;
                
            case 'users':
                handleUserRoutes($method, $id, $action);
                break;
                
            case 'orders':
                handleOrderRoutes($method, $id, $action);
                break;
                
            case 'tickets':
                handleTicketRoutes($method, $id, $action);
                break;
                
            case 'auth':
                handleAuthRoutes($method, $action);
                break;
                
            default:
                jsonResponse(['success' => false, 'error' => 'Endpoint not found'], 404);
        }
    } catch (Exception $e) {
        error_log("API Error: " . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Internal server error'], 500);
    }
}

function handleEventRoutes($method, $id, $action) {
    $controller = new EventController();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                if ($action === 'stats') {
                    $controller->getStats($id);
                } elseif ($action === 'duplicate') {
                    $controller->duplicate($id);
                } else {
                    $controller->get($id);
                }
            } else {
                $query = $_GET['q'] ?? '';
                if ($query) {
                    $controller->search();
                } elseif (isset($_GET['upcoming'])) {
                    $controller->upcoming();
                } elseif (isset($_GET['featured'])) {
                    $controller->featured();
                } else {
                    $controller->list();
                }
            }
            break;
            
        case 'POST':
            if ($id && $action === 'publish') {
                $controller->publish($id);
            } elseif ($id && $action === 'cancel') {
                $controller->cancel($id);
            } elseif ($id && $action === 'duplicate') {
                $controller->duplicate($id);
            } else {
                $controller->create();
            }
            break;
            
        case 'PUT':
        case 'PATCH':
            if ($id) {
                $controller->update($id);
            } else {
                jsonResponse(['success' => false, 'error' => 'Event ID required'], 400);
            }
            break;
            
        default:
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
}

function handleUserRoutes($method, $id, $action) {
    $userModel = new User();
    
    switch ($method) {
        case 'POST':
            if ($action === 'register') {
                handleUserRegistration($userModel);
            } elseif ($action === 'login') {
                handleUserLogin($userModel);
            } elseif ($action === 'logout') {
                handleUserLogout();
            } else {
                jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
            }
            break;
            
        case 'GET':
            if ($id) {
                if ($action === 'orders') {
                    handleUserOrders($id);
                } elseif ($action === 'tickets') {
                    handleUserTickets($id);
                } else {
                    handleGetUser($userModel, $id);
                }
            } else {
                handleUserSearch($userModel);
            }
            break;
            
        case 'PUT':
        case 'PATCH':
            if ($id) {
                handleUserUpdate($userModel, $id);
            } else {
                jsonResponse(['success' => false, 'error' => 'User ID required'], 400);
            }
            break;
            
        default:
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
}

function handleOrderRoutes($method, $id, $action) {
    $orderModel = new Order();
    
    switch ($method) {
        case 'POST':
            if ($action === 'create') {
                handleOrderCreate($orderModel);
            } else {
                handleOrderCreate($orderModel);
            }
            break;
            
        case 'GET':
            if ($id) {
                if ($action === 'tickets') {
                    handleOrderTickets($id);
                } else {
                    handleGetOrder($orderModel, $id);
                }
            } else {
                handleOrderSearch($orderModel);
            }
            break;
            
        case 'PUT':
        case 'PATCH':
            if ($id) {
                if ($action === 'cancel') {
                    handleOrderCancel($orderModel, $id);
                } elseif ($action === 'refund') {
                    handleOrderRefund($orderModel, $id);
                } else {
                    handleOrderUpdate($orderModel, $id);
                }
            } else {
                jsonResponse(['success' => false, 'error' => 'Order ID required'], 400);
            }
            break;
            
        default:
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
}

function handleTicketRoutes($method, $id, $action) {
    $ticketModel = new Ticket();
    
    switch ($method) {
        case 'GET':
            if ($id) {
                if ($action === 'check-in') {
                    handleTicketCheckIn($ticketModel, $id);
                } else {
                    handleGetTicket($ticketModel, $id);
                }
            } else {
                handleTicketSearch($ticketModel);
            }
            break;
            
        case 'POST':
            if ($action === 'check-in') {
                handleTicketCheckInByCode($ticketModel);
            } elseif ($action === 'transfer') {
                handleTicketTransfer($ticketModel);
            } elseif ($action === 'accept-transfer') {
                handleTicketAcceptTransfer($ticketModel);
            } else {
                jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
            }
            break;
            
        case 'PUT':
        case 'PATCH':
            if ($id) {
                if ($action === 'attendee') {
                    handleTicketUpdateAttendee($ticketModel, $id);
                } else {
                    jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
                }
            } else {
                jsonResponse(['success' => false, 'error' => 'Ticket ID required'], 400);
            }
            break;
            
        default:
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
}

function handleAuthRoutes($method, $action) {
    switch ($method) {
        case 'POST':
            if ($action === 'login') {
                handleAuthLogin();
            } elseif ($action === 'refresh') {
                handleAuthRefresh();
            } else {
                jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
            }
            break;
            
        case 'POST':
            if ($action === 'logout') {
                handleAuthLogout();
            } else {
                jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
            }
            break;
            
        default:
            jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
}

// User handlers
function handleUserRegistration($userModel) {
    $data = getJsonInput();
    
    $result = $userModel->create($data);
    
    if ($result['success']) {
        jsonResponse($result, 201);
    } else {
        jsonResponse($result, 400);
    }
}

function handleUserLogin($userModel) {
    $data = getJsonInput();
    
    $result = $userModel->authenticate($data['email'], $data['password']);
    
    if ($result['success']) {
        $token = generateJWT($result['user']);
        jsonResponse(['success' => true, 'user' => $result['user'], 'token' => $token]);
    } else {
        jsonResponse($result, 401);
    }
}

function handleUserLogout() {
    // In a real implementation, you might want to invalidate the token
    jsonResponse(['success' => true, 'message' => 'Logged out successfully']);
}

function handleGetUser($userModel, $id) {
    $user = $userModel->getById($id);
    
    if ($user) {
        jsonResponse(['success' => true, 'user' => $user]);
    } else {
        jsonResponse(['success' => false, 'error' => 'User not found'], 404);
    }
}

function handleUserUpdate($userModel, $id) {
    $data = getJsonInput();
    $result = $userModel->update($id, $data);
    
    if ($result['success']) {
        jsonResponse($result);
    } else {
        jsonResponse($result, 400);
    }
}

function handleUserSearch($userModel) {
    $query = $_GET['q'] ?? '';
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = (int)($_GET['offset'] ?? 0);
    
    $users = $userModel->search($query, $limit, $offset);
    jsonResponse(['success' => true, 'users' => $users]);
}

function handleUserOrders($userId) {
    $orderModel = new Order();
    $status = $_GET['status'] ?? null;
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 20);
    
    $result = $orderModel->getUserOrders($userId, $status, $page, $perPage);
    jsonResponse(['success' => true, 'data' => $result]);
}

function handleUserTickets($userId) {
    $ticketModel = new Ticket();
    $status = $_GET['status'] ?? null;
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 20);
    
    $result = $ticketModel->getUserTickets($userId, $status, $page, $perPage);
    jsonResponse(['success' => true, 'data' => $result]);
}

// Order handlers
function handleOrderCreate($orderModel) {
    $data = getJsonInput();
    $result = $orderModel->create($data);
    
    if ($result['success']) {
        jsonResponse($result, 201);
    } else {
        jsonResponse($result, 400);
    }
}

function handleGetOrder($orderModel, $id) {
    $order = $orderModel->getById($id);
    
    if ($order) {
        $order['items'] = $orderModel->getOrderItems($id);
        $order['tickets'] = $orderModel->getTickets($id);
        jsonResponse(['success' => true, 'order' => $order]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Order not found'], 404);
    }
}

function handleOrderUpdate($orderModel, $id) {
    $data = getJsonInput();
    
    if (isset($data['status'])) {
        $result = $orderModel->updateStatus($id, $data['status']);
    } elseif (isset($data['payment_status'])) {
        $result = $orderModel->updatePaymentStatus($id, $data['payment_status'], $data);
    } else {
        jsonResponse(['success' => false, 'error' => 'No valid update data provided'], 400);
        return;
    }
    
    if ($result['success']) {
        jsonResponse($result);
    } else {
        jsonResponse($result, 400);
    }
}

function handleOrderCancel($orderModel, $id) {
    $data = getJsonInput();
    $reason = $data['reason'] ?? null;
    
    $result = $orderModel->cancel($id, $reason);
    
    if ($result['success']) {
        jsonResponse($result);
    } else {
        jsonResponse($result, 400);
    }
}

function handleOrderRefund($orderModel, $id) {
    $data = getJsonInput();
    $amount = $data['amount'];
    $reason = $data['reason'] ?? null;
    
    $result = $orderModel->refund($id, $amount, $reason);
    
    if ($result['success']) {
        jsonResponse($result);
    } else {
        jsonResponse($result, 400);
    }
}

function handleOrderSearch($orderModel) {
    $query = $_GET['q'] ?? '';
    $filters = [];
    
    if (!empty($_GET['status'])) {
        $filters['status'] = $_GET['status'];
    }
    
    if (!empty($_GET['payment_status'])) {
        $filters['payment_status'] = $_GET['payment_status'];
    }
    
    if (!empty($_GET['date_from'])) {
        $filters['date_from'] = $_GET['date_from'];
    }
    
    if (!empty($_GET['date_to'])) {
        $filters['date_to'] = $_GET['date_to'];
    }
    
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 20);
    
    $result = $orderModel->search($query, $filters, $page, $perPage);
    jsonResponse(['success' => true, 'data' => $result]);
}

function handleOrderTickets($orderId) {
    $orderModel = new Order();
    $tickets = $orderModel->getTickets($orderId);
    jsonResponse(['success' => true, 'tickets' => $tickets]);
}

// Ticket handlers
function handleGetTicket($ticketModel, $id) {
    $ticket = $ticketModel->getById($id);
    
    if ($ticket) {
        jsonResponse(['success' => true, 'ticket' => $ticket]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Ticket not found'], 404);
    }
}

function handleTicketCheckIn($ticketModel, $ticketId) {
    $data = getJsonInput();
    $gateId = $data['gate_id'] ?? null;
    $deviceId = $data['device_id'] ?? null;
    $operatorId = $data['operator_id'] ?? null;
    
    $result = $ticketModel->checkIn($ticketId, $gateId, $deviceId, $operatorId);
    
    if ($result['success']) {
        jsonResponse($result);
    } else {
        jsonResponse($result, 400);
    }
}

function handleTicketCheckInByCode($ticketModel) {
    $data = getJsonInput();
    $code = $data['code'] ?? '';
    $gateId = $data['gate_id'] ?? null;
    $deviceId = $data['device_id'] ?? null;
    $operatorId = $data['operator_id'] ?? null;
    
    $result = $ticketModel->checkInByCode($code, $gateId, $deviceId, $operatorId);
    
    if ($result['success']) {
        jsonResponse($result);
    } else {
        jsonResponse($result, 400);
    }
}

function handleTicketUpdateAttendee($ticketModel, $ticketId) {
    $data = getJsonInput();
    $result = $ticketModel->updateAttendeeInfo($ticketId, $data);
    
    if ($result['success']) {
        jsonResponse($result);
    } else {
        jsonResponse($result, 400);
    }
}

function handleTicketTransfer($ticketModel) {
    $data = getJsonInput();
    $ticketId = $data['ticket_id'];
    $toUserId = $data['to_user_id'];
    
    $result = $ticketModel->transfer($ticketId, $toUserId, getCurrentUserId());
    
    if ($result['success']) {
        jsonResponse($result);
    } else {
        jsonResponse($result, 400);
    }
}

function handleTicketAcceptTransfer($ticketModel) {
    $data = getJsonInput();
    $transferToken = $data['transfer_token'];
    
    $result = $ticketModel->acceptTransfer($transferToken, getCurrentUserId());
    
    if ($result['success']) {
        jsonResponse($result);
    } else {
        jsonResponse($result, 400);
    }
}

function handleTicketSearch($ticketModel) {
    $query = $_GET['q'] ?? '';
    $eventId = $_GET['event_id'] ?? null;
    $status = $_GET['status'] ?? null;
    
    if ($query) {
        $ticket = $ticketModel->getByTicketNumber($query);
        if ($ticket) {
            jsonResponse(['success' => true, 'ticket' => $ticket]);
        } else {
            jsonResponse(['success' => false, 'error' => 'Ticket not found'], 404);
        }
    } elseif ($eventId) {
        $tickets = $ticketModel->getByEvent($eventId, $status);
        jsonResponse(['success' => true, 'tickets' => $tickets]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Search query or event ID required'], 400);
    }
}

// Auth handlers
function handleAuthLogin() {
    $data = getJsonInput();
    $userModel = new User();
    
    $result = $userModel->authenticate($data['email'], $data['password']);
    
    if ($result['success']) {
        $token = generateJWT($result['user']);
        jsonResponse(['success' => true, 'user' => $result['user'], 'token' => $token]);
    } else {
        jsonResponse($result, 401);
    }
}

function handleAuthRefresh() {
    $token = getAuthToken();
    if (!$token) {
        jsonResponse(['success' => false, 'error' => 'Token required'], 401);
        return;
    }
    
    try {
        $payload = Firebase\JWT\JWT::decode($token, Config::getInstance()->get('security.jwt_secret'), ['HS256']);
        $userModel = new User();
        $user = $userModel->getById($payload->user_id);
        
        if ($user) {
            $newToken = generateJWT($user);
            jsonResponse(['success' => true, 'token' => $newToken]);
        } else {
            jsonResponse(['success' => false, 'error' => 'User not found'], 404);
        }
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => 'Invalid token'], 401);
    }
}

function handleAuthLogout() {
    jsonResponse(['success' => true, 'message' => 'Logged out successfully']);
}

// Helper functions
function generateJWT($user) {
    $payload = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'iat' => time(),
        'exp' => time() + Config::getInstance()->get('security.jwt_expiry', 86400)
    ];
    
    return Firebase\JWT\JWT::encode($payload, Config::getInstance()->get('security.jwt_secret'));
}

function getCurrentUserId() {
    $token = getAuthToken();
    if (!$token) {
        return null;
    }
    
    try {
        $payload = Firebase\JWT\JWT::decode($token, Config::getInstance()->get('security.jwt_secret'), ['HS256']);
        return $payload->user_id ?? null;
    } catch (Exception $e) {
        return null;
    }
}

function getAuthToken() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    
    if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return $matches[1];
    }
    
    return null;
}

function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?? [];
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    echo json_encode($data);
    exit;
}

function serveAdminDashboard($path = '/') {
    // Route to different admin pages
    switch ($path) {
        case '/events':
            serveAdminEvents();
            break;
        case '/users':
            serveAdminUsers();
            break;
        case '/venues':
            serveAdminVenues();
            break;
        case '/orders':
            serveAdminOrders();
            break;
        case '/tickets':
            serveAdminTickets();
            break;
        case '/checkin':
        case '/check-in':
            serveAdminCheckIn();
            break;
        case '/analytics':
            serveAdminAnalytics();
            break;
        case '/reports':
            serveAdminReports();
            break;
        case '/settings':
            serveAdminSettings();
            break;
        default:
            serveAdminMain();
            break;
    }
}

function serveAdminMain() {
    // Serve the main admin dashboard
    $dashboardPath = __DIR__ . '/../views/admin/dashboard.php';
    
    if (file_exists($dashboardPath)) {
        include $dashboardPath;
    } else {
        // Fallback to simple admin page
        includeAdminDashboard('dashboard');
    }
}

function serveAdminEvents() {
    includeAdminDashboard('events');
}

function serveAdminUsers() {
    includeAdminDashboard('users');
}

function serveAdminVenues() {
    includeAdminDashboard('venues');
}

function serveAdminOrders() {
    includeAdminDashboard('orders');
}

function serveAdminTickets() {
    includeAdminDashboard('tickets');
}

function serveAdminCheckIn() {
    includeAdminDashboard('checkin');
}

function serveAdminAnalytics() {
    includeAdminDashboard('analytics');
}

function serveAdminReports() {
    includeAdminDashboard('reports');
}

function serveAdminSettings() {
    includeAdminDashboard('settings');
}

function includeAdminDashboard($page = 'dashboard') {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo ucfirst($page); ?> - Event Ticketing System</title>
            <link rel="stylesheet" href="/css/admin.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        </head>
        <body>
            <div class="admin-layout">
                <aside class="sidebar">
                    <div class="sidebar-header">
                        <h1>Event Tickets</h1>
                        <p>Admin Dashboard</p>
                    </div>
                    <nav class="sidebar-nav">
                        <ul>
                            <li><a href="/admin" class="<?php echo $page === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                            <li><a href="/admin/events" class="<?php echo $page === 'events' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Events</a></li>
                            <li><a href="/admin/venues" class="<?php echo $page === 'venues' ? 'active' : ''; ?>"><i class="fas fa-map-marker-alt"></i> Venues</a></li>
                            <li><a href="/admin/orders" class="<?php echo $page === 'orders' ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                            <li><a href="/admin/tickets" class="<?php echo $page === 'tickets' ? 'active' : ''; ?>"><i class="fas fa-ticket-alt"></i> Tickets</a></li>
                            <li><a href="/admin/users" class="<?php echo $page === 'users' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Users</a></li>
                            <li><a href="/admin/checkin" class="<?php echo $page === 'checkin' ? 'active' : ''; ?>"><i class="fas fa-qrcode"></i> Check-in</a></li>
                            <li><a href="/admin/analytics" class="<?php echo $page === 'analytics' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Analytics</a></li>
                            <li><a href="/admin/reports" class="<?php echo $page === 'reports' ? 'active' : ''; ?>"><i class="fas fa-file-alt"></i> Reports</a></li>
                            <li><a href="/admin/settings" class="<?php echo $page === 'settings' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> Settings</a></li>
                        </ul>
                    </nav>
                </aside>
                
                <main class="main-content">
                    <?php renderAdminPage($page); ?>
                </main>
            </div>
            
            <script>
                // Global functions
                function showNotification(message, type = 'success') {
                    const alert = document.createElement('div');
                    alert.className = `alert alert-${type}`;
                    alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
                    document.querySelector('.main-content').insertBefore(alert, document.querySelector('.main-content').firstChild);
                    setTimeout(() => alert.remove(), 5000);
                }
                
                function openModal(modalId) {
                    document.getElementById(modalId).classList.add('show');
                }
                
                function closeModal(modalId) {
                    document.getElementById(modalId).classList.remove('show');
                }
                
                // Dashboard functions
                function createEvent() {
                    showNotification('Event creation form would open here', 'info');
                }
                
                // Events functions
                function editEvent(id) {
                    showNotification(`Editing event #${id}`, 'info');
                }
                
                function viewEvent(id) {
                    showNotification(`Viewing event #${id} details`, 'info');
                }
                
                // Users functions
                function createUser() {
                    showNotification('User creation form would open here', 'info');
                }
                
                function editUser(id) {
                    showNotification(`Editing user #${id}`, 'info');
                }
                
                function suspendUser(id) {
                    if (confirm('Are you sure you want to suspend this user?')) {
                        showNotification(`User #${id} suspended`, 'warning');
                    }
                }
                
                // Venues functions
                function createVenue() {
                    showNotification('Venue creation form would open here', 'info');
                }
                
                function editVenue(id) {
                    showNotification(`Editing venue #${id}`, 'info');
                }
                
                function viewVenue(id) {
                    showNotification(`Viewing venue #${id} details`, 'info');
                }
                
                // Orders functions
                function viewOrder(id) {
                    showNotification(`Viewing order #${id} details`, 'info');
                }
                
                function refundOrder(id) {
                    if (confirm('Are you sure you want to refund this order?')) {
                        showNotification(`Order #${id} refunded`, 'success');
                    }
                }
                
                function exportOrders() {
                    showNotification('Exporting orders to CSV...', 'success');
                }
                
                // Tickets functions
                function viewTicket(id) {
                    showNotification(`Viewing ticket #${id} details`, 'info');
                }
                
                function checkInTicket(id) {
                    showNotification(`Checking in ticket #${id}`, 'success');
                }
                
                function exportTickets() {
                    showNotification('Exporting tickets to CSV...', 'success');
                }
                
                // Check-in functions
                function openCheckInModal() {
                    openModal('checkInModal');
                    document.getElementById('ticketCode').focus();
                }
                
                function processCheckIn() {
                    const code = document.getElementById('ticketCode').value;
                    const resultDiv = document.getElementById('checkInResult');
                    
                    if (!code) {
                        showNotification('Please enter a ticket code', 'warning');
                        return;
                    }
                    
                    // Simulate check-in process
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            Ticket checked in successfully!
                            <br><small>Ticket: ${code}</small>
                        </div>
                    `;
                    
                    document.getElementById('ticketCode').value = '';
                    showNotification('Ticket checked in successfully!', 'success');
                }
                
                function refreshCheckIns() {
                    showNotification('Check-in data refreshed', 'success');
                }
                
                // Reports functions
                function generateReport() {
                    showNotification('Generating comprehensive report...', 'info');
                }
                
                function exportReport(type) {
                    showNotification(`Exporting ${type} report to CSV...`, 'success');
                }
                
                // Settings functions
                function saveSettings() {
                    showNotification('Settings saved successfully!', 'success');
                }
                
                // Initialize page-specific functionality
                document.addEventListener('DOMContentLoaded', function() {
                    // Add active state to current page
                    const currentPath = window.location.pathname;
                    const navLinks = document.querySelectorAll('.sidebar-nav a');
                    
                    navLinks.forEach(link => {
                        if (link.getAttribute('href') === currentPath) {
                            link.classList.add('active');
                        }
                    });
                    
                    // Initialize modals
                    const modals = document.querySelectorAll('.modal');
                    modals.forEach(modal => {
                        modal.addEventListener('click', function(e) {
                            if (e.target === modal) {
                                closeModal(modal.id);
                            }
                        });
                    });
                });
            </script>
        </body>
        </html>
        <?php
    }
    
    function renderAdminPage($page) {
        switch ($page) {
            case 'events':
                renderEventsPage();
                break;
            case 'users':
                renderUsersPage();
                break;
            case 'venues':
                renderVenuesPage();
                break;
            case 'orders':
                renderOrdersPage();
                break;
            case 'tickets':
                renderTicketsPage();
                break;
            case 'checkin':
                renderCheckInPage();
                break;
            case 'analytics':
                renderAnalyticsPage();
                break;
            case 'reports':
                renderReportsPage();
                break;
            case 'settings':
                renderSettingsPage();
                break;
            default:
                renderDashboardPage();
                break;
        }
    }
    
    function renderDashboardPage() {
        ?>
        <header class="header">
            <h2>Dashboard Overview</h2>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="createEvent()">
                    <i class="fas fa-plus"></i> New Event
                </button>
            </div>
        </header>
        
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
                <h3>Welcome to Event Ticketing System</h3>
            </div>
            <div class="card-body">
                <p>Your enterprise event management system is ready!</p>
                <div style="margin-top: 20px;">
                    <button class="btn btn-primary" onclick="createEvent()">
                        <i class="fas fa-plus"></i> Create Your First Event
                    </button>
                    <button class="btn btn-info" onclick="window.location.href='/api'">
                        <i class="fas fa-code"></i> View API Documentation
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    
    function renderEventsPage() {
        ?>
        <header class="header">
            <h2>Events Management</h2>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="createEvent()">
                    <i class="fas fa-plus"></i> Create Event
                </button>
            </div>
        </header>
        
        <div class="card">
            <div class="card-header">
                <h3>All Events</h3>
                <input type="text" class="form-control" style="width: 200px;" placeholder="Search events...">
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Date</th>
                                <th>Venue</th>
                                <th>Status</th>
                                <th>Tickets Sold</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Summer Music Festival 2024</td>
                                <td>2024-07-15</td>
                                <td>Grand Convention Center</td>
                                <td><span class="badge badge-success">Published</span></td>
                                <td>245</td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-primary" onclick="editEvent(1)">Edit</button>
                                        <button class="btn btn-sm btn-info" onclick="viewEvent(1)">View</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Tech Conference 2024</td>
                                <td>2024-09-20</td>
                                <td>Grand Convention Center</td>
                                <td><span class="badge badge-success">Published</span></td>
                                <td>156</td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-primary" onclick="editEvent(2)">Edit</button>
                                        <button class="btn btn-sm btn-info" onclick="viewEvent(2)">View</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    function renderUsersPage() {
        ?>
        <header class="header">
            <h2>User Management</h2>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="createUser()">
                    <i class="fas fa-user-plus"></i> Add User
                </button>
            </div>
        </header>
        
        <div class="card">
            <div class="card-header">
                <h3>All Users</h3>
                <input type="text" class="form-control" style="width: 200px;" placeholder="Search users...">
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Super Admin</td>
                                <td>admin@eventticketing.com</td>
                                <td><span class="badge badge-primary">Super Admin</span></td>
                                <td><span class="status-indicator active">Active</span></td>
                                <td>2024-01-01</td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-primary" onclick="editUser(1)">Edit</button>
                                        <button class="btn btn-sm btn-warning" onclick="suspendUser(1)">Suspend</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>John Doe</td>
                                <td>john.doe@example.com</td>
                                <td><span class="badge badge-secondary">User</span></td>
                                <td><span class="status-indicator active">Active</span></td>
                                <td>2024-01-15</td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-primary" onclick="editUser(2)">Edit</button>
                                        <button class="btn btn-sm btn-warning" onclick="suspendUser(2)">Suspend</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    function renderVenuesPage() {
        ?>
        <header class="header">
            <h2>Venue Management</h2>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="createVenue()">
                    <i class="fas fa-map-marker-alt"></i> Add Venue
                </button>
            </div>
        </header>
        
        <div class="card">
            <div class="card-header">
                <h3>All Venues</h3>
                <input type="text" class="form-control" style="width: 200px;" placeholder="Search venues...">
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Venue Name</th>
                                <th>Location</th>
                                <th>Capacity</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Grand Convention Center</td>
                                <td>New York, USA</td>
                                <td>5,000</td>
                                <td>info@grandconvention.com</td>
                                <td><span class="status-indicator active">Active</span></td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-primary" onclick="editVenue(1)">Edit</button>
                                        <button class="btn btn-sm btn-info" onclick="viewVenue(1)">View</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Madison Square Garden</td>
                                <td>New York, USA</td>
                                <td>20,000</td>
                                <td>info@msg.com</td>
                                <td><span class="status-indicator active">Active</span></td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-primary" onclick="editVenue(2)">Edit</button>
                                        <button class="btn btn-sm btn-info" onclick="viewVenue(2)">View</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    function renderOrdersPage() {
        ?>
        <header class="header">
            <h2>Order Management</h2>
            <div class="header-actions">
                <button class="btn btn-success" onclick="exportOrders()">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </header>
        
        <div class="card">
            <div class="card-header">
                <h3>All Orders</h3>
                <select class="form-control" style="width: 150px;">
                    <option>All Status</option>
                    <option>Pending</option>
                    <option>Paid</option>
                    <option>Cancelled</option>
                </select>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Event</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>ORD-20240115001</td>
                                <td>John Doe</td>
                                <td>Summer Music Festival</td>
                                <td>$150.00</td>
                                <td><span class="badge badge-success">Paid</span></td>
                                <td>2024-01-15</td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-info" onclick="viewOrder(1)">View</button>
                                        <button class="btn btn-sm btn-warning" onclick="refundOrder(1)">Refund</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    function renderTicketsPage() {
        ?>
        <header class="header">
            <h2>Ticket Management</h2>
            <div class="header-actions">
                <button class="btn btn-info" onclick="exportTickets()">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </header>
        
        <div class="card">
            <div class="card-header">
                <h3>All Tickets</h3>
                <input type="text" class="form-control" style="width: 200px;" placeholder="Search tickets...">
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>Event</th>
                                <th>Attendee</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>TKT-20240115001</td>
                                <td>Summer Music Festival</td>
                                <td>John Doe</td>
                                <td>General Admission</td>
                                <td><span class="badge badge-success">Confirmed</span></td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-info" onclick="viewTicket(1)">View</button>
                                        <button class="btn btn-sm btn-warning" onclick="checkInTicket(1)">Check-in</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    function renderCheckInPage() {
        ?>
        <header class="header">
            <h2>Check-in Management</h2>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="openCheckInModal()">
                    <i class="fas fa-qrcode"></i> Scan Ticket
                </button>
            </div>
        </header>
        
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-value">156</div>
                <div class="stat-label">Checked In Today</div>
            </div>
            <div class="stat-card info">
                <div class="stat-value">234</div>
                <div class="stat-label">Pending Check-in</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-value">8</div>
                <div class="stat-label">Failed Attempts</div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Recent Check-ins</h3>
                <button class="btn btn-sm btn-outline" onclick="refreshCheckIns()">
                    <i class="fas fa-sync"></i> Refresh
                </button>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Ticket #</th>
                                <th>Attendee</th>
                                <th>Event</th>
                                <th>Gate</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>10:30 AM</td>
                                <td>TKT-20240115001</td>
                                <td>John Doe</td>
                                <td>Summer Music Festival</td>
                                <td>Main Entrance</td>
                                <td><span class="badge badge-success">Success</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Check-in Modal -->
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
        <?php
    }
    
    function renderAnalyticsPage() {
        ?>
        <header class="header">
            <h2>Analytics Dashboard</h2>
            <div class="header-actions">
                <select class="form-control" style="width: 150px;">
                    <option>Last 30 days</option>
                    <option>Last 7 days</option>
                    <option>Last 3 months</option>
                </select>
            </div>
        </header>
        
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-value">$12,450</div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value">1,234</div>
                <div class="stat-label">Tickets Sold</div>
            </div>
            <div class="stat-card info">
                <div class="stat-value">89%</div>
                <div class="stat-label">Check-in Rate</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-value">4.8</div>
                <div class="stat-label">Avg Rating</div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Revenue Trends</h3>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
        <?php
    }
    
    function renderReportsPage() {
        ?>
        <header class="header">
            <h2>Reports & Exports</h2>
            <div class="header-actions">
                <button class="btn btn-success" onclick="generateReport()">
                    <i class="fas fa-file-export"></i> Generate Report
                </button>
            </div>
        </header>
        
        <div class="card">
            <div class="card-header">
                <h3>Available Reports</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div class="card" style="margin: 0;">
                        <div class="card-header">
                            <h4>Sales Report</h4>
                        </div>
                        <div class="card-body">
                            <p>Complete sales analysis with revenue breakdown</p>
                            <button class="btn btn-primary" onclick="exportReport('sales')">Export CSV</button>
                        </div>
                    </div>
                    <div class="card" style="margin: 0;">
                        <div class="card-header">
                            <h4>Attendance Report</h4>
                        </div>
                        <div class="card-body">
                            <p>Detailed attendance statistics and check-in data</p>
                            <button class="btn btn-primary" onclick="exportReport('attendance')">Export CSV</button>
                        </div>
                    </div>
                    <div class="card" style="margin: 0;">
                        <div class="card-header">
                            <h4>Financial Report</h4>
                        </div>
                        <div class="card-body">
                            <p>Financial reconciliation and tax reports</p>
                            <button class="btn btn-primary" onclick="exportReport('financial')">Export CSV</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    function renderSettingsPage() {
        ?>
        <header class="header">
            <h2>System Settings</h2>
            <div class="header-actions">
                <button class="btn btn-success" onclick="saveSettings()">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </header>
        
        <div class="card">
            <div class="card-header">
                <h3>General Settings</h3>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">System Name</label>
                        <input type="text" class="form-control" value="Event Ticketing System">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Admin Email</label>
                        <input type="email" class="form-control" value="admin@eventticketing.com">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Default Currency</label>
                    <select class="form-control" style="width: 200px;">
                        <option value="USD" selected>USD - US Dollar</option>
                        <option value="EUR">EUR - Euro</option>
                        <option value="GBP">GBP - British Pound</option>
                    </select>
                </div>
            </div>
        </div>
        <?php
    }

function serveFrontend() {
    // Serve API documentation or basic frontend
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Event Ticketing System API</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
            .endpoint { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
            .method { display: inline-block; padding: 3px 8px; border-radius: 3px; color: white; font-weight: bold; }
            .get { background: #61affe; }
            .post { background: #49cc90; }
            .put { background: #fca130; }
            .delete { background: #f93e3e; }
            .path { font-family: monospace; font-weight: bold; }
        </style>
    </head>
    <body>
        <h1>Event Ticketing System API</h1>
        <p>Welcome to the Enterprise Event Management and Ticketing System API.</p>
        
        <h2>Available Endpoints</h2>
        
        <div class="endpoint">
            <span class="method get">GET</span>
            <span class="path">/api/events</span>
            <p>List all events</p>
        </div>
        
        <div class="endpoint">
            <span class="method post">POST</span>
            <span class="path">/api/events</span>
            <p>Create a new event</p>
        </div>
        
        <div class="endpoint">
            <span class="method get">GET</span>
            <span class="path">/api/events/{id}</span>
            <p>Get event details</p>
        </div>
        
        <div class="endpoint">
            <span class="method post">POST</span>
            <span class="path">/api/orders</span>
            <p>Create a new order</p>
        </div>
        
        <div class="endpoint">
            <span class="method get">GET</span>
            <span class="path">/api/tickets/{id}</span>
            <p>Get ticket details</p>
        </div>
        
        <div class="endpoint">
            <span class="method post">POST</span>
            <span class="path">/api/tickets/check-in</span>
            <p>Check in ticket by code</p>
        </div>
        
        <div class="endpoint">
            <span class="method post">POST</span>
            <span class="path">/api/auth/login</span>
            <p>User login</p>
        </div>
        
        <h2>Authentication</h2>
        <p>Most endpoints require authentication. Include the JWT token in the Authorization header:</p>
        <code>Authorization: Bearer {your-jwt-token}</code>
        
        <h2>Documentation</h2>
        <p>For detailed API documentation, please refer to the API documentation files.</p>
    </body>
    </html>
    <?php
}
?>
