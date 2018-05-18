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
class EventScheduleZone extends AppModel
{
    protected $table = 'event_schedule_zones';
    public $rules = array(
        'event_zone_id' => 'sometimes|required',
        'event_id' => 'sometimes|required',
    );
}
