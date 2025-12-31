<?php
// Application constants
define('APP_NAME', 'Task Manager');
// Determine application base URL dynamically when possible (works when served via webserver)
if (php_sapi_name() !== 'cli' && isset($_SERVER['HTTP_HOST'])) {
	$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? 'https' : 'http';
	// Get the base directory: go up from config/ to project root, then get relative path
	$projectRoot = dirname(dirname(__FILE__));
	$docRoot = $_SERVER['DOCUMENT_ROOT'];
	$relPath = str_replace('\\', '/', substr($projectRoot, strlen($docRoot)));
	$relPath = rtrim($relPath, '/');
	$base = $protocol . '://' . $_SERVER['HTTP_HOST'] . ($relPath === '' ? '' : $relPath);
	define('APP_URL', $base);
} else {
	// Fallback for CLI or when server vars aren't available — adjust if needed
	define('APP_URL', 'http://localhost/task-manager');
}
define('SESSION_TIMEOUT', 3600); // 1 hour

// Password hashing
define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_OPTIONS', ['cost' => 12]);

// API endpoints
define('API_BASE', '/api/');

// File paths
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('CONFIG_PATH', ROOT_PATH . '/config/');
define('INCLUDES_PATH', ROOT_PATH . '/includes/');
define('API_PATH', ROOT_PATH . '/api/');
?>
