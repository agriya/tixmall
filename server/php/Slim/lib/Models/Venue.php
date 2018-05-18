<?php
/**
 * Venue
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
 * Venue
*/
class Venue extends AppModel
{
    protected $table = 'venues';
    public $rules = array(
        'name' => 'sometimes|required',
        'slug' => 'sometimes|required',
        'address1' => 'sometimes|required',
        'address2' => 'sometimes|required',
        'is_active' => 'sometimes|required',
    );
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
    public function attachments()
    {
        return $this->hasMany('Models\Attachment', 'foreign_id', 'id')->whereIn('class', array(
            'Venue',
            'VenueSlider'
        ));
    }
    public function venue_zone()
    {
        return $this->hasMany('Models\VenueZone', 'venue_id', 'id')->with('attachments');
    }
    public function event_zone()
    {
        return $this->hasManyThrough('Models\EventZone', 'Models\VenueZone')->with('event_zone_prices', 'venue_zone');
    }
    public function event_venue_zone()
    {
        return $this->hasMany('Models\EventZone', 'venue_id', 'id')->with('event_zone_prices');
    }
    public function venue_service()
    {
        return $this->belongsTo('Models\VenueService', 'venue_service_id', 'id');
    }
}
