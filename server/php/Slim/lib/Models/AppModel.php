<?php
/**
 * AppModel
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
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Translation\FileLoader as FileLoader;
use Illuminate\Filesystem\Filesystem as Filesystem;
use Illuminate\Translation\Translator;
use Carbon\Carbon;
class AppModel extends \Illuminate\Database\Eloquent\Model
{
    public function validate($data)
    {
        $translation_file_loader = new FileLoader(new Filesystem, __DIR__ . '../lang');
        $translator = new Translator($translation_file_loader, 'en');
        $factory = new ValidatorFactory($translator);
        $v = $factory->make($data, $this->rules);
        $v->passes();
        return $v->failed();
    }
    public function scopeFilter($query, $params = array())
    {
        $sortby = (!empty($params['sortby'])) ? $params['sortby'] : 'asc';
        if (!empty($params['fields'])) {
            $fields = explode(',', $params['fields']);
            $query->select($fields);
        }
        if (!empty($params['q'])) {
            $query->where('name', 'LIKE', "%" . $params['q'] . "%");
        }
        if (empty($params['filter']) || (!empty($params['filter']) && $params['filter'] != 'chart'))  {
            if (!empty($params['sort'])) {
                $query->orderBy($params['sort'], $sortby);
            } else {
                if (empty($query->getQuery()->groups)) {
                    $query->orderBy('id', $sortby);
                }
            }
        }
        if (!empty($params['page'])) {
            $offset = ($params['page'] - 1) * PAGE_LIMIT + 1;
            $query->skip($offset)->take(PAGE_LIMIT);
        }
        if (!empty($params['is_active'])) {
            $query->where('is_active', $params['is_active']);
        }
        if (!empty($params['cuisine'])) {
            $cuisine_ids = explode(',', $params['cuisine'][0]);
            $cusines = RestaurantCuisine::whereIn('cuisine_id', $cuisine_ids)->get();
            $i = 0;
            foreach ($cusines as $cuisine) {
                $ids[$i] = $cuisine->restaurant_id;
                $i++;
            }
            $query->whereIn('id', $ids);
        }
        if (!empty($params['series_id'])) {
            $series_ids = explode(',', $params['series_id']);
            $query->whereIn('series_id', $series_ids);
        }
        if (!empty($params['category_id'])) {
            $category_ids = explode(',', $params['category_id']);
            $query->whereIn('category_id', $category_ids);
        }
        if (!empty($params['venue_id'])) {
            $venue_ids = explode(',', $params['venue_id']);
            $query->whereIn('venue_id', $venue_ids);
        }
        if (!empty($params['setting_category_id'])) {
            $query->where('setting_category_id', $params['setting_category_id']);
        }
        if (!empty($params['filter'])) {
            if ($params['filter'] == 'upcoming') {
                $query->where('created_at', '>=', date("Y/m/d"));
            }
        }
        if (((!empty($params['filter'])) && $params['filter'] == 'related') && !empty($params['news_category_id']) && !empty($params['id'])) {
            $categoryId = explode(",", $params['news_category_id']);
            $categoriesValues = NewsNewsCategory::whereIn('news_category_id', $categoryId)->select('news_id')->get();
            $news_id = $params['id'];
            $query->whereNotIn('id', array(
                $news_id
            ));
            $query->whereIn('id', $categoriesValues);
        } elseif (!empty($params['news_category_id'])) {
            $query->whereIn('id', $params['news_category_id']);
        }
        if (!empty($params['event_date'])) {
            $newdate = strtotime('+30 days', strtotime($params['event_date']));
            $enddate = date('Y-m-d', $newdate);
            if (!empty($params['event_end_date'])) {
                $enddate = $params['event_end_date'];
            }
            $eventDates = EventSchedule::whereDate('start_date', '>=', $params['event_date'])->whereDate('end_date', '<=', $enddate)->select('id', 'event_id')->get()->groupBy(function ($date)
            {
                return Carbon::parse($date->start_date)->format('Y-m-d');
            });
            $eventDates = $eventDates->toArray();
            $i = 0;
            foreach ($eventDates as $key => $values) {
                foreach ($values as $key1 => $value) {
                    $event_schedules[$i] = $value['event_id'];
                    $i++;
                }
            }
            $query->whereIn('id', $event_schedules);
        }
        if (!empty($params['venue_zone_id'])) {
            $query->where('venue_zone_id', $params['venue_zone_id']);
        }
        if (!empty($params['venue_zone_section_id'])) {
            $query->where('venue_zone_section_id', $params['venue_zone_section_id']);
        }
        if (!empty($params['venue_zone_section_row_id'])) {
            $query->where('venue_zone_section_row_id', $params['venue_zone_section_row_id']);
        }
        if (!empty($params['event_id'])) {
            $query->where('event_id', $params['event_id']);
        }
        if (!empty($params['event_schedule_id'])) {
            $order_ids = OrderItem::select('order_id')->where('event_schedule_id', $params['event_schedule_id'])->distinct()->get()->toArray();
            foreach($order_ids as $order_id) {
                $ids[] = $order_id['order_id'];
            }
            $query->whereIn('id', $ids);
        }
        if (!empty($params['price_type_id'])) {
            $order_ids = OrderItem::select('order_id')->where('price_type_id', $params['price_type_id'])->distinct()->get()->toArray();
            foreach($order_ids as $order_id) {
                $ids[] = $order_id['order_id'];
            }
            $query->whereIn('id', $ids);
        }
        if (!empty($params['start_date']) && !empty($params['end_date'])) {
            $query->whereDate('created_at', '>=', $params['start_date'])
                ->whereDate('created_at', '<=', $params['end_date']);
        }
        if (!empty($params['sales_channel'])) {
            if($params['sales_channel'] == 'mobile') {
                $query->where('is_booked_via_mobile', 1);
            } else {
                $query->where('is_booked_via_mobile', 0);
            }
        }
        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }
        if (!empty($params['list_id'])) {
            $listId = explode(",", $params['list_id']);
            $guestsList = GuestsList::whereIn('list_id', $listId)->select('guest_id')->get()->toArray(); 
            foreach($guestsList as $value)
            {
                 $guestsUser[] = $value['guest_id'];
            }    
            $query->whereIn('id',$guestsUser);
        }        
        return $query;
    }
}
