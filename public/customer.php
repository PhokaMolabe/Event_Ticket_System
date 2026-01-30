<?php

// CUSTOMER-FACING EVENT TICKETING WEBSITE
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();

// Sample events data
$events = [
    [
        'id' => 1,
        'title' => 'Summer Music Festival 2024',
        'description' => 'Experience the ultimate summer music festival with top artists from around the world.',
        'date' => '2024-07-15',
        'time' => '18:00',
        'venue' => 'Grand Convention Center',
        'city' => 'New York',
        'price_min' => 75,
        'price_max' => 250,
        'image' => 'https://via.placeholder.com/400x250/667eea/ffffff?text=Summer+Music+Festival',
        'category' => 'Music',
        'total_tickets' => 5000,
        'sold_tickets' => 3847,
        'status' => 'published'
    ],
    [
        'id' => 2,
        'title' => 'Tech Innovation Summit',
        'description' => 'Join industry leaders for the biggest tech conference of the year.',
        'date' => '2024-09-20',
        'time' => '09:00',
        'venue' => 'Madison Square Garden',
        'city' => 'New York',
        'price_min' => 150,
        'price_max' => 500,
        'image' => 'https://via.placeholder.com/400x250/764ba2/ffffff?text=Tech+Summit',
        'category' => 'Conference',
        'total_tickets' => 2000,
        'sold_tickets' => 1654,
        'status' => 'published'
    ],
    [
        'id' => 3,
        'title' => 'International Food Festival',
        'description' => 'Taste cuisines from around the world at this amazing food festival.',
        'date' => '2024-08-10',
        'time' => '12:00',
        'venue' => 'Central Park Arena',
        'city' => 'New York',
        'price_min' => 25,
        'price_max' => 100,
        'image' => 'https://via.placeholder.com/400x250/28a745/ffffff?text=Food+Festival',
        'category' => 'Food',
        'total_tickets' => 3000,
        'sold_tickets' => 2891,
        'status' => 'published'
    ],
    [
        'id' => 4,
        'title' => 'Comedy Night Special',
        'description' => 'Laugh out loud with the best comedians in the industry.',
        'date' => '2024-06-25',
        'time' => '20:00',
        'venue' => 'Comedy Club Downtown',
        'city' => 'New York',
        'price_min' => 35,
        'price_max' => 85,
        'image' => 'https://via.placeholder.com/400x250/ffc107/000000?text=Comedy+Night',
        'category' => 'Entertainment',
        'total_tickets' => 500,
        'sold_tickets' => 423,
        'status' => 'published'
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8rem;
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
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-outline:hover {
            background: white;
            color: #667eea;
        }
        
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            animation: fadeInUp 0.8s ease;
        }
        
        .hero p {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.9;
            animation: fadeInUp 0.8s ease 0.2s both;
        }
        
        .search-section {
            background: white;
            padding: 40px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .search-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .search-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .search-input {
            flex: 1;
            min-width: 200px;
            padding: 12px 20px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .events-section {
            padding: 80px 0;
        }
        
        .container {
            max-width: 1200px;
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
        
        .section-header p {
            font-size: 1.1rem;
            color: #666;
        }
        
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .event-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .event-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        
        .event-content {
            padding: 25px;
        }
        
        .event-category {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .event-title {
            font-size: 1.4rem;
            margin-bottom: 10px;
            color: #333;
        }
        
        .event-description {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .event-details {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .event-detail {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #555;
        }
        
        .event-detail i {
            width: 20px;
            color: #667eea;
        }
        
        .event-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .event-status {
            margin-bottom: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-success { background: #d4edda; color: #155724; }
        .status-warning { background: #fff3cd; color: #856404; }
        .status-danger { background: #f8d7da; color: #721c24; }
        .status-info { background: #d1ecf1; color: #0c5460; }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.3s ease;
        }
        
        .footer {
            background: #333;
            color: white;
            padding: 50px 0 20px;
            margin-top: 80px;
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 30px;
        }
        
        .footer-section h3 {
            margin-bottom: 20px;
            color: #667eea;
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
            color: #667eea;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #555;
            color: #ccc;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .nav-links {
                display: none;
            }
            
            .events-grid {
                grid-template-columns: 1fr;
            }
            
            .search-form {
                flex-direction: column;
            }
        }
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
                <a href="enterprise.php" class="btn btn-outline">Admin Login</a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Discover Amazing Events</h1>
            <p>Find and book tickets for the best concerts, conferences, and experiences in your city</p>
            <a href="#events" class="btn btn-outline" style="font-size: 1.1rem; padding: 15px 30px;">
                <i class="fas fa-search"></i> Explore Events
            </a>
        </div>
    </section>

    <!-- Search Section -->
    <section class="search-section">
        <div class="search-container">
            <form class="search-form" onsubmit="searchEvents(event)">
                <input type="text" class="search-input" placeholder="Search events, artists, venues..." id="searchInput">
                <select class="search-input" id="categoryFilter">
                    <option value="">All Categories</option>
                    <option value="Music">Music</option>
                    <option value="Conference">Conference</option>
                    <option value="Food">Food</option>
                    <option value="Entertainment">Entertainment</option>
                </select>
                <select class="search-input" id="priceFilter">
                    <option value="">Any Price</option>
                    <option value="0-50">Under $50</option>
                    <option value="50-100">$50 - $100</option>
                    <option value="100-200">$100 - $200</option>
                    <option value="200+">$200+</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
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
                    <div class="event-card">
                        <img src="<?php echo $event['image']; ?>" alt="<?php echo $event['title']; ?>" class="event-image">
                        <div class="event-content">
                            <span class="event-category"><?php echo $event['category']; ?></span>
                            <h3 class="event-title"><?php echo $event['title']; ?></h3>
                            <p class="event-description"><?php echo $event['description']; ?></p>
                            
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
                                    <span><?php echo $event['venue']; ?>, <?php echo $event['city']; ?></span>
                                </div>
                            </div>
                            
                            <div class="event-price">
                                $<?php echo $event['price_min']; ?> - $<?php echo $event['price_max']; ?>
                            </div>
                            
                            <div class="event-status">
                                <span class="status-badge status-<?php echo $status['class']; ?>">
                                    <?php echo $status['text']; ?> (<?php echo $soldPercentage; ?>% sold)
                                </span>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $soldPercentage; ?>%;"></div>
                                </div>
                                <small style="color: #666;">
                                    <?php echo number_format($event['sold_tickets']); ?> / <?php echo number_format($event['total_tickets']); ?> tickets sold
                                </small>
                            </div>
                            
                            <button class="btn btn-primary" style="width: 100%;" onclick="bookEvent(<?php echo $event['id']; ?>)">
                                <i class="fas fa-ticket-alt"></i> Book Now
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
        // Search functionality
        function searchEvents(event) {
            event.preventDefault();
            const searchTerm = document.getElementById('searchInput').value;
            const category = document.getElementById('categoryFilter').value;
            const price = document.getElementById('priceFilter').value;
            
            console.log('Searching for:', { searchTerm, category, price });
            // In a real app, this would filter the events or make an API call
            alert('Search functionality would filter events based on your criteria!');
        }
        
        // Book event function
        function bookEvent(eventId) {
            console.log('Booking event:', eventId);
            alert('Event booking flow would start here! In a real app, this would take you to the ticket selection page.');
        }
        
        // Smooth scrolling for navigation links
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
        
        // Add scroll animations
        window.addEventListener('scroll', () => {
            const cards = document.querySelectorAll('.event-card');
            cards.forEach(card => {
                const rect = card.getBoundingClientRect();
                if (rect.top < window.innerHeight && rect.bottom > 0) {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }
            });
        });
        
        // Initialize animations
        document.addEventListener('DOMContentLoaded', () => {
            console.log('EventPro Customer Website Loaded!');
        });
    </script>
</body>
</html>
