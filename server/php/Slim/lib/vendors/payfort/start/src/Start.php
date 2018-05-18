<?php

/**
 * Class to hold Start Start API settings
 *
 * @author Yazin <yazin@payfort.com>
 * @link https://start.payfort.com/docs/
 * @license http://opensource.org/licenses/MIT
 */

class Start
{
  /**
  * Client version
  * @var string
  */
  const VERSION = '0.1.2';

  /**
  * Current API key
  * @var string
  */
  protected static $apiKey;
  
  /**
  * ShoppingCart name and version
  * Our plugin version
  * “WooCommerce 4.2.2 / Start Plugin 0.1.2”
  * @var string
  */
  protected static $userAgent = "";

  /**
  * API Server URL
  * @var string
  */
  protected static $baseURL = 'https://api.start.payfort.com/';

  /**
  * API endpoints
  * @var array
  */
  protected static $endpoints = array(
    'charge'        => 'charges/',
    'token'        => 'tokens/',
    'charge_list'   => 'charges/',
    'customer'      => 'customers/',
    'customer_list' => 'customers/',
    'refund'        => 'refunds/',
    'exception'     => 'health/exception'
  );

  public static $useCurl = false;
  public static $fallback = true;

  /*
  * Path to the CA Certificates required when making CURL calls
  */
  public static function getCaPath() {
    return realpath(dirname(__FILE__)) . '/data/ca-certificates.crt';
  }

  /**
  * sets API Key
  *
  * @param string $apiKey API key
  */
  public static function setApiKey($apiKey)
  {
    self::$apiKey = $apiKey;
  }

  /**
  * returns current set API Key
  *
  * @return string ApiKey
  */
  public static function getApiKey()
  {
    return self::$apiKey;
  }
  /**
  * sets API Key
  *
  * @param string $userAgent UserAgent
  */
  public static function setUserAgent($userAgent)
  {
    self::$userAgent = $userAgent;
  }

  /**
  * returns current set UserAgent
  *
  * @return string UserAgent
  */
  public static function getUserAgent()
  {
    return self::$userAgent;
  }

  /**
  * returns current set API Base URL
  *
  * @return string BaseURL
  */
  public static function getBaseURL()
  {
    return self::$baseURL;
  }

  /**
  * returns full endpoint URI
  *
  * @return string endpoint URI
  */
  public static function getEndPoint($name)
  {
    return self::$baseURL . self::$endpoints[$name];
  }

  public static function handleErrors($result, $httpStatusCode)
  {
    switch($result['error']['type']) {
      case Start_Error_Authentication::$TYPE:
        throw new Start_Error_Authentication($result['error']['message'], $result['error']['code'], $httpStatusCode);
        break;

      case Start_Error_Banking::$TYPE:
        throw new Start_Error_Banking($result['error']['message'], $result['error']['code'], $httpStatusCode);
        break;

      case Start_Error_Processing::$TYPE:
        throw new Start_Error_Processing($result['error']['message'], $result['error']['code'], $httpStatusCode);
        break;

      case Start_Error_Request::$TYPE:
        throw new Start_Error_Request($result['error']['message'], $result['error']['code'], $httpStatusCode);
        break;
    }

    // None of the above? Throw a general White Error
    throw new Start_Error($result['error']['message'], $result['error']['code'], $httpStatusCode);
  }
}
