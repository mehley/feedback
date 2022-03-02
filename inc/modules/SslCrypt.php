<?php
/**
 * Class Ver und Entschlüsselung Klasse die an keine Session gebunden ist.
 */
class SslCrypt {
	public static $cipher = 'AES-128-CBC';

	/**
	 * Key für die Ver und Entschlüsselung holen
	 * @param  string $keyType - wenn null, wird ein automatisch generierter Key geholt, wenn static wird ein unveränderlicher Key geholt
	 * @return string
	 */
	private static function getKey(): string{
	    global $config;

		$key = System::getSessionId();
		if ($key === null) {
			return $config['key'];
		}

		return substr($key, 0, 32);
	}

    /**
     * @param $stringToEncrypt
     * @param null $key
     * @return string
     */
    public static function encrypt($stringToEncrypt, $key = null) {
		$ivlen          = openssl_cipher_iv_length($cipher = self::$cipher);
		$iv             = openssl_random_pseudo_bytes($ivlen);
		$key            = $key === null ? self::getKey() : $key;
		$ciphertext_raw = openssl_encrypt($stringToEncrypt, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
		$hmac           = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);
		$ciphertext     = base64_encode($iv . $hmac . $ciphertext_raw);

		return $ciphertext;
	}

    /**
     * @param $stringToDecrypt
     * @param null $key
     * @return false|mixed|string|null
     */
    public static function decrypt($stringToDecrypt, $key = null) {
	    // trying to decrypt something which isn't encrypted?
        if (strlen($stringToDecrypt)<80)
            {
                return ($stringToDecrypt);
            }

		try {

            $c                  = base64_decode($stringToDecrypt);
			$ivlen              = openssl_cipher_iv_length($cipher = self::$cipher);
			$iv                 = substr($c, 0, $ivlen);
			$hmac               = substr($c, $ivlen, $sha2len = 32);
			$ciphertext_raw     = substr($c, $ivlen + $sha2len);
			$key                = $key === null ? self::getKey() : $key;
			$original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
            $calcmac            = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);

			//PHP 5.6+ timing attack safe comparison
			if (hash_equals($hmac, $calcmac)) {
            	return $original_plaintext;
			}

		} catch (Exception $e) {
			return $stringToDecrypt;
		}

		// String konnte nicht entschlüsselt werden...
        // ...weil zwar offenbar verschlüsselt, aber die Schlüssel passen nicht zusammen; sieht nach Murks aus
        // schön ist das aber nicht
        if ((empty($_POST))&&(empty($_GET)))
            {
            return null;
            }


        http_response_code(404);
        die();


	}
}
