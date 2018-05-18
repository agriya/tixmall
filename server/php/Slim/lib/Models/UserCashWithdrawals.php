<?php
/**
 * UserCashWithdrawals
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
class UserCashWithdrawals extends AppModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_cash_withdrawals';
    //Rules
    public $rules = array(
        'user_id' => 'sometimes|required|integer',
        'money_transfer_account_id' => 'sometimes|required|integer',
        'amount' => 'sometimes|required',
        'status' => 'sometimes'
    );
    //User relation
    public function user()
    {
        return $this->belongsTo('Models\User', 'user_id', 'id')->select('id', 'username', 'email');
    }
}
