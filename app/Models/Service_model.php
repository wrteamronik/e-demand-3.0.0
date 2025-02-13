<?php

namespace App\Models;

use CodeIgniter\Model;
use Mpdf\Tag\Em;

class Service_model extends Model
{
    protected $table = 'services';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'category_id',
        'tax_id',
        'tax',
        'title',
        'slug',
        'description',
        'tags',
        'image',
        'price',
        'discounted_price',
        'is_cancelable',
        'cancelable_till',
        'tax_type',
        'number_of_members_required',
        'duration',
        'rating',
        'number_of_ratings',
        'on_site_allowed',
        'max_quantity_allowed',
        'is_pay_later_allowed',
        'status',
        'price_with_tax',
        'tax_value',
        'original_price_with_tax',
        'other_images',
        'long_description',
        'files',
        'faqs',
        'at_store',
        'at_doorstep',
        'approved_by_admin'
    ];
    public $admin_id;
    public function __construct()
    {
        $ionAuth = new \IonAuth\Libraries\IonAuth();
        $this->admin_id = ($ionAuth->isAdmin()) ? $ionAuth->user()->row()->id : 0;
        $this->ionAuth = new \IonAuth\Libraries\IonAuth();
    }
    public function list($from_app = false, $search = '', $limit = 10, $offset = 0, $sort = 'id', $order = 'ASC', $where = [], $additional_data = [], $column_name = '', $whereIn = [], $for_new_total = null, $at_store = null, $at_doorstep = null)
    {
        $multipleWhere = '';
        $db      = \Config\Database::connect();
        $builder = $db->table('services s');
        $builder2 = $db->table('services s');
        if ($search and $search != '') {
            $multipleWhere = [
                '`s.id`' => $search,
                '`s.title`' => $search,
                '`s.description`' => $search,
                '`s.status`' => $search,
                '`s.tags`' => $search,
                '`s.price`' => $search,
                '`s.discounted_price`' => $search,
                '`s.rating`' => $search,
                '`s.number_of_ratings`' => $search,
                '`s.max_quantity_allowed`' => $search
            ];
        }
        $total  = $builder->select('COUNT(s.id) as `total` ');
        $max_price = $builder->select('MAX(s.price) as max_price');
        $min_price = $builder->select('MIN(s.price) as min_price');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $builder->groupStart();
            $builder->orLike($multipleWhere);
            $builder->groupEnd();
        }
        if (isset($where) && !empty($where)) {
            $builder->where($where);
        }
        if (!empty($whereIn)) {
            $builder->whereIn($column_name, $whereIn);
        }
        if (isset($_GET['service_filter_approve']) && $_GET['service_filter_approve'] != '') {
            $builder->where('s.approved_by_admin',  $_GET['service_filter_approve']);
        }
        if (isset($_GET['service_filter']) && $_GET['service_filter'] != '') {
            $builder->where('s.status',  $_GET['service_filter']);
        }
        if (isset($_GET['service_custom_provider_filter']) && $_GET['service_custom_provider_filter'] != '') {
            $builder->where('s.user_id',  $_GET['service_custom_provider_filter']);
        }
        if (isset($_GET['service_category_custom_filter']) && $_GET['service_category_custom_filter'] != '') {
            $builder->where('s.category_id',  $_GET['service_category_custom_filter']);
        }
        if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
            $parnter_ids = get_near_partners($additional_data['latitude'], $additional_data['longitude'], $additional_data['max_serviceable_distance'], true);
            if (isset($parnter_ids) && !empty($parnter_ids) && !isset($parnter_ids['error'])) {
                $builder->whereIn('s.user_id', $parnter_ids);
            }
        }
        $service_count = $builder->get()->getResultArray();
        $total = $service_count[0]['total'];
        $min_price = $service_count[0]['min_price'];
        $max_price = $service_count[0]['max_price'];
        // $builder->select('s.*,c.name as category_name,p.username as partner_name,c.parent_id,pd.need_approval_for_the_service');
        if (isset($_GET['service_filter']) && $_GET['service_filter'] != '') {
            $builder->where('s.status',  $_GET['service_filter']);
        }
        if (isset($_GET['service_custom_provider_filter']) && $_GET['service_custom_provider_filter'] != '') {
            $builder->where('s.user_id',  $_GET['service_custom_provider_filter']);
        }
        if (isset($_GET['service_category_custom_filter']) && $_GET['service_category_custom_filter'] != '') {
            $builder->where('s.category_id',  $_GET['service_category_custom_filter']);
        }
        if (isset($_GET['service_filter_approve']) && $_GET['service_filter_approve'] != '') {
            $builder->where('s.approved_by_admin',  $_GET['service_filter_approve']);
        }
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $builder->groupStart();
            $builder->orLike($multipleWhere);
            $builder->groupEnd();
        }
        if (isset($where) && !empty($where)) {
            $builder->where($where);
        }
        if (!empty($whereIn)) {
            $builder->whereIn($column_name, $whereIn);
        }
        if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
            $parnter_ids = get_near_partners($additional_data['latitude'], $additional_data['longitude'], $additional_data['max_serviceable_distance'], true);
            if (isset($parnter_ids) && !empty($parnter_ids) && !isset($parnter_ids['error'])) {
                $builder->whereIn('s.user_id', $parnter_ids);
            }
        }
     
    

        $subquery = $builder2->select('service_id, (SUM(sr.rating) / NULLIF(COUNT(sr.rating), 0)) as average_rating')
            ->from('services_ratings sr') // Explicit alias for services_ratings
            ->groupBy('service_id')
            ->getCompiledSelect(); // Get the compiled subquery

        $service_record = $builder->select('s.*,
                p.username as partner_name, pd.need_approval_for_the_service, 
                c.name as category_name, c.parent_id, 
                avg_rating.average_rating')
            ->join('users p', 'p.id = s.user_id', 'left')
            ->join('partner_details pd', 'pd.partner_id = s.user_id', 'left')
            ->join('categories c', 'c.id = s.category_id', 'left')
            ->join("($subquery) avg_rating", 'avg_rating.service_id = s.id', 'left')
            ->groupBy('s.id')
            ->orderBy('s.' . $sort, $order)
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();


        $bulkData = array();
        $bulkData['total'] = $total;
        $bulkData['min_price'] = $min_price;
        $bulkData['max_price'] = $max_price;
        $rows = array();
        $tempRow = array();
        if ($from_app == false) {
            $db      = \Config\Database::connect();
            $builder = $db->table('users u');
            $builder->select('u.*,ug.group_id')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->whereIn('ug.group_id', [1, 3])
                ->where(['phone' => $_SESSION['identity']]);
            $user1 = $builder->get()->getResultArray();
            $permissions = get_permission($user1[0]['id']);
        }
        foreach ($service_record as $row) {
            $operations = "";
            $operations = '<div class="dropdown">
            <a class="" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <button class="btn btn-secondary   btn-sm px-3"> <i class="fas fa-ellipsis-v "></i></button>
            </a>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">';
            if ($from_app == false) {
                if ($this->ionAuth->isAdmin()) {
                    if ($permissions['update']['services'] == 1) {
                        $operations .= '<a class="dropdown-item"href="' . base_url('/admin/services/edit_service/' . $row['id']) . '"><i class="fa fa-pen mr-1 text-primary"></i> Edit Service</a>';
                    }
                    $operations .= '<a class="dropdown-item"href="' . base_url('/admin/services/duplicate/' . $row['id']) . '"><i class="fas fa-copy text-info mr-1"></i> Duplicate Service</a>';
                } else {

                    if ($row['need_approval_for_the_service'] == 0) {
                    
                        if ($permissions['update']['services'] == 1) {
                            $operations .= '<a class="dropdown-item"href="' . base_url('/partner/services/edit_service/' . $row['id']) . '"><i class="fa fa-pen mr-1 text-primary"></i> Edit Service</a>';
                        }
                    }
                    $operations .= '<a class="dropdown-item"href="' . base_url('/partner/services/duplicate/' . $row['id']) . '"><i class="fas fa-copy text-info mr-1"></i> Duplicate Service</a>';
                }
                if ($permissions['delete']['services'] == 1) {
                    $operations .= '<a class="dropdown-item delete" data-id="' . $row['id'] . '" > <i class="fa fa-trash text-danger mr-1"></i> Delete</a>';
                }
                if ($row['need_approval_for_the_service'] == 1) {
                    $operations .= ($row['approved_by_admin'] == 1) ?
                        '<a class="dropdown-item disapprove_service" href="#" id="disapprove_service"> <i class="fas fa-times text-danger mr-1"></i>Disapprove Service</a>' :
                        '<a class="dropdown-item approve_service" href="#" id="approve_service" ><i class="fas fa-check text-success mr-1"></i>Approve Service</a>';
                }
            }
            if ($this->ionAuth->isAdmin()) {
            } else if (isset($where['user_id']) && !empty($where['user_id'])) {
                $operations .= '<a class="dropdown-item" href="' . base_url('/partner/services/edit_service/' . $row['id']) . '" ><i class="fa fa-pen mr-1 text-primary"></i> Edit Service</a>';
            }
            if ($this->ionAuth->isAdmin()) {

                $operations .= '<a class="dropdown-item" href="' . base_url('/admin/services/service_detail/' . $row['id']) . '" ><i class="fa fa-eye mr-1 text-primary"></i> View Service</a>';
            }
            $operations .= '</div></div>';
            if ($from_app) {
                if (isset($row['image']) && !empty($row['image']) && check_exists(base_url($row['image']))) {
                    $images = '<div class="o-media o-media--middle">
                    <a  href="' .  $row['image'] . '" data-lightbox="image-1"><img class="o-media__img images_in_card"  src="' .  $row['image'] . '" data-lightbox="image-1" alt="' .     $row['id'] . '"></a>';
                } else {
                    $images = '<div class="o-media o-media--middle">
                <img class="o-media__img images_in_card" src="' . base_url('public/backend/assets/profiles/default.png') . '" data-lightbox="image-1" alt="' .     $row['partner_name'] . '">';
                }
            } else {
                if (isset($row['image']) && !empty($row['image']) && check_exists(base_url($row['image']))) {
                    $images = '<div class="o-media o-media--middle">
                    <a  href="' .   base_url($row['image'])  . '" data-lightbox="image-1"><img class="o-media__img images_in_card"  src="' . base_url($row['image']) . '" data-lightbox="image-1" alt="' .     $row['id'] . '"></a>';
                } else {
                    $images = '<div class="o-media o-media--middle">
                    <img class="o-media__img images_in_card" src="' . base_url('public/backend/assets/profiles/default.png') . '" data-lightbox="image-1" alt="' .     $row['partner_name'] . '">';
                }
            }
            $is_cancelable =  ($row['is_cancelable'] == 1) ? "<span class='badge badge-success'>Yes</span>" : "<span class='badge badge-danger'>Not Allowed</span>";
            $status =  ($row['status'] == 1) ? 'Enable' : 'Disable';
            $status_number =  ($row['status'] == 1) ? '1' : '0';
            $pay_later = ($row['is_pay_later_allowed'] == 1) ? '1' : '0';
            $rating = $row['rating'];
            json_decode($row['tags']);
            if (json_last_error() === JSON_ERROR_NONE) {
                $tags = json_decode($row['tags']);
            } else {
                $tags = $row['tags'];
            }
            if (!empty($row['other_images'])) {
                $row['other_images'] = array_map(function ($data) {
                    return base_url($data);
                }, json_decode($row['other_images'], true));
            } else {
                $row['other_images'] = [];
            }
            if (!empty($row['files'])) {
                $row['files'] = array_map(function ($data) {
                    return base_url($data);
                }, json_decode($row['files'], true));
            } else {
                $row['files'] = [];
            }
            $faqsData = json_decode($row['faqs'], true);
            if (is_array($faqsData)) {
                $faqs = [];
                foreach ($faqsData as $pair) {
                    $faq = [
                        'question' => $pair[0],
                        'answer' => $pair[1]
                    ];
                    $faqs[] = $faq;
                }
                $row['faqs'] = $faqs;
            } else {
            }
            $label = ($row['status'] == 1) ?
                "<div class='tag border-0 rounded-md ltr:ml-2 rtl:mr-2 bg-emerald-success text-emerald-success dark:bg-emerald-500/20 dark:text-emerald-100 ml-3 mr-3 mx-5'>Active
                </div>" :
                "<div class='tag border-0 rounded-md ltr:ml-2 rtl:mr-2 bg-emerald-danger text-emerald-danger dark:bg-emerald-500/20 dark:text-emerald-100 ml-3 mr-3 '>Deactive
                </div>";
            $pay_later_badge = ($row['is_pay_later_allowed'] == 1) ?
                "<div class='  text-emerald-success  ml-3 mr-3 mx-5'>Yes
                </div>" :
                "<div class=' text-emerald-danger ml-3 mr-3 '>No
                </div>";
            $is_cancellable_badge = ($row['is_cancelable'] == 1) ?
                "<div class='  text-emerald-success  ml-3 mr-3 mx-5'>Yes
            </div>" :
                "<div class=' text-emerald-danger ml-3 mr-3 '>No
            </div>";
            $tempRow['id'] = $row['id'];
            $tempRow['user_id'] = $row['user_id'];
            $tempRow['category_id'] = $row['category_id'];
            $tempRow['parent_id'] = $row['parent_id'];
            $tempRow['category_name'] = $row['category_name'];
            $tempRow['partner_name'] = $row['partner_name'];
            $tempRow['tax'] = $row['tax'];
            $tempRow['tax_type'] = $row['tax_type'];
            $tax_data = fetch_details('taxes', ['id' => $row['tax_id']], ['title', 'percentage']);
            $tempRow['title'] = $row['title'];
            $tempRow['slug'] = $row['slug'];
            $tempRow['description'] = $row['description'];
            $tempRow['long_description'] = $row['long_description'];
            $tempRow['tags'] = $tags;
            $tempRow['image_of_the_service'] = $images;
            $tempRow['other_images'] = $row['other_images'];
            $tempRow['files'] = $row['files'];
            $tempRow['price'] = $row['price'];
            $tempRow['discounted_price'] = $row['discounted_price'];
            $tempRow['number_of_members_required'] = $row['number_of_members_required'];
            $tempRow['duration'] = $row['duration'];
            $tempRow['rating'] = $rating;
            $tempRow['number_of_ratings'] = $row['number_of_ratings'];
            $tempRow['max_quantity_allowed'] = $row['max_quantity_allowed'];
            $tempRow['is_pay_later_allowed_badge'] = $pay_later_badge;
            $tempRow['is_pay_later_allowed'] = $pay_later;
            $tempRow['status'] = $status;
            $tempRow['created_at'] =  format_date($row['created_at'], 'd-m-Y');
            $tempRow['cancelable_till'] = $row['cancelable_till'];
            $tempRow['cancelable'] = $row['is_cancelable'];
            $tempRow['cancelable_badge'] = $is_cancellable_badge;
            $tempRow['status_badge'] = $label;
            $tempRow['tax_id'] = $row['tax_id'];
            $tempRow['faqs'] = $row['faqs'];
            $tempRow['at_store'] = isset($row['at_store']) && !empty($row['at_store']) ? $row['at_store'] : "0";
            $tempRow['at_doorstep'] = isset($row['at_doorstep']) && !empty($row['at_doorstep']) ? $row['at_doorstep'] : "0";
            $approved_by_admin_badge = ($row['approved_by_admin'] == 1) ?
                "<div class='  text-emerald-success  ml-3 mr-3 mx-5'>Yes
        </div>" :
                "<div class=' text-emerald-danger ml-3 mr-3 '>No
        </div>";
            $tempRow['approved_by_admin'] = isset($row['approved_by_admin']) && !empty($row['approved_by_admin']) ? $row['approved_by_admin'] : "0";
            $tempRow['approved_by_admin_badge'] = $approved_by_admin_badge;
            if (empty($tax_data)) {
                $tempRow['tax_title'] = "";
                $tempRow['tax_percentage'] = "";
            } else {
                $tempRow['tax_title'] = $tax_data[0]['title'];
                $tempRow['tax_percentage'] = $tax_data[0]['percentage'];
            }
            $taxPercentageData = fetch_details('taxes', ['id' => $row['tax_id']], ['percentage']);
            if (!empty($taxPercentageData)) {
                $taxPercentage = $taxPercentageData[0]['percentage'];
            } else {
                $taxPercentage = 0;
            }
            if ($row['discounted_price'] == "0") {
                if ($row['tax_type'] == "excluded") {
                    $tempRow['tax_value'] = number_format((intval(($row['price'] * ($taxPercentage) / 100))), 2);
                    $tempRow['price_with_tax']  = strval($row['price'] + ($row['price'] * ($taxPercentage) / 100));
                    $tempRow['original_price_with_tax'] = strval($row['price'] + ($row['price'] * ($taxPercentage) / 100));
                } else {
                    $tempRow['tax_value'] = "";
                    $tempRow['price_with_tax']  = strval($row['price']);
                    $tempRow['original_price_with_tax'] = strval($row['price']);
                }
            } else {
                if ($row['tax_type'] == "excluded") {
                    $tempRow['tax_value'] = number_format((intval(($row['discounted_price'] * ($taxPercentage) / 100))), 2);
                    $tempRow['price_with_tax']  = strval($row['discounted_price'] + ($row['discounted_price'] * ($taxPercentage) / 100));
                    $tempRow['original_price_with_tax'] = strval($row['price'] + ($row['discounted_price'] * ($taxPercentage) / 100));
                } else {
                    $tempRow['tax_value'] = "";
                    $tempRow['price_with_tax']  = strval($row['discounted_price']);
                    $tempRow['original_price_with_tax'] = strval($row['price']);
                }
            }
            $tempRow['status_number'] = $status_number;
            if (!$from_app) {
                $tempRow['operations'] = $operations;
                $tempRow['is_cancelable'] = $is_cancelable;
            } else {
                $tempRow['is_cancelable'] = $row['is_cancelable'];
                $quantity =  (isset($additional_data['user_id']) && !empty($additional_data['user_id'])) ? in_cart_qty($row['id'], $additional_data['user_id']) : "";
                $tempRow['in_cart_quantity'] =  $quantity;
            }

            $rate_data = get_service_ratings($row['id']);
            $average_rating = $db->table('services s')
                ->select(' 
                            (SUM(sr.rating) / count(sr.rating)) as average_rating
                            ')
                ->join('services_ratings sr', 'sr.service_id = s.id')
                ->where('s.id', $row['id'])
                ->get()->getResultArray();
            foreach ($average_rating as $row2) {
                $tempRow['average_rating'] = (isset($row2['average_rating']) &&  $row2['average_rating'] != "") ?  number_format($row2['average_rating'], 2) : 0;
            }
            foreach ($rate_data as $row1) {
                $tempRow['total_ratings'] = (isset($row1['total_ratings']) && $row1['total_ratings'] != "") ? $row1['total_ratings'] : 0;
                $tempRow['rating_5'] = (isset($row1['rating_5']) && $row1['rating_5'] != "") ?  ($row1['rating_5']) : 0;
                $tempRow['rating_4'] = (isset($row1['rating_4']) && $row1['rating_4'] != "") ?  ($row1['rating_4'])  : 0;
                $tempRow['rating_3'] = (isset($row1['rating_3']) && $row1['rating_3'] != "") ?  ($row1['rating_3']) : 0;
                $tempRow['rating_2'] = (isset($row1['rating_2']) && $row1['rating_2'] != "") ?  ($row1['rating_2']) : 0;
                $tempRow['rating_1'] = (isset($row1['rating_1']) && $row1['rating_1'] != "") ?  ($row1['rating_1']) : 0;
            }
            $rows[] = $tempRow;
        }
        if ($from_app) {



            $multipleWhere = '';
            $db      = \Config\Database::connect();
            $builder = $db->table('services s');
            if ($search and $search != '') {
                $multipleWhere = [
                    '`s.id`' => $search,
                    '`s.title`' => $search,
                    '`s.description`' => $search,
                    '`s.status`' => $search,
                    '`s.tags`' => $search,
                    '`s.price`' => $search,
                    '`s.discounted_price`' => $search,
                    '`s.rating`' => $search,
                    '`s.number_of_ratings`' => $search,
                    '`s.max_quantity_allowed`' => $search
                ];
            }
            $total  = $builder->select('COUNT(s.id) as `total` ');
            $max_price = $builder->select('MAX(s.price) as max_price');
            $min_price = $builder->select('MIN(s.price) as min_price');
            $min_discount_price = $builder->select('MIN(s.discounted_price) as min_discount_price');
            $max_discount_price = $builder->select('MAX(s.discounted_price) as max_discount_price');

            $service_count = $builder->get()->getResultArray();

            // if (isset($multipleWhere) && !empty($multipleWhere)) {
            //     $builder->groupStart();
            //     $builder->orLike($multipleWhere);
            //     $builder->groupEnd();
            // }
            // if (isset($where) && !empty($where)) {
            //     $builder->where($where);
            // }
            // if (!empty($whereIn)) {
            //     $builder->whereIn($column_name, $whereIn);
            // }
            if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
                $parnter_ids = get_near_partners($additional_data['latitude'], $additional_data['longitude'], $additional_data['max_serviceable_distance'], true);
                if (isset($parnter_ids) && !empty($parnter_ids) && !isset($parnter_ids['error'])) {
                    $builder->whereIn('s.user_id', $parnter_ids);
                }
            }

            $total = $service_count[0]['total'];
            $min_price = $service_count[0]['min_price'];
            $max_price = $service_count[0]['max_price'];
            $min_discount_price = $service_count[0]['min_discount_price'];
            $max_discount_price = $service_count[0]['max_discount_price'];
            if ((isset($at_store) && ($at_store === 1)) && (isset($at_doorstep) && ($at_doorstep === 1))) {
                $builder->groupStart();
                $builder->where('s.at_store', 1);
                $builder->Where('s.at_doorstep', 1);
                $builder->groupEnd();
            }
            if (($at_store == 0) || ($at_store == 1)) {
                $builder->where('s.at_store', $at_store);
            }
            if (($at_doorstep == 0) || ($at_doorstep == 1)) {
                $builder->where('s.at_doorstep', $at_doorstep);
            }

            $builder->select('s.*,(SUM(sr.rating) / COUNT(sr.rating)) as average_rating,c.name as category_name,p.username as partner_name,c.parent_id,ps.max_order_limit,ps.order_type,COUNT(DISTINCT CASE WHEN s.id then o.id END) as number_of_orders,ps.purchase_date');
            if (isset($multipleWhere) && !empty($multipleWhere)) {
                $builder->groupStart();
                $builder->orLike($multipleWhere);
                $builder->groupEnd();
            }
            if (isset($where) && !empty($where)) {
                $builder->where($where);
            }
            if (!empty($whereIn)) {
                $builder->whereIn($column_name, $whereIn);
            }
            if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
                $parnter_ids = get_near_partners($additional_data['latitude'], $additional_data['longitude'], $additional_data['max_serviceable_distance'], true);
                if (isset($parnter_ids) && !empty($parnter_ids) && !isset($parnter_ids['error'])) {
                    $builder->whereIn('s.user_id', $parnter_ids);
                }
            }
            $service_record = $builder
                ->join('users p', 'p.id=s.user_id', 'left')
                ->join('orders o', 'o.partner_id = s.user_id AND o.parent_id IS NULL', 'left')
                ->join('partner_subscriptions ps', 'ps.partner_id=s.user_id', 'left')
                ->join('categories c', 'c.id=s.category_id', 'left')
                ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
                ->groupBy('s.id')
                ->orderBy($sort, $order)->limit($limit, $offset)->get()->getResultArray();
            $bulkData = array();
            $bulkData['total'] = $total;
            $bulkData['min_price'] = $min_price;
            $bulkData['max_price'] = $max_price;
            $bulkData['min_discount_price'] = $min_discount_price;
            $bulkData['max_discount_price'] = $max_discount_price;
            $rows = array();
            $tempRow = array();
            foreach ($service_record as $row) {

                $operations = "";
                if ($this->ionAuth->isAdmin()) {
                    $operations = '  
                  <button class="btn btn-info btn-sm edit" data-toggle="modal" title="Edit" data-target="#exampleModal">                 
                  <i class="fa fa-pencil" aria-hidden="true"></i> 
                  </button> 
                  ';
                }
                $operations .= '<button class="btn btn-danger btn-sm delete"  title="Delete" data-id="' . $row['id'] . '"> 
              <i class="fa fa-trash" aria-hidden="true"></i> 
              </button> ';
                if (isset($where['user_id']) && !empty($where['user_id'])) {
                    $operations .= '<button class="btn btn-success btn-sm edit" data-id="' . $row['id'] . '" data-toggle="modal" title="Edit" data-target="#update_modal"><i class="fa fa-pen" aria-hidden="true"></i> </button> ';
                }
                if ($from_app) {
                    if (isset($row['image']) && !empty($row['image']) && check_exists(base_url($row['image']))) {
                        $images = base_url($row['image']);
                    } else {
                        $images = '';
                    }
                } else {
                    if (isset($row['image']) && !empty($row['image']) && check_exists(base_url($row['image']))) {
                        $images = '<a  href="' . base_url($row['image'])  . '" data-lightbox="image-1"><img height="80px" width="80px" class="rounded" src="' . base_url($row['image']) . '" alt=""></a>';
                    } else {
                        $images = 'nothing found';
                    }
                }
                if (!empty($row['other_images'])) {
                    $row['other_images'] = array_map(function ($data) {
                        return base_url($data);
                    }, json_decode($row['other_images'], true));
                } else {
                    $row['other_images'] = [];
                }
                if (!empty($row['files'])) {
                    $row['files'] = array_map(function ($data) {
                        return base_url($data);
                    }, json_decode($row['files'], true));
                } else {
                    $row['files'] = [];
                }
                $is_cancelable =  ($row['is_cancelable'] == 1) ? "<span class='badge badge-success'>Yes</span>" : "<span class='badge badge-danger'>Not Allowed</span>";
                $status =  ($row['status'] == 1) ? 'Enable' : 'Disable';
                $status_number =  ($row['status'] == 1) ? '1' : '0';
                $pay_later = ($row['is_pay_later_allowed'] == 1) ? '1' : '0';
                $rating = $row['rating'];
                json_decode($row['tags']);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $tags = json_decode($row['tags']);
                } else {
                    $tags = $row['tags'];
                }
                $label = ($row['status'] == 1) ?
                    '<div class="badge badge-success projects-badge"> Active </div>' :
                    '<div class="badge badge-danger projects-badge"> Deactive </div>';
                $faqsData = json_decode($row['faqs'], true);
                if (is_array($faqsData)) {
                    $faqs = [];
                    foreach ($faqsData as $pair) {
                        $faq = [
                            'question' => $pair[0],
                            'answer' => $pair[1]
                        ];
                        $faqs[] = $faq;
                    }
                    $row['faqs'] = $faqs;
                }
                $tempRow['id'] = $row['id'];
                $tempRow['user_id'] = $row['user_id'];
                $tempRow['category_id'] = $row['category_id'];
                $tempRow['parent_id'] = $row['parent_id'];
                $tempRow['category_name'] = $row['category_name'];
                $tempRow['partner_name'] = $row['partner_name'];
                $tempRow['tax'] = $row['tax'];
                $tempRow['tax_type'] = $row['tax_type'];
                $tempRow['at_store'] = isset($row['at_store']) && !empty($row['at_store']) ? $row['at_store'] : "0";
                $tempRow['at_doorstep'] = isset($row['at_doorstep']) && !empty($row['at_doorstep']) ? $row['at_doorstep'] : "0";
                $tempRow['approved_by_admin'] = isset($row['approved_by_admin']) && !empty($row['approved_by_admin']) ? $row['approved_by_admin'] : "0";
                $tax_data = fetch_details('taxes', ['id' => $row['tax_id']], ['title', 'percentage']);
                $rate_data = get_service_ratings($row['id']);
                $average_rating = $db->table('services s')
                    ->select(' 
                                (SUM(sr.rating) / count(sr.rating)) as average_rating
                                ')
                    ->join('services_ratings sr', 'sr.service_id = s.id')
                    ->where('s.id', $row['id'])
                    ->get()->getResultArray();
                foreach ($average_rating as $row2) {
                    $tempRow['average_rating'] = (isset($row2['average_rating']) &&  $row2['average_rating'] != "") ?  number_format($row2['average_rating'], 2) : 0;
                }
                foreach ($rate_data as $row1) {
                    $tempRow['total_ratings'] = (isset($row1['total_ratings']) && $row1['total_ratings'] != "") ? $row1['total_ratings'] : 0;
                    $tempRow['rating_5'] = (isset($row1['rating_5']) && $row1['rating_5'] != "") ?  ($row1['rating_5']) : 0;
                    $tempRow['rating_4'] = (isset($row1['rating_4']) && $row1['rating_4'] != "") ?  ($row1['rating_4'])  : 0;
                    $tempRow['rating_3'] = (isset($row1['rating_3']) && $row1['rating_3'] != "") ?  ($row1['rating_3']) : 0;
                    $tempRow['rating_2'] = (isset($row1['rating_2']) && $row1['rating_2'] != "") ?  ($row1['rating_2']) : 0;
                    $tempRow['rating_1'] = (isset($row1['rating_1']) && $row1['rating_1'] != "") ?  ($row1['rating_1']) : 0;
                }
                $tempRow['title'] = $row['title'];
                $tempRow['slug'] = $row['slug'];
                $tempRow['description'] = $row['description'];
                $tempRow['long_description'] = $row['long_description'];
                $tempRow['tags'] = $tags;
                $tempRow['image_of_the_service'] = $images;
                $tempRow['other_images'] = $row['other_images'];
                $tempRow['files'] = $row['files'];
                $tempRow['price'] = $row['price'];
                $tempRow['discounted_price'] = $row['discounted_price'];
                $tempRow['number_of_members_required'] = $row['number_of_members_required'];
                $tempRow['duration'] = $row['duration'];
                $tempRow['rating'] = $rating;
                $tempRow['number_of_ratings'] = $row['number_of_ratings'];
                $tempRow['max_quantity_allowed'] = $row['max_quantity_allowed'];
                $tempRow['is_pay_later_allowed'] = $pay_later;
                $tempRow['status'] = $status;
                $tempRow['created_at'] = $row['created_at'];
                $tempRow['cancelable_till'] = $row['cancelable_till'];
                $tempRow['cancelable'] = $row['is_cancelable'];
                $tempRow['status_badge'] = $label;
                $tempRow['tax_id'] = $row['tax_id'];
                $tempRow['faqs'] = $row['faqs'];
                if (empty($tax_data)) {
                    $tempRow['tax_title'] = "";
                    $tempRow['tax_percentage'] = "";
                } else {
                    $tempRow['tax_title'] = $tax_data[0]['title'];
                    $tempRow['tax_percentage'] = $tax_data[0]['percentage'];
                }
                $taxPercentageData = fetch_details('taxes', ['id' => $row['tax_id']], ['percentage']);
                if (!empty($taxPercentageData)) {
                    $taxPercentage = $taxPercentageData[0]['percentage'];
                } else {
                    $taxPercentage = 0;
                }
                if ($row['discounted_price'] == "0") {
                    if ($row['tax_type'] == "excluded") {
                        $tempRow['tax_value'] = number_format((intval(($row['price'] * ($taxPercentage) / 100))), 2);
                        $tempRow['price_with_tax']  = strval($row['price'] + ($row['price'] * ($taxPercentage) / 100));
                        $tempRow['original_price_with_tax'] = strval($row['price'] + ($row['price'] * ($taxPercentage) / 100));
                    } else {
                        $tempRow['tax_value'] = "";
                        $tempRow['price_with_tax']  = strval($row['price']);
                        $tempRow['original_price_with_tax'] = strval($row['price']);
                    }
                } else {
                    if ($row['tax_type'] == "excluded") {
                        $tempRow['tax_value'] = number_format((intval(($row['discounted_price'] * ($taxPercentage) / 100))), 2);
                        $tempRow['price_with_tax']  = strval($row['discounted_price'] + ($row['discounted_price'] * ($taxPercentage) / 100));
                        $tempRow['original_price_with_tax'] = strval($row['price'] + ($row['discounted_price'] * ($taxPercentage) / 100));
                    } else {
                        $tempRow['tax_value'] = "";
                        $tempRow['price_with_tax']  = strval($row['discounted_price']);
                        $tempRow['original_price_with_tax'] = strval($row['price']);
                    }
                }
                $tempRow['status_number'] = $status_number;
                if (!$from_app) {
                    $tempRow['operations'] = $operations;
                    $tempRow['is_cancelable'] = $is_cancelable;
                } else {
                    $tempRow['is_cancelable'] = $row['is_cancelable'];
                    $quantity =  (isset($additional_data['user_id']) && !empty($additional_data['user_id'])) ? in_cart_qty($row['id'], $additional_data['user_id']) : "";
                    $tempRow['in_cart_quantity'] =  $quantity;
                }


                $rows[] = $tempRow;
            }
            $data['total'] = (empty($total)) ? (string) count($rows) : $total;
            $data['min_price'] = $min_price;
            $data['max_price'] = $max_price;
            $data['min_discount_price'] = $min_discount_price;
            $data['max_discount_price'] = $max_discount_price;
            $data['data'] = $rows;
            $builder2 = $db->table('services s');
            if (isset($multipleWhere) && !empty($multipleWhere)) {
                $builder->orWhere($multipleWhere);
            }
            if (isset($where) && !empty($where)) {
                $builder->where($where);
            }
            if (!empty($whereIn)) {
                $builder->whereIn($column_name, $whereIn);
            }
            $new_total1 = $builder2->select('COUNT(s.id) as `total`,MAX(s.price) as max_price,MIN(s.price) as min_price,MIN(s.discounted_price) as min_discount_price,MAX(s.discounted_price) as max_discount_price')
                ->where('s.user_id', $for_new_total)->get()->getResultArray();
            $new_total = $new_total1[0]['total'];
            $new_min_price = $new_total1[0]['min_price'];
            $new_max_price = $new_total1[0]['max_price'];
            $new_min_discount_price = $new_total1[0]['min_discount_price'];
            $new_max_discount_price = $new_total1[0]['max_discount_price'];
            $data['new_total'] = $new_total;
            $data['new_min_price'] = $new_min_price;
            $data['new_max_price'] = $new_max_price;
            $data['new_min_discount_price'] = $new_min_discount_price;
            $data['new_max_discount_price'] = $new_max_discount_price;
            return $data;
        } else {


            $bulkData['rows'] = $rows;
            return json_encode($bulkData);
        }
    }
}
