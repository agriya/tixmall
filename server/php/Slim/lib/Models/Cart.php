<?php
/**
 * Cart
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
class Cart extends AppModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'carts';
    //Rules
    public $rules = array(
        'price' => 'sometimes|required'
    );
    public function users()
    {
        return $this->belongsTo('User', 'user_id', 'id');
    }
    public function events()
    {
        return $this->belongsTo('Models\Event', 'event_id', 'id')->with('event_schedule');
    }
    public function venue_zone_section_seats()
    {
        return $this->belongsTo('Models\VenueZoneSectionSeat', 'venue_zone_section_seat_id', 'id')->with('venue_zone_section_row', 'venue_zone_section', 'venue_zone', 'venue');
    }
    public function gift_voucher()
    {
        return $this->belongsTo('Models\GiftVoucher', 'gift_voucher_id', 'id');
    }
    public function price_type()
    {
        return $this->belongsTo('Models\PriceType', 'price_type_id', 'id');
    }
    public function event_schedule()
    {
        return $this->belongsTo('Models\EventSchedule', 'event_schedule_id', 'id');
    }
}
