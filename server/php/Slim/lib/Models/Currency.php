<?php
/**
 * Contact
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
class Currency extends AppModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'currencies';
    //Rules
    public $rules = array(
        'name' => 'sometimes|required',
        'price' => 'sometimes|required'
    );
}
