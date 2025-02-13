<?php

namespace App\Controllers\admin;

use App\Models\Category_model;
use App\Models\Service_model;

class Categories extends Admin
{
    public $category,  $validation;
    public function __construct()
    {
        parent::__construct();
        $this->category = new Category_model();
        $this->validation = \Config\Services::validation();
        $this->service = new Service_model();
        $this->superadmin = $this->session->get('email');
        helper('ResponceServices');
    }
    public function index()
    {
        if (!$this->isLoggedIn || !$this->userIsAdmin) {
            return redirect('admin/login');
        }
        setPageInfo($this->data, 'Categories | Admin Panel', 'categories');
        $this->data['categories'] = fetch_details('categories', [], ['id', 'name']);
        $this->data['parent_categories'] = fetch_details('categories', ['parent_id' => '0'], ['id', 'name']);
        return view('backend/admin/template', $this->data);
    }
    public function add_category()
    {

        if (!$this->isLoggedIn || !$this->userIsAdmin) {
            return redirect('admin/login');
        }
        $result = checkModificationInDemoMode($this->superadmin);
        if ($result !== true) {
            return $this->response->setJSON($result);
        }
        try {
            $rules = [
                'name' => [
                    "rules" => 'required|trim',
                    "errors" => [
                        "required" => "Please enter name for category"
                    ]
                ],
                'russian_name' => [
                    "rules" => 'required|trim',
                    "errors" => [
                        "required" => "Please enter Russian name for category"
                    ]
                ],
                'estonian_name' => [
                    "rules" => 'required|trim',
                    "errors" => [
                        "required" => "Please enter Estonian name for category"
                    ]
                ],
                'image' => [
                    "rules" => 'uploaded[image]',
                    "errors" => [
                        "uploaded" => "Please select an image",

                    ]
                ],
            ];
            $type = $this->request->getPost('make_parent');
            if (isset($type) && $type == "1") {
                $rules['parent_id'] = [
                    "rules" => 'required|trim',
                    "errors" => [
                        "required" => "Please select parent category"
                    ]
                ];
            }
            $this->validation->setRules($rules);
            if (!$this->validation->withRequest($this->request)->run()) {
                $errors  = $this->validation->getErrors();
                return ErrorResponse($errors, true, [], [], 200, csrf_token(), csrf_hash());
            }
            $name = trim($_POST['name']);
            $ru_name = trim($_POST['russian_name']);
            $es_name = trim($_POST['estonian_name']);
            $Category_image = $this->request->getFile('image');
            $ext = $Category_image->getExtension();
            $image_name = time() . '.' . $ext;
            $data['name'] = $name;
            $data['russian_name'] = $ru_name;
            $data['estonian_name'] = $es_name;
            $data['image'] = $image_name;
            $data['slug_name'] = slugify($name);
            $data['admin_commission'] = "0";
            $data['parent_id'] = $_POST['parent_id'];
            $data['dark_color'] = $_POST['dark_theme_color'] != "#000000" ? $_POST['dark_theme_color'] : "#2A2C3E";
            $data['light_color'] = $_POST['light_theme_color'] != "#000000" ? $_POST['light_theme_color'] : "#FFFFFF";
            $data['status'] = 1;
            $path = FCPATH . 'public/uploads/categories/';
            if (!create_folder('public/uploads/categories/')) {
                return ErrorResponse("Failed to create folders", true, [], [], 200, csrf_token(), csrf_hash());
            }
            $full_path = $path . $image_name;
            if ($Category_image->isValid() && !$Category_image->hasMoved()) {
                $tempPath = $Category_image->getTempName();
                compressImage($tempPath, $full_path, 70);
            }
            if ($this->category->save($data)) {
                return successResponse("Category added successfully", false, [], [], 200, csrf_token(), csrf_hash());
            } else {
                return ErrorResponse("some error while addding category", true, [], [], 200, csrf_token(), csrf_hash());
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Categories.php - add_category()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
        $creator_id = $this->userId;
        $permission = is_permitted($creator_id, 'create', 'categories');
        if (!$permission) {
            return NoPermission();
        }
    }
    public function list()
    {
        try {
            $limit = (isset($_GET['limit']) && !empty($_GET['limit'])) ? $_GET['limit'] : 10;
            $offset = (isset($_GET['offset']) && !empty($_GET['offset'])) ? $_GET['offset'] : 0;
            $sort = (isset($_GET['sort']) && !empty($_GET['sort'])) ? $_GET['sort'] : 'id';
            $order = (isset($_GET['order']) && !empty($_GET['order'])) ? $_GET['order'] : 'ASC';
            $search = (isset($_GET['search']) && !empty($_GET['search'])) ? $_GET['search'] : '';
            $where = [];
            $from_app = false;
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                $where['parent_id'] = $_POST['id'];
                $from_app = true;
            }
            $data = $this->category->list($from_app, $search, $limit, $offset, $sort, $order, $where);
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                if (!empty($data['data'])) {
                    return successResponse("Sub Categories fetched successfully", false, $data['data'], [], 200, csrf_token(), csrf_hash());
                } else {
                    return ErrorResponse("Sub Categories not found on this category", true,  $data['data'], [], 200, csrf_token(), csrf_hash());
                }
            }
            return $data;
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . ' --> app/Controllers/admin/Categories.php - list()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function get_categories()
    {
        try {
            $limit = $_GET['limit'] ?? 10;
            $offset = $_GET['offset'] ?? 0;
            $sort = $_GET['sort'] ?? 'id';
            $order = $_GET['order'] ?? 'ASC';
            $search = $_GET['search'] ?? '';
            $where = [];
            $from_app = false;
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                $where['parent_id'] = $_POST['id'];
                $from_app = true;
            }
            $data = $this->category->list($from_app, $search, $limit, $offset, $sort, $order, $where);
            return $this->response->setJSON($data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Categories.php - get_categories()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function update_category()
    {
        try {
            $result = checkModificationInDemoMode($this->superadmin);
            if ($result !== true) {
                return $this->response->setJSON($result);
            }
            $creator_id = $this->userId;
            $permission = is_permitted($creator_id, 'update', 'categories');
            if (!$permission) {
                return NoPermission();
            }
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            $type = $this->request->getPost('edit_make_parent');
            $rules = [
                'name' => [
                    "rules" => 'required|trim',
                    "errors" => [
                        "required" => "Please enter name for category"
                    ]
                ],
                'russian_name' => [
                    "rules" => 'required|trim',
                    "errors" => [
                        "required" => "Please enter Russian name for category"
                    ]
                ],
                'estonian_name' => [
                    "rules" => 'required|trim',
                    "errors" => [
                        "required" => "Please enter Estonian name for category"
                    ]
                ]
            ];
            if (isset($type) && $type == "1") {
                $rules['edit_parent_id'] = [
                    "rules" => 'required|trim',
                    "errors" =>
                    ["required" => "Please select parent category"]
                ];
            }
            $this->validation->setRules($rules);
            if (!$this->validation->withRequest($this->request)->run()) {
                $errors = $this->validation->getErrors();
                return ErrorResponse($errors, true, [], [], 200, csrf_token(), csrf_hash());
            }
            if (!create_folder('public/uploads/categories/')) {
                return ErrorResponse("Failed to create folders", true, [], [], 200, csrf_token(), csrf_hash());
            }
            $id = $this->request->getPost('id');
            $parent_id = $type == "1" ? $this->request->getPost(('edit_parent_id')) : "0";
            $name = $this->request->getPost('name');
            $russian_name = $this->request->getPost('russian_name');
            $estonian_name = $this->request->getPost('estonian_name');
            $old_data = fetch_details('categories', ['id' => $id]);
            $old_image = $old_data[0]['image'];
            $image = $this->request->getFile('image');
            $image_name = !empty($image) && $image->getName() != "" ? $image->getName() : $old_image;
            $data = [
                'parent_id' => $parent_id,
                'name' => $name,
                'russian_name' => $russian_name,
                'estonian_name' => $estonian_name,
                'admin_commission' => "0",
                'dark_color' => $_POST['edit_dark_theme_color'],
                'light_color' => $_POST['edit_light_theme_color'],
                'status' => 1
            ];
            $old_path = "public/uploads/categories/" . $old_image;
            if (!empty($_FILES['image']) && ($_FILES['image']['name']) != "") {
                if (file_exists($old_path) && !empty($old_path) && ($_FILES['image']['name']) != "") {
                    unlink($old_path);
                }
                $Category_image = $_FILES['image'];
                $path = FCPATH . 'public/uploads/categories/';
                $ext = pathinfo($Category_image['name'], PATHINFO_EXTENSION);
                $image_name = time() . '.' . $ext;
                $full_path = $path . $image_name;
                if (is_uploaded_file($Category_image['tmp_name'])) {
                    $tempPath = $Category_image['tmp_name'];
                    compressImage($tempPath, $full_path, 70);
                }
            } else {
                $image_name = $old_image;
            }
            $data['image'] =  $image_name;
            $upd = $this->category->update($id, $data);
            if ($upd) {
                return successResponse("Category updated successfully", false, [], [], 200, csrf_token(), csrf_hash());
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Categories.php - update_category()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function remove_category()
    {
        try {
            $result = checkModificationInDemoMode($this->superadmin);
            if ($result !== true) {
                return $this->response->setJSON($result);
            }
            $creator_id = $this->userId;
            $permission = is_permitted($creator_id, 'delete', 'categories');
            if (!$permission) {
                return NoPermission();
            }
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            $id = $this->request->getPost('user_id');
            $db = \Config\Database::connect();
            $builder = $db->table('categories');
            $cart_builder = $db->table('cart');
            $icons = fetch_details('categories', ['id' => $id]);
            $subcategories = fetch_details('categories', ['parent_id' => $id], ['id', 'name']);
            $services = fetch_details('services', ['category_id' => $id], ['id']);
            foreach ($subcategories as $sb) {
                $sb['status'] = 0;
                $this->category->update($sb['id'], $sb);
            }
            foreach ($services as $s) {
                $s['status'] = 0;
                $this->service->update($s['id'], $s);
                $cart_builder->delete(['service_id' => $s['id']]);
            }
            $category_image = $icons[0]['image'];
            $path = "public/uploads/categories/" . $category_image;
            if ($builder->delete(['id' => $id])) {
                if (unlink($path)) {
                    return successResponse("Category Removed successfully", false, [], [], 200, csrf_token(), csrf_hash());
                }
            }
            return ErrorResponse("An error occured during deleting this item", true, [], [], 200, csrf_token(), csrf_hash());
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Categories.php - remove_category()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
}
