<?php
/**
 * VenueService
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
 * VenueService
*/
class VenueService extends AppModel
{
    protected $table = 'venue_services';
    public $rules = array(
        'service_name' => 'sometimes|required',
    );
}
