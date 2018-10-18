<?php
// https://stackoverflow.com/questions/10916284/how-to-encrypt-decrypt-data-in-php

/**
 * Class Encrypt
 */
class Encrypt extends SafeClass
{
    public static $KeySize = 32; // 256-bit
    public static $PublicKeySize = 16; // 128-bit
    public static $EncryptionMethod = 'AES-256-CBC';

    public static function GetEncryptionMethods()
    {
        return openssl_get_cipher_methods();
    }

    /**
     * @return string
     */
    public static function GetPrivateKey()
    {
        $strong = true;
        $encryption_key = openssl_random_pseudo_bytes(self::$KeySize, $strong);

        return base64_encode($encryption_key);
    }

    public static function pkcs7_pad($data)
    {
        $length = self::$KeySize - strlen($data) % self::$KeySize;
        return $data . str_repeat(chr($length), $length);
    }

    public static function pkcs7_unpad($data)
    {
        return substr($data, 0, -ord($data[strlen($data) - 1]));
    }

    public static function GetPublicKey()
    {
        $strong = true;
        $encryption_key = openssl_random_pseudo_bytes(self::$PublicKeySize, $strong);

        return base64_encode($encryption_key);
    }

    public static function EncryptData($data, $private_key, $public_key)
    {
        return base64_encode(openssl_encrypt(
            self::pkcs7_pad($data), // padded data
            self::$EncryptionMethod,        // cipher and mode
            base64_decode($private_key),      // secret key
            0,                    // options (not used)
            base64_decode($public_key)                   // initialisation vector
        ));

    }

    public static function DecryptData($data, $private_key, $public_key)
    {
        return self::pkcs7_unpad(openssl_decrypt(
            base64_decode($data),
            self::$EncryptionMethod,
            base64_decode($private_key),
            0,
            base64_decode($public_key)
        ));
    }
}