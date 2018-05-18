<?php
/**
 * MoneyTransferAccount
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
class MoneyTransferAccount extends AppModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'money_transfer_accounts';
    //Rules
    public $rules = array(
        'user_id' => 'sometimes|required|integer',
        'name' => 'sometimes|required',
        'account_no' => 'sometimes|required',
        'routine_no' => 'sometimes|required',
        'swift_code' => 'sometimes|required'
    );
}
