<?php
require_once __DIR__ . '/../../config.inc.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../lib/database.php';
require_once '../lib/core.php';
require_once '../lib/constants.php';
$payfort_ipn_data['post_variable'] = serialize($_POST);
$payfort_ipn_data['ip_id'] = (!empty(saveIp())) ? saveIp() : null;
$carts = Models\Cart::where('session_id', $_GET['session_id'])->get()->toArray();
$order = new Models\Order;
$orderItem = new Models\OrderItem;
$payment = new Models\Payment;
$creditCard = new Models\CreditCard;
$paymentSettings = Models\PaymentGatewaySetting::where('payment_gateway_id', \Constants\PaymentGateways::Payfort)->get()->toArray();
foreach ($paymentSettings as $value) {
    $payfort[$value->name] = $value->test_mode_value;
}
if (!empty($carts)) {
    $order->order_status_id = 0;
    $order->payment_gateway_id = \Constants\PaymentGateways::Payfort;
    $order->user_id = 0;
    $order->event_id = 0;
    $order->quantity = 1;
    $order->price = 0;
    $order->total_amount = 0;
    $order->save();
    $price = 0.00;
    $count = 0;
    $results = array();
    foreach ($carts as $cart) {
        $price = $price + $cart->price;
        $order->id = $order->id;
        $order->user_id = $cart->user_id;
        $order->event_id = $cart->event_id;
        $order->price = $cart->price;
        $order->update();
        $orderitem = new Models\OrderItem;
        $orderitem->order_id = $order->id;
        $orderitem->user_id = $cart->user_id;
        $orderitem->venue_zone_section_seat_id = $cart->venue_zone_section_seat_id;
        $orderitem->price = $cart->price;
        $orderitem->save();
        $count++;
    }
    $order->id = $order->id;
    $orderId = $order->id;
    $order->quantity = $count;
    $order->total_amount = $price;
    $order->save();
    //Payment success
    if (!empty($_POST)) {
        $token = $_POST["startToken"];
        $email = $_POST["startEmail"];
        Start::setApiKey($payfort['secret_key']);
        // Start::setApiKey('test_sec_k_16dc38ad730d6ba806a92');
        try {
            if (!empty($_POST['customer_id'])) {
                $charge = Start_Charge::create(array(
                    "amount" => 1,
                    "currency" => 'aed',
                    "customer_id" => $_POST['customer_id'],
                    "email" => $email,
                    "ip" => $_SERVER["REMOTE_ADDR"],
                    "description" => "Charge Description"
                ));
            } else {
                $customer = Start_Customer::create(array(
                    "name" => "Abdullah Ahmed",
                    "email" => "abdullah@msn.com",
                    "card" => $token,
                    "description" => "Tixmall project payment"
                ));
                if (!empty($customer['id'])) {
                    $charge = Start_Charge::create(array(
                        "amount" => 1,
                        "currency" => 'aed',
                        "customer_id" => $customer['id'],
                        "email" => $email,
                        "ip" => $_SERVER["REMOTE_ADDR"],
                        "description" => "Charge Description"
                    ));
                    $result = array();
                    $creditCard->user_id = $carts['0']['user_id'];
                    $creditCard->customer_id = $charge['card']['customer_id'];
                    $creditCard->card_type = $charge['card']['brand'];
                    $creditCard->exp_year = $charge['card']['exp_year'];
                    $creditCard->exp_month = $charge['card']['exp_month'];
                    $creditCard->name = 'cardName';
                    $creditCard->masked_cc = $charge['card']['last4'];
                    $creditCard->card_id = $charge['card']['id'];
                    $creditCard->save();
                    $result = $creditCard->toArray();
                    return renderWithJson($result);
                }
            }
            //Payment success
            if ($charge['state'] == 'captured') {
                $post['id'] = $charge['id'];
                $post['captured_amount'] = $charge['captured_amount'];
                $post['amount'] = $charge['amount'];
                $post['account_id'] = $charge['account_id'];
                $post['auth_code'] = $charge['auth_code'];
                $post['token_id'] = $charge['token_id'];
                $post['status'] = $charge['state'];
                $post['email'] = $charge['email'];
                $post['currency'] = $charge['currency'];
                $post['description'] = $charge['description'];
                $post['card_id'] = $charge['card']['id'];
                // Add transaction log
                $payment->_savePaidLog(1, $post, 'Order');
                $order = Models\Order::where('id', $orderId)->with('events')->first();
                //Add transactions to user and restaurant
                $payment->addTransactions($order, 'Order', 'Completed');
                //Update Order status
                $order->order_status_id = Constants\OrderStatus::Captured;
                $order->id = $orderId;
                $order->update();
                $creditCard = Models\CreditCard::where('user_id', $carts['user_id'])->get();
                if (!empty($creditCard)) {
                    $creditCard = Models\CreditCard::where('user_id', $carts['0']['user_id'])->get();
                    $result['data'] = $creditCard->toArray();
                    return renderWithJson($result);
                }
            }
        }
        catch(Start_Error $e) {
            $error_code = $e->getErrorCode();
            $error_message = $e->getMessage();
            echo $error_code;
            echo $error_message;
            $order->order_status_id = \Constants\OrderStatus::PaymentFailed;
            $order->id = $orderId;
            $order->update();
        }
    } else {
        return renderWithJson($result, 'POST no records', 1);
    }
} else {
    return renderWithJson($result, 'carts no records', 1);
}
