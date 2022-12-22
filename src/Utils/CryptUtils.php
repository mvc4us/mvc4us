<?php
declare(strict_types=1);

namespace Mvc4us\Utils;

use Mvc4us\Config\Config;

final class CryptUtils
{

    private const CIPHER_ALGO = 'aes-256-cbc';
    private const HASH_ALGO = 'sha256';

    /**
     * @throws \Exception
     */
    public static function encrypt(string $message, ?string $key = null): string
    {
        if (!extension_loaded('openssl')) {
            throw new \LogicException('Cannot find the "openssl" extension.');
        }

        if (empty($key)) {
            $key = self::getAppSecret();
        }
        $key = self::getCipherKey($key);
        //if (mb_strlen($key, '8bit') !== 32) {
        //    throw new \Exception("CryptUtils needs a 256-bit key!");
        //}

        $ivSize = openssl_cipher_iv_length(self::CIPHER_ALGO);
        $iv = openssl_random_pseudo_bytes($ivSize);
        $ciphertext = openssl_encrypt($message, self::CIPHER_ALGO, $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac(self::HASH_ALGO, $iv . $ciphertext, $key, true);

        return base64_encode($hmac . $iv . $ciphertext);
    }

    /**
     * @throws \Exception
     */
    public static function decrypt($message, $key = null): string
    {
        if (!extension_loaded('openssl')) {
            throw new \LogicException('Cannot find the "openssl" extension.');
        }

        if (empty($key)) {
            $key = self::getAppSecret();
        }
        $key = self::getCipherKey($key);

        $decoded = base64_decode($message);
        $hmac = mb_substr($decoded, 0, 32, '8bit');
        $ivSize = openssl_cipher_iv_length(self::CIPHER_ALGO);
        $iv = mb_substr($decoded, 32, $ivSize, '8bit');
        $ciphertext = mb_substr($decoded, $ivSize + 32, null, '8bit');

        $decrypted = false;
        $calculated = hash_hmac(self::HASH_ALGO, $iv . $ciphertext, $key, true);
        if (hash_equals($hmac, $calculated)) {
            $decrypted = openssl_decrypt($ciphertext, self::CIPHER_ALGO, $key, OPENSSL_RAW_DATA, $iv);
        }

        if ($decrypted === false) {
            throw new \InvalidArgumentException('Provided key is not valid for the message.');
        }
        return $decrypted;
    }

    /**
     * @throws \Exception
     */
    public static function passwordGenerator(
        int $length = 8,
        string $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789'
    ): string {
        $charactersLength = strlen($characters);
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, $charactersLength - 1)];
        }

        return $password;
    }

    public static function getAppSecret(): string
    {
        $key = Config::get('security', 'key');
        if (empty($key)) {
            throw new \InvalidArgumentException('Secret key ([security].key) is not defined in any *.toml file.');
        }
        return $key;
    }

    private static function getCipherKey(string $key): string
    {
        while (mb_strlen($key, '8bit') < 32) {
            $key .= $key;
        }
        return mb_substr($key, 0, 32, '8bit');
    }
}
