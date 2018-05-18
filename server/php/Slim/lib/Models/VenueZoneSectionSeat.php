<?php
/**
 * VenueZoneSectionSeat
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
 * VenueZoneSectionSeat
*/
class VenueZoneSectionSeat extends AppModel
{
    protected $table = 'venue_zone_section_seats';
    public $rules = array(
        'venueId' => 'sometimes|required',
        'venueZoneId' => 'sometimes|required',
        'venueZoneSectionId' => 'sometimes|required',
        'venueZoneSectionRowId' => 'sometimes|required',
        'seatNumber' => 'sometimes|required',
        'seatInformation' => 'sometimes|required',
        'xPosition' => 'sometimes|required',
        'yPosition' => 'sometimes|required',
        'isSeat' => 'sometimes|required',
        'isBox' => 'sometimes|required',
        'boxNumber' => 'sometimes|required',
    );
    public function venue()
    {
        return $this->belongsTo('Models\Venue', 'venue_id', 'id');
    }
    public function venue_zone()
    {
        return $this->belongsTo('Models\VenueZone', 'venue_zone_id', 'id');
    }
    public function venue_zone_section()
    {
        return $this->belongsTo('Models\VenueZoneSection', 'venue_zone_section_id', 'id');
    }
    public function venue_zone_section_row()
    {
        return $this->belongsTo('Models\VenueZoneSectionRow', 'venue_zone_section_row_id', 'id');
    }
}
