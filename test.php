<?php

// Simple test file to check if everything works
require_once 'config/config.php';
require_once 'config/database.php';

try {
    $config = Config::getInstance();
    $db = Database::getInstance();
    
    echo "<h1>✅ Event Ticketing System - Test Successful!</h1>";
    echo "<h2>Configuration Status:</h2>";
    echo "<ul>";
    echo "<li>✅ Config class loaded</li>";
    echo "<li>✅ Database connection: " . ($db ? "Connected" : "Not connected") . "</li>";
    echo "<li>✅ Environment: " . $config->get('app.env', 'unknown') . "</li>";
    echo "</ul>";
    
    echo "<h2>Access Links:</h2>";
    echo "<ul>";
    echo "<li><a href='/admin'>📊 Admin Dashboard</a></li>";
    echo "<li><a href='/'>📚 API Documentation</a></li>";
    echo "<li><a href='/api/events'>🎫 Test API (Events)</a></li>";
    echo "</ul>";
    
    echo "<h2>Next Steps:</h2>";
    echo "<ol>";
    echo "<li>Visit the <a href='/admin'>Admin Dashboard</a></li>";
    echo "<li>Explore the API endpoints</li>";
    echo "<li>Create your first event</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h1>❌ Error: " . $e->getMessage() . "</h1>";
    echo "<p>Please check your database configuration in .env file</p>";
}
?>
