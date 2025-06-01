<?php
require_once('urlcache.php');

// Get URL from query parameter
$url = isset($_GET['u']) ? trim($_GET['u']) : null;

// Validate URL
if (!$url) {
    echo "Error: URL parameter is required";
    exit();
}

// Check if URL starts with http:// or https://
if (substr($url, 0, 4) !== 'http') {
    // Try to prepend http:// if not present
    if (substr($url, 0, 7) !== 'http://' && substr($url, 0, 8) !== 'https://') {
        $url = 'http://' . $url;
    }
}

// Validate URL format
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo "Error: Invalid URL format";
    exit();
}

try {
    // Cache the URL and get the key
    $key = save_url($url);
    
    // Redirect to the read.php page with the key
    header("Location: /r?a=" . $key);
    exit();
} catch (Exception $e) {
    echo "Error caching URL: " . $e->getMessage();
}
?>
