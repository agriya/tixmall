<?php
/**
 * Order
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
class Order extends AppModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'orders';
    //Rules
    public $rules = array(
        'user_id' => 'sometimes|required|integer',
        'event_id' => 'sometimes|required|integer',
        'total_amount' => 'sometimes|required',
    );
    //Restaurant relation
    public function events()
    {
        return $this->belongsTo('Models\Event', 'event_id', 'id')->select('id', 'name');
    }
    public function delivery_methods()
    {
        return $this->belongsTo('Models\DeliveryMethod', 'delivery_method_id', 'id');
    }
    //Order Items relation
    public function order_items()
    {
        return $this->hasMany('Models\OrderItem', 'order_id', 'id')->with('users', 'events', 'venue_zone_section_seats', 'price_type', 'gift_voucher');
    }
    //creditcards relation
     public function credit_card()
    {
        return $this->belongsTo('Models\CreditCard', 'credit_card_id', 'id');
    }
    public function city()
    {
        return $this->belongsTo('Models\City', 'city_id', 'id')->select('id','name');
    }
    public function state()
    {
        return $this->belongsTo('Models\State', 'state_id', 'id')->select('id','name');
    }
    public function country()
    {
        return $this->belongsTo('Models\Country', 'country_id', 'id')->select('id','iso_alpha2');
    }
}
