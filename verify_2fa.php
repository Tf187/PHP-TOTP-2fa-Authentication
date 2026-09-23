<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Demo\Security\TwoFactorService;

if ($argc < 3) {
    echo "Usage:\n";
    echo "php examples/verify_2fa.php <SECRET> <6-DIGIT-CODE>\n";
    exit(1);
}

$secret = $argv[1];
$code = $argv[2];

$twoFactor = new TwoFactorService();

if ($twoFactor->verify($secret, $code)) {
    echo "Code is valid." . PHP_EOL;
    exit(0);
}

echo "Code is invalid." . PHP_EOL;
exit(1);
