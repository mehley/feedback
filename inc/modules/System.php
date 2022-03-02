<?php

/**
 * Class System
 */
final class System
{
    /**
     * @param array $array
     */
    public static function debug(array $array): array
    {
        die(print_r($array));
    }

    public static function getSessionId(): ?string
    {
        if(session_status() !== PHP_SESSION_NONE){
            return session_id();
        }else{
            return null;
        }
    }

    /**
     * @param $array
     * @return array
     */
    public static function decryptArray($array): array
    {
        if (!session_id()) {
            die('keine Session id!');
        }

        $temp = [];
        foreach ($array as $key => $value) {
            // Fix issue with IE11 - sends keys like '[-----------------------------7df23af4c05b6--]'
            if (strpos($key, '-----------------------------') !== false) {
                continue;
            }
                if (array_key_exists(SslCrypt::decrypt($key), $temp)){
                    if (is_array($value)){
                        foreach ($value as $keyB => $valueB){

                            $temp[SslCrypt::decrypt($key)][$keyB] = $valueB;
                        }
                    }
                }else{
                    $temp[SslCrypt::decrypt($key)] = $value;
                }
        }

        return $temp;
    }

    /**
     * @return false
     */
    public static function decryptPOST(): void
    {
        if(isset($_POST)){

            $GLOBALS['_POST_RAW'] = $_POST;

            $_POST = self::decryptArray($_POST);

        }
    }

    /**
     * @param false $fixedKey
     */
    public static function decryptGET($fixedKey = false): void
    {
        global $config;

        if (!session_id()) {
            die('keine Session id!');
        }

        $GLOBALS['_GET_RAW'] = $_GET;

        $temp = $_SERVER['QUERY_STRING'];
        $text = explode('&', $temp);

        if (base64_decode($text[0], true)) {
            if ($fixedKey == true){
                $tryTemp = SslCrypt::decrypt($text[0], $config['key']);
            }else{
                $tryTemp = SslCrypt::decrypt($text[0]);
            }
        } else {
            //whatever leave query string as it is, not our business
            $tryTemp = urldecode($temp);
        }
        if (function_exists('taint')) {
            taint($tryTemp);
        }

        $tempArray = explode('&', $tryTemp);

        foreach ($tempArray as $key => $value) {
            $pKey = substr($value, 0, strpos($value, '='));
            $pValue = substr($value, strpos($value, '=') + 1);

            if (substr($pKey, -2, 2) == '[]') {
                // array
                $GLOBALS['GET'][substr($pKey, 0, -2)][] = $pValue;
            } else {
                // single value
                $GLOBALS['GET'][$pKey] = $pValue;
            }
        }

        $_GET = $GLOBALS['GET'];
    }

    public function __construct()
    {
        die('Diese klasse darf nicht instanziert werden');
    }
}