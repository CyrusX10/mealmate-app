<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$prefix = '/mealmate/mealmate-webapp';

if (strpos($uri, $prefix) === 0) {
    $uri = substr($uri, strlen($prefix));
}
if ($uri === '' || $uri === '/') {
    $uri = '/index.php';
}

$file = __DIR__ . $uri;

if (is_file($file)) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $mimeTypes = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
    ];

    if ($ext === 'php') {
        chdir(dirname($file));
        require $file;
        return true;
    }

    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
    }
    readfile($file);
    return true;
}

http_response_code(404);
echo 'Not Found';
