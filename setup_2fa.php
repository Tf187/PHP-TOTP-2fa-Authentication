<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Demo\Security\Encryption;
use Demo\Security\RecoveryCodeService;
use Demo\Security\TwoFactorService;

$twoFactor = new TwoFactorService();
$recoveryCodes = new RecoveryCodeService();

$secret = $twoFactor->generateSecret();

$uri = $twoFactor->getOtpAuthUri(
    'Example App',
    'user@example.com',
    $secret
);

echo "TOTP secret:\n";
echo $secret . PHP_EOL . PHP_EOL;

echo "Authenticator URI:\n";
echo $uri . PHP_EOL . PHP_EOL;

echo "Recovery codes:\n";

$plainRecoveryCodes = $recoveryCodes->generate();

foreach ($plainRecoveryCodes as $code) {
    echo '- ' . $code . PHP_EOL;
}

echo PHP_EOL;
echo "Store only the hashed recovery codes:\n";

$hashedCodes = $recoveryCodes->hashCodes($plainRecoveryCodes);

echo json_encode(
    $hashedCodes,
    JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
) . PHP_EOL;

$key = getenv('TWO_FACTOR_ENCRYPTION_KEY');

if ($key) {
    $encryption = new Encryption($key);

    echo PHP_EOL;
    echo "Encrypted TOTP secret:\n";
    echo $encryption->encrypt($secret) . PHP_EOL;
} else {
    echo PHP_EOL;
    echo "TWO_FACTOR_ENCRYPTION_KEY is not set, so encryption was skipped." . PHP_EOL;
}
