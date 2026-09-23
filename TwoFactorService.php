<?php

declare(strict_types=1);

namespace Demo\Security;

use PragmaRX\Google2FA\Google2FA;

final class TwoFactorService
{
    private Google2FA $google2fa;

    public function __construct(?Google2FA $google2fa = null)
    {
        $this->google2fa = $google2fa ?? new Google2FA();
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function getOtpAuthUri(
        string $issuer,
        string $accountName,
        string $secret
    ): string {
        return $this->google2fa->getQRCodeUrl(
            $issuer,
            $accountName,
            $secret
        );
    }

    public function verify(
        string $secret,
        string $code,
        int $window = 1
    ): bool {
        $code = trim($code);

        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        return $this->google2fa->verifyKey(
            $secret,
            $code,
            $window
        );
    }
}
