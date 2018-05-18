<?php
class Start_Request {
    public static function make_request($url, $data = array()) {
        try {
            return Start::$useCurl ? Start_Net_Curl::make_request($url, $data) : Start_Net_Stream::make_request($url, $data);
        } catch (Start_Error_SSLError $e) {
            // fallback to opposite method
            if (Start::$fallback) {
                return Start::$useCurl ? Start_Net_Stream::make_request($url, $data) : Start_Net_Curl::make_request($url, $data);
            } else {
                throw $e;
            }
        }
    }
}
