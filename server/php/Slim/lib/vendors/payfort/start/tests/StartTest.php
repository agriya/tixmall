<?php


class StartTest extends \PHPUnit_Framework_TestCase
{
  function testApiKey()
  {
    $testKey = 'test_sec_k_2b99b969196bece8fa7fd';
    Start::setApiKey($testKey);
    $this->assertEquals($testKey, Start::getApiKey());
  }

  function testSimpleMethods()
  {
    $this->assertEquals('https://api.start.payfort.com/', Start::getBaseURL());
  }

  function testEndPoints()
  {
    $this->assertEquals('https://api.start.payfort.com/charges/', Start::getEndPoint('charge'));
    $this->assertEquals('https://api.start.payfort.com/charges/', Start::getEndPoint('charge_list'));
  }
}
