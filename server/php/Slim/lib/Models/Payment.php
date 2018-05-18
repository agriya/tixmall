<?php
/**
 * Payment
 *
 * PHP version 5
 *
 * @category   PHP
 * @package    Tixmall
 * @subpackage Core
 * @author     Agriya <info@agriya.com>
 * @copyright  2018 Agriya Infoway Private Ltd
 * @license    http://www.agriya.com/ Agriya Infoway Licence
 * @link       http://www.agriya.com
 */
namespace Models;
require_once __DIR__ . '/../../../config.inc.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once '../lib/database.php';
require_once '../lib/vendors/Inflector.php';
require_once '../lib/core.php';
require_once '../lib/constants.php';
require_once '../lib/vendors/OAuth2/Autoloader.php';
require_once '../lib/vendors/payfort/start/Start.php';
define('FPDF_FONTPATH','font/');
require_once '../lib/vendors/mpdf60/mpdf.php';
//Settings define
require_once '../lib/settings.php';
class Payment extends AppModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = '';
    //Payfort payment process
    public function payment_process($session_id, $data)
    {
        $carts = Cart::where('session_id', $session_id)->get()->toArray();
        $orderItem = new OrderItem;
        $payment = new Payment;
        $creditCards = new CreditCard;
        $paymentSettings = PaymentGatewaySetting::where('payment_gateway_id', \Constants\PaymentGateways::Payfort)->get()->toArray();
        foreach ($paymentSettings as $value) {
            if ($value['name'] == 'secret_key') {
                $payfort_secret_key = $value['test_mode_value'];
            } else {
                $payfort_open_key = $value['test_mode_value'];
            }
        }
        if (!empty($carts)) {
            $order = new Order;
            $order->user_id = '0';
            $order->event_id = '0';
            $order->order_status_id = '0';
            if (!empty($data['address'])) {
                $order->address = $data['address'];
            }
            if (!empty($data['country_iso_alpha2'])) {
                $order->country_id = findCountryIdFromIso2($data['country_iso_alpha2']);
            }
            if (!empty($data['state_name'])) {
                $order->state_id = findOrSaveAndGetStateId($data['state_name'], $order->country_id);
            }
            if (!empty($data['city_name'])) {
                $order->city_id = findOrSaveAndGetCityId($data['city_name'], $order->country_id, $order->state_id);
            }
            $order->coupon_id = '0';
            $order->payment_gateway_id = \Constants\PaymentGateways::Payfort;
            $order->quantity = '0';
            $order->price = '0.00';
            $order->donation_amount = '0.00';
            $order->total_amount = '0.00';
            $order->delivery_method_id = '0';
            $order->delivery_amount = '0.00';
            if (!empty($data['address1'])) {
                $order->address1 = $data['address1'];
            }
            if (!empty($data['zip_code'])) {
                $order->zip_code = $data['zip_code'];
            }
            $order->site_fee = '0.00';
            $order->gift_voucher_id = '0';
            $order->save();
            $price = 0.00;
            $count = 0;
            $results = array();
            foreach ($carts as $cart) {
                if (empty($cart['user_id'])) {
                    $user_id = $data['user_id'];
                } else {
                    $user_id = $cart['user_id'];
                }
                $price = $price + $cart['price'];
                $order->id = $order->id;
                $order->user_id = $user_id;
                $order->event_id = $cart['event_id'];
                $order->price = $cart['price'];
                if ($cart->is_donation == 1) {
                    $order->donation_amount = $cart['price'];
                }
                $delivery_method_id = $cart['delivery_method_id'];
                $order->update();
                $orderitem = new OrderItem;
                $orderitem->order_id = $order->id;
                $orderitem->user_id = $user_id;
                $orderitem->venue_zone_section_seat_id = $cart['venue_zone_section_seat_id'];
                $orderitem->price = $cart['price'];
                $orderitem->price_type_id = $cart['price_type_id'];
                $orderitem->event_schedule_id = $cart['event_schedule_id'];
                $orderitem->gift_voucher_certificate_id = $cart['gift_voucher_id'];
                $orderitem->event_id = $cart['event_id'];
                $orderitem->event_zone_id = $cart['event_zone_id'];
                $orderitem->venue_zone_section_id = $cart['venue_zone_section_id'];
                $orderitem->venue_zone_section_row_id = $cart['venue_zone_section_row_id'];
                $orderitem->save();                
                $count++;
            }
            if (!empty($delivery_method_id)) {
                $delivery_method = DeliveryMethod::where('id', $delivery_method_id)->first();
                $order->delivery_method_id = $delivery_method['id'];
                $order->delivery_amount = $delivery_method['price'];
            }           
            $order->id = $order->id;
            $orderId = $order->id;
            $payment->sendGiftVoucherMail($orderId);
            $order->quantity = $count;
            if (SITE_FEE != null) {
                $total_amount = $price + $delivery_method['price'] + SITE_FEE;
            } else {
                $total_amount = $price + $delivery_method['price'];
            }
             // Processing Fee process
                if (PROCESSING_FEE != null) {
                    $processing_fee = ($count * PROCESSING_FEE);
                    $total_amount  = $total_amount +  ($processing_fee);                     
                }
            
            // Handling fee process
            if (HANDLING_FEE != null) {
                $handling_fee = str_replace('%','',HANDLING_FEE);
                $total_amount = $total_amount + (($handling_fee / 100) * $total_amount);                
            }
            $flag = 0;
            if (!empty($data['gift_voucher_id'])) {
                $giftVoucher = GiftVoucher::where('id', $data['gift_voucher_id'])->where('is_used', 0)->first();
                if (!empty($giftVoucher['amount'])) {
                    if ($total_amount < $giftVoucher['amount']) {
                        $total_amount = $giftVoucher['amount'] - $total_amount;
                        $payment->UpdateGiftAvailableAmout($total_amount, $order->id, $giftVoucher['id']);
                        $flag = 1;
                    } elseif ($total_amount > $giftVoucher['amount']) {
                        $total_amount = $total_amount - $giftVoucher['amount'];
                        $payment->UpdateGiftAvailableAmout($total_amount = 0, $order->id, $giftVoucher['id']);
                        $flag = 0;
                    }
                }
            }
            $order->total_amount = $total_amount;
            $order->save();
            if ($flag == 0) {
                if (!empty($data)) {
                    \Start::setApiKey('test_sec_k_16dc38ad730d6ba806a92');
                    try {
                        if (!empty($data['customer_id'])) {
                            $charge = \Start_Charge::create(array(
                                "amount" => round($total_amount) ,
                                "currency" => 'aed',
                                "customer_id" => $data['customer_id'],
                                "email" => $data['email'],
                                "ip" => $_SERVER["REMOTE_ADDR"],
                                "description" => "Charge Description"
                            ));
                          $customerId = $charge['card']['customer_id'];
                        } else {
                            \Start::setApiKey('test_open_k_c3f462a1e8277114c1da');
                            $token = \Start_Token::create(array(
                                "number" => $data['credit_card_number'],
                                "exp_month" => $data['exp_month'],
                                "exp_year" => $data['exp_year'],
                                "cvc" => $data['cvc'],
                                "name" => $data['name'],
                            ));
                            \Start::setApiKey('test_sec_k_16dc38ad730d6ba806a92');
                            $customer = \Start_Customer::create(array(
                                "name" => $data['name'],
                                "email" => $data['email'],
                                "card" => $token['id'],
                                "description" => "Tixmall project payment"
                            ));
                            if (!empty($customer['id'])) {
                                $charge = \Start_Charge::create(array(
                                    "amount" => round($total_amount) ,
                                    "currency" => 'aed',
                                    "customer_id" => $customer['id'],
                                    "email" => $data['email'],
                                    "ip" => $_SERVER["REMOTE_ADDR"],
                                    "description" => "Charge Description"
                                ));
                                $result = array();
                                if (!empty($data['is_saved']) && $data['is_saved'] == 1) {
                                    $creditCards->user_id = $carts['0']['user_id'];
                                    $creditCards->customer_id = $charge['card']['customer_id'];
                                    $creditCards->card_type = $charge['card']['brand'];
                                    $creditCards->exp_year = $charge['card']['exp_year'];
                                    $creditCards->exp_month = $charge['card']['exp_month'];
                                    $creditCards->name = $charge['card']['name'];
                                    $creditCards->masked_cc = $charge['card']['last4'];
                                    $creditCards->card_id = $charge['card']['id'];
                                    $creditCards->save();
                                    $customerId = $creditCards->customer_id;
                                }
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
                            $CreditCard = CreditCard::where('user_id', $carts['user_id'])->get();
                            if (!empty($CreditCard)) {
                                $CreditCard = CreditCard::where('user_id', $carts['0']['user_id'])->get();
                                $result['data'] = $CreditCard->toArray();
                                $CreditCardId = CreditCard::where('user_id',  $carts['0']['user_id'])->where('customer_id',$customerId)->first()->toArray();
                                $order = $payment->OrderUpdation($order->id,$payment,$CreditCardId['id']);
                                $payment_response = array(
                                    'data' => $order,
                                    'error' => array(
                                        'code' => '0',
                                        'message' => 'You payment has been if completed successfully.'
                                    )
                                );
                                $payment->OrderEmailSend($order->id);
                                Cart::where('session_id', $session_id)->delete();
                            }
                        }
                    }
                    catch(Start_Error $e) {
                        $error_code = $e->getErrorCode();
                        $error_message = $e->getMessage();
                        $order->order_status_id = \Constants\OrderStatus::PaymentFailed;
                        $order->id = $orderId;
                        $order->update();
                        $payment_response = array(
                            'data' => $order,
                            'error' => array(
                                'code' => $error_code,
                                'message' => 'You payment has been failed. Please try again with valid credentials'
                            )
                        );
                        $payment->OrderEmailSend($order->id);
                    }
                } else {
                    $payment_response = array(
                        'data' => null,
                        'error' => array(
                            'code' => 1,
                            'message' => 'You order has not been completed. Please update all data'
                        )
                    );
                }
            } else {
                $order = $payment->OrderUpdation($order->id, $payment,$CreditCard->id);
                $payment_response = array(
                    'data' => $order,
                    'error' => array(
                        'code' => '0',
                        'message' => 'You payment has been completed successfully.'
                    )
                );
                $payment->OrderEmailSend($order->id);
                Cart::where('session_id', $session_id)->delete();
            }
        } else {
            //Payment process withput card details
            $payment_response = array(
                'data' => null,
                'error' => array(
                    'code' => 1,
                    'message' => 'Your cart is empty. Please add items in your basket'
                )
            );
        }
        return $payment_response;
    }
    //Sudopay transaction log
    public function _savePaidLog($foreign_id, $paymentDetails, $class = '')
    {
        $PayfortTransactionLog = new PayfortTransactionLog;
        $PayfortTransactionLog->foreign_id = $foreign_id;
        $PayfortTransactionLog->class = $class;
        $PayfortTransactionLog->amount = !empty($paymentDetails['amount']) ? $paymentDetails['amount'] : '';
        $PayfortTransactionLog->token_id = !empty($paymentDetails['token_id']) ? $paymentDetails['token_id'] : '';
        $PayfortTransactionLog->currency = !empty($paymentDetails['currency']) ? $paymentDetails['currency'] : '';
        $PayfortTransactionLog->auth_code = !empty($paymentDetails['auth_code']) ? $paymentDetails['auth_code'] : '';
        $PayfortTransactionLog->status = !empty($paymentDetails['status']) ? $paymentDetails['status'] : '';
        $PayfortTransactionLog->account_id = !empty($paymentDetails['account_id']) ? $paymentDetails['account_id'] : '';
        $PayfortTransactionLog->charge_id = !empty($paymentDetails['charge_id']) ? $paymentDetails['charge_id'] : '';
        $PayfortTransactionLog->captured_amount = !empty($paymentDetails['captured_amount']) ? $paymentDetails['captured_amount'] : '';
        $PayfortTransactionLog->email = !empty($paymentDetails['email']) ? $paymentDetails['email'] : '';
        $PayfortTransactionLog->description = !empty($paymentDetails['description']) ? $paymentDetails['description'] : '';
        $PayfortTransactionLog->card_id = !empty($paymentDetails['card_id']) ? $paymentDetails['card_id'] : '';
        $PayfortTransactionLog->save();
    }
    //Add Transaction
    public function addTransactions($order, $type, $status)
    {
        if ($type == 'Order') {
            //Transaction
            $order_users = array(
                'credit' => 1,
                'debit' => $order['user_id']
            );
            foreach ($order_users as $key => $value) {
                $transaction = new Transaction;
                if ($key == 'credit') {
                    //restaurant transaction
                    $transaction->user_id = $value;
                    $transaction->transaction_type = 'credit';
                } elseif ($key == 'debit') {
                    //restaurant transaction
                    $transaction->user_id = $value;
                    $transaction->transaction_type = 'debit';
                }
                $transaction->amount = $order['total_amount'];
                $transaction->currency = CURRENCY_CODE;
                $transaction->transaction_key = \Constants\Transactions::Order;
                $transaction->reference_content_id = $order['id'];
                $transaction->reference_content_table = \Constants\Transactions::Order;
                $transaction->status = $status;
                $transaction->save();
            }
        }
        return true;
    }
    public function sendGiftVoucherMail($orderId)
    {
        $order = OrderItem::where('order_id', $orderId)->select('gift_voucher_certificate_id')->get()->toArray();
        foreach ($order as $value) {
            if ($value['gift_voucher_certificate_id'] != 0) {
                $giftVoucher = GiftVoucher::where('id', $value['gift_voucher_certificate_id'])->first()->toArray();
                if (!empty($giftVoucher)) {
                    $emailFindReplace = array(
                        '##TO_NAME##' => $giftVoucher['to_name'],
                        '##FROM_NAME##' => $giftVoucher['from_name'],
                        '##CODE##' => $giftVoucher['code']
                    );
                    sendMail('giftvoucher', $emailFindReplace, $giftVoucher['to_email']);
                }
            }
        }
    }
    public function UpdateGiftAvailableAmout($total_amount, $orderId, $giftVoucherID)
    {
        if (!empty($giftVoucherID)) {
            GiftVoucher::where('id', $giftVoucherID)->update(['is_used' => '1', 'avaliable_amount' => $total_amount]);
            Order::where('id', $orderId)->update(['gift_voucher_id' => $giftVoucherID]);
        }
    }
    public function OrderUpdation($orderId, $payment,$CreditCardId)
    {
        Order::where('id', $orderId)->update(['credit_card_id' => $CreditCardId]);
        $order = Order::where('id', $orderId)->with('events','credit_card','city','state','country')->first();
        $payment->addTransactions($order, 'Order', 'Completed');
        $order->order_status_id = 2;
        $order->id = $orderId;
        $order->update();
        return $order;
    }
    public function OrderEmailSend($OrderId)
    {
        $order = Order::where('id', $OrderId)->select('order_status_id', 'total_amount', 'id','price','quantity')->first()->toArray();
        $orderItem = OrderItem::with('users', 'events', 'venue_zone_section_seats', 'event_schedule','price_type')->where('order_id', $order['id'])->get()->toArray();
        $venueService = VenueService::all()->toArray();
        foreach ($orderItem as $orderItemValue) {
            $user_email = $orderItemValue['users']['email'];
            $username = $orderItemValue['users']['username'];
            $user_id = $orderItemValue['users']['id'];
            $eventname = $orderItemValue['events']['name'];
            $event_id = $orderItemValue['events']['id'];
            $eventdate = $orderItemValue['event_schedule']['start_date'];
            $eventvenueopen = $orderItemValue['event_schedule']['venue_opens_at'];
            $seat_number = $orderItemValue['venue_zone_section_seats']['seat_number'];
            $venue_section_row = $orderItemValue['venue_zone_section_seats']['venue_zone_section_row']['name'];
            $venue_seaction = $orderItemValue['venue_zone_section_seats']['venue_zone_section']['name'];
            $venue_zone = $orderItemValue['venue_zone_section_seats']['venue_zone']['name'];
            $venue_zone_id = $orderItemValue['venue_zone_section_seats']['venue_zone']['id'];
            $venue = $orderItemValue['venue_zone_section_seats']['venue']['name'];
            $venue_id = $orderItemValue['venue_zone_section_seats']['venue']['id'];
            $venue_zone_count = $orderItemValue['venue_zone_section_seats']['venue_zone_section']['seat_count'];
            $pricetype = $orderItemValue['price_type']['name'];
            if (!empty($orderItemValue['events'])) {
                $event_zones = EventZone::where('venue_id', $venue_id)->where('event_id', $event_id)->where('venue_zone_id', $venue_zone_id)->first();
                if (!empty($event_zones)) {
                    $eventzones = $event_zones->toArray();
                    $available_count = $eventzones['available_count'];
                    $seat_count = ($available_count - 1);
                    $event_zones->available_count = $seat_count;
                    $event_zones->save();
                }
            }
            if (!empty(TICKET_PDF_HTML)) {              
                $ticketFindReplace = array(
                    '##VENU_NAME##' => $venue,
                    '##EVENT_NAME##' => $eventname,
                    '##EVENT_START_DATE##' => date('Y-m-d', strtotime($eventdate)),
                    '##EVENT_START_TIME##' => date("H:i:s", strtotime($eventdate)),
                    '##VENU_OPEN_AT##'  => $eventvenueopen,
                    '##SECTION##' =>  $venue_seaction,
                    '##ROW##' => $venue_section_row,
                    '##SEAT##' => $seat_number,
                    '##TOTAL_AMOUNT##' => $order['total_amount'],
                    '##PRICE_TYPE##' =>  $pricetype,
                    '##USER_ID##' =>  $username,
                    '##ORDER_ID##' => $order['id'],
                    '##VENU_SERVICE##'=>  $venueService,
                    '##SITE_URL##'=>  DOMAIN_URL
                );
                if (!file_exists(APP_PATH . "/media/Booking/".$order['id'])) {
                    mkdir(APP_PATH . "/media/Booking/".$order['id'], 0777, true);
                }
                $pdf = new \mPDF('c','A4','','',12,12,10,10,10,10);
                $pdf->WriteHTML(strtr(TICKET_PDF_HTML, $ticketFindReplace));
                $pdf->Output(APP_PATH . "/media/Booking/".$order['id']."/booking.pdf", 'F');
                }
            }
        if (!empty($orderItem)) {
            $handling_fee = str_replace('%','',HANDLING_FEE);
            $emailFindReplace = array(
                '##SITE_URL##' => DOMAIN_URL,
                '##USERNAME##' => $username,
                '##ORDERID##' => $OrderId,
                '##USERID##' => $user_id,
                '##EVENT_NAME##' => $eventname,
                '##START_DATE##'=> date_format($eventdate,'l jS F Y g:ia'),
                '##SECTION##' => $venue_seaction,
                '##ROW##' => $venue_section_row,
                '##SEAT##' => $seat_number,
                '##QUANTITY##' => $order['quantity'],
                '##PRICE##' => $order['price'],
                '##HANDING_FEE##'=> (($handling_fee / 100) *  $order['total_amount']),
                '##PROCESSING_FEE##' => ($order['quantity'] * PROCESSING_FEE),
                '##TOTAL_AMOUNT##'=> $order['total_amount']
            );
            sendMail('eventbooking', $emailFindReplace, $user_email,$order['id']);
        }
    }
}
