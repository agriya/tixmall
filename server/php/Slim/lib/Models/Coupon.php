<?php
/**
 * Coupon
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
 * Coupon
*/
class Coupon extends AppModel
{
    protected $table = 'coupons';
    public $rules = array(
        'updatedAt' => 'sometimes|required',
        'name' => 'sometimes|required',
        'isFlatDiscount' => 'sometimes|required',
        'discount' => 'sometimes|required',
        'code' => 'sometimes|required',
    );
}
