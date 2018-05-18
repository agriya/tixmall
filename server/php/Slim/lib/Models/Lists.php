<?php
/**
 * List
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
 * List
*/
class Lists extends AppModel
{
    protected $table = 'lists';
    public $rules = array(
        'name' => 'sometimes|required'
    );
     public function guest_list()
    {
        return $this->hasMany('Models\GuestsList', 'list_id', 'id')->with('guest');
    }
}
