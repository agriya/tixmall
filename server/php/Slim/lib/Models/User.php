<?php
/**
 * User
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
class User extends AppModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';
    public $rules = array(
        'first_name' => 'sometimes|required',
        'last_name' => 'sometimes|required',
        'mobile' => 'sometimes|required',
        'username' => 'sometimes|required|alpha_num',
        'email' => 'sometimes|required|email',
        'password' => 'sometimes|required'
    );
    protected $scopes_3 = array(
        'canUpdateUser',
        'canListUserTransactions',
        'canCreateUserCashWithdrawals',
        'canListUserCashWithdrawals',
        'canCreateMoneyTransferAccount',
        'canUpdateMoneyTransferAccount',
        'canViewMoneyTransferAccount',
        'canListMoneyTransferAccount',
        'canDeleteMoneyTransferAccount',
        'canCreateGiftVoucher',
        'canListGiftVoucher',
        'canListOrder',
        'canViewOrder',
        'canDeleteEvent',
        'canUpdateEvent',
        'canCreateEvent',
        'canDeleteEventZone',
        'canViewEventZone',
        'canUpdateEventZone',
        'canListEventZone',
        'canCreateEventZone',
        'canViewStats',
        'canListSalesReport',
        'canListSalesReportDetail',
        'canListCapacityReport',
        'canListDemographicReport',
        'canListFinancialReport',
        'canListParticipantReport',
        'canListVisitorReport',
        'CanListLists',
        'CanCreateList',
        'CanViewList',
        'CanUpdateList',
        'CanDeleteList',
        'CanListGuests',
        'CanCreateGuest',
        'CanViewGuest',
        'CanUpdateGuest',
        'CanDeleteGuest',
        'CanListGuestLists',
        'CanSendInvitation',
        'CanDeleteEventSchedule',
        'CanUpdateEventSchedule',
        'canSendTicket',
        'canViewCategory',
        'canCreateVenueZone',
        'canDeleteVenueZone',
        'canUpdateVenueZone',
        'canListVenueZone',
        'canViewCity',
        'canViewState',
        'canViewCountry',
        'canViewCoupon',
        'canViewSeries'
    );  
    protected $scopes_2 = array(
        'canUpdateUser',
        'canListUserTransactions',
        'canCreateUserCashWithdrawals',
        'canListUserCashWithdrawals',
        'canCreateMoneyTransferAccount',
        'canUpdateMoneyTransferAccount',
        'canViewMoneyTransferAccount',
        'canListMoneyTransferAccount',
        'canDeleteMoneyTransferAccount',
        'canCreateGiftVoucher',
        'canListGiftVoucher',
        'canListOrder',
        'canCreateOrder',
        'canViewOrder',
        'canDeleteCreditCard',
        'canSendTicket'
    );
    protected $scopes_1 = array();
    /**
     * To check if username already exist in user table, if so generate new username with append number
     *
     * @param string $username User name which want to check if already exsist
     *
     * @return mixed
     */
    public function checkUserName($username)
    {
        $userExist = User::where('username', $username)->first();
        if (count($userExist) > 0) {
            $org_username = $username;
            $i = 1;
            do {
                $username = $org_username . $i;
                $userExist = User::where('username', $username)->first();
                if (count($userExist) < 0) {
                    break;
                }
                $i++;
            } while ($i < 1000);
        }
        return $username;
    }
    public function city()
    {
        return $this->belongsTo('Models\City', 'city_id', 'id')->select('id', 'name');
    }
    //state relation
    public function state()
    {
        return $this->belongsTo('Models\State', 'state_id', 'id')->select('id', 'name');
    }
    //country relation
    public function country()
    {
        return $this->belongsTo('Models\Country', 'country_id', 'id')->select('id', 'iso_alpha2', 'name');
    }
    public function occupation()
    {
        return $this->belongsTo('Models\Occupation', 'occupation_id', 'id')->select('id', 'name');
    }
    public function education()
    {
        return $this->belongsTo('Models\Education', 'education_id', 'id')->select('id', 'name');
    }
}
