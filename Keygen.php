<?php
// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the Composer autoloader
require 'vendor/autoload.php'; // Adjust the path if necessary

use Minishlink\WebPush\VAPID;

$vapidKeys = VAPID::createVapidKeys();

$publicKey = $vapidKeys['publicKey'];
$privateKey = $vapidKeys['privateKey'];

echo "Public Key: $publicKey\n";
echo "Private Key: $privateKey\n";
