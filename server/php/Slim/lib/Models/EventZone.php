<?php
/**
 * EventZone
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
/*
 * EventZone
*/
class EventZone extends AppModel
{
    protected $table = 'event_zones';
    public $rules = array(
        'name' => 'sometimes|required',
        'eventId' => 'sometimes|required',
        'venueId' => 'sometimes|required',
        'venueZoneId' => 'sometimes|required'
    );
    public function event()
    {
        return $this->belongsTo('Models\Event', 'event_id', 'id');
    }
    public function venue()
    {
        return $this->belongsTo('Models\Venue', 'venue_id', 'id');
    }
    public function venue_zone()
    {
        return $this->belongsTo('Models\VenueZone', 'venue_zone_id', 'id');
    }
    public function event_zone_sections()
    {
        return $this->hasMany('Models\EventZoneSection', 'event_zone_id');
    }
    public function event_zone_section_rows()
    {
        return $this->hasMany('Models\EventZoneSectionRow', 'event_zone_id');
    }
    public function event_zone_prices()
    {
        return $this->hasMany('Models\EventZonePrice', 'event_zone_id')->with('price_type');
    }
    public function delete()
    {
        // delete all related venue zone sections and seats
        $this->event_zone_sections()->delete();
        $this->event_zone_section_rows()->delete();
        $this->event_zone_prices()->delete();
        // delete the venue_zones
        return parent::delete();
    }
}
