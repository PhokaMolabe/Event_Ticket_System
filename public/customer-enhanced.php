<?php

// ENHANCED CUSTOMER WEBSITE - EXACT DESIGN FROM IMAGE
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();

// Enhanced events data matching the image design
$events = [
    [
        'id' => 1,
        'title' => 'Travis Scott',
        'subtitle' => 'Utopia Tour',
        'date' => '2024-07-15',
        'time' => '20:00',
        'venue' => 'Madison Square Garden',
        'city' => 'New York, NY',
        'price' => 125,
        'image' => 'https://via.placeholder.com/300x400/1a1a1a/ffffff?text=Travis+Scott',
        'category' => 'Concert',
        'total_tickets' => 20000,
        'sold_tickets' => 18450,
        'status' => 'published',
        'artist' => 'Travis Scott'
    ],
    [
        'id' => 2,
        'title' => 'Post Malone',
        'subtitle' => 'If Ya Wanna',
        'date' => '2024-08-20',
        'time' => '19:30',
        'venue' => 'Barclays Center',
        'city' => 'Brooklyn, NY',
        'price' => 95,
        'image' => 'https://via.placeholder.com/300x400/2a2a2a/ffffff?text=Post+Malone',
        'category' => 'Concert',
        'total_tickets' => 15000,
        'sold_tickets' => 12300,
        'status' => 'published',
        'artist' => 'Post Malone'
    ],
    [
        'id' => 3,
        'title' => 'Summer Fest',
        'subtitle' => '3 Day Pass',
        'date' => '2024-07-01',
        'time' => '12:00',
        'venue' => 'Central Park',
        'city' => 'New York, NY',
        'price' => 285,
        'image' => 'https://via.placeholder.com/300x400/3a3a3a/ffffff?text=Summer+Fest',
        'category' => 'Festival',
        'total_tickets' => 50000,
        'sold_tickets' => 48900,
        'status' => 'published',
        'artist' => 'Various Artists'
    ],
    [
        'id' => 4,
        'title' => 'Drake',
        'subtitle' => 'It\'s All A Blur',
        'date' => '2024-09-10',
        'time' => '21:00',
        'venue' => 'Madison Square Garden',
        'city' => 'New York, NY',
        'price' => 150,
        'image' => 'https://via.placeholder.com/300x400/4a4a4a/ffffff?text=Drake',
        'category' => 'Concert',
        'total_tickets' => 20000,
        'sold_tickets' => 19800,
        'status' => 'published',
        'artist' => 'Drake'
    ],
    [
        'id' => 5,
        'title' => 'Bad Bunny',
        'subtitle' => 'World\'s Hottest Tour',
        'date' => '2024-10-15',
        'time' => '20:30',
        'venue' => 'Yankee Stadium',
        'city' => 'Bronx, NY',
        'price' => 110,
        'image' => 'https://via.placeholder.com/300x400/5a5a5a/ffffff?text=Bad+Bunny',
        'category' => 'Concert',
        'total_tickets' => 45000,
        'sold_tickets' => 42000,
        'status' => 'published',
        'artist' => 'Bad Bunny'
    ],
    [
        'id' => 6,
        'title' => 'The Weeknd',
        'subtitle' => 'After Hours Til Dawn',
        'date' => '2024-11-20',
        'time' => '19:00',
        'venue' => 'MetLife Stadium',
        'city' => 'East Rutherford, NJ',
        'price' => 135,
        'image' => 'https://via.placeholder.com/300x400/6a6a6a/ffffff?text=The+Weeknd',
        'category' => 'Concert',
        'total_tickets' => 55000,
        'sold_tickets' => 38000,
        'status' => 'published',
        'artist' => 'The Weeknd'
    ]
];

function getSoldPercentage($sold, $total) {
    return round(($sold / $total) * 100, 1);
}

