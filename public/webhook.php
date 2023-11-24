<?php

declare(strict_types=1);

require('../src/config.default.php');
require('../src/config.php');

/** @var $config array */

$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
$hash = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$hash = explode('=', $hash)[1];
$secret = $config['github']['webhook']['secret'];

if (!in_array($event, ['push', 'ping'])) {
    http_response_code(400);
    die('Bad event.');
}

$data = file_get_contents('php://input');
if (!hash_equals($hash, hash_hmac('sha256', $data, $secret))) {
    http_response_code(403);
    die('Bad token.');
}

$data = json_decode($data, true);

// Environment fix; alternatively modify php-fpm conf
putenv('PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin');
switch ($data['repository']['full_name']) {
    case 'kyutc/RestockFrontend':
        exec('../src/Webhook/deploy-frontend.sh 2>&1', $output, $code);
        if ($code != 0) {
            http_response_code(500);
            print_r($output);
            die();
        }
        break;
    case 'kyutc/RestockApi':
        exec('../src/Webhook/deploy-frontend.sh 2>&1', $output, $code);
        if ($code != 0) {
            http_response_code(500);
            die();
        }
        break;
    default:
        http_response_code(400);
        die('Unknown repo.');
}

http_response_code(200);
print_r($output);