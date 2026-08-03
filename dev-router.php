<?php
// Get requested URI
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Allow direct access to existing files and directories
if (file_exists(__DIR__ . $request_uri) && !is_dir(__DIR__ . $request_uri)) {
    return false;
}

// Check inside public/ folder for static assets
if (file_exists(__DIR__ . '/public' . $request_uri) && !is_dir(__DIR__ . '/public' . $request_uri)) {
    $filePath = __DIR__ . '/public' . $request_uri;
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimeTypes = [
        'css' => 'text/css',
        'js'  => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg'=> 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'txt' => 'text/plain',
        'xml' => 'application/xml',
        'ico' => 'image/x-icon'
    ];
    $mimeType = $mimeTypes[$ext] ?? mime_content_type($filePath) ?: 'application/octet-stream';
    header("Content-Type: " . $mimeType);
    readfile($filePath);
    exit;
}

// Redirect everything else to public/index.php
require __DIR__ . '/public/index.php';
