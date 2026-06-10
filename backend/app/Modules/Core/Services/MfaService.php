<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

class MfaService
{
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecret(int $length = 16): string
    {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_CHARS[random_int(0, 31)];
        }
        return $secret;
    }

    public function getQrCodeUri(string $issuer, string $accountName, string $secret): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($accountName)
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=6&period=30';
    }

    public function verifyCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        if (strlen($code) !== 6 || !ctype_digit($code)) {
            return false;
        }

        try {
            $key = $this->base32Decode($secret);
        } catch (\Exception $e) {
            return false;
        }

        $currentTimeSlice = (int) floor(time() / 30);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $timeSlice = $currentTimeSlice + $i;
            if ($this->calculateCode($key, $timeSlice) === $code) {
                return true;
            }
        }

        return false;
    }

    private function base32Decode(string $base32): string
    {
        $base32 = strtoupper($base32);
        $content = '';
        $buffer = 0;
        $bufferSize = 0;

        for ($i = 0; $i < strlen($base32); $i++) {
            $char = $base32[$i];
            if ($char === '=') {
                break;
            }

            $pos = strpos(self::BASE32_CHARS, $char);
            if ($pos === false) {
                throw new \InvalidArgumentException('Invalid base32 character: ' . $char);
            }

            $buffer = ($buffer << 5) | $pos;
            $bufferSize += 5;

            if ($bufferSize >= 8) {
                $bufferSize -= 8;
                $content .= chr(($buffer >> $bufferSize) & 0xFF);
            }
        }

        return $content;
    }

    private function calculateCode(string $key, int $timeSlice): string
    {
        $timeHex = str_pad(dechex($timeSlice), 16, '0', STR_PAD_LEFT);
        $timeBin = pack('H*', $timeHex);

        $hashBin = hash_hmac('sha1', $timeBin, $key, true);
        $offset = ord($hashBin[19]) & 0x0F;

        $truncatedHash = (
            ((ord($hashBin[$offset]) & 0x7F) << 24) |
            ((ord($hashBin[$offset + 1]) & 0xFF) << 16) |
            ((ord($hashBin[$offset + 2]) & 0xFF) << 8) |
            (ord($hashBin[$offset + 3]) & 0xFF)
        );

        $code = $truncatedHash % 1000000;
        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }
}
