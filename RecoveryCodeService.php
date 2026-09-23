<?php

declare(strict_types=1);

namespace Demo\Security;

final class RecoveryCodeService
{
    /**
     * @return list<string>
     */
    public function generate(int $count = 8): array
    {
        if ($count < 1 || $count > 50) {
            throw new \InvalidArgumentException(
                'Recovery code count must be between 1 and 50.'
            );
        }

        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = sprintf(
                '%s-%s',
                strtoupper(bin2hex(random_bytes(4))),
                strtoupper(bin2hex(random_bytes(4)))
            );
        }

        return $codes;
    }

    /**
     * @param list<string> $codes
     * @return list<string>
     */
    public function hashCodes(array $codes): array
    {
        return array_map(
            static fn (string $code): string =>
                password_hash($code, PASSWORD_DEFAULT),
            $codes
        );
    }

    /**
     * Returns the index of a matching recovery code.
     * Returns null when no code matches.
     *
     * @param list<string> $hashedCodes
     */
    public function findMatchingCode(
        string $submittedCode,
        array $hashedCodes
    ): ?int {
        $submittedCode = strtoupper(trim($submittedCode));

        foreach ($hashedCodes as $index => $hash) {
            if (password_verify($submittedCode, $hash)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param list<string> $hashedCodes
     * @return list<string>
     */
    public function consumeCode(
        int $index,
        array $hashedCodes
    ): array {
        if (!array_key_exists($index, $hashedCodes)) {
            return $hashedCodes;
        }

        unset($hashedCodes[$index]);

        return array_values($hashedCodes);
    }
}
