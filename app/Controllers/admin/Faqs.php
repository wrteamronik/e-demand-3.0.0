<?php

namespace App\Controllers\admin;

use App\Models\Faqs_model;

class Faqs extends Admin
{
    public   $validation, $faqs, $creator_id;
    public function __construct()
    {
        parent::__construct();
        helper(['form', 'url']);
        $this->faqs = new Faqs_model();
        $this->validation = \Config\Services::validation();
        $this->creator_id = $this->userId;
        $this->superadmin = $this->session->get('email');
        helper('ResponceServices');
    }
    public function index()
    {
        if (!$this->isLoggedIn || !$this->userIsAdmin) {
            return redirect('admin/login');
        }
        setPageInfo($this->data, 'FAQs | Admin Panel', 'faqs');
        $this->data['faqs'] = fetch_details('faqs');
        return view('backend/admin/template', $this->data);
    }
    public function add_faqs()
    {
        try {
            $result = checkModificationInDemoMode($this->superadmin);
            if ($result !== true) {
                return $this->response->setJSON($result);
            }
            $permission = is_permitted($this->creator_id, 'create', 'faq');
            if (!$permission) {
                return NoPermission();
            }
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('unauthorised');
            }
            $this->validation->setRules(
                [
                    'english_question' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please enter question in English for FAQ"
                        ]
                    ],
                    'russian_question' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please enter question in Russian for FAQ"
                        ]
                    ],
                    'estonian_question' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please enter question in Estonian for FAQ"
                        ]
                    ],
                    'english_answer' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please enter answer in English for FAQ",
                        ]
                    ],
                    'russian_answer' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please enter answer in Russian for FAQ",
                        ]
                    ],
                    'estonian_answer' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please enter answer in Estonian for FAQ",
                        ]
                    ],
                ],
            );
            if (!$this->validation->withRequest($this->request)->run()) {
                $errors  = $this->validation->getErrors();
                return ErrorResponse($errors, true, [], [], 200, csrf_token(), csrf_hash());
            }

            $english_question = trim($_POST['english_question']);
            $russian_question = trim($_POST['russian_question']);
            $estonian_question = trim($_POST['estonian_question']);

            $english_answer = ($_POST['english_answer']);
            $russian_answer = ($_POST['russian_answer']);
            $estonian_answer = ($_POST['estonian_answer']);

            $data['english_question'] = $english_question;
            $data['russian_question'] = $russian_question;
            $data['estonian_question'] = $estonian_question;

            $data['english_answer'] = $english_answer;
            $data['russian_answer'] = $russian_answer;
            $data['estonian_answer'] = $estonian_answer;

            if ($this->faqs->save($data)) {
                return successResponse("Faq added successfully", false, [], [], 200, csrf_token(), csrf_hash());
            } else {
                return ErrorResponse("please try again....", true, [], [], 200, csrf_token(), csrf_hash());
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Faqs.php - add_faqs()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function list($from_app = false, $search = '', $limit = 10, $offset = 0, $sort = 'id', $order = 'ASC', $where = [])
    {
        try {
            $db      = \Config\Database::connect();
            $builder = $db->table('faqs');
            $sortable_fields = ['id' => 'id', 'question' => 'question', 'answer' => 'answer'];
            $offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
            $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
            $sort = isset($_GET['sort']) && in_array($_GET['sort'], $sortable_fields) ? $_GET['sort'] : 'id';
            $order = isset($_GET['order']) && in_array($_GET['order'], ['ASC', 'DESC']) ? $_GET['order'] : 'ASC';
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $builder->select(' COUNT(id) as `total` ');
            $multipleWhere = $search ? ['`id`' => $search, '`question`' => $search, '`answer`' => $search, '`status`' => $search] : '';
            if ($multipleWhere) {
                $builder->orWhere($multipleWhere);
            }
            if ($where) {
                $builder->where($where);
            }
            $offer_count = $builder->get()->getRowArray();
            $total = $offer_count['total'];
            $builder->select();
            if ($multipleWhere) {
                $builder->orLike($multipleWhere);
            }
            if ($where) {
                $builder->where($where);
            }
            $offer_recored = $builder->orderBy($sort, $order)->limit($limit, $offset)->get()->getResultArray();
            $bulkData = array();
            $bulkData['total'] = $total;
            $rows = array();
            foreach ($offer_recored as $row) {
                $operations = '<div class="dropdown">
                    <a class="" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <button class="btn btn-secondary   btn-sm px-3"> <i class="fas fa-ellipsis-v "></i></button>
                    </a><div class="dropdown-menu" aria-labelledby="dropdownMenuLink">';
                $operations .= '<a class="dropdown-item edit_faqs " data-id="' . $row['id'] . '"  data-toggle="modal" data-target="#update_modal" onclick="faqs_id(this)"><i class="fa fa-pen mr-1 text-primary"></i> Edit</a>';
                $operations .= '<a class="dropdown-item remove_faqs" data-id="' . $row['id'] . '" onclick="faqs_id(this)" data-toggle="modal" data-target="#delete_modal" title = "Delete the Faqs"> <i class="fa fa-trash text-danger mr-1"></i> Delete</a>';
                $operations .= '</div></div>';
                $tempRow['id'] = $row['id'];
                $tempRow['english_answer'] = $row['english_answer'];
                $tempRow['russian_answer'] = $row['russian_answer'];
                $tempRow['estonian_answer'] = $row['estonian_answer'];
                $tempRow['created_at'] = format_date($row['created_at'], 'd-m-Y');
                $tempRow['english_question'] = $row['english_question'];
                $tempRow['russian_question'] = $row['russian_question'];
                $tempRow['estonian_question'] = $row['estonian_question'];
                $tempRow['operations'] = $operations;
                $rows[] = $tempRow;
            }
            $bulkData['rows'] = $rows;
            return json_encode($bulkData);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . ' --> app/Controllers/admin/Faqs.php - list()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function remove_faqs()
    {
        try {
            $result = checkModificationInDemoMode($this->superadmin);
            if ($result !== true) {
                return $this->response->setJSON($result);
            }
            $permission = is_permitted($this->creator_id, 'delete', 'faq');
            if (!$permission) {
                return NoPermission();
            }
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('unauthorised');
            }
            $id = $this->request->getPost('id');
            $db = \Config\Database::connect();
            $builder = $db->table('faqs');
            if ($builder->delete(['id' => $id])) {
                return successResponse("FAQ deleted successfully", false, [], [], 200, csrf_token(), csrf_hash());
            } else {
                return ErrorResponse("An error occurred during deleting this item", true, [], [], 200, csrf_token(), csrf_hash());
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Faqs.php - remove_faqs()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function edit_faqs()
    {
        try {
            $result = checkModificationInDemoMode($this->superadmin);
            if ($result !== true) {
                return $this->response->setJSON($result);
            }
            $permission = is_permitted($this->creator_id, 'update', 'faq');
            if (!$permission) {
                return NoPermission();
            }
            $this->validation->setRules(
                [
                    'english_question' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please enter question in English for FAQ"
                        ]
                    ],
                    'russian_question' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please enter question in Russian for FAQ"
                        ]
                    ],
                    'estonian_question' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please enter question in Estonian for FAQ"
                        ]
                    ],
                    'english_answer' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please enter answer in English for FAQ",
                        ]
                    ],
                    'russian_answer' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please enter answer in Russian for FAQ",
                        ]
                    ],
                    'estonian_answer' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please enter answer in Estonian for FAQ",
                        ]
                    ],
                ],
            );
            if (!$this->validation->withRequest($this->request)->run()) {
                return ErrorResponse($this->validation->getErrors(), true, [], [], 200, csrf_token(), csrf_hash());
            }
            $db = \Config\Database::connect();
            $builder = $db->table('faqs');
            if ($this->isLoggedIn && $this->userIsAdmin) {
                $id = $this->request->getPost('id');

                $english_question = $this->request->getPost('english_question');
                $russian_question = $this->request->getPost('russian_question');
                $estonian_question = $this->request->getPost('estonian_question');

                $english_answer = $this->request->getPost('english_answer');
                $russian_answer = $this->request->getPost('russian_answer');
                $estonian_answer = $this->request->getPost('estonian_answer');


                $old_data = fetch_details('faqs', ['id' => $id]);


                $data['english_question'] = $english_question;
                $data['russian_question'] = $russian_question;
                $data['estonian_question'] = $estonian_question;

                $data['english_answer'] = $english_answer;
                $data['russian_answer'] = $russian_answer;
                $data['estonian_answer'] = $estonian_answer;

                if ($builder->update($data, ['id' => $id])) {
                    return successResponse("FAQ updated successfully", false, [], [], 200, csrf_token(), csrf_hash());
                } else {
                    return ErrorResponse("Some error occurred", true, [], [], 200, csrf_token(), csrf_hash());
                }
            } else {
                return redirect('unauthorised');
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Faqs.php - edit_faqs()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
}
