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
class Contact extends AppModel
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'contacts';
    //Rules
    public $rules = array(
        'first_name' => 'sometimes|required',
        'last_name' => 'sometimes|required',
        'email' => 'sometimes|required|email',
        'phone' => 'sometimes|required',
        'subject' => 'sometimes|required',
        'message' => 'sometimes|required'
    );
    public function ip()
    {
        return $this->belongsTo('Models\Ip', 'ip_id', 'id');
    }
}
