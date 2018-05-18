<?php
/**
 * GiftVoucher
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
class GiftVoucher extends AppModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'gift_vouchers';
    //Rules
    public $rules = array(
        'amount' => 'sometimes|required',
        'to_name' => 'sometimes|required',
        'to_email' => 'sometimes|required',
        'message' => 'sometimes|required',
        'user_id' => 'sometimes|required',
    );
}
