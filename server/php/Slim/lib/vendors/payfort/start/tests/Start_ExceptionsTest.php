<?php

class Start_ExceptionsTest extends \PHPUnit_Framework_TestCase
{

  /**
  * @expectedException Start_Error_Authentication
  */
  function testListAuthenticationException()
  {
    Start::setApiKey('invalid_token');
    Start_Charge::all();
  }

  /**
  * @expectedException Start_Error_Authentication
  */
  function testAuthenticationException()
  {
    Start::setApiKey('invalid_token');

    $data = array(
      "amount" => 1050,
      "currency" => "usd",
      "card" => array(
        "number" => "4242424242424242",
        "exp_month" => 11,
        "exp_year" => 2016,
        "cvc" => "123"
      ),
      "description" => "Charge for test@example.com"
    );

    Start_Charge::create($data);
  }

  /**
  * @expectedException Start_Error_Request
  */
  function testCardException()
  {
    Start::setApiKey('test_sec_k_2b99b969196bece8fa7fd');

    $data = array(
      "amount" => 1050,
      "currency" => "usd",
      "card" => array(
        "number" => "4141414141414141",
        "exp_month" => 11,
        "exp_year" => 2016,
        "cvc" => "123"
      ),
      "description" => "Charge for test@example.com"
    );

    Start_Charge::create($data);
  }

  // This test should raise an exception but doesn't. Raised issue:
  //
  // /**
  // * @expectedException Start_Error_Request
  // */
  // function testParametersException()
  // {
  //   Start::setApiKey('test_sec_k_2b99b969196bece8fa7fd');

  //   $data = array(
  //     "amount" => -1.30,
  //     "currency" => "usd",
  //     "card" => array(
  //       "number" => "4242424242424242",
  //       "exp_month" => 12,
  //       "exp_year" => 2016,
  //       "cvc" => "123"
  //     ),
  //     "description" => "Charge for test@example.com"
  //   );

  //   Start_Charge::create($data);
  // }

  // We need to setup the card to raise a Processing error
  // /*
  //  * @expectedException Start_Error_Processing
  //  */
  // function testApiException()
  // {
  //   Start::setApiKey('test_sec_k_2b99b969196bece8fa7fd');

  //   $data = array(
  //     "amount" => 1050,
  //     "currency" => "usd",
  //     "card" => array(
  //       "number" => "3566002020360505",
  //       "exp_month" => 12,
  //       "exp_year" => 2016,
  //       "cvc" => "123"
  //     ),
  //     "description" => "Charge for test@example.com"
  //   );

  //   Start_Charge::create($data);
  // }
}
