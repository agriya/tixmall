<?php
class Start_Net_Curl {
    public static function make_request($url, $data = array()) {
        if (!defined('CURL_SSLVERSION_TLSv1_2')) {
            define('CURL_SSLVERSION_TLSv1_2', 6);
        }

        $url = Start::getEndPoint($url);
        $ch = curl_init();

        if (Start::getUserAgent() != "") {
            $userAgent = Start::getUserAgent() . ' / StartPHP CURL ' . Start::VERSION;
        } else {
            $userAgent = 'StartPHP CURL' . Start::VERSION;
        }

        curl_setopt($ch, CURLOPT_CAINFO, Start::getCaPath());
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, Start::getApiKey() . ':');
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($data)))
            );
        }
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = json_decode(curl_exec($ch), true);

        // Check for errors and such.
        $info = curl_getinfo($ch);
        $errno = curl_errno($ch);
        if ($result === false || $errno != 0) {
            // Do error checking
            $curl_error = curl_error($ch);

            if ($errno == '1' || $errno == '35' || $errno == '51' || $errno == '60') {
                $exception_message = "You weren’t able to make API request due to SSL/TLS error. "
                    . "  Here you can read how to solve this: https://docs.start.payfort.com/help/php/ssl#error_" . $errno
                    . " Curl error: " . $curl_error;
            } else {
                $exception_message = "Curl error: " . $curl_error;
            }
            throw new Start_Error_SSLError($exception_message);
        } else if ($info['http_code'] < 200 || $info['http_code'] > 299) {
            // Got a non-200 error code.
            Start::handleErrors($result, $info['http_code']);
        }
        curl_close($ch);

        return $result;
    }
}
