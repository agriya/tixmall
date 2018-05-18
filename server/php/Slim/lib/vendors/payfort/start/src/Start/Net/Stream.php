<?php
class Start_Net_Stream {

    public static function make_request($url, $data = array()) {
        $url = Start::getEndPoint($url);
        $api_key = Start::getApiKey();

        $headers = array(
            'Connection: close',
            "Authorization: Basic ". base64_encode("$api_key:")
        );

        if (!empty($data)) {
            $method = 'POST';
            $content = json_encode($data);
            array_push($headers, 'Content-Type: application/json');
            array_push($headers, 'Content-Length: ' . strlen($content));
        } else {
            $method = 'GET';
            $content = '';
        }

        if (Start::getUserAgent() != "") {
            $user_agent = Start::getUserAgent() . ' / StartPHP Stream ' . Start::VERSION;
        } else {
            $user_agent = 'StartPHP Stream' . Start::VERSION;
        }

        $opts = array(
            'http' => array(
                'method'  => $method,
                'content' => $content,
                'header'  => $headers,
                'timeout' => 20,
                'ignore_errors' => true,
                'user_agent' => $user_agent
            ),
            'ssl' => array(
                'verify_peer'   => true,
                'cafile'        => Start::getCaPath()
            )
        );

        $context  = stream_context_create($opts);
        $response = "{}";
        $exception_message = "";
        try {
            $response = file_get_contents($url, false, $context);
        } catch (Exception $e) {
            $exception_message = "You weren’t able to make API request due to SSL/TLS connection error. "
                . "Here you can read how to solve this: https://docs.start.payfort.com/help/php/ssl. "
                . "Error details: ". $e->getMessage();

            throw new Start_Error_SSLError($exception_message);
        }

        $result = json_decode($response, true);
        $headers = self::parseHeaders($http_response_header);

        if ($headers['http_code'] < 200 || $headers['http_code'] > 299) {
            Start::handleErrors($result, $headers['http_code']);
        } else {
            return $result;
        }
    }

    public static function parseHeaders( $headers )
    {
        $head = array();
        foreach( $headers as $k=>$v )
        {
            $t = explode( ':', $v, 2 );
            if( isset( $t[1] ) )
                $head[ trim($t[0]) ] = trim( $t[1] );
            else
            {
                $head[] = $v;
                if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) )
                    $head['http_code'] = intval($out[1]);
            }
        }
        return $head;
    }
}
