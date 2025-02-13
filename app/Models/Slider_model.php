<?php

namespace App\Models;

use CodeIgniter\Model;

class Slider_model extends Model
{
    protected $DBGroup = 'default';
    protected $table = 'sliders';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['type', 'type_id', 'app_image', 'status','url','web_image'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
    protected $url  = 'url';

    public $base, $admin_id, $db;
    public function list($from_app = false, $search = '', $limit = 10, $offset = 0, $sort = 'sl.id', $order = 'ASC', $where = [])
    {
        $multipleWhere = '';
        $db      = \Config\Database::connect();
        $condition = "";
        $builder = $db->table('sliders sl');
        $sortable_fields = ['id' => 'id', 'type' => 'type', 'type_id' => 'type_id', 'status' => 'status'];
        if (isset($search) and $search != '') {
            $multipleWhere = ['`sl.id`' => $search, '`sl.type`' => $search, '`sl.status`' => $search];
        }
        if ($from_app) {
            $where['sl.status'] = 1;
        }
        $total  = $builder->select(' COUNT(id) as `total` ');
        if (isset($_GET['id']) && $_GET['id'] != '') {
            $builder->where($condition);
        }
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $builder->groupStart();
            $builder->orLike($multipleWhere);
            $builder->groupEnd();
        }
        if (isset($where) && !empty($where)) {
            $builder->where($where);
        }
        if (isset($_GET['slider_filter']) && $_GET['slider_filter'] != '') {
            $builder->where('sl.status',  $_GET['slider_filter']);
        }
        $slider_count = $builder->get()->getResultArray();
        $total = $slider_count[0]['total'];
        $builder->select('sl.*,c.name as category_name,c.parent_id as category_parent_id,pd.company_name as provider_name');
        if (isset($search) and $search != '') {
            $multipleWhere = ['`sl.id`' => $search, '`sl.type`' => $search, '`sl.status`' => $search];
        }
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $builder->groupStart();
            $builder->orLike($multipleWhere);
            $builder->groupEnd();
        }
        if (isset($where) && !empty($where)) {
            $builder->where($where);
        }
        if (isset($_GET['slider_filter']) && $_GET['slider_filter'] != '') {
            $builder->where('sl.status',  $_GET['slider_filter']);
        }
        $slider_record = $builder->join('categories c', 'c.id=sl.type_id', 'left')->join('partner_details pd', 'pd.partner_id=sl.type_id', 'left')->orderBy($sort, $order)->limit($limit, $offset)->get()->getResultArray();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        if ($from_app == false) {
            $db      = \Config\Database::connect();
            $builder = $db->table('users u');
            $builder->select('u.*,ug.group_id')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->where('ug.group_id', 1)
                ->where(['phone' => $_SESSION['identity']]);
            $user1 = $builder->get()->getResultArray();
            $permissions = get_permission($user1[0]['id']);
        }
        foreach ($slider_record as $row) {
            if ($from_app == false) {
                $image = '';
            }
            if ($from_app == false) {
                if (check_exists(base_url('/public/uploads/sliders/' . $row['app_image']))) {
                    $app_image = '  <a  href="' . base_url('/public/uploads/sliders/' . $row['app_image'])  . '" data-lightbox="image-1"><img class="o-media__img images_in_card" src="' . base_url('/public/uploads/sliders/' . $row['app_image']) . '" alt="' .     $row['id'] . '"></a>';
                } else {
                    $app_image = 'nothing found';
                }

                if (check_exists(base_url('/public/uploads/sliders/' . $row['web_image']))) {
                    $web_image = '  <a  href="' . base_url('/public/uploads/sliders/' . $row['web_image'])  . '" data-lightbox="image-1"><img class="o-media__img images_in_card" src="' . base_url('/public/uploads/sliders/' . $row['web_image']) . '" alt="' .     $row['id'] . '"></a>';
                } else {
                    $web_image = 'nothing found';
                }


            } else {
                if (check_exists(base_url('/public/uploads/sliders/' . $row['app_image']))) {
                    $app_image = base_url('/public/uploads/sliders/' . $row['app_image']);
                } else {
                    $app_image = 'nothing found';
                }
                if (check_exists(base_url('/public/uploads/sliders/' . $row['web_image']))) {
                    $web_image = base_url('/public/uploads/sliders/' . $row['web_image']);
                } else {
                    $web_image = 'nothing found';
                }

                
                
            }
            $operations = "";
            $type_all =  $row['type'];
            if ($from_app == false) {
                $operations = '<div class="dropdown">
            <a class="" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <button class="btn btn-secondary   btn-sm px-3"> <i class="fas fa-ellipsis-v "></i></button>
            </a>
            <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">';
                if ($permissions['update']['sliders'] == 1) {
                    $operations .= '<a class="dropdown-item edite-slider "data-id="' . $row['id'] . '"  data-toggle="modal" data-target="#update_modal" onclick="update_slider(this)"><i class="fa fa-pen mr-1 text-primary"></i> Edit Slider</a>';
                }
                if ($permissions['delete']['sliders'] == 1) {
                    $operations .= '<a class="dropdown-item delete-slider" data-id="' . $row['id'] . '" data-toggle="modal" data-target="#delete_modal" onclick="category_id(this)"title = "Delete the slider" > <i class="fa fa-trash text-danger mr-1"></i> Delete the slider</a>';
                }
                $operations .= '</div></div>';
            }
            $status =  ($row['status'] == 1) ? 'Enable' : 'Disable';
            $tempRow['id'] = $row['id'];
            $tempRow['type'] = $row['type'];
            $tempRow['type_id'] = $row['type_id'];
            $tempRow['slider_app_image'] = $app_image;
            $tempRow['slider_web_image'] = $web_image;
            $tempRow['category_parent_id'] = $row['category_parent_id'];

            $tempRow['category_name'] = isset($row['category_name']) ? $row['category_name'] : '';
            $tempRow['provider_name'] = isset($row['provider_name']) ? $row['provider_name'] : '';
            $tempRow['url'] = (isset($row['url']) && $row['url']!="") ? $row['url'] : '';

            if ($from_app == false) {
                $status = ($row['status'] == 1) ?
                    "<div class='tag border-0 rounded-md ltr:ml-2 rtl:mr-2 bg-emerald-success text-emerald-success dark:bg-emerald-500/20 dark:text-emerald-100 ml-3 mr-3 mx-5'>Active
                </div>" :
                    "<div class='tag border-0 rounded-md ltr:ml-2 rtl:mr-2 bg-emerald-danger text-emerald-danger dark:bg-emerald-500/20 dark:text-emerald-100 ml-3 mr-3 '>Deactive
                </div>";
                $tempRow['status'] = $status;
                $tempRow['og_status'] = $row['status'];
                $tempRow['operations'] = $operations;
            }
            if ($from_app == false) {
                $tempRow['created_at'] =  format_date($row['created_at'], 'd-m-Y');
            }
            $rows[] = $tempRow;
        }
        if ($from_app) {
            $data['total'] = $total;
            $data['data'] = $rows;
            return $data;
        } else {
            $bulkData['rows'] = $rows;
            return json_encode($bulkData);
        }
    }
}
