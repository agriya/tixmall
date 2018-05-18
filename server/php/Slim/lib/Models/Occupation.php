<?php
/**
 * Occupation
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
 * Occupation
 */
class Occupation extends AppModel
{
    protected $table = 'occupations';
    public $rules = array(
    'name' => 'sometimes|required', 
    'slug' => 'sometimes|required'
    );
}
