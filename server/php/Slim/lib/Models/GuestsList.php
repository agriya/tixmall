<?php
/**
 * GuestsList
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
 * GuestsList
*/
class GuestsList extends AppModel
{
    protected $table = 'guests_lists';
    public $rules = array();

    public function guest()
    {
        return $this->belongsTo('Models\Guest', 'guest_id', 'id');
    }
    public function lists()
    {
        return $this->belongsTo('Models\Lists', 'list_id', 'id');
    }
}
