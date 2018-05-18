<?php
/**
 * NewsCategory
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
 * Category
*/
class NewsCategory extends AppModel
{
    protected $table = 'news_categories';
    public $rules = array(
        'name' => 'sometimes|required'
    );
}
