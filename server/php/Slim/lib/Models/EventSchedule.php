<?php
/**
 * State
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
class EventSchedule extends AppModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'event_schedules';
    //Rules
    public $rules = array(
        'event_id' => 'sometimes|required|integer',
    );
    public function event_schedule_zone()
    {
        return $this->hasMany('Models\EventScheduleZone', 'event_schedule_id', 'id');
    }
}