function getAvailabilityStatus($sold, $total) {
    $percentage = ($sold / $total) * 100;
    if ($percentage >= 95) return ['text' => 'Almost Sold Out', 'class' => 'danger'];
    if ($percentage >= 75) return ['text' => 'Selling Fast', 'class' => 'warning'];
    if ($percentage >= 50) return ['text' => 'Available', 'class' => 'success'];
    return ['text' => 'Plenty Available', 'class' => 'info'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventPro - Discover Amazing Events</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #fafafa;
        }
        
        /* Header */
        .header {
            background: #000;
            color: white;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s ease;
        }
        
        .nav-links a:hover {
            opacity: 0.8;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #ff6b6b;
            color: white;
        }
        
        .btn-primary:hover {
            background: #ff5252;
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-outline:hover {
            background: white;
            color: #000;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%);
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .hero p {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        /* Filter Section */
        .filter-section {
            background: white;
            padding: 30px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 80px;
            z-index: 900;
        }
        
        .filter-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 20px;
            border: 2px solid #e9ecef;
            background: white;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .filter-btn:hover,
        .filter-btn.active {
            border-color: #ff6b6b;
            background: #ff6b6b;
            color: white;
        }
        
        .search-box {
            flex: 1;
            min-width: 300px;
            padding: 10px 20px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .search-box:focus {
            outline: none;
            border-color: #ff6b6b;
        }
        
        /* Events Section */
        .events-section {
            padding: 60px 0;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .section-header h2 {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #333;
        }
        
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }
        
        /* Event Card - Exact Design from Image */
        .event-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }
        
        .event-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        
        .event-image-container {
            position: relative;
            height: 400px;
            overflow: hidden;
        }
        
        .event-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .event-card:hover .event-image {
            transform: scale(1.05);
        }
        
        .event-date-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .event-content {
            padding: 20px;
        }
        
        .event-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: #333;
        }
        
        .event-subtitle {
            color: #666;
            font-size: 1rem;
            margin-bottom: 15px;
        }
        
        .event-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .event-detail {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #555;
            font-size: 0.9rem;
        }
        
        .event-detail i {
            width: 16px;
            color: #ff6b6b;
        }
        
        .event-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .event-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #ff6b6b;
        }
        
        .event-status {
            font-size: 0.8rem;
            color: #666;
        }
        
        .get-tickets-btn {
            background: #ff6b6b;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 15px;
        }
        
        .get-tickets-btn:hover {
            background: #ff5252;
            transform: translateY(-2px);
        }
        
        /* Footer */
        .footer {
            background: #000;
            color: white;
            padding: 50px 0 20px;
            margin-top: 80px;
        }
        
        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 30px;
        }
        
        .footer-section h3 {
            margin-bottom: 20px;
            color: #ff6b6b;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 10px;
        }
        
        .footer-section a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-section a:hover {
            color: #ff6b6b;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #333;
            color: #ccc;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .nav-links {
                display: none;
            }
            
            .events-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }
            
            .filter-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                min-width: auto;
            }
        }
        
        /* Loading Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .event-card {
            animation: fadeIn 0.6s ease forwards;
        }
        
        .event-card:nth-child(1) { animation-delay: 0.1s; }
        .event-card:nth-child(2) { animation-delay: 0.2s; }
        .event-card:nth-child(3) { animation-delay: 0.3s; }
        .event-card:nth-child(4) { animation-delay: 0.4s; }
        .event-card:nth-child(5) { animation-delay: 0.5s; }
        .event-card:nth-child(6) { animation-delay: 0.6s; }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-ticket-alt"></i>
                EventPro
            </div>
            <nav class="nav-links">
                <a href="#events">Events</a>
                <a href="#categories">Categories</a>
                <a href="#about">About</a>
                <a href="enterprise-enhanced.php" class="btn btn-outline">Admin Login</a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Discover Amazing Events</h1>
            <p>Find and book tickets for the best concerts, festivals, and experiences</p>
            <a href="#events" class="btn btn-outline" style="font-size: 1.1rem; padding: 15px 30px;">
                <i class="fas fa-search"></i> Explore Events
            </a>
        </div>
    </section>

    <!-- Filter Section -->
    <section class="filter-section">
        <div class="filter-container">
            <button class="filter-btn active" onclick="filterEvents('all', this)">All Events</button>
            <button class="filter-btn" onclick="filterEvents('concert', this)">Concerts</button>
            <button class="filter-btn" onclick="filterEvents('festival', this)">Festivals</button>
            <button class="filter-btn" onclick="filterEvents('sports', this)">Sports</button>
            <input type="text" class="search-box" placeholder="Search events, artists, venues..." id="searchInput" onkeyup="searchEvents(this.value)">
        </div>
    </section>

    <!-- Events Section -->
    <section class="events-section" id="events">
        <div class="container">
            <div class="section-header">
                <h2>Upcoming Events</h2>
                <p>Don't miss out on these amazing experiences</p>
            </div>
            
            <div class="events-grid" id="eventsGrid">
                <?php foreach ($events as $event): ?>
                    <?php 
                    $soldPercentage = getSoldPercentage($event['sold_tickets'], $event['total_tickets']);
                    $status = getAvailabilityStatus($event['sold_tickets'], $event['total_tickets']);
                    ?>
                    <div class="event-card" data-category="<?php echo $event['category']; ?>" data-title="<?php echo strtolower($event['title'] . ' ' . $event['subtitle']); ?>">
                        <div class="event-image-container">
                            <img src="<?php echo $event['image']; ?>" alt="<?php echo $event['title']; ?>" class="event-image">
                            <div class="event-date-badge">
                                <?php echo date('M j', strtotime($event['date'])); ?>
                            </div>
                        </div>
                        <div class="event-content">
                            <h3 class="event-title"><?php echo $event['title']; ?></h3>
                            <p class="event-subtitle"><?php echo $event['subtitle']; ?></p>
                            
                            <div class="event-details">
                                <div class="event-detail">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo date('F j, Y', strtotime($event['date'])); ?></span>
                                </div>
                                <div class="event-detail">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo $event['time']; ?></span>
                                </div>
                                <div class="event-detail">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo $event['venue']; ?></span>
                                </div>
                            </div>
                            
                            <div class="event-footer">
                                <div>
                                    <div class="event-price">$<?php echo $event['price']; ?></div>
                                    <div class="event-status"><?php echo $status['text']; ?></div>
                                </div>
                            </div>
                            
                            <button class="get-tickets-btn" onclick="getTickets(<?php echo $event['id']; ?>)">
                                Get Tickets
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>About EventPro</h3>
                <p>Your premier destination for discovering and booking tickets to the best events in your city.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#events">Browse Events</a></li>
                    <li><a href="#categories">Categories</a></li>
                    <li><a href="#about">About Us</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Support</h3>
                <ul>
                    <li><a href="#help">Help Center</a></li>
                    <li><a href="#faq">FAQ</a></li>
                    <li><a href="#terms">Terms of Service</a></li>
                    <li><a href="#privacy">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Connect With Us</h3>
                <div style="display: flex; gap: 15px; font-size: 1.5rem;">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 EventPro. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Filter functionality
        function filterEvents(category, button) {
            // Update active button
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            button.classList.add('active');
            
            // Filter events
            const cards = document.querySelectorAll('.event-card');
            cards.forEach(card => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Search functionality
        function searchEvents(searchTerm) {
            const cards = document.querySelectorAll('.event-card');
            const term = searchTerm.toLowerCase();
            
            cards.forEach(card => {
                if (card.dataset.title.includes(term)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Get tickets function
        function getTickets(eventId) {
            console.log('Getting tickets for event:', eventId);
            
            // Create modal for ticket selection
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.8);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
            `;
            
            modal.innerHTML = `
                <div style="background: white; padding: 40px; border-radius: 12px; max-width: 500px; width: 90%;">
                    <h2 style="margin-bottom: 20px;">Select Tickets</h2>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px;">Number of Tickets:</label>
                        <select id="ticketQuantity" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                            <option value="1">1 Ticket</option>
                            <option value="2">2 Tickets</option>
                            <option value="3">3 Tickets</option>
                            <option value="4">4 Tickets</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 5px;">Ticket Type:</label>
                        <select id="ticketType" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
                            <option value="general">General Admission</option>
                            <option value="vip">VIP</option>
                            <option value="premium">Premium</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button onclick="proceedToCheckout(${eventId})" style="flex: 1; padding: 12px; background: #ff6b6b; color: white; border: none; border-radius: 6px; cursor: pointer;">Proceed to Checkout</button>
                        <button onclick="closeModal()" style="flex: 1; padding: 12px; background: #ccc; color: #333; border: none; border-radius: 6px; cursor: pointer;">Cancel</button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
        }
        
        function proceedToCheckout(eventId) {
            const quantity = document.getElementById('ticketQuantity').value;
            const type = document.getElementById('ticketType').value;
            
            closeModal();
            
            // Show success message
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #28a745;
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                z-index: 10001;
                animation: slideIn 0.3s ease;
            `;
            notification.innerHTML = `<i class="fas fa-check-circle"></i> Added ${quantity} ${type} ticket(s) to cart!`;
            document.body.appendChild(notification);
            
            setTimeout(() => notification.remove(), 3000);
        }
        
        function closeModal() {
            const modal = document.querySelector('div[style*="position: fixed"]');
            if (modal) modal.remove();
        }
        
        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            console.log('EventPro Enhanced Customer Website Loaded!');
        });
    </script>
</body>
</html>
