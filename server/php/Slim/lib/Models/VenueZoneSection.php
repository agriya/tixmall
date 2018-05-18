<?php
/**
 * VenueZoneSection
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
 * VenueZoneSection
*/
class VenueZoneSection extends AppModel
{
    protected $table = 'venue_zone_sections';
    public $rules = array(
        'name' => 'sometimes|required',
        'venue_id' => 'sometimes|required',
        'venue_zone_id' => 'sometimes|required',
        'seat_count' => 'sometimes|required',
    );
}
