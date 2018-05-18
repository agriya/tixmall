<?php

class Start_ChargeTest extends \PHPUnit_Framework_TestCase
{
    protected $cardForSuccess = array(
        "number" => "4242424242424242",
        "exp_month" => 11,
        "exp_year" => 2020,
        "cvc" => "123"
    );

    protected $cardForFailure = array(
        "number" => "4000000000000002",
        "exp_month" => 11,
        "exp_year" => 2020,
        "cvc" => "123"
    );

    function setUp()
    {
        Start::$fallback = false;
        Start::setApiKey('test_sec_k_2b99b969196bece8fa7fd');

        if (getenv("CURL") == "1") {
            Start::$useCurl = true;
        }
    }

    function testList()
    {
        $result = Start_Charge::all();
        //No assertion. If there is an error, an exception is thrown. Otherwise it was ok.
    }

    function testCreateSuccess()
    {
        $data = array(
            "amount" => 1050,
            "currency" => "usd",
            "email" => "ahmed@example.com",
            "card" => $this->cardForSuccess,
            "description" => "Charge for test@example.com"
        );

        $result = Start_Charge::create($data);

        $this->assertEquals($result["state"], "captured");
    }

    function testInvalidData()
    {
        $data = array(
            "amount" => 1050,
            "currency" => "usd",
            "email" => "ahmed@example.com",
        );

        try {
            $result = Start_Charge::create($data);
        } catch (Start_Error_Request $e) {
            $this->assertSame('unprocessable_entity', $e->getErrorCode());
            $this->assertSame('Request params are invalid.', $e->getMessage());
        }
    }

    function testCreateFailure()
    {
        $data = array(
            "amount" => 1050,
            "currency" => "usd",
            "email" => "ahmed@example.com",
            "card" => $this->cardForFailure,
            "description" => "Charge for test@example.com"
        );
        try {
            $result = Start_Charge::create($data);
        } catch (Start_Error_Banking $e) {
            $this->assertSame('card_declined', $e->getErrorCode());
            $this->assertSame('Charge was declined.', $e->getMessage());
        }
    }

    function testMetadata()
    {
        $data = array(
            "amount" => 1050,
            "currency" => "usd",
            "email" => "ahmed@example.com",
            "card" => $this->cardForSuccess,
            "description" => "Charge for test@example.com",
            "metadata" => array(
                "reference_id" => "1234567890",
                "tag" => "new"
            )
        );

        $result = Start_Charge::create($data);

        $this->assertEquals($result["metadata"], array(
            "reference_id" => "1234567890",
            "tag" => "new"
        ));
    }
}
