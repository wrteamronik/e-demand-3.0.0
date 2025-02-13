<?php
namespace App\Controllers\admin;
use App\Models\Featured_sections_model;
class Featured_sections extends Admin
{
    public   $validation, $sections, $creator_id;
    public function __construct()
    {
        parent::__construct();
        helper(['form', 'url', 'ResponceServices']);
        $this->sections = new Featured_sections_model();
        $this->validation = \Config\Services::validation();
        $this->creator_id = $this->userId;
        $this->superadmin = $this->session->get('email');
    }
    public function index()
    {
        if (!$this->isLoggedIn || !$this->userIsAdmin) {
            return redirect('admin/login');
        }
        setPageInfo($this->data, 'Featured section | Admin Panel', 'featured_sections');
        $this->data['categories_name'] = fetch_details('categories', [], ['id', 'name']);
        $this->data['partners'] = fetch_details('partner_details', []);
        $this->data['provider_title'] = fetch_details('partner_details', [], ['id', 'partner_id', 'company_name']);
        return view('backend/admin/template', $this->data);
    }
    public function add_featured_section()
    {
        try {
            $result = checkModificationInDemoMode($this->superadmin);
            if ($result !== true) {
                return $this->response->setJSON($result);
            }
            $permission = is_permitted($this->creator_id, 'add', 'featured_section');
            if (!$permission) {
                return NoPermission();
            }
            $section_type = $this->request->getPost('section_type') ?? "";
            $common_rules = [
                'section_type' => [
                    "rules" => 'required|trim',
                    "errors" => ["required" => "Please select type for feature section"]
                ]
            ];
            if ($section_type == 'partners' || $section_type == 'categories' || $section_type == 'banner') {
                if (($section_type == 'partners')) {
                    $specific_rule = 'partners_ids';
                    $specific_rule = 'title';
                } else if (($section_type == 'categories')) {
                    $specific_rule = 'category_item';
                    $specific_rule = 'title';
                } else if ($section_type == 'banner') {
                    $specific_rule = 'banner_type';
                }
                if ($section_type == 'partners') {
                    $specific_error = 'partner';
                    $specific_error = 'title';
                } else if (($section_type == 'categories')) {
                    $specific_error = 'category';
                    $specific_error = 'title';
                } else if ($section_type == 'banner') {
                    $specific_error = 'banner';
                }
                $rules = array_merge($common_rules, [
                    $specific_rule => [
                        "rules" => 'required',
                        "errors" => ["required" => "Please select at least one $specific_error"]
                    ]
                ]);
            } else {
                $rules = array_merge($common_rules, [
                    'section_type' => [
                        "rules" => 'required',
                        "errors" => ["required" => "Please choose any Section Type"]
                    ]
                ]);
            }
            $this->validation->setRules($rules);
            if (!$this->validation->withRequest($this->request)->run()) {
                $errors  = $this->validation->getErrors();
                return ErrorResponse($errors, true, [], [], 200, csrf_token(), csrf_hash());
            }
            $sections = fetch_details('sections');
            if (!empty($sections)) {
                foreach ($sections as $row) {
                    if ($section_type == $row['section_type']) {
                        if ($row['section_type'] == "ongoing_order" || $row['section_type'] == "previous_order") {
                            return ErrorResponse("You may only include the  " . $section_type . " section once.", true, [], [], 200, csrf_token(), csrf_hash());
                        }
                    }
                }
            }
            $title = $this->request->getPost('title') ?? "";
            $data = [];
            $data = [
                'partners_ids' => null,
                'category_ids' => null,
                'banner_type' => null,
                'banner_url' => null,
                'limit' => 0
            ];
            if (isset($section_type)) {
                if ($section_type == 'partners') {
                    $data['partners_ids'] = implode(',', $_POST['partners_ids']);
                } elseif ($section_type == 'categories') {
                    $data['category_ids'] = implode(',', $_POST['category_item']);
                } elseif ($section_type == 'top_rated_partner') {
                    $data['limit'] = $this->request->getPost('limit');
                } elseif ($section_type == 'previous_order') {
                    $data['limit'] = $this->request->getPost('previous_order_limit');
                } elseif ($section_type == 'ongoing_order') {
                    $data['limit'] = $this->request->getPost('ongoing_order_limit');
                } elseif ($section_type == 'near_by_provider') {
                    $data['limit'] = $this->request->getPost('limit_for_near_by_providers');
                } elseif ($section_type == 'banner') {
                    $banner_type = $this->request->getPost('banner_type');
                    $data['banner_type'] =  $banner_type;
                    if ($banner_type == "banner_category") {
                        $data['category_ids'] = $_POST['banner_category_item'];
                    } else if ($banner_type == "banner_provider") {
                        $data['partners_ids'] = $_POST['banner_providers'];
                    } else if ($banner_type == "banner_url") {
                        $data['banner_url'] = $_POST['url'];
                    }
                    $t = time();
                    $app_image = $this->request->getFile('app_image');
                    $web_image = $this->request->getFile('web_image');
                    $app_image_ext = $app_image->getExtension();
                    $app_image_name = $t . '.' . $app_image_ext;
                    $web_image_ext = $web_image->getExtension();
                    $web_image_name = $t . '.' . $web_image_ext;
                    $folders = [
                        'public/uploads/feature_section/' => "Failed to create profile folders",
                    ];
                    foreach ($folders as $path => $errorMessage) {
                        if (!create_folder($path)) {
                            return ErrorResponse($errorMessage, true, [], [], 200, csrf_token(), csrf_hash());
                        }
                    }
                    $data['app_banner_image'] = $app_image_name;
                    $data['web_banner_image'] = $web_image_name;
                    $app_full_path = "public/uploads/feature_section/"  . $app_image_name;
                    $app_tempPath = $app_image->getTempName();
                    compressImage($app_tempPath, $app_full_path, 70);
                    $web_full_path = "public/uploads/feature_section/"  . $web_image_name;
                    $web_tempPath = $web_image->getTempName();
                    compressImage($web_tempPath, $web_full_path, 70);
                }
            }
            $data['status'] = isset($_POST['status']) ? 1 : 0;
            $data['title'] = $title  ?? "";
            $data['section_type'] = $section_type;
            $db      = \Config\Database::connect();
            $builder = $db->table('sections');
            $builder->selectMax('rank');
            $order = $builder->get()->getResultArray();
            $data['rank'] = ($order[0]['rank']) + 1;
            if ($this->sections->save($data)) {
                return successResponse("Featured section added successfully", false, [], [], 200, csrf_token(), csrf_hash());
            } else {
                return ErrorResponse("Please try again...", true, [], [], 200, csrf_token(), csrf_hash());
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Featured_sections.php - add_featured_section()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function list()
    {
        try {
            $multipleWhere = '';
            $db      = \Config\Database::connect();
            $builder = $db->table('sections s');
            $sortable_fields = ['id' => 'id', 'rank' => 'rank', 'title' => 'title', 'categories' => 'categories', 'style' => 'style', 'service_type' => 'service_type'];
            $sort = 'rank';
            $limit = 10;
            $condition  = [];
            $offset = 0;
            if (isset($_GET['offset'])) {
                $offset = $_GET['offset'];
            }
            if (isset($_GET['limit'])) {
                $limit = $_GET['limit'];
            }
            if (isset($_GET['sort'])) {
                if ($_GET['sort'] == 'id') {
                    $sort = (isset($sortable_fields[$sort])) ? $sortable_fields[$sort] : "id";
                } else {
                    $sort = $_GET['sort'];
                }
            }
            $order = "ASC";
            if (isset($_GET['order'])) {
                $order = $_GET['order'];
            }
            if (isset($_GET['search']) and $_GET['search'] != '') {
                $search = $_GET['search'];
                $multipleWhere = ['`s.id`' => $search, '`s.title`' => $search, '`s.created_at`' => $search, 'section_type' => $search];
            }
            if (isset($_GET['feature_section_filter']) && $_GET['feature_section_filter'] != '') {
                $builder->where('s.status',  $_GET['feature_section_filter']);
            }
            $total  = $builder->select(' COUNT(id) as `total` ');
            if (isset($_GET['id']) && $_GET['id'] != '') {
                $builder->where($condition);
            }
            if (isset($multipleWhere) && !empty($multipleWhere)) {
                $builder->orWhere($multipleWhere);
            }
            if (isset($where) && !empty($where)) {
                $builder->where($where);
            }
            $offer_count = $builder->get()->getResultArray();
            $total = $offer_count[0]['total'];
            $builder->select();
            if (isset($multipleWhere) && !empty($multipleWhere)) {
                $builder->orLike($multipleWhere);
            }
            if (isset($where) && !empty($where)) {
                $builder->where($where);
            }
            if (isset($_GET['feature_section_filter']) && $_GET['feature_section_filter'] != '') {
                $builder->where('s.status',  $_GET['feature_section_filter']);
            }
            $offer_recored = $builder->orderBy($sort, $order)->limit($limit, $offset)->get()->getResultArray();
            $bulkData = array();
            $bulkData['total'] = $total;
            $rows = array();
            $tempRow = array();
            $user1 = fetch_details('users', ["phone" => $_SESSION['identity']],);
            $permissions = get_permission($user1[0]['id']);
            foreach ($offer_recored as $row) {
                $operations = "";
                $label = ($row['status'] == 1) ?
                    "<div class='tag border-0 rounded-md ltr:ml-2 rtl:mr-2 bg-emerald-success text-emerald-success dark:bg-emerald-500/20 dark:text-emerald-100 ml-3 mr-3 mx-5'>Active
            </div>" :
                    "<div class='tag border-0 rounded-md ltr:ml-2 rtl:mr-2 bg-emerald-danger text-emerald-danger dark:bg-emerald-500/20 dark:text-emerald-100 ml-3 mr-3 '>Deactive
            </div>";
                $operations = '<div class="dropdown">
                <a class="" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <button class="btn btn-secondary   btn-sm px-3"> <i class="fas fa-ellipsis-v "></i></button>
                </a>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">';
                if ($permissions['update']['featured_section'] == 1) {
                    $operations .= '<a class="dropdown-item update_featured_section "data-id="' . $row['id'] . '"  data-id="' . $row['id'] . '"  data-toggle="modal" data-target="#update_modal" onclick="feature_section_id(this)"><i class="fa fa-pen mr-1 text-primary"></i> Edit</a>';
                }
                if ($permissions['delete']['featured_section'] == 1) {
                    $operations .= '<a class="dropdown-item delete-featured_section" data-id="' . $row['id'] . '" onclick="feature_section_id(this)" data-toggle="modal" data-target="#delete_modal"> <i class="fa fa-trash text-danger mr-1"></i> Delete </a>';
                }
                if ($row['section_type'] == "banner") {
                    if (check_exists(base_url('/public/uploads/feature_section/' . $row['app_banner_image']))) {
                        $app_banner_image = '  <a  href="' . base_url('/public/uploads/feature_section/' . $row['app_banner_image'])  . '" data-lightbox="image-1"><img class="o-media__img images_in_card" src="' . base_url('/public/uploads/feature_section/' . $row['app_banner_image']) . '" alt="' .     $row['id'] . '"></a>';
                    } else {
                        $app_banner_image = 'nothing found';
                    }
                    if (check_exists(base_url('/public/uploads/feature_section/' . $row['web_banner_image']))) {
                        $web_banner_image = '  <a  href="' . base_url('/public/uploads/feature_section/' . $row['web_banner_image'])  . '" data-lightbox="image-1"><img class="o-media__img images_in_card" src="' . base_url('/public/uploads/feature_section/' . $row['web_banner_image']) . '" alt="' .     $row['id'] . '"></a>';
                    } else {
                        $web_banner_image = 'nothing found';
                    }
                } else {
                    $app_banner_image = '-';
                    $web_banner_image = '-';
                }
                $operations .= '</div></div>';
                $tempRow['id'] = $row['id'];
                $tempRow['title'] = ($row['title']!="") ?$row['title']: "-";
                $tempRow['category_ids'] = $row['category_ids'];
                $tempRow['section_type'] = $row['section_type'];
                $tempRow['section_type_badge'] = feature_section_type($row['section_type']);
                $tempRow['banner_type_badge'] = banner_type($row['banner_type']);

                
                $tempRow['partners_ids'] = $row['partners_ids'];
                $tempRow['created_at'] = format_date($row['created_at'], 'd-m-Y');
                $tempRow['status'] = $row['status'];
                $tempRow['status_badge'] = $label;
                $tempRow['rank'] =  $row['rank'];
                $tempRow['limit'] =  $row['limit'];
                $tempRow['app_banner_image'] =  $app_banner_image;
                $tempRow['web_banner_image'] =  $web_banner_image;
                $tempRow['banner_url'] = $row['banner_url'];
                $tempRow['banner_type'] = $row['banner_type'];
                $tempRow['icon'] = '<i class="fas fa-sort text-new-primary" title="Hold to move"></i>';
                $tempRow['operations'] = $operations;
                $rows[] = $tempRow;
            }
            $bulkData['rows'] = $rows;
            return json_encode($bulkData);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Featured_sections.php - list()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function delete_featured_section()
{
    try {
        if (!$this->isLoggedIn || !$this->userIsAdmin) {
            return redirect('admin/login');
        }
        $result = checkModificationInDemoMode($this->superadmin);
        if ($result !== true) {
            return $this->response->setJSON($result);
        }
        $permission = is_permitted($this->creator_id, 'delete', 'featured_section');
        if (!$permission) {
            return NoPermission();
        }
        $id = $this->request->getPost('id');
        $db = \Config\Database::connect();
        $builder = $db->table('sections');
        $builder->delete(['id' => $id]);
        $builder = $db->table('sections');
        $builder->orderBy('rank', 'ASC');
        $sections = $builder->get()->getResultArray();
        foreach ($sections as $index => $section) {
            $newRank = $index + 1;
            $builder->where('id', $section['id']);
            $builder->update(['rank' => $newRank]);
        }
        return successResponse("Featured section deleted and ranks reorganized successfully", false, [], [], 200, csrf_token(), csrf_hash());
    } catch (\Throwable $th) {
        log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Featured_sections.php - delete_featured_section()');
        return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
    }
}
    public function update_featured_section()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            $result = checkModificationInDemoMode($this->superadmin);
            if ($result !== true) {
                return $this->response->setJSON($result);
            }
            $id = $this->request->getPost('id');
            $db      = \Config\Database::connect();
            $builder = $db->table('sections');
            $permission = is_permitted($this->creator_id, 'update', 'featured_section');
            if (!$permission) {
                return NoPermission();
            }
            $section_type = ($this->request->getPost('section_type')) ? $this->request->getPost('section_type') : "";
            $common_rules = [
                'section_type' => [
                    "rules" => 'required|trim',
                    "errors" => ["required" => "Please select type for feature section"]
                ]
            ];
            if ($section_type == 'partners' || $section_type == 'categories' || $section_type == 'banner') {
                if (($section_type == 'partners')) {
                    $specific_rule = 'partners_ids';
                    $specific_rule = 'title';
                } else if (($section_type == 'categories')) {
                    $specific_rule = 'category_item';
                    $specific_rule = 'title';
                } else if ($section_type == 'banner') {
                    $specific_rule = 'banner_type';
                }
                if ($section_type == 'partners') {
                    $specific_error = 'partner';
                    $specific_error = 'title';
                } else if (($section_type == 'categories')) {
                    $specific_error = 'category';
                    $specific_error = 'title';
                } else if ($section_type == 'banner') {
                    $specific_error = 'banner';
                }
                $rules = array_merge($common_rules, [
                    $specific_rule => [
                        "rules" => 'required',
                        "errors" => ["required" => "Please select at least one $specific_error"]
                    ]
                ]);
            } else {
                $rules = array_merge($common_rules, [
                    'section_type' => [
                        "rules" => 'required',
                        "errors" => ["required" => "Please choose any Section Type"]
                    ]
                ]);
            }
            $this->validation->setRules($rules);
            if (!$this->validation->withRequest($this->request)->run()) {
                $errors  = $this->validation->getErrors();
                return ErrorResponse($errors, true, [], [], 200, csrf_token(), csrf_hash());
            }
            $partner_ids = $category_ids = null;
            $id = $this->request->getPost('id');
            $title = $this->request->getPost('title');
            $data['title'] = $title ?? "";
            $data['section_type'] = $_POST['section_type'];
            $data['category_ids'] = $category_ids;
            if ($_POST['section_type'] == 'partners') {
                $partner_ids = implode(',', $_POST['edit_partners_ids']);
                $data['partners_ids'] = $partner_ids;
            } elseif ($_POST['section_type'] == 'categories') {
                $category_ids = implode(',', $_POST['edit_Category_item']);
                $data['category_ids'] = $category_ids;
            } elseif ($_POST['section_type']  == 'previous_order') {
                $data['limit'] = $this->request->getPost('previous_order_limit');;
            } else  if (isset($section_type) && $section_type == 'ongoing_order') {
                $data['limit'] = $this->request->getPost('ongoing_order_limit');;
            } elseif ($section_type == 'banner') {
                $data['title'] = "";
                $banner_type = $this->request->getPost('banner_type');
                $data['banner_type'] =  $banner_type;
                if ($banner_type == "banner_category") {
                    $data['category_ids'] =  $_POST['banner_category_item'];
                } else if ($banner_type == "banner_provider") {
                    $data['partners_ids'] =  $_POST['banner_providers'];
                } else if ($banner_type == "banner_url") {
                    $data['banner_url'] = $_POST['url'];
                }
                $t = time();


                $old_data = fetch_details('sections', ['id' => $id]);
                $old_app_image = $old_data[0]['app_banner_image'];
                $old_web_image = $old_data[0]['web_banner_image'];

                $app_banner_image = $this->request->getFile('app_image');
                $web_banner_image = $this->request->getFile('web_image');


                $app_image_name = ($app_banner_image->getName() == "") ? $old_app_image :  $app_banner_image->getName();
                $web_image_name = ($web_banner_image->getName() == "") ? $old_web_image :  $web_banner_image->getName();

                $old_app_image_path = "public/uploads/feature_section/" . $old_app_image;
                $old_web_image_path = "public/uploads/feature_section/" . $old_web_image;
                

                if($app_banner_image->getSize()>0){

                  
                    if (file_exists(FCPATH . $old_app_image_path)) {
                        unlink(FCPATH . $old_app_image_path);
                    }

                    $app_image_full_path = "public/uploads/feature_section/"  . $app_image_name;
                    $tempPath = $app_banner_image->getTempName();
                    compressImage($tempPath, $app_image_full_path, 70);
                }

                if($web_banner_image->getSize()>0){

                    if (file_exists(FCPATH . $old_web_image_path)) {
                        unlink(FCPATH . $old_web_image_path);
                    }
                    $web_image_full_path = "public/uploads/feature_section/"  . $web_image_name;
                    $tempPath = $web_banner_image->getTempName();
                    compressImage($tempPath, $web_image_full_path, 70);
                }

                // $app_image = $this->request->getFile('app_image');
                // $web_image = $this->request->getFile('web_image');
                // $app_image_ext = $app_image->getExtension();
                // $app_image_name = $t . '.' . $app_image_ext;
                // $web_image_ext = $web_image->getExtension();
                // $web_image_name = $t . '.' . $web_image_ext;
                // $folders = [
                //     'public/uploads/feature_section/' => "Failed to create profile folders",
                // ];
                // foreach ($folders as $path => $errorMessage) {
                //     if (!create_folder($path)) {
                //         return ErrorResponse($errorMessage, true, [], [], 200, csrf_token(), csrf_hash());
                //     }
                // }
                $data['app_banner_image'] = $app_image_name;
                $data['web_banner_image'] = $web_image_name;
                // $app_full_path = "public/uploads/feature_section/"  . $app_image_name;
                // $app_tempPath = $app_image->getTempName();
                // compressImage($app_tempPath, $app_full_path, 70);
                // $web_full_path = "public/uploads/feature_section/"  . $web_image_name;
                // $web_tempPath = $web_image->getTempName();
                // compressImage($web_tempPath, $web_full_path, 70);
            }
            $data['status'] = isset($_POST['edit_status']) ? 1 : 0;
            if ($builder->update($data, ['id' => $id])) {
                return successResponse("Featured section updated successfully", false, [], [], 200, csrf_token(), csrf_hash());
            } else {
                return ErrorResponse("some erroe occuring", true, [], [], 200, csrf_token(), csrf_hash());
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Featured_sections.php - update_featured_section()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function change_order()
    {
        try {
            $ids = json_decode($_POST['ids']);

            $update = [];
            $db      = \Config\Database::connect();
            $builder = $db->table('sections');
            foreach ($ids as $key => $id) {
                $update = [
                    'id' => $id,
                    'rank' => ($key + 1)
                ];
                $builder->update($update, ['id' => $id]);
            }
            return successResponse("Featured Section order set successfully", false, [], [], 200, csrf_token(), csrf_hash());
        } catch (\Exception $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Featured_sections.php - change_order()');
            return ErrorResponse("Something went wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
}
