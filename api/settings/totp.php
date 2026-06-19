<?php
// TOTP implementation (RFC 6238)
class TOTP {
    private static $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    
    // Generate a random 16-character base32 secret
    public static function generateSecret($length = 16) {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::$base32Chars[random_int(0, 31)];
        }
        return $secret;
    }
    
    // Decode base32 to binary
    private static function base32Decode($base32) {
        $base32 = strtoupper($base32);
        $len = strlen($base32);
        $buffer = '';
        $bits = 0;
        $value = 0;
        for ($i = 0; $i < $len; $i++) {
            $char = $base32[$i];
            $pos = strpos(self::$base32Chars, $char);
            if ($pos === false) continue;
            $value = ($value << 5) | $pos;
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $buffer .= chr(($value >> $bits) & 0xFF);
            }
        }
        return $buffer;
    }
    
    // Generate TOTP code for a given secret and time (default 30‑second step)
    public static function getCode($secret, $time = null) {
        if ($time === null) $time = floor(time() / 30);
        $key = self::base32Decode($secret);
        $timeBin = pack('N', $time);
        $timeBin = str_repeat(chr(0), 4) . $timeBin; // 8 bytes big-endian
        $hmac = hash_hmac('sha1', $timeBin, $key, true);
        $offset = ord($hmac[19]) & 0x0F;
        $code = (ord($hmac[$offset]) & 0x7F) << 24 |
                (ord($hmac[$offset+1]) & 0xFF) << 16 |
                (ord($hmac[$offset+2]) & 0xFF) << 8 |
                (ord($hmac[$offset+3]) & 0xFF);
        $code = $code % pow(10, 6);
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    // Verify a code against the secret (allow 1 step before/after for clock drift)
    public static function verify($secret, $code) {
        $time = floor(time() / 30);
        for ($i = -1; $i <= 1; $i++) {
            if (self::getCode($secret, $time + $i) === $code) {
                return true;
            }
        }
        return false;
    }
}
?>