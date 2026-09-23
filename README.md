# PHP TOTP Two-Factor Authentication

A small, framework-independent example showing how to implement **TOTP-based two-factor authentication (2FA)** in PHP.

The project demonstrates:

- TOTP secret generation
- Authenticator-app setup
- 6-digit code verification
- Encrypted storage of TOTP secrets
- Single-use recovery codes
- Secure session handling
- Basic rate-limiting guidance
- Database schema examples

It is intentionally kept simple so the individual security concepts are easy to understand and reuse.

> This repository is an educational example. Review the implementation and adapt it to your own threat model before using it in production.

---

## How TOTP 2FA works

A normal login uses only one factor:

```text
Email + Password
```

With two-factor authentication enabled, the flow becomes:

```text
Email + Password
        |
        v
Password verified
        |
        v
Request 6-digit TOTP code
        |
        v
Verify authenticator code
        |
        v
Create authenticated session
```

TOTP stands for **Time-based One-Time Password**.

The server and the user's authenticator application share a secret. Both independently calculate a short-lived numeric code from that secret and the current time.

Common compatible authenticator apps include:

- Google Authenticator
- Microsoft Authenticator
- 1Password
- Bitwarden
- Authy
- Apple Passwords

---

## Requirements

- PHP 8.2+
- Composer
- Sodium PHP extension
- PDO-compatible database

Install dependencies:

```bash
composer install
```

---

## Project structure

```text
.
├── .github/
│   └── workflows/
│       └── php.yml
├── database/
│   └── schema.sql
├── examples/
│   ├── setup_2fa.php
│   └── verify_2fa.php
├── src/
│   ├── Encryption.php
│   ├── RecoveryCodeService.php
│   └── TwoFactorService.php
├── .env.example
├── .gitignore
├── composer.json
├── LICENSE
├── README.md
└── SECURITY.md
```

---

## 1. Database fields

A user record needs a few additional fields.

See:

```text
database/schema.sql
```

The important fields are:

```sql
two_factor_enabled
two_factor_secret
two_factor_confirmed_at
two_factor_recovery_codes
```

The TOTP secret should be **encrypted**, not hashed.

Why?

Passwords only need to be compared, so they can be hashed.

A TOTP secret must be read by the server again when a code is validated.

```text
Passwords            -> hash
TOTP secrets         -> encrypt
Recovery codes       -> hash
```

---

## 2. Generate an encryption key

The project uses Sodium secret-box encryption.

Generate a random key:

```bash
php -r "echo base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)), PHP_EOL;"
```

Copy the generated value into your environment configuration.

Example:

```env
TWO_FACTOR_ENCRYPTION_KEY=YOUR_BASE64_KEY_HERE
```

Never commit the real key.

---

## 3. Generate a TOTP secret

```php
use Demo\Security\TwoFactorService;

$twoFactor = new TwoFactorService();

$secret = $twoFactor->generateSecret();
```

A secret might look like:

```text
JBSWY3DPEHPK3PXP
```

Do not enable 2FA immediately after generating it.

The user should first scan the QR code or enter the secret manually and successfully verify a code.

Recommended setup flow:

```text
Generate secret
      |
      v
Display QR / setup key
      |
      v
User adds account to authenticator
      |
      v
User enters first TOTP code
      |
      v
Server verifies code
      |
      v
Enable 2FA
```

---

## 4. Create an authenticator URI

Authenticator apps understand `otpauth://` URIs.

```php
$uri = $twoFactor->getOtpAuthUri(
    'Example App',
    'user@example.com',
    $secret
);
```

The returned value can be converted into a QR code by any QR-code library.

Example:

```text
otpauth://totp/Example%20App:user@example.com?secret=...
```

The QR code should never contain anything except the setup URI that belongs to the current user.

---

## 5. Verify a 6-digit code

