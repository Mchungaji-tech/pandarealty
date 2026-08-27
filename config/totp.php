<?php
/**
 * Panda Realty - Google Authenticator RFC 6238 TOTP Procedural Implementation
 * Designed & Developed by TekTrend
 */

/**
 * Generate a random 16-character Base32 secret for Google Authenticator
 */
function generate_totp_secret($length = 16) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $secret;
}

/**
 * Decode base32 string into binary
 */
function base32_decode_custom($secret) {
    if (empty($secret)) return '';
    $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $base32charsFlipped = array_flip(str_split($base32chars));
    $paddingCharCount = substr_count($secret, '=');
    $allowedValues = array(6, 4, 3, 1, 0);
    if (!in_array($paddingCharCount, $allowedValues)) return false;
    for ($i = 0; $i < 4; $i++) {
        if ($paddingCharCount == $allowedValues[$i] &&
            substr($secret, -($allowedValues[$i])) != str_repeat('=', $allowedValues[$i])) return false;
    }
    $secret = str_replace('=', '', $secret);
    $secret = str_split($secret);
    $binaryString = '';
    for ($i = 0; $i < count($secret); $i = $i + 8) {
        $x = '';
        if (!in_array($secret[$i], str_split($base32chars))) return false;
        for ($j = 0; $j < 8; $j++) {
            if (isset($secret[$i + $j])) {
                $x .= str_pad(base_convert(@$base32charsFlipped[$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }
        }
        $eightBits = str_split($x, 8);
        for ($z = 0; $z < count($eightBits); $z++) {
            $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
        }
    }
    return $binaryString;
}

/**
 * Calculate the current TOTP 6-digit code for a secret
 */
function get_totp_code($secret, $timeSlice = null) {
    if ($timeSlice === null) {
        $timeSlice = floor(time() / 30);
    }
    $secretKey = base32_decode_custom($secret);
    if (empty($secretKey)) return false;

    // Pack time into binary string
    $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);
    // Hash with HMAC-SHA1
    $hmac = hash_hmac('sha1', $time, $secretKey, true);
    // Offset
    $offset = ord(substr($hmac, -1)) & 0x0F;
    // Extract 4 bytes
    $hashPart = substr($hmac, $offset, 4);
    // Unpack
    $value = unpack('N', $hashPart);
    $value = $value[1];
    $value = $value & 0x7FFFFFFF;

    $modulo = 10 ** 6;
    return str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);
}

/**
 * Verify a user's submitted 6-digit 2FA code with clock drift allowance (window = 1 slice before/after)
 */
function verify_totp_code($secret, $code, $discrepancy = 1) {
    if (strlen($code) !== 6 || !ctype_digit($code)) {
        return false;
    }
    $currentTimeSlice = floor(time() / 30);
    for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
        $calculatedCode = get_totp_code($secret, $currentTimeSlice + $i);
        if ($calculatedCode === $code) {
            return true;
        }
    }
    return false;
}

/**
 * Generate Google Authenticator QR Code URL using Google Chart API / SVG QR endpoint
 */
function get_totp_qr_url($issuer, $accountName, $secret) {
    $otpauth = 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($accountName) . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer);
    return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($otpauth);
}
