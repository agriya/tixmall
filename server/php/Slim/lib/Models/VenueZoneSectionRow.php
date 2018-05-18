<?php
/**
 * VenueZoneSectionRow
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
class VenueZoneSectionRow extends AppModel
{
    protected $table = 'venue_zone_section_rows';
    public $rules = array(
        'name' => 'sometimes|required',
        'seat_count' => 'sometimes|required',
    );
}
