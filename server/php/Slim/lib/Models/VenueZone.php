<?php
/**
 * VenueZone
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
 * VenueZone
*/
class VenueZone extends AppModel
{
    protected $table = 'venue_zones';
    public $rules = array(
        'name' => 'sometimes|required',
        'seatCount' => 'sometimes|required',
        'image' => 'sometimes|required',
        'svg_image' => 'sometimes|required',
    );
    public function attachments()
    {
        return $this->hasMany('Models\Attachment', 'foreign_id', 'id')->where('class', 'VenueZone');
    }
    public function venue_zone_sections()
    {
        return $this->hasMany('Models\VenueZoneSection', 'venue_zone_id');
    }
    public function venue_zone_section_seats()
    {
        return $this->hasMany('Models\VenueZoneSectionSeat', 'venue_zone_id');
    }
    public function venue_zone_section_row()
    {
        return $this->hasManyThrough('Models\VenueZoneSectionRow', 'Models\VenueZoneSection', 'venue_zone_id', 'venue_zone_section_id');
    }
    public function event_zone()
    {
        return $this->belongsTo('Models\EventZone', 'venue_zone_id');
    }
    public function venue()
    {
        return $this->belongsTo('Models\Venue', 'venue_id', 'id');
    }
    public function delete()
    {
        // delete all related venue zone sections and seats
        $this->venue_zone_sections()->delete();
        $this->venue_zone_section_seats()->delete();
        // delete the venue_zones
        return parent::delete();
    }
}
