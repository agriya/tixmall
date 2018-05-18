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
class EventZonePrice extends AppModel
{
    protected $table = 'event_zone_prices';
    public $rules = array(
        'event_zone_id' => 'sometimes|required',
        'price_type_id' => 'sometimes|required',
        'price' => 'sometimes|required',
    );
    public function price_type()
    {
        return $this->belongsTo('Models\PriceType', 'price_type_id', 'id');
    }
}
