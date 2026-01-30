<?php

// Simple debug test
echo "<h1>🔍 Debug Test - Event Ticketing System</h1>";

// Test 1: Check if PHP is working
echo "<h2>✅ PHP Status: Working</h2>";

// Test 2: Check if config loads
try {
    require_once 'config/config.php';
    echo "<h2>✅ Config: Loading successfully</h2>";
    
    $config = Config::getInstance();
    echo "<p>App Name: " . $config->get('app.name', 'Not set') . "</p>";
} catch (Exception $e) {
    echo "<h2>❌ Config Error: " . $e->getMessage() . "</h2>";
}

// Test 3: Check if database connects
try {
    require_once 'config/database.php';
    $db = Database::getInstance();
    echo "<h2>✅ Database: Connected</h2>";
} catch (Exception $e) {
    echo "<h2>❌ Database Error: " . $e->getMessage() . "</h2>";
}

// Test 4: Show current working directory
echo "<h2>📁 Current Directory: " . __DIR__ . "</h2>";

// Test 5: Show links to test functionality
echo "<h2>🔗 Test Links:</h2>";
echo "<ul>";
echo "<li><a href='/admin'>📊 Admin Dashboard</a></li>";
echo "<li><a href='/admin/events'>🎪 Events Page</a></li>";
echo "<li><a href='/admin/users'>👥 Users Page</a></li>";
echo "<li><a href='/admin/checkin'>🎫 Check-in Page</a></li>";
echo "<li><a href='/'>📚 API Documentation</a></li>";
echo "</ul>";

echo "<h2>🧪 Manual Test:</h2>";
echo "<p>1. Click any link above</p>";
echo "<p>2. Look for working buttons and navigation</p>";
echo "<p>3. Check browser console for errors</p>";

echo "<script>
console.log('Debug test loaded successfully');
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - checking buttons...');
    const buttons = document.querySelectorAll('button');
    console.log('Found buttons:', buttons.length);
    buttons.forEach((btn, index) => {
        console.log('Button ' + index + ':', btn.textContent, btn.onclick);
    });
});
</script>";

?>
