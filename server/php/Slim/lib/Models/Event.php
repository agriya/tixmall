<?php
/**
 * Event
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
 * Event
*/
class Event extends AppModel
{
    protected $table = 'events';
    public $rules = array(
        'venueId' => 'sometimes|required',
        'categoryId' => 'sometimes|required',
        'seriesId' => 'sometimes|required',
        'name' => 'sometimes|required',
        'slug' => 'sometimes|required',
        'startDate' => 'sometimes|required',
        'endDate' => 'sometimes|required',
        'description' => 'sometimes|required',
        'trailerVideoUrl' => 'sometimes|required',
        'isActive' => 'sometimes|required',
        'isFreeEvent' => 'sometimes|required',
    );
    public function venue()
    {
        return $this->belongsTo('Models\Venue', 'venue_id', 'id');
    }
    public function category()
    {
        return $this->belongsTo('Models\Category', 'category_id', 'id');
    }
    public function series()
    {
        return $this->belongsTo('Models\Series', 'series_id', 'id');
    }
    public function event_schedule()
    {
        return $this->hasMany('Models\EventSchedule', 'event_id', 'id');
    }
    public function attachments()
    {
        return $this->hasOne('Models\Attachment', 'foreign_id', 'id')->where('class', 'Event');
    }
    public function attachment_floor_plan()
    {
        return $this->hasOne('Models\Attachment', 'foreign_id', 'id')->where('class', 'EventFloorPlan');
    }
    public function attachment_ticket_price()
    {
        return $this->hasOne('Models\Attachment', 'foreign_id', 'id')->where('class', 'TicketPrices');
    }
    public function video()
    {
        return $this->hasOne('Models\Attachment', 'foreign_id', 'id')->where('class', 'EventVideo');
    }    
}
