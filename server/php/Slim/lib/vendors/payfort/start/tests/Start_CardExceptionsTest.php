<?php

class Start_CardExceptionsTest extends \PHPUnit_Framework_TestCase
{

  function setUp()
  {
    Start::setApiKey('test_sec_k_2b99b969196bece8fa7fd');
    Start::$fallback = false;

    if (getenv("CURL") == "1") {
        Start::$useCurl = true;
    }
  }

  function testCardDeclined()
  {
    $data = array(
      "amount" => 1050,
      "currency" => "usd",
      "email" => "ahmed@example.com",
      "card" => array(
        "number" => "4000000000000002",
        "exp_month" => 11,
        "exp_year" => 2016,
        "cvc" => "123"
      ),
      "description" => "Charge for test@example.com"
    );

    try{
      $result = Start_Charge::create($data);
    } catch (Start_Error_Banking $e) {
      $this->assertEquals('card_declined', $e->getErrorCode());
    }
  }

  function testInvalidCard()
  {
    $data = array(
      "amount" => 1050,
      "currency" => "usd",
      "card" => array(
        "number" => "4141414141414141",
        "exp_month" => 12,
        "exp_year" => 2016,
        "cvc" => "123"
      ),
      "description" => "Charge to test@example.com"
    );

    try{
      $result = Start_Charge::create($data);
    } catch (Start_Error_Request $e) {
      $this->assertEquals('unprocessable_entity', $e->getErrorCode());
    }
  }

  function testInvalidCVC()
  {
    $data = array(
      "amount" => 1050,
      "currency" => "usd",
      "card" => array(
        "number" => "4242424242424242",
        "exp_month" => 11,
        "exp_year" => 2016,
        "cvc" => "abc"
      ),
      "description" => "Charge for test@example.com"
    );

    try{
      $result = Start_Charge::create($data);
    } catch (Start_Error_Request $e) {
      $this->assertEquals('unprocessable_entity', $e->getErrorCode());
    }
  }

  function testExpiredCard()
  {
    $data = array(
      "amount" => 1050,
      "currency" => "usd",
      "card" => array(
        "number" => "4242424242424242",
        "exp_month" => 11,
        "exp_year" => 2012,
        "cvc" => "123"
      ),
      "description" => "Charge for test@example.com"
    );

    try{
      $result = Start_Charge::create($data);
    } catch (Start_Error_Request $e) {
      $this->assertEquals('unprocessable_entity', $e->getErrorCode());
    }
  }

  /**
   * Waiting for test card to go up

  function testProcessingError()
  {
    $data = array(
      "amount" => 1050,
      "currency" => "usd",
      "card" => array(
        "number" => "4000000000000119",
        "exp_month" => 11,
        "exp_year" => 2016,
        "cvc" => "123"
      ),
      "description" => "Charge for test@example.com"
    );

    try{
      $result = Start_Charge::create($data);
    } catch (Start_Error_Card $e) {
      $this->assertEquals('processing_error', $e->getErrorCode());
    }
  }

   */

  function testIncorrectNumber()
  {
    $data = array(
      "amount" => 1050,
      "currency" => "usd",
      "card" => array(
        "number" => "1234123412341234",
        "exp_month" => 11,
        "exp_year" => 2016,
        "cvc" => "123"
      ),
      "description" => "Charge for test@example.com"
    );

    try{
      $result = Start_Charge::create($data);
    } catch (Start_Error_Request $e) {
      $this->assertEquals('unprocessable_entity', $e->getErrorCode());
    }
  }

  function testInvalidYear()
  {
    $data = array(
      "amount" => 1050,
      "currency" => "usd",
      "card" => array(
        "number" => "4242424242424242",
        "exp_month" => 11,
        "exp_year" => 1990,
        "cvc" => "123"
      ),
      "description" => "Charge for test@example.com"
    );

    try{
      $result = Start_Charge::create($data);
    } catch (Start_Error_Request $e) {
      $this->assertEquals('unprocessable_entity', $e->getErrorCode());
    }
  }

  function testInvalidMonth()
  {
    $data = array(
      "amount" => 1050,
      "currency" => "usd",
      "card" => array(
        "number" => "4242424242424242",
        "exp_month" => 15,
        "exp_year" => 2015,
        "cvc" => "123"
      ),
      "description" => "Charge for test@example.com"
    );

    try{
      $result = Start_Charge::create($data);
    } catch (Start_Error_Request $e) {
      $this->assertEquals('unprocessable_entity', $e->getErrorCode());
    }
  }
}
