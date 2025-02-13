<?php

namespace App\Controllers\admin;

use App\Models\Service_model;
use Config\ApiResponseAndNotificationStrings;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Services extends Admin
{
    public $validation, $db, $ionAuth, $creator_id, $service;
    public function __construct()
    {
        parent::__construct();
        $this->service = new Service_model();
        $this->validation = \Config\Services::validation();
        $this->db = \Config\Database::connect();
        $this->ionAuth = new \IonAuth\Libraries\IonAuth();
        $this->creator_id = $this->userId;
        $this->superadmin = $this->session->get('email');
        $this->trans = new ApiResponseAndNotificationStrings();
        
        helper('ResponceServices');
    }
    public function index()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            setPageInfo($this->data, 'Services | Admin Panel', 'services');
            $this->data['categories_name'] = fetch_details('categories', [], ['id', 'name', 'parent_id']);
            $this->data['categories_tree'] = $this->getCategoriesTree();
            $partner_data = $this->db->table('users u')
                ->select('u.id,u.username,pd.company_name,pd.number_of_members')
                ->join('partner_details pd', 'pd.partner_id = u.id')
                ->where('is_approved', '1')
                ->get()->getResultArray();
            $this->data['partner_name'] = $partner_data;
            $tax_data = fetch_details('taxes', ['status' => '1'], ['id', 'title', 'percentage']);
            $this->data['tax_data'] = $tax_data;
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Services.php - index()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    function getCategoriesTree()
    {
        try {
            $categories = $this->db->table('categories')->get()->getResultArray();
            $tree = [];
            foreach ($categories as $category) {
                if (!$category['parent_id']) {
                    $tree[] = $this->buildTree($categories, $category);
                }
            }
            return $tree;
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Services.php - getCategoriesTree()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    function buildTree(&$categories, $currentCategory)
    {
        try {
            $tree = [
                'id' => $currentCategory['id'],
                'text' => $currentCategory['name'],
            ];
            $children = [];
            foreach ($categories as $category) {
                if ($category['parent_id'] == $currentCategory['id']) {
                    $children[] = $this->buildTree($categories, $category);
                }
            }
            if (!empty($children)) {
                $tree['children'] = $children;
            }
            return $tree;
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Services.php - buildTree()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function list($from_app = false, $search = '', $limit = 10, $offset = 0, $sort = 'id', $order = 'ASC', $where = [], $additional_data = [], $column_name = '', $whereIn = [])
    {

        try {
            $Service_model = new Service_model();
            $limit = (isset($_GET['limit']) && !empty($_GET['limit'])) ? $_GET['limit'] : 10;
            $offset = (isset($_GET['offset']) && !empty($_GET['offset'])) ? $_GET['offset'] : 0;
            $sort = (isset($_GET['sort']) && !empty($_GET['sort'])) ? $_GET['sort'] : 'id';
            $order = (isset($_GET['order']) && !empty($_GET['order'])) ? $_GET['order'] : 'ASC';
            $search = (isset($_GET['search']) && !empty($_GET['search'])) ? $_GET['search'] : '';
            $data = $Service_model->list(false, $search, $limit, $offset, $sort, $order);

            return $data;
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Services.php - list()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function add_service()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            $result = checkModificationInDemoMode($this->superadmin);
            if ($result !== true) {
                return $this->response->setJSON($result);
            }
            if (isset($_POST) && !empty($_POST)) {
                $price = $this->request->getPost('price');
                $this->validation->setRules(
                    [
                        'partner' => [
                            "rules" => 'required',
                            "errors" => [
                                "required" => "Please select provider"
                            ]
                        ],
                        'title' => [
                            "rules" => 'required',
                            "errors" => [
                                "required" => "Please enter service title"
                            ]
                        ],
                        'categories' => [
                            "rules" => 'required',
                            "errors" => [
                                "required" => "Please select category"
                            ]
                        ],
                        'tags' => [
                            "rules" => 'required',
                            "errors" => [
                                "required" => "Please enter service tag"
                            ]
                        ],
                        'description' => [
                            "rules" => 'required',
                            "errors" => [
                                "required" => "Please enter description"
                            ]
                        ],
                        'long_description' => [
                            "rules" => 'required',
                            "errors" => [
                                "required" => "Please enter long description"
                            ]
                        ],
                        'price' => [
                            "rules" => 'required|numeric',
                            "errors" => [
                                "required" => "Please enter price",
                                "numeric" => "Please enter numeric value for price"
                            ]
                        ],
                        'discounted_price' => [
                            "rules" => 'required|numeric|less_than[' . $price . ']',
                            "errors" => [
                                "required" => "Please enter discounted price",
                                "numeric" => "Please enter numeric value for discounted price",
                                "less_than" => "Discounted price should be less than price"
                            ]
                        ],
                        'members' => [
                            "rules" => 'required|numeric',
                            "errors" => [
                                "required" => "Please enter required member for service",
                                "numeric" => "Please enter numeric value for required member"
                            ]
                        ],
                        'duration' => [
                            "rules" => 'required|numeric',
                            "errors" => [
                                "required" => "Please enter duration to perform task",
                                "numeric" => "Please enter numeric value for duration of task"
                            ]
                        ],
                        'max_qty' => [
                            "rules" => 'required|numeric',
                            "errors" => [
                                "required" => "Please enter max quantity allowed for services",
                                "numeric" => "Please enter numeric value for max quantity allowed for services"
                            ]
                        ],
                    ],
                );
                if (!$this->validation->withRequest($this->request)->run()) {
                    $errors  = $this->validation->getErrors();
                    return ErrorResponse($errors, true, [], [], 200, csrf_token(), csrf_hash());
                }
                if (isset($_POST['tags'][0]) && !empty($_POST['tags'][0])) {
                    $base_tags = $this->request->getPost('tags');
                    $s_t = $base_tags;
                    $val = explode(',', str_replace(']', '', str_replace('[', '', $s_t[0])));
                    $tags = [];
                    foreach ($val as $s) {
                        $tags[] = json_decode($s, true)['value'];
                    }
                } else {
                    return ErrorResponse("Tags required!", true, [], [], 200, csrf_token(), csrf_hash());
                }
                $title = $this->removeScript($this->request->getPost('title'));
                $description = $this->removeScript($this->request->getPost('description'));
                $path = "public/uploads/services/";
                if ($this->request->getVar('service_id')) {
                    $data = fetch_details('services', ['id' => $this->request->getVar('service_id')], ['image', 'other_images', 'files']);
                }
                if (!is_dir(FCPATH . 'public/uploads/services/')) {
                    if (!mkdir(FCPATH . 'public/uploads/services/', 0775, true)) {
                        return ErrorResponse("Failed to create folders", true, [], [], 200, csrf_token(), csrf_hash());
                    }
                }
                $uploadedFiles = $this->request->getFiles('filepond');
                if (!empty($uploadedFiles)) {
                    $imagefile = $uploadedFiles['service_image_selector'];
                    $files_selector = [];
                    $main_image_name = "";
                    if ($imagefile->isValid()) {
                        $name = $imagefile->getRandomName();
                        $service_image_full_path = 'public/uploads/services/' . $name;
                        $tempPath = $imagefile->getTempName();
                        compressImage($tempPath, $service_image_full_path, 70);
                        $main_image_name = 'public/uploads/services/' . $name;
                    } else if ($imagefile->getSize() == 0) {
                        if ($this->request->getVar('service_id') && !empty($data)) {
                            $main_image_name = $data[0]['image'];
                        }
                    }
                }
                if (!empty($uploadedFiles)) {
                    $imagefile = $uploadedFiles['files'];

                    $files_selector = [];
                    if ($imagefile[0]->getSize() == 0) {
                        if ($this->request->getVar('service_id') && !empty($data)) {
                            $files = ['files' => $data[0]['files']];
                        }
                    } else {
                        foreach ($imagefile as $key => $img) {
                            if ($img->isValid()) {
                                $name = $img->getName();
                                $name = str_replace([' ', '_', '@', '#', '$', '%'], '-', $name);
                                if ($img->move($path, $name)) {
                                    $image_name = $name;
                                    $files_selector[$key] = "public/uploads/services/" . $image_name;
                                }
                            }
                        }
                        $files = ['files' => !empty($files_selector) ? json_encode($files_selector) : "",];
                    }
                }
                if (!empty($uploadedFiles)) {


                    $imagefile = $uploadedFiles['other_service_image_selector'];
                    $other_service_image_selector = [];
                    if ($imagefile[0]->getSize() == 0) {
                        if ($this->request->getVar('service_id') && !empty($data)) {
                            $other_images = ['other_images' => $data[0]['other_images']];
                        } else {
                            $other_images = ['other_images' => !empty($other_service_image_selector) ? json_encode($other_service_image_selector) : "",];
                        }
                    } else {
                        foreach ($imagefile as $key => $img) {
                            if ($img->isValid()) {
                                $name = $img->getRandomName();
                                $full_path = "public/uploads/services/"  . $name;
                                $tempPath = $img->getTempName();
                                compressImage($tempPath, $full_path, 70);
                                $other_service_image_selector[$key] = "public/uploads/services/" . $name;
                            }
                        }
                        $other_images = ['other_images' => !empty($other_service_image_selector) ? json_encode($other_service_image_selector) : "",];
                    }
                }

                $category_id = $this->request->getPost('categories');
                $discounted_price = $this->request->getPost('discounted_price');
                if ($discounted_price >= $price && $discounted_price == $price) {
                    return ErrorResponse("discounted price can not be higher than or equal to the price!", true, [], [], 200, csrf_token(), csrf_hash());
                }
                $user_id = $this->request->getPost('partner');
                $partner_data = fetch_details('partner_details', ['partner_id' => $this->request->getPost('partner')]);
                if ($this->request->getVar('members') > $partner_data[0]['number_of_members']) {
                    return ErrorResponse("Number Of member could not greater than " . $partner_data[0]['number_of_members'], true, [], [], 200, csrf_token(), csrf_hash());
                }
                $faqs = $this->request->getPost('faqs');


                if (!empty($faqs)) {

                    if ($faqs[0][0] == "" && $faqs[0][1] == "") {
                        $empty = [];
                        $faqData = ['faqs' => json_encode($empty)];
                    } else {
                        $faqData = ['faqs' => !empty($faqs) ? json_encode($faqs) : ""];
                    }
                } else {
                    $empty = [];
                    $faqData = ['faqs' => json_encode($empty)];
                }
                $check_payment_gateway = get_settings('payment_gateways_settings', true);
                $cod_setting =  $check_payment_gateway['cod_setting'];
                if ($cod_setting == 1) {
                    $is_pay_later_allowed = ($this->request->getPost('pay_later') == "on") ? 1 : 0;
                } else {
                    $is_pay_later_allowed = 0;
                }
                $is_cancelable = (isset($_POST['is_cancelable'])) ? 1 : 0;
                $service = [
                    'user_id' => $user_id,
                    'category_id' => $category_id,
                    'tax_type' => $this->request->getVar('tax_type'),
                    'tax_id' => $this->request->getVar('tax_id'),
                    'title' => $title,
                    'description' => $description,
                    'slug' => '',
                    'tags' =>  implode(',', $tags),
                    'price' => $price,
                    'discounted_price' => $discounted_price,
                    'image' => $main_image_name,
                    'other_images' => $other_images['other_images'],
                    'number_of_members_required' => $this->request->getVar('members'),
                    'duration' => $this->request->getVar('duration'),
                    'rating' => 0,
                    'number_of_ratings' => 0,
                    'on_site_allowed' => ($this->request->getPost('on_site') == "on") ? 1 : 0,
                    'is_pay_later_allowed' => $is_pay_later_allowed,
                    'is_cancelable' => $is_cancelable,
                    'cancelable_till' => $this->request->getVar('cancelable_till'),
                    'max_quantity_allowed' => $this->request->getPost('max_qty'),
                    'status' => (isset($_POST['status'])) ? 1 : 0,
                    'long_description' => (isset($_POST['long_description'])) ? $_POST['long_description'] : "",
                    'files' => isset($files) ? $files : "",
                    'faqs' => isset($faqData) ? $faqData : "",
                    'at_store' => (isset($_POST['at_store'])) ? 1 : 0,
                    'at_doorstep' => (isset($_POST['at_doorstep'])) ? 1 : 0,
                    'approved_by_admin' => $_POST['approve_service_value'],
                ];
                if ($this->service->save($service)) {
                    return successResponse("Service saved successfully", false, [], [], 200, csrf_token(), csrf_hash());
                } else {
                    return ErrorResponse("Service can not be saved!", true, [], [], 200, csrf_token(), csrf_hash());
                }
            } else {
                return redirect()->to('partner/services');
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Services.php - add_service()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function delete_service()
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
            $old_data = fetch_details('services', ['id' => $id], ['image']);
            if ($old_data[0]['image'] != NULL &&  file_exists($old_data[0]['image'])) {
                unlink($old_data[0]['image']);
            }
            $builder = $this->db->table('services')->delete(['id' => $id]);
            $builder2 = $this->db->table('cart')->delete(['service_id' => $id]);
            $builder3 = $this->db->table('services_ratings')->delete(['service_id' => $id]);
            if ($builder) {
                return successResponse("success in deleting the service", false, [], [], 200, csrf_token(), csrf_hash());
            } else {
                return ErrorResponse("Unsuccessful in deleting services", true, [], [], 200, csrf_token(), csrf_hash());
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Services.php - delete_service()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function update_service()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            $result = checkModificationInDemoMode($this->superadmin);
            if ($result !== true) {
                return $this->response->setJSON($result);
            }
            if (isset($_POST) && !empty($_POST)) {
                $price = $this->request->getPost('price');
                $rules = [
                    'partner' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please select provider"
                        ]
                    ],
                    'title' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please enter service title"
                        ]
                    ],
                    'categories' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please select category"
                        ]
                    ],
                    'tags' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please enter service tag"
                        ]
                    ],
                    'description' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please enter description"
                        ]
                    ],
                    'price' => [
                        "rules" => 'required|numeric',
                        "errors" => [
                            "required" => "Please enter price",
                            "numeric" => "Please enter numeric value for price"
                        ]
                    ],
                    'discounted_price' => [
                        "rules" => 'required|numeric|less_than[' . $price . ']',
                        "errors" => [
                            "required" => "Please enter discounted price",
                            "numeric" => "Please enter numeric value for discounted price",
                            "less_than" => "Discounted price should be less than price"
                        ]
                    ],
                    'members' => [
                        "rules" => 'required|numeric',
                        "errors" => [
                            "required" => "Please enter required member for service",
                            "numeric" => "Please enter numeric value for required member"
                        ]
                    ],
                    'duration' => [
                        "rules" => 'required|numeric',
                        "errors" => [
                            "required" => "Please enter duration to perform task",
                            "numeric" => "Please enter numeric value for duration of task"
                        ]
                    ],
                    'max_qty' => [
                        "rules" => 'required|numeric',
                        "errors" => [
                            "required" => "Please enter max quantity allowed for services",
                            "numeric" => "Please enter numeric value for max quantity allowed for services"
                        ]
                    ],
                ];
                if (isset($_FILES['service_image_selector']) && $_FILES['service_image_selector']['size'] > 0) {
                    $rules['service_image_selector'] = [
                        "rules" => 'uploaded[service_image_selector]|ext_in[service_image_selector,png,jpg,gif,jpeg,webp]|max_size[service_image_selector,8496]|is_image[service_image_selector]'
                    ];
                }
                $this->validation->setRules($rules);
                if (!$this->validation->withRequest($this->request)->run()) {
                    $errors  = $this->validation->getErrors();
                    return ErrorResponse($errors, true, [], [], 200, csrf_token(), csrf_hash());
                }
                $Service_id = $this->request->getPost('service_id');
                $old_files = fetch_details('services', ['id' => $Service_id], ['files'])[0]['files'];
                $old_other_images = fetch_details('services', ['id' => $Service_id], ['other_images'])[0]['other_images'];
                $old_icon = fetch_details('services', ['id' => $Service_id], ['image'])[0]['image'];
                if (isset($_POST['tags'][0]) && !empty($_POST['tags'][0])) {
                    $base_tags = $this->request->getPost('tags');
                    $s_t = $base_tags;
                    $val = explode(',', str_replace(']', '', str_replace('[', '', $s_t[0])));
                    $tags = [];
                    foreach ($val as $s) {
                        $tags[] = json_decode($s, true)['value'];
                    }
                } else {
                    return ErrorResponse("Tags required!", true, [], [], 200, csrf_token(), csrf_hash());
                }
                $title = $this->removeScript($this->request->getPost('title'));
                $description = $this->removeScript($this->request->getPost('description'));
                $path = "public/uploads/services/";
                $image_name = "";
                $og_image = fetch_details('services', ['id' => $Service_id], ['image']);
                $files = fetch_details('services', ['id' => $Service_id], ['files']);
                $other_images = fetch_details('services', ['id' => $Service_id], ['other_images']);
                $faqs = $this->request->getPost('faqs');
                if (!empty($faqs)) {
                    if ($faqs[0][0] == "" && $faqs[0][1] == "") {
                        $empty = [];
                        $faqData = ['faqs' => json_encode($empty)];
                    } else {
                        $faqData = ['faqs' => !empty($faqs) ? json_encode($faqs) : ""];
                    }
                    // $faqData = ['faqs' => !empty($faqs) ? json_encode($faqs) : ""];
                } else {
                    $faqs = [];
                    $faqData = ['faqs' => !empty($faqs) ? json_encode($faqs) : ""];
                }
                $uploadedFiles = $this->request->getFiles('filepond');
                if (!empty($uploadedFiles['service_image_selector_edit']) && $uploadedFiles['service_image_selector_edit']->getError() === UPLOAD_ERR_OK) {
                    $imagefile = $uploadedFiles['service_image_selector_edit'];
                    if ($imagefile->isValid()) {
                        $name = $imagefile->getRandomName();
                        $full_path = "public/uploads/services/"  . $name;
                        $tempPath = $imagefile->getTempName();
                        compressImage($tempPath, $full_path, 70);
                        if (!empty($old_icon) && file_exists(FCPATH . $old_icon)) {
                            unlink(FCPATH . $old_icon);
                        }
                        $image_name = 'public/uploads/services/' . $name;
                    }
                } else {
                    if (isset($og_image) && !empty($og_image)) {
                        $image_name = $og_image['0']['image'];
                    } else {
                        $image_name = NULL;
                    }
                }
                if (!empty($uploadedFiles['other_service_image_selector_edit'][0]) && $uploadedFiles['other_service_image_selector_edit'][0]->getError() === UPLOAD_ERR_OK) {
                    $imagefile = $uploadedFiles['other_service_image_selector_edit'];
                    $other_service_image_selector = [];
                    foreach ($imagefile as $key => $img) {
                        if ($img->isValid()) {
                            $name = $img->getRandomName();
                            $full_path = "public/uploads/services/"  . $name;
                            $tempPath = $img->getTempName();
                            compressImage($tempPath, $full_path, 70);
                            if (!empty($old_other_images)) {
                                $old_other_images_array = json_decode($old_other_images, true);
                                foreach ($old_other_images_array as $old) {
                                    if (file_exists(FCPATH . $old)) {
                                        unlink(FCPATH . $old);
                                    }
                                }
                            }
                            $other_image_name = $name;
                            $other_service_image_selector[$key] = "public/uploads/services/" . $other_image_name;
                        }
                    }
                    $other_images[0] = ['other_images' => !empty($other_service_image_selector) ? json_encode($other_service_image_selector) : "",];
                } else {
                    $other_images = $other_images;
                }
                if (!empty($uploadedFiles['files_edit'][0]) && $uploadedFiles['files_edit'][0]->getError() === UPLOAD_ERR_OK) {
                    $imagefile = $uploadedFiles['files_edit'];
                    $files_selector = [];
                    foreach ($imagefile as $key => $img) {
                        if ($img->isValid()) {
                            $name = $img->getName();
                            $name = str_replace([' ', '_', '@', '#', '$', '%'], '-', $name);
                            if ($img->move($path, $name)) {
                                if (!empty($old_files)) {
                                    $old_files_images_array = json_decode($old_files, true);
                                    foreach ($old_files_images_array as $old) {
                                        if (file_exists(FCPATH . $old)) {
                                            unlink(FCPATH . $old);
                                        }
                                    }
                                }
                                $file_image_name = $name;
                                $files_selector[$key] = "public/uploads/services/" . $file_image_name;
                            }
                        }
                    }
                    $files = ['files' => !empty($files_selector) ? json_encode($files_selector) : ""];
                } else {
                    if (isset($files) && !empty($files)) {
                        $files = $files['0']['files'];
                    } else {
                        $files = NULL;
                    }
                }
                $category_id = $_POST['categories'];
                $discounted_price = $this->request->getPost('discounted_price');
                $price = $this->request->getPost('price');
                if ($discounted_price >= $price && $discounted_price == $price) {
                    return ErrorResponse("discounted price can not be higher than or equal to the price", true, [], [], 200, csrf_token(), csrf_hash());
                }
                $user_id = $this->request->getPost('partner');
                if (isset($_POST['is_cancelable'])) {
                    $is_cancelable = "1";
                } else {
                    $is_cancelable = "0";
                }
                if ($is_cancelable == "1" && $this->request->getVar('cancelable_till') == "") {
                    return ErrorResponse("Please Add Minutes", true, [], [], 200, csrf_token(), csrf_hash());
                }
                $check_payment_gateway = get_settings('payment_gateways_settings', true);
                $cod_setting =  $check_payment_gateway['cod_setting'];
                if ($cod_setting == 1) {
                    $is_pay_later_allowed = ($this->request->getPost('pay_later') == "on") ? 1 : 0;
                } else {
                    $is_pay_later_allowed = 0;
                }
                $service = [
                    'user_id' => $user_id,
                    'category_id' => $category_id,
                    'tax_type' => $this->request->getPost('tax_type'),
                    'tax_id' => $this->request->getPost('tax_id'),
                    'tax' => $this->request->getPost('tax'),
                    'title' => $title,
                    'description' => $description,
                    'slug' => '',
                    'tags' =>  implode(',', $tags),
                    'price' => $price,
                    'discounted_price' => $discounted_price,
                    'image' => $image_name,
                    'other_images' => $other_images[0]['other_images'],
                    'number_of_members_required' => $this->request->getPost('members'),
                    'duration' => $this->request->getPost('duration'),
                    'rating' => 0,
                    'number_of_ratings' => 0,
                    'files' => isset($files) ? $files : "",
                    'is_pay_later_allowed' => $is_pay_later_allowed,
                    'is_cancelable' => $is_cancelable,
                    'cancelable_till' => $this->request->getPost('cancelable_till'),
                    'max_quantity_allowed' => $this->request->getPost('max_qty'),
                    'status' => ($this->request->getPost('status') == "on") ? 1 : 0,
                    'long_description' => (isset($_POST['long_description'])) ? $_POST['long_description'] : "",
                    'faqs' => isset($faqData) ? $faqData : [],
                    'at_store' => (isset($_POST['at_store'])) ? 1 : 0,
                    'at_doorstep' => (isset($_POST['at_doorstep'])) ? 1 : 0,
                    'approved_by_admin' => $_POST['approve_service_value'],
                ];
                if ($this->service->update($Service_id, $service)) {
                    return successResponse("Service saved successfully", false, [], [], 200, csrf_token(), csrf_hash());
                } else {
                    return ErrorResponse("Service can not be Save!", true, [], [], 200, csrf_token(), csrf_hash());
                }
            } else {
                return redirect()->to('partner/services');
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Services.php - update_service()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function edit_service()
    {
        try {
            helper('function');
            $uri = service('uri');
            if ($this->isLoggedIn && $this->userIsAdmin) {
                $service_id = $uri->getSegments()[3];
                setPageInfo($this->data, 'Services | Admin Panel', 'services');
                $this->data['categories_name'] = fetch_details('categories', [], ['id', 'name', 'parent_id']);
                $this->data['service'] = fetch_details('services', ['id' => $service_id])[0];
                $partner_data = $this->db->table('users u')
                    ->select('u.id,u.username,pd.company_name,at_store,at_doorstep,pd.need_approval_for_the_service')
                    ->join('partner_details pd', 'pd.partner_id = u.id')
                    ->where('is_approved', '1')
                    ->get()->getResultArray();
                $this->data['partner_name'] = $partner_data;
                $tax_data = fetch_details('taxes', ['status' => '1'], ['id', 'title', 'percentage']);
                $this->data['tax_data'] = $tax_data;
                $this->data['main_page'] = 'edit_service';
                return view('backend/admin/template', $this->data);
            } else {
                return redirect('admin/login');
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Services.php - edit_service()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function add_service_view()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            $permission = is_permitted($this->creator_id, 'create', 'services');
            if (!$permission) {
                return NoPermission();
            }
            setPageInfo($this->data, 'Add Service | Admin Panel', 'add_service');
            $partner_details = !empty(fetch_details('partner_details', ['partner_id' => $this->userId])) ? fetch_details('partner_details', ['partner_id' => $this->userId])[0] : [];
            $partner_timings = !empty(fetch_details('partner_timings', ['partner_id' => $this->userId])) ? fetch_details('partner_timings', ['partner_id' => $this->userId]) : [];
            $this->data['data'] = fetch_details('users', ['id' => $this->userId])[0];
            $currency = get_settings('general_settings', true);
            if (empty($currency)) {
                $_SESSION['toastMessage'] = 'Please first add currency and basic details in general settings ';
                $_SESSION['toastMessageType'] = 'error';
                $this->session->markAsFlashdata('toastMessage');
                $this->session->markAsFlashdata('toastMessageType');
                return redirect()->to('admin/settings/general-settings')->withCookies();
            }
            $this->data['currency'] = $currency['currency'];
            $this->data['partner_details'] = $partner_details;
            $this->data['partner_timings'] = $partner_timings;
            $this->data['city_name'] = fetch_details('cities', [], ['id', 'name']);
            $this->data['categories_name'] = fetch_details('categories', [], ['id', 'name', 'parent_id']);
            $this->data['categories_tree'] = $this->getCategoriesTree();
            $partner_data = $this->db->table('users u')
                ->select('u.id,u.username,pd.company_name,pd.number_of_members,pd.at_store,pd.at_doorstep,pd.need_approval_for_the_service')
                ->join('partner_details pd', 'pd.partner_id = u.id')
                ->where('is_approved', '1')
                ->get()->getResultArray();
            $this->data['partner_name'] = $partner_data;
            $tax_data = fetch_details('taxes', ['status' => '1'], ['id', 'title', 'percentage']);
            $this->data['tax_data'] = $tax_data;
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Services.php - add_service_view()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function service_detail()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            $uri = service('uri');
            $service_id = $uri->getSegments()[3];
            setPageInfo($this->data, 'Services | Admin Panel', 'service_details');
            $this->data['categories_name'] = fetch_details('categories', [], ['id', 'name']);
            $this->data['categories_tree'] = $this->getCategoriesTree();
            $partner_data = $this->db->table('users u')
                ->select('u.id,u.username,pd.company_name,pd.number_of_members')
                ->join('partner_details pd', 'pd.partner_id = u.id')
                ->where('is_approved', '1')
                ->get()->getResultArray();
            $this->data['partner_name'] = $partner_data;
            $tax_data = fetch_details('taxes', ['status' => '1'], ['id', 'title', 'percentage']);
            $service = fetch_details('services', ['id' => $service_id]);
            $this->data['service'] = $service;
            $this->data['tax_data'] = $tax_data;
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Services.php - service_detail()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function disapprove_service()
    {
        try {
            $result = checkModificationInDemoMode($this->superadmin);
            if ($result !== true) {
                return $this->response->setJSON($result);
            }
            $permission = is_permitted($this->creator_id, 'update', 'services');
            if ($permission) {
                if ($this->isLoggedIn && $this->userIsAdmin) {
                    $partner_id = $this->request->getPost('partner_id');
                    $service_id = $this->request->getPost('service_id');
                    $builder = $this->db->table('services');
                    $service_approval = $builder->set('approved_by_admin', 0)->where('user_id', $partner_id)->where('id', $service_id)->update();
                    $partner_details = fetch_details('partner_details', ['partner_id' => $partner_id])[0];
                    $to_send_id = $partner_id;
                    $builder = $this->db->table('users')->select('fcm_id,email,platform');
                    $users_fcm = $builder->where('id', $to_send_id)->get()->getResultArray();
                    foreach ($users_fcm as $ids) {
                        if ($ids['fcm_id'] != "") {
                            $fcm_ids['fcm_id'] = $ids['fcm_id'];
                            $fcm_ids['platform'] = $ids['platform'];
                            $email = $ids['email'];
                        }
                    }
                    if (!empty($fcm_ids) && check_notification_setting('service_disapproved', 'notification')) {
                        $fcmMsg = array(
                            'content_available' => "true",
                            'title' => $this->trans->serviceRequestRejection,
                            'body' => $this->trans->serviceRequestApprovalRejectedMessage,
                            'type_id' => "$to_send_id",
                            'type' => 'service_request_status',
                            'status' => 'reject',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        );
                        $registrationIDs_chunks = array_chunk($users_fcm, 1000);
                        send_notification($fcmMsg, $registrationIDs_chunks);
                    }
                    if ($service_approval) {
                        if (!empty($users_fcm[0]['email']) && check_notification_setting('service_disapproved', 'email') && is_unsubscribe_enabled($partner_id) == 1) {
                            send_custom_email('service_disapproved', $partner_id, $users_fcm[0]['email']);
                        }
                        if (check_notification_setting('service_disapproved', 'sms')) {
                            send_custom_sms('service_disapproved', $partner_id, $users_fcm[0]['email']);
                        }
                        return successResponse("Service is disapproved", false, [], [], 200, csrf_token(), csrf_hash());
                    } else {
                        return successResponse("Could not disapprove service", false, [$service_approval], [], 200, csrf_token(), csrf_hash());
                    }
                } else {
                    return redirect('admin/login');
                }
            } else {
                return NoPermission();
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Services.php - disapprove_service()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function approve_service()
    {
        try {
            $result = checkModificationInDemoMode($this->superadmin);
            if ($result !== true) {
                return $this->response->setJSON($result);
            }
            $permission = is_permitted($this->creator_id, 'update', 'services');
            if (!$permission) {
                return NoPermission();
            }
            $partner_id = $this->request->getPost('partner_id');
            $service_id = $this->request->getPost('service_id');
            $builder = $this->db->table('services');
            $service_approval = $builder->set('approved_by_admin', 1)->where('user_id', $partner_id)->where('id', $service_id)->update();
            $partner_details = fetch_details('partner_details', ['partner_id' => $partner_id])[0];
            $to_send_id = $partner_id;
            $builder = $this->db->table('users')->select('fcm_id,email,platform');
            $users_fcm = $builder->where('id', $to_send_id)->get()->getResultArray();
            foreach ($users_fcm as $ids) {
                if ($ids['fcm_id'] != "") {
                    $fcm_ids['fcm_id'] = $ids['fcm_id'];
                    $fcm_ids['platform'] = $ids['platform'];
                    $email = $ids['email'];
                }
            }
            if (!empty($fcm_ids) && check_notification_setting('service_approved', 'notification')) {
                $fcmMsg = array(
                    'content_available' => "true",
                    'title' => $this->trans->serviceRequestApproval,
                    'body' => $this->trans->serviceApprovalRequestApprovedMessage,
                    'type' => 'service_request_status',
                    'status' => 'approve',
                    'type_id' => "$to_send_id",
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                );
                $registrationIDs_chunks = array_chunk($users_fcm, 1000);
                send_notification($fcmMsg, $registrationIDs_chunks);
            }
            if ($service_approval) {
                if (!empty($users_fcm[0]['email']) && check_notification_setting('service_approved', 'email') && is_unsubscribe_enabled($partner_id) == 1) {
                    send_custom_email('service_approved', $partner_id, $users_fcm[0]['email']);
                }
                if (check_notification_setting('service_approved', 'sms')) {
                    send_custom_sms('service_approved', $partner_id, $users_fcm[0]['email']);
                }
                return successResponse("Service is approved", false, [], [], 200, csrf_token(), csrf_hash());
            } else {
                return successResponse("Could not Approval service", false, [$service_approval], [], 200, csrf_token(), csrf_hash());
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Services.php - approve_service()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function duplicate()
    {
        try {
            helper('function');
            $uri = service('uri');
            if ($this->isLoggedIn && $this->userIsAdmin) {
                $service_id = $uri->getSegments()[3];
                setPageInfo($this->data, 'Services | Admin Panel', 'services');
                $this->data['categories_name'] = fetch_details('categories', [], ['id', 'name', 'parent_id']);
                $this->data['service'] = fetch_details('services', ['id' => $service_id])[0];
                $partner_data = $this->db->table('users u')
                    ->select('u.id,u.username,pd.company_name,at_store,at_doorstep,pd.need_approval_for_the_service')
                    ->join('partner_details pd', 'pd.partner_id = u.id')
                    ->where('is_approved', '1')
                    ->get()->getResultArray();
                $this->data['partner_name'] = $partner_data;
                $tax_data = fetch_details('taxes', ['status' => '1'], ['id', 'title', 'percentage']);
                $this->data['tax_data'] = $tax_data;
                $this->data['main_page'] = 'service_clone';
                return view('backend/admin/template', $this->data);
            } else {
                return redirect('admin/login');
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Services.php - duplicate()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function bulk_import_services()
    {
        if ($this->isLoggedIn && $this->userIsAdmin) {
            setPageInfo($this->data, 'Services | Admin Panel', 'bulk_import_services');
            $partner_data = $this->db->table('users u')
                ->select('u.id,u.username,pd.company_name,pd.number_of_members')
                ->join('partner_details pd', 'pd.partner_id = u.id')
                ->where('is_approved', '1')
                ->get()->getResultArray();
            $this->data['partner_name'] = $partner_data;
            return view('backend/admin/template', $this->data);
        } else {
            return redirect('admin/login');
        }
    }
    public function bulk_import_service_upload()
    {
        $result = checkModificationInDemoMode($this->superadmin);
        if ($result !== true) {
            return $this->response->setJSON($result);
        }
        $file = $this->request->getFile('file');
        $filePath = FCPATH . 'public/uploads/service_bulk_upload/';
        if (!is_dir($filePath)) {
            if (!mkdir($filePath, 0775, true)) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'message' => 'Failed to create folders'
                ]);
            }
        }
        $newName = $file->getRandomName();
        $file->move($filePath, $newName);
        $fullPath = $filePath . $newName;
        $spreadsheet = IOFactory::load($fullPath);
        $sheet = $spreadsheet->getActiveSheet();
        $headerRow = $sheet->getRowIterator(1)->current();
        $cellIterator = $headerRow->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        $faqHeaders = [];
        $other_image_Headers = [];
        $FilesHeaders = [];
        $columnIndex = 0;
        $OtherImagecolumnIndex = 0;
        $FilescolumnIndex = 0;
        $headerRow = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1', NULL, TRUE, TRUE, TRUE);
        $headers = explode(',', $headerRow[1]['A']);
        $headers = array_map(function ($header) {
            return trim($header, ' "');
        }, $headers);
        if (!in_array('ID', $headers)) {
            //insert
            //faq
            foreach ($cellIterator as $cell) {
                $header = $cell->getValue();
                if (preg_match('/^faq\[question\]\[(\d+)\]$/', $header, $matches)) {
                    $faqNumber = $matches[1];
                    $faqHeaders[$faqNumber]['question'] = $columnIndex;
                } elseif (preg_match('/^faq\[answer\]\[(\d+)\]$/', $header, $matches)) {
                    $faqNumber = $matches[1];
                    $faqHeaders[$faqNumber]['answer'] = $columnIndex;
                }
                $columnIndex++;
            }
            //other_image
            foreach ($cellIterator as $cell) {
                $header = $cell->getValue();
                if (preg_match('/^Other Image\[(\d+)\]$/', $header, $matches)) {
                    $other_image_number = $matches[1];
                    $other_image_Headers[$other_image_number] = $OtherImagecolumnIndex;
                } elseif (preg_match('/^Other Image\[(\d+)\]$/', $header, $matches)) {
                    $other_image_number = $matches[1];
                    $other_image_Headers[$other_image_number] = $OtherImagecolumnIndex;
                }
                $OtherImagecolumnIndex++;
            }
            //files
            foreach ($cellIterator as $cell) {
                $header = $cell->getValue();
                if (preg_match('/^Files\[(\d+)\]$/', $header, $matches)) {
                    $fileNumber = $matches[1];
                    $FilesHeaders[$fileNumber] = $FilescolumnIndex;
                } elseif (preg_match('/^Files\[(\d+)\]$/', $header, $matches)) {
                    $fileNumber = $matches[1];
                    $FilesHeaders[$fileNumber] = $FilescolumnIndex;
                }
                $FilescolumnIndex++;
            }
            $data = $sheet->toArray();
            array_shift($data);
            $data = array_filter($data, function ($row) {
                return !empty(array_filter($row));
            });
            $services = [];
            foreach ($data as $rowIndex => $row) {
                $provider = fetch_details('partner_details', ['partner_id' => $row[0]]);
                if (empty($provider)) {
                    return ErrorResponse("Provider ID :: " . $row[0] . " not found", true, [], [], 200, csrf_token(), csrf_hash());
                }
                $category = fetch_details('categories', ['id' => $row[1]]);
                if (empty($category)) {
                    return ErrorResponse("Category ID :: " . $row[1] . " not found", true, [], [], 200, csrf_token(), csrf_hash());
                }
                $tax = fetch_details('taxes', ['id' => $row[10]]);
                if (empty($tax)) {
                    return ErrorResponse("Tax ID :: " . $row[10] . " not found", true, [], [], 200, csrf_token(), csrf_hash());
                }
                $faqs = [];
                foreach ($faqHeaders as $faqNumber => $indexes) {
                    $question = isset($row[$indexes['question']]) ? trim($row[$indexes['question']]) : '';
                    $answer = isset($row[$indexes['answer']]) ? trim($row[$indexes['answer']]) : '';
                    if (!empty($question) || !empty($answer)) {
                        $faqs[] = [$question, $answer];
                    }
                }
                $other_images = [];
                foreach ($other_image_Headers as $indexes) {
                    $other_image = isset($row[$indexes]) ? trim($row[$indexes]) : '';
                    if (!empty($other_image)) {
                        copy_image($row[$indexes], '/public/uploads/services/');
                        if (!empty($other_image)) {
                            $other_images[] = $other_image;
                        }
                    }
                }
                $files = [];
                foreach ($FilesHeaders as $indexes) {
                    $file = isset($row[$indexes]) ? trim($row[$indexes]) : '';
                    if (!empty($file)) {
                        copy_image($row[$indexes], '/public/uploads/services/');
                        $files[] = $file;
                    }
                }
                $image = !empty($row[20]) ? copy_image($row[20], '/public/uploads/services/') : "";
                $services[] = [
                    'user_id' => $row[0],
                    'category_id' => $row[1],
                    'title' => $row[2],
                    'tags' => $row[3],
                    'description' => $row[4],
                    'duration' => $row[5],
                    'number_of_members_required' => $row[6],
                    'max_quantity_allowed' => $row[7],
                    'long_description' => $row[8],
                    'tax_type' => $row[9],
                    'tax_id' => $row[10],
                    'price' => $row[11],
                    'discounted_price' => $row[12],
                    'is_cancelable' => $row[13],
                    'cancelable_till' => ($row[13] == 1) ? $row[14] : "",
                    'is_pay_later_allowed' => $row[15],
                    'at_store' => $row[16],
                    'at_doorstep' => $row[17],
                    'status' => $row[18],
                    'approved_by_admin' => ($provider[0]['need_approval_for_the_service'] == "1") ? "0" : "1",
                    'faqs' => json_encode($faqs),
                    'other_images' => json_encode($other_images),
                    'image' => $image,
                    'files' => json_encode($files),
                ];
            }
            $serviceModel = new Service_model();
            foreach ($services as  $service) {
                if (!$serviceModel->insert($service)) {
                    return ErrorResponse("Failed to add service", true, [], [], 200, csrf_token(), csrf_hash());
                }
            }
            return successResponse("Services added successfully", false, [], [], 200, csrf_token(), csrf_hash());
        } else {
            //update
            foreach ($cellIterator as $cell) {
                $header = $cell->getValue();
                if (preg_match('/^faq\[question\]\[(\d+)\]$/', $header, $matches)) {
                    $faqNumber = $matches[1];
                    $faqHeaders[$faqNumber]['question'] = $columnIndex;
                } elseif (preg_match('/^faq\[answer\]\[(\d+)\]$/', $header, $matches)) {
                    $faqNumber = $matches[1];
                    $faqHeaders[$faqNumber]['answer'] = $columnIndex;
                }
                $columnIndex++;
            }
            //other_image
            foreach ($cellIterator as $cell) {
                $header = $cell->getValue();
                if (preg_match('/^Other Image\[(\d+)\]$/', $header, $matches)) {
                    $other_image_number = $matches[1];
                    $other_image_Headers[$other_image_number] = $OtherImagecolumnIndex;
                } elseif (preg_match('/^Other Image\[(\d+)\]$/', $header, $matches)) {
                    $other_image_number = $matches[1];
                    $other_image_Headers[$other_image_number] = $OtherImagecolumnIndex;
                }
                $OtherImagecolumnIndex++;
            }
            //files
            foreach ($cellIterator as $cell) {
                $header = $cell->getValue();
                if (preg_match('/^Files\[(\d+)\]$/', $header, $matches)) {
                    $fileNumber = $matches[1];
                    $FilesHeaders[$fileNumber] = $FilescolumnIndex;
                } elseif (preg_match('/^Files\[(\d+)\]$/', $header, $matches)) {
                    $fileNumber = $matches[1];
                    $FilesHeaders[$fileNumber] = $FilescolumnIndex;
                }
                $FilescolumnIndex++;
            }
            $data = $sheet->toArray();
            array_shift($data);
            $data = array_filter($data, function ($row) {
                return !empty(array_filter($row));
            });
            $services = [];
            foreach ($data as $rowIndex => $row) {
                $fetch_service_data = fetch_details('services', ['id' => $row[0]], ['image', 'other_images', 'files']);
                if (!empty($fetch_service_data)) {
                    $other_images = $fetch_service_data[0]['other_images'];
                    $old_other_images = is_string($other_images) ? json_decode($other_images, true) : $other_images;
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $old_other_images = [];
                    }
                }
                if (!empty($fetch_service_data)) {
                    $old_files = $fetch_service_data[0]['files'];
                    $old_files = is_string($old_files) ? json_decode($old_files, true) : $old_files;
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $old_files = [];
                    }
                }
                $provider = fetch_details('partner_details', ['partner_id' => $row[1]]);
                if (empty($provider)) {
                    return ErrorResponse("Provider ID :: " . $row[1] . " not found", true, [], [], 200, csrf_token(), csrf_hash());
                }
                $category = fetch_details('categories', ['id' => $row[2]]);
                if (empty($category)) {
                    return ErrorResponse("Category ID :: " . $row[2] . " not found", true, [], [], 200, csrf_token(), csrf_hash());
                }
                $tax = fetch_details('taxes', ['id' => $row[11]]);
                if (empty($tax)) {
                    return ErrorResponse("Tax ID :: " . $row[11] . " not found", true, [], [], 200, csrf_token(), csrf_hash());
                }
                $faqs = [];
                foreach ($faqHeaders as $faqNumber => $indexes) {
                    $question = isset($row[$indexes['question']]) ? trim($row[$indexes['question']]) : '';
                    $answer = isset($row[$indexes['answer']]) ? trim($row[$indexes['answer']]) : '';
                    if (!empty($question) || !empty($answer)) {
                        $faqs[] = [$question, $answer];
                    }
                }
                $other_images = [];
                foreach ($other_image_Headers as $indexes) {
                    $other_image = isset($row[$indexes]) ? trim($row[$indexes]) : '';
                    if (!empty($other_image) && !in_array($other_image, $old_other_images)) {
                        $oi = copy_image($row[$indexes], '/public/uploads/services/');
                        if (!empty($other_image)) {
                            $other_images[] = $oi;
                        }
                    } else if (!empty($old_other_images)) {
                        $other_images = $old_other_images;
                    } else {
                        $other_images = [];
                    }
                }
                $files = [];
                foreach ($FilesHeaders as $indexes) {
                    $file = isset($row[$indexes]) ? trim($row[$indexes]) : '';
                    if (!empty($file) && !in_array($file, $old_files)) {
                        $oi = copy_image($row[$indexes], '/public/uploads/services/');
                        if (!empty($file)) {
                            $files[] = $oi;
                        }
                    } else if (!empty($old_files)) {
                        $files = $old_files;
                    } else {
                        $files = [];
                    }
                }
                $image = !empty($row[21]) ? copy_image($row[21], '/public/uploads/services/') : "";
                $services[] = [
                    'id' => $row[0],
                    'user_id' => $row[1],
                    'category_id' => $row[2],
                    'title' => $row[3],
                    'tags' => $row[4],
                    'description' => $row[5],
                    'duration' => $row[6],
                    'number_of_members_required' => $row[7],
                    'max_quantity_allowed' => $row[8],
                    'long_description' => $row[9],
                    'tax_type' => $row[10],
                    'tax_id' => $row[11],
                    'price' => $row[12],
                    'discounted_price' => $row[13],
                    'is_cancelable' => $row[14],
                    'cancelable_till' => ($row[14 == 1]) ? $row[15] : "",
                    'is_pay_later_allowed' => $row[16],
                    'at_store' => $row[17],
                    'at_doorstep' => $row[18],
                    'status' => $row[19],
                    'image' => $image,
                    'approved_by_admin' => ($provider[0]['need_approval_for_the_service'] == "1") ? "0" : "1",
                    'faqs' => json_encode($faqs),
                    'other_images' => json_encode($other_images),
                    'files' => json_encode($files),
                ];
            }
            $serviceModel = new Service_model();
            foreach ($services as $service) {
                $id = $service['id'];
                unset($service['id']);
                if (!$serviceModel->update($id, $service)) {
                    return ErrorResponse("Failed to update service", true, [], [], 200, csrf_token(), csrf_hash());
                }
            }
            return successResponse("Services updated successfully", false, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function downloadSampleForInsert()
    {
        $result = checkModificationInDemoMode($this->superadmin);
        if ($result !== true) {
            $_SESSION['toastMessage'] = $result['message'];
            $_SESSION['toastMessageType'] = 'error';
            $this->session->markAsFlashdata('toastMessage');
            $this->session->markAsFlashdata('toastMessageType');
            return redirect()->to('admin/services/bulk_import_services')->withCookies();
        }
        try {
            $headers = [
                'Provider ID',
                'Category ID',
                'Title',
                'Tags',
                'Short Description',
                'Duration to perform task',
                'Members Required to Perform Task',
                'Max Quantity allowed for services',
                'Description',
                'Price Type',
                'Tax ID',
                'Price',
                'Discounted Price',
                'Is Cancelable',
                'Cancelable before',
                'Pay Later Allowed',
                'At Store',
                'At Doorstep',
                'Status',
                'Approve Service',
                'Image',
                'faq[question][1]',
                'faq[answer][1]',
                'faq[question][2]',
                'faq[answer][2]',
                'Other Image[1]',
                'Other Image[2]',
                'Files[1]',
                'Files[2]',
            ];
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \Exception('Failed to open output stream.');
            }
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="service_sample_without_data.csv"');
            fputcsv($output, $headers);
            fclose($output);
            exit;
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Services.php - download-sample-for-insert()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function downloadSampleForUpdate()
    {
        $result = checkModificationInDemoMode($this->superadmin);
        if ($result !== true) {
            $response = [
                'type' => 'error',
                'message' => $result['message']
            ];
            return $this->response->setJSON($response);
        }
        try {
            $headers = [
                'ID',
                'Provider ID',
                'Category ID',
                'Title',
                'Tags',
                'Short Description',
                'Duration to perform task',
                'Members Required to Perform Task',
                'Max Quantity allowed for services',
                'Description',
                'Price Type',
                'Tax ID',
                'Price',
                'Discounted Price',
                'Is Cancelable',
                'Cancelable before',
                'Pay Later Allowed',
                'At Store',
                'At Doorstep',
                'Status',
                'Approve Service',
                'Image'
            ];
            $partners = $this->request->getPost('partners');
            if (!empty($partners)) {
                $services = fetch_details('services', [], [], "", 0, 'id', 'DESC', 'user_id', $partners);
            } else {
                $services = fetch_details('services');
            }
            $all_data = [];
            $max_faqs = 0;
            $max_other_images = 0;
            $max_files = 0;
            foreach ($services as $row) {
                $faqs = json_decode($row['faqs'], true);
                if (is_array($faqs)) {
                    $max_faqs = max($max_faqs, count($faqs));
                }
            }
            for ($i = 1; $i <= $max_faqs; $i++) {
                $headers[] = "faq[question][$i]";
                $headers[] = "faq[answer][$i]";
            }
            foreach ($services as $row) {
                $other_images1 = json_decode($row['other_images'], true);
                if (is_array($other_images1)) {
                    $max_other_images = max($max_other_images, count($other_images1));
                }
            }
            for ($i = 1; $i <= $max_other_images; $i++) {
                $headers[] = "Other Image[$i]";
            }
            foreach ($services as $row) {
                $files1 = json_decode($row['files'], true);
                if (is_array($files1)) {
                    $max_files = max($max_files, count($files1));
                }
            }
            for ($i = 1; $i <= $max_files; $i++) {
                $headers[] = "Files[$i]";
            }
            foreach ($services as $row) {
                $faqs = json_decode($row['faqs'], true);
                $other_images = json_decode($row['other_images'], true);
                $files = json_decode($row['files'], true);
                $rowData = [
                    'ID' => $row['id'],
                    'Provider ID' => $row['user_id'],
                    'Category ID' => $row['category_id'],
                    'Title' => $row['title'],
                    'Tags' => $row['tags'],
                    'Short Description' => $row['description'],
                    'Duration to perform task' => $row['duration'],
                    'Members Required to Perform Task' => $row['number_of_members_required'],
                    'Max Quantity allowed for services' => $row['max_quantity_allowed'],
                    'Description' => strip_tags(htmlspecialchars_decode(stripslashes($row['long_description'])), '<p><br>'),
                    'Price Type' => $row['tax_type'],
                    'Tax ID' => $row['tax_id'],
                    'Price' => $row['price'],
                    'Discounted Price' => $row['discounted_price'],
                    'Is Cancelable' => $row['is_cancelable'],
                    'Cancelable before' => $row['cancelable_till'],
                    'Pay Later Allowed' => $row['is_pay_later_allowed'],
                    'At Store' => $row['at_store'],
                    'At Doorstep' => $row['at_doorstep'],
                    'Status' => $row['status'],
                    'Approve Service' => $row['approved_by_admin'],
                    'Image' => $row['image'],
                ];
                if (is_array($faqs)) {
                    foreach ($faqs as $index => $faq) {
                        $rowData["faq[question][" . ($index + 1) . "]"] = isset($faq[0]) ? $faq[0] : '';
                        $rowData["faq[answer][" . ($index + 1) . "]"] = isset($faq[1]) ? $faq[1] : '';
                    }
                }
                for ($i = count($faqs ?? []); $i < $max_faqs; $i++) {
                    $rowData["faq[question][" . ($i + 1) . "]"] = '';
                    $rowData["faq[answer][" . ($i + 1) . "]"] = '';
                }
                if (is_array($other_images)) {
                    foreach ($other_images as $index => $other_image) {
                        $rowData["Other Image[" . ($index + 1) . "]"] = isset($other_image) ? $other_image : '';
                    }
                }
                for ($i = count($other_images ?? []); $i < $max_other_images; $i++) {
                    $rowData["Other Image[" . ($i + 1) . "]"] = '';
                }
                if (is_array($files)) {
                    foreach ($files as $index => $file) {
                        $rowData["Files[" . ($index + 1) . "]"] = isset($file) ? $file : '';
                    }
                }
                for ($i = count($files ?? []); $i < $max_files; $i++) {
                    $rowData["Files[" . ($i + 1) . "]"] = '';
                }
                $all_data[] = $rowData;
            }
            ob_start();
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);
            foreach ($all_data as $rowData) {
                fputcsv($output, $rowData);
            }
            fclose($output);
            $csvContent = ob_get_clean();
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="service_sample_with_data.csv"');
            echo $csvContent;
            exit;
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Services.php - download-sample-for-insert()');
            header('Content-Type: application/json');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function ServiceAddInstructions()
    {
        try {
            $filePath = (FCPATH . '/public/uploads/site/Service-Add-Instructions.pdf');
            $fileName = 'Service-Add-Instructions.pdf';
            if (file_exists($filePath)) {
                return $this->response->download($filePath, null)->setFileName($fileName);
            } else {
                $_SESSION['toastMessage'] = "Cannot download";
                $_SESSION['toastMessageType'] = 'error';
                $this->session->markAsFlashdata('toastMessage');
                $this->session->markAsFlashdata('toastMessageType');
                return redirect()->to('admin/services/bulk_import_services')->withCookies();
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Services.php - download_service_add_instruction_file()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function ServiceUpdateInstructions()
    {
        try {
            $filePath = (FCPATH . '/public/uploads/site/Service-Update-Instructions.pdf');
            $fileName = 'Service-Update-Instructions.pdf';
            if (file_exists($filePath)) {
                return $this->response->download($filePath, null)->setFileName($fileName);
            } else {
                $_SESSION['toastMessage'] = "Cannot download";
                $_SESSION['toastMessageType'] = 'error';
                $this->session->markAsFlashdata('toastMessage');
                $this->session->markAsFlashdata('toastMessageType');
                return redirect()->to('admin/services')->withCookies();
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Services.php - download_service_add_instruction_file()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
}
