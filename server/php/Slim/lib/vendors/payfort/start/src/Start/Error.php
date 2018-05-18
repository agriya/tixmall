<?php
/**
 * Generic Start error
 */

class Start_Error extends \Exception
{

  /**
  * HTTP status code
  * @var int
  */
  protected $httpStatus;

  /**
  * Error Code
  * @var string
  */
  protected $errorCode;

  /**
  * @param string $message a human readable message
  * @param string $errorCode the specific reason the error occured
  * @param int $httpStatus the HTTP status code
  */
  public function __construct($message, $errorCode, $httpStatus)
  {
    parent::__construct($message);
    $this->errorCode = $errorCode;
    $this->httpStatus = $httpStatus;
  }

  /**
  * Get HTTP status code
  *
  * @return string
  */
  public function getHttpStatus()
  {
    return $this->httpStatus;
  }

  /**
  * Get Error Code
  *
  * @return string
  */
  public function getErrorCode()
  {
    return $this->errorCode;
  }
}
