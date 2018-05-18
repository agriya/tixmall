<?php
/**
 * News
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
 * News
*/
class News extends AppModel
{
    protected $table = 'news';
    public $rules = array(
        'title' => 'sometimes|required',
        'slug' => 'sometimes|required',
        'description' => 'sometimes|required',
        'isPublished' => 'sometimes|required',
        'publishedOn' => 'sometimes|required',
    );
    public function attachments()
    {
        return $this->hasOne('Models\Attachment', 'foreign_id', 'id')->where('class', 'News');
    }
    public function news_category()
    {
        return $this->hasMany('Models\NewsNewsCategory', 'news_id', 'id');
    }
}
