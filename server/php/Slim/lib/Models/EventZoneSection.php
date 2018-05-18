<?php
/**
 * EventZoneSection
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
 * EventZoneSection
*/
class EventZoneSection extends AppModel
{
    protected $table = 'event_zone_sections';
    public $rules = array(
        'event_zone_id' => 'sometimes|required',
        'venue_zone_section_id' => 'sometimes|required',
    );
}
