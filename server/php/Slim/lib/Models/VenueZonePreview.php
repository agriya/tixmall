<?php
/**
 * VenueZonePreview
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
 * VenueZonePreview
*/
class VenueZonePreview extends AppModel
{
    protected $table = 'venue_zone_previews';
    public $rules = array(
        'venue_zone_id' => 'sometimes|required|integer',
        'venue_section_id' => 'sometimes|required|integer',
    );
    public function attachments()
    {
        return $this->hasMany('Models\Attachment', 'foreign_id', 'id')->where('class', 'VenueZonePreview');;
    }
    public function venue_zone_section_seats()
    {
        return $this->belongsTo('Models\VenueZoneSectionSeat', 'venue_section_row_seat_id', 'id')->with('venue_zone_section_row', 'venue_zone_section', 'venue_zone', 'venue');
    }
}
