<?php
/**
 * SendInvitation
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
 * SendInvitation
*/
class SendInvitation extends AppModel
{
    protected $table = 'send_invitations';
    public $rules = array(
        'event_id' => 'sometimes|required',
        'event_schedule_id' => 'sometimes|required',
        'price_type_id' => 'sometimes|required',
        'is_send_to_list' => 'sometimes|required',
        'send_to_id' => 'sometimes|required'
    );
}