```php
$valid = $twoFactor->verify(
    $secret,
    $_POST['code'] ?? ''
);

if (!$valid) {
    http_response_code(401);
    exit('Invalid authentication code.');
}
```

Input validation should happen before verification.

For example:

```php
$code = trim($_POST['code'] ?? '');

if (!preg_match('/^\d{6}$/', $code)) {
    http_response_code(400);
    exit('Invalid code format.');
}
```

---

## 6. Encrypt the secret before storing it

```php
use Demo\Security\Encryption;

$encryption = new Encryption(
    $_ENV['TWO_FACTOR_ENCRYPTION_KEY']
);

$encryptedSecret = $encryption->encrypt($secret);
```

Store only the encrypted value in the database.

To verify a login later:

```php
$secret = $encryption->decrypt(
    $user['two_factor_secret']
);
```

Then validate the submitted TOTP code against the decrypted secret.

---

## 7. Recovery codes

Recovery codes are useful when a user loses access to the authenticator device.

Generate them:

```php
use Demo\Security\RecoveryCodeService;

$recoveryCodes = new RecoveryCodeService();

$plainCodes = $recoveryCodes->generate();
```

Show the plain codes to the user once.

Store only hashed versions:

```php
$hashedCodes = $recoveryCodes->hashCodes(
    $plainCodes
);
```

Example recovery code:

```text
2F4A7C19-91B0DE22
```

A recovery code must be:

- random
- stored as a hash
- single-use
- invalidated after successful use

---

## 8. Login flow

A secure login flow should not create a fully authenticated session before 2FA has been verified.

```php
if (password_verify($password, $user['password_hash'])) {

    if ($user['two_factor_enabled']) {
        $_SESSION['pending_2fa_user_id'] = $user['id'];

        header('Location: /two-factor');
        exit;
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['authenticated'] = true;
}
```

After a successful 2FA challenge:

```php
session_regenerate_id(true);

$_SESSION['user_id'] = $user['id'];
$_SESSION['authenticated'] = true;
$_SESSION['two_factor_verified'] = true;

unset($_SESSION['pending_2fa_user_id']);
```

Regenerating the session ID helps protect against session fixation.

---

## 9. Disabling 2FA

Disabling 2FA should be treated as a sensitive action.

Require the user to re-authenticate with:

```text
Current password
+
Current authenticator code
```

Only then remove:

```text
two_factor_secret
two_factor_recovery_codes
two_factor_confirmed_at
```

and set:

```text
two_factor_enabled = false
```

---

## 10. Rate limiting

TOTP verification endpoints should be rate-limited.

A simple policy could be:

```text
5 attempts per 5 minutes
```

A real implementation can combine:

- user ID
- session ID
- IP address
- device information

Do not rely on the IP address alone.

---

## 11. Security checklist

- [ ] Passwords are hashed using `password_hash()`
- [ ] TOTP secrets are encrypted at rest
- [ ] Encryption keys are stored outside the database
- [ ] Environment files are excluded from Git
- [ ] Recovery codes are hashed
- [ ] Recovery codes are single-use
- [ ] 2FA activation requires successful verification
- [ ] Login is not completed before 2FA verification
- [ ] Session ID is regenerated after authentication
- [ ] 2FA endpoints are rate-limited
- [ ] 2FA removal requires re-authentication
- [ ] HTTPS is enforced in production
- [ ] Security-sensitive actions are logged

---

## Running the examples

Install dependencies:

```bash
composer install
```

Run the setup example:

```bash
php examples/setup_2fa.php
```

Run a verification example:

```bash
php examples/verify_2fa.php 123456
```

Replace `123456` with the current code generated by the authenticator application.

---

## Notes

This repository focuses on the core 2FA mechanics.

A production application should additionally consider:

- CSRF protection
- persistent rate limiting
- audit logging
- trusted-device policies
- secure cookie configuration
- account recovery procedures
- secret rotation
- backup and restore procedures
- session revocation
- alerts for security-sensitive account changes

---

