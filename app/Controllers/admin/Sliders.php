<?php
namespace App\Controllers\admin;
use App\Models\Slider_model;
class Sliders extends Admin
{
    public $sliders, $creator_id;
    public function __construct()
    {
        parent::__construct();
        $this->sliders = new Slider_model();
        $this->creator_id = $this->userId;
        $this->db = \Config\Database::connect();
        $this->validation = \Config\Services::validation();
        $this->superadmin = $this->session->get('email');
        helper('ResponceServices');
    }
    public function index()
    {
        if (!$this->isLoggedIn || !$this->userIsAdmin) {
            return redirect('admin/login');
        }
        setPageInfo($this->data, 'Sliders  | Admin Panel', 'sliders');
        $this->data['categories_name'] = fetch_details('categories', [], ['id', 'name']);
        $this->data['provider_title'] = fetch_details('partner_details', [], ['id', 'partner_id', 'company_name']);
        $this->data['services_title'] = $this->db->table('services s')
            ->select('s.id,s.title')
            ->join('users u', 's.user_id = u.id')
            ->where('status', '1')
            ->get()->getResultArray();
        return view('backend/admin/template', $this->data);
    }
    public function add_slider()
    {
        try {
            if (!checkModificationInDemoMode($this->superadmin)) {
                return $this->response->setJSON(checkModificationInDemoMode($this->superadmin));
            }
            if (!is_permitted($this->creator_id, 'create', 'sliders')) {
                return NoPermission();
            }
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            $type = $this->request->getPost('type');
            $common_rules = [
                'app_image' => [
                    "rules" => 'uploaded[app_image]',
                    "errors" => [
                        "uploaded" => "The app_image field is required.",
                    ]
                ],
                'web_image' => [
                    "rules" => 'uploaded[web_image]',
                    "errors" => [
                        "uploaded" => "The web_image field is required.",
                    ]
                ]
            ];
            if ($type == "Category" || $type == "provider" || $type == "url" || $type == "typeurl") {
                $specific_rule = '';
                $specific_error = '';
                $string = "";
                if ($type == "Category") {
                    $specific_rule = 'Category_item';
                    $specific_error = 'category';
                    $string = 'select';
                } elseif ($type == "provider") {
                    $specific_rule = 'service_item';
                    $specific_error = 'provider';
                    $string = 'select';
                } elseif ($type == "url") {
                    $specific_rule = 'url';
                    $specific_error = 'url';
                    $string = 'add';
                }
                $specific_rules = [
                    $specific_rule => [
                        "rules" => 'required',
                        "errors" => ["required" => "Please $string $specific_error"]
                    ]
                ];
            } else {
                $specific_rules = [
                    'type' => [
                        "rules" => 'required',
                        "errors" => ["required" => "Please select type of slider"]
                    ]
                ];
            }
            $validation_rules = array_merge($common_rules, $specific_rules);
            $this->validation->setRules($validation_rules);
            if (!$this->validation->withRequest($this->request)->run()) {
                $errors  = $this->validation->getErrors();
                return ErrorResponse($errors, true, [], [], 200, csrf_token(), csrf_hash());
            }
            $t = time();
            $name = $this->request->getPost('type');
            $url = "";
            $app_image = $this->request->getFile('app_image');
            $web_image = $this->request->getFile('web_image');
            if ($name == "Category") {
                $id = $this->request->getPost('Category_item');
            } else if ($name == "provider") {
                $id = $this->request->getPost('service_item');
            } else if ($name == "url") {
                $url = $this->request->getPost('url');
                $id = "000";
            } else {
                $id = "000";
            }
            $app_image_ext = $app_image->getExtension();
            $app_image_name = $t . '_app.' . $app_image_ext; // For the app image
            $web_image_ext = $web_image->getExtension();
            $web_image_name = $t . '_web.' . $web_image_ext; // For the web image
            $data['type'] = $name;
            $data['type_id'] = $id;
            $data['app_image'] = $app_image_name;
            $data['web_image'] = $web_image_name;
            $data['status'] = (isset($_POST['slider_switch'])) ? 1 : 0;
            $data['url'] = $url;
            if (!is_dir(FCPATH . 'public/uploads/sliders/')) {
                if (!mkdir(FCPATH . 'public/uploads/sliders/', 0775, true)) {
                    return ErrorResponse("Failed to create folders", true, [], [], 200, csrf_token(), csrf_hash());
                }
            }
            if ($this->sliders->save($data)) {
                $app_full_path = "public/uploads/sliders/"  . $app_image_name;
                $app_tempPath = $app_image->getTempName();
                compressImage($app_tempPath, $app_full_path, 70);
                $web_full_path = "public/uploads/sliders/"  . $web_image_name;
                $web_tempPath = $web_image->getTempName();
                compressImage($web_tempPath, $web_full_path, 70);
                return successResponse("slider added successfully", false, [], [], 200, csrf_token(), csrf_hash());
            } else {
                return ErrorResponse("some error occurred", true, [], [], 200, csrf_token(), csrf_hash());
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Sliders.php - add_slider()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function list()
    {
        $limit = (isset($_GET['limit']) && !empty($_GET['limit'])) ? $_GET['limit'] : 10;
        $offset = (isset($_GET['offset']) && !empty($_GET['offset'])) ? $_GET['offset'] : 0;
        $sort = (isset($_GET['sort']) && !empty($_GET['sort'])) ? $_GET['sort'] : 'id';
        $order = (isset($_GET['order']) && !empty($_GET['order'])) ? $_GET['order'] : 'ASC';
        $search = (isset($_GET['search']) && !empty($_GET['search'])) ? $_GET['search'] : '';
        print_r($this->sliders->list(false, $search, $limit, $offset, $sort, $order));
    }
    public function update_slider()
    {
        try {
            $result = checkModificationInDemoMode($this->superadmin);
            if ($result !== true) {
                return $this->response->setJSON($result);
            }
            $permission = is_permitted($this->creator_id, 'update', 'sliders');
            if ($permission) {
                if ($this->isLoggedIn && $this->userIsAdmin) {
                    $type = $this->request->getPost('type_1');
                    $common_rules = [];
                    // if ($type == "Category" || $type == "services") {
                    //     $specific_rule = ($type == "Category") ? 'Category_item_1' : 'service_item_1';
                    //     $specific_error = ($type == "Category") ? 'category' : 'service';
                    //     $common_rules = [
                    //         $specific_rule => [
                    //             "rules" => 'required',
                    //             "errors" => ["required" => "Please select $specific_error"]
                    //         ]
                    //     ];
                    // } else {
                    //     $common_rules = [
                    //         'type_1' => [
                    //             "rules" => 'required',
                    //             "errors" => ["required" => "Please select type of slider"]
                    //         ]
                    //     ];
                    // }
                    if ($type == "Category" || $type == "services" || $type == "url" || $type == "typeurl") {
                        $specific_rule = '';
                        $specific_error = '';
                        $string = "";
                        if ($type == "Category") {
                            $specific_rule = 'Category_item_1';
                            $specific_error = 'category';
                            $string = 'select';
                        } elseif ($type == "provider") {
                            $specific_rule = 'service_item_1';
                            $specific_error = 'service';
                            $string = 'select';
                        } elseif ($type == "url") {
                            $specific_rule = 'url';
                            $specific_error = 'url';
                            $string = 'add';
                        }
                        $specific_rules = [
                            $specific_rule => [
                                "rules" => 'required',
                                "errors" => ["required" => "Please $string $specific_error"]
                            ]
                        ];
                    } else {
                        $specific_rules = [
                            'type_1' => [
                                "rules" => 'required',
                                "errors" => ["required" => "Please select type of slider"]
                            ]
                        ];
                    }
                    // $validation_rules = array_merge($common_rules, $specific_rules);
                    // $this->validation->setRules($common_rules);
                    // if (!$this->validation->withRequest($this->request)->run()) {
                    //     $errors  = $this->validation->getErrors();
                    //     return ErrorResponse($errors, true, [], [], 200, csrf_token(), csrf_hash());
                    // }
                    $validation_rules = array_merge($common_rules, $specific_rules);
                    $this->validation->setRules($validation_rules);
                    if (!$this->validation->withRequest($this->request)->run()) {
                        $errors  = $this->validation->getErrors();
                        return ErrorResponse($errors, true, [], [], 200, csrf_token(), csrf_hash());
                    }
                    $id = $this->request->getPost('id');
                    $name = $this->request->getPost('type_1');
                    $old_data = fetch_details('sliders', ['id' => $id]);
                    $old_app_image = $old_data[0]['app_image'];
                    $old_web_image = $old_data[0]['web_image'];
                    $url = "";
                    if ($name == "Category") {
                        $type_id = $this->request->getPost('Category_item_1');
                    } else if ($name == "provider") {
                        $type_id = $this->request->getPost('service_item_1');
                    } else if ($name == "url") {
                        $url = $this->request->getPost('url');
                        $type_id = "000";
                    } else {
                        $type_id = "000";
                    }
                    $app_image = $this->request->getFile('app_image');
                    $app_image_name = ($app_image->getName() == "") ? $old_app_image :  $app_image->getName();
                    $web_image = $this->request->getFile('web_image');
                    $web_image_name = ($web_image->getName() == "") ? $old_web_image :  $web_image->getName();
                    $folders = [
                        'public/uploads/sliders/' => "Failed to create sliders folders",
                    ];
                    foreach ($folders as $path => $errorMessage) {
                        if (!create_folder($path)) {
                            return ErrorResponse($errorMessage, true, [], [], 200, csrf_token(), csrf_hash());
                        }
                    }
                    $data['type'] = $name;
                    $data['type_id'] = $type_id;
                    $data['app_image'] = $app_image_name;
                    $data['web_image'] = $web_image_name;
                    $data['status'] = (isset($_POST['edit_slider_switch'])) ? 1 : 0;
                    $data['url'] = $url;
                    $old_app_image_path = "public/uploads/sliders/" . $old_app_image;
                    $old_web_image_path = "public/uploads/sliders/" . $old_web_image;
                    // print_R($data);
                    // die;
                    $upd =  $this->sliders->update($id, $data);
                    if ($upd) {
                        if ($app_image->getName() == "" && $web_image->getName() == "") {
                            return successResponse("slider updated successfully", false, [], [], 200, csrf_token(), csrf_hash());
                        } else {

                           
                        
                            if($app_image->getName()!= ""){
                                $full_path = "public/uploads/sliders/"  . $app_image_name;
                                $tempPath = $app_image->getTempName();
                                compressImage($tempPath, $full_path, 70);
                                if (file_exists(FCPATH . $old_app_image_path)) {
                                    unlink(FCPATH . $old_app_image_path);
                                }
    
                            }
                          

                            if($web_image->getName() != ""){

                                $full_path = "public/uploads/sliders/"  . $web_image_name;
                                $tempPath = $web_image->getTempName();
                                compressImage($tempPath, $full_path, 70);

                                
                            if (file_exists(FCPATH . $old_web_image_path)) {
                                unlink(FCPATH . $old_web_image_path);
                            }
                            }
                            return successResponse("slider updated successfully", false, [], [], 200, csrf_token(), csrf_hash());
                        }
                    }
                } else {
                    return redirect('admin/login');
                }
            } else {
                return NoPermission();
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Sliders.php - update_slider()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function delete_sliders()
    {
        try {
            $result = checkModificationInDemoMode($this->superadmin);
            if ($result !== true) {
                return $this->response->setJSON($result);
            }
            $permission = is_permitted($this->creator_id, 'delete', 'sliders');
            if ($permission) {
                if ($this->isLoggedIn && $this->userIsAdmin) {
                    $db      = \Config\Database::connect();
                    $id = $this->request->getPost('user_id');
                    $old_data = fetch_details('sliders', ['id' => $id]);
                    $old_path = "";
                    if (!empty($old_data)) {
                        $app_old_image = $old_data[0]['app_image'];
                        $app_old_path = "public/uploads/sliders/" . $app_old_image;
                        $web_old_image = $old_data[0]['web_image'];
                        $web_old_path = "public/uploads/sliders/" . $web_old_image;
                    }
                    $builder = $db->table('sliders');
                    if ($builder->delete(['id' => $id])) {
                        if (!empty($old_data)) {
                            unlink($app_old_path);
                            unlink($web_old_path);
                        }
                        return successResponse("Successfully deleted", false, [], [], 200, csrf_token(), csrf_hash());
                    } else {
                        return ErrorResponse("some error occrured", true, [], [], 200, csrf_token(), csrf_hash());
                    }
                } else {
                    return redirect('admin/login');
                }
            } else {
                return NoPermission();
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Sliders.php - delete_sliders()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
}
