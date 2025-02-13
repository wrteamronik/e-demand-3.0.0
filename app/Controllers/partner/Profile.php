<?php
namespace App\Controllers\partner;
class Profile extends Partner
{
    protected $validationListTemplate = 'list';
    public function __construct()
    {
        parent::__construct();
        helper('ResponceServices');
    }
    public function index()
    {
        if ($this->isLoggedIn) {
            setPageInfo($this->data, 'Profile | Provider Panel', 'profile');
            $partner_details = !empty(fetch_details('partner_details', ['partner_id' => $this->userId])) ? fetch_details('partner_details', ['partner_id' => $this->userId])[0] : [];
            $partner_timings = !empty(fetch_details('partner_timings', ['partner_id' => $this->userId])) ? fetch_details('partner_timings', ['partner_id' => $this->userId]) : [];
            $this->data['data'] = fetch_details('users', ['id' => $this->userId])[0];
            $this->data['partner_details'] = $partner_details;
            $this->data['partner_timings'] = array_reverse($partner_timings);
            $settings = get_settings('general_settings', true);
            $user_id = $this->ionAuth->getUserId();
            $admin_commission = fetch_details('partner_details', ['partner_id' => $user_id], 'admin_commission');
            $this->data['city_id']  = fetch_details('users', ['id' => $user_id], 'city')[0]['city'];
            $this->data['city'] = $this->data['city_id'];
            $this->data['admin_commission'] = $admin_commission[0]['admin_commission'];
            $this->data['currency'] = $settings['currency'];
            $this->data['city_name'] = $this->data['city_id'];
            
            $this->data['allow_pre_booking_chat'] = $settings['allow_pre_booking_chat'] ?? 0;
            $this->data['allow_post_booking_chat'] = $settings['allow_post_booking_chat'] ?? 0;

            return view('backend/partner/template', $this->data);
        } else {
            return redirect('partner/login');
        }
    }
    public function update_profile()
    {
        try {
            if (isset($_POST) && !empty($_POST)) {
                try {
                    $config = new \Config\IonAuth();
                    $tables  = $config->tables;
                    $this->validation->setRules(
                        [
                            'username' => [
                                "rules" => 'required|trim',
                                "errors" => [
                                    "required" => "Please enter username"
                                ]
                            ],
                            'email' => [
                                "rules" => 'required|trim',
                                "errors" => [
                                    "required" => "Please enter provider's email",
                                ]
                            ],
                            'phone' => [
                                "rules" => 'required|numeric|',
                                "errors" => [
                                    "required" => "Please enter admin's phone number",
                                    "numeric" => "Please enter numeric phone number",
                                    "is_unique" => "This phone number is already registered"
                                ]
                            ],
                            'address' => [
                                "rules" => 'required|trim',
                                "errors" => [
                                    "required" => "Please enter address",
                                ]
                            ],
                            'latitude' => [
                                "rules" => 'required|trim',
                                "errors" => [
                                    "required" => "Please choose provider location",
                                ]
                            ],
                            'longitude' => [
                                "rules" => 'required|trim',
                                "errors" => [
                                    "required" => "Please choose provider location",
                                ]
                            ],
                            'type' => [
                                "rules" => 'required',
                                "errors" => [
                                    "required" => "Please select provider's type",
                                ]
                            ],
                            'visiting_charges' => [
                                "rules" => 'required|numeric',
                                "errors" => [
                                    "required" => "Please enter visiting charges",
                                    "numeric" => "Please enter numeric value for visiting charges"
                                ]
                            ],
                            'advance_booking_days' => [
                                "rules" => 'required|numeric',
                                "errors" => [
                                    "required" => "Please enter advance booking days",
                                    "numeric" => "Please enter numeric advance booking days"
                                ]
                            ],
                            'start_time' => [
                                "rules" => 'required',
                                "errors" => [
                                    "required" => "Please enter provider's working days",
                                ]
                            ],
                            'end_time' => [
                                "rules" => 'required',
                                "errors" => [
                                    "required" => "Please enter provider's working properly ",
                                ]
                            ],
                          
                        ],
                    );
                    if (!$this->validation->withRequest($this->request)->run()) {
                        $errors = $this->validation->getErrors();
                        return ErrorResponse($errors, true, [], [], 200, csrf_token(), csrf_hash());
                    } else {
                        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                            $response['error'] = true;
                            $response['message'] = DEMO_MODE_ERROR;
                            $response['csrfName'] = csrf_token();
                            $response['csrfHash'] = csrf_hash();
                            return $this->response->setJSON($response);
                        }
                        $data = fetch_details('users', ['id' => $this->userId], 'image')[0];
                        $IdProofs = fetch_details('partner_details', ['partner_id' => $this->userId], ['national_id', 'address_id', 'passport', 'banner'])[0];
                        $old_image = $data['image'];
                        $old_banner = $IdProofs['banner'];
                        $old_national_id = $IdProofs['national_id'];
                        $old_address_id = $IdProofs['address_id'];
                        $old_passport = $IdProofs['passport'];
                        $old_other_images = fetch_details('partner_details', ['partner_id' => $this->userId], ['other_images']);
                        if (!empty($_FILES['image']) && isset($_FILES['image'])) {
                            $file =  $this->request->getFile('image');
                            $path =  './public/backend/assets/profile/';
                            $path_db =  'public/backend/assets/profile/';
                            if ($file->isValid()) {
                                if ($file->move($path)) {
                                    if (!empty($old_image)) {
                                        if (file_exists($old_image) && !empty($old_image))
                                            unlink(FCPATH . $old_image);
                                    }
                                    $image = $path_db . $file->getName();
                                }
                            } else {
                                $image = $old_image;
                            }
                        } else {
                            $image = $old_image;
                        }
                        if (!empty($_FILES['banner']) && isset($_FILES['banner'])) {
                            $file =  $this->request->getFile('banner');
                            $path =  './public/backend/assets/banner/';
                            $path_db =  'public/backend/assets/banner/';
                            if ($file->isValid()) {
                                if ($file->move($path)) {
                                    if (!empty($old_banner)) {
                                        if (file_exists($old_banner) && !empty($old_banner))
                                            unlink(FCPATH . $old_banner);
                                    }
                                    $banner = $path_db . $file->getName();
                                }
                            } else {
                                $banner = $old_banner;
                            }
                        } else {
                            $banner = $old_banner;
                        }
                        if (!empty($_FILES['national_id']) && isset($_FILES['national_id'])) {
                            $file =  $this->request->getFile('national_id');
                            $path =  './public/backend/assets/national_id/';
                            $path_db =  'public/backend/assets/national_id/';
                            if ($file->isValid()) {
                                if ($file->move($path)) {
                                    if (!empty($old_national_id)) {
                                        if (file_exists($old_national_id) && !empty($old_national_id))
                                            unlink($old_national_id);
                                    }
                                    $national_id = $path_db . $file->getName();
                                }
                            } else {
                                $national_id = $old_national_id;
                            }
                        } else {
                            $national_id = $old_national_id;
                        }
                        if (!empty($_FILES['address_id']) && isset($_FILES['address_id'])) {
                            $file =  $this->request->getFile('address_id');
                            $path =  './public/backend/assets/address_id/';
                            $path_db =  'public/backend/assets/address_id/';
                            if ($file->isValid()) {
                                if ($file->move($path)) {
                                    if (!empty($old_address_id)) {
                                        if (file_exists($old_address_id) && !empty($old_address_id))
                                            unlink($old_address_id);
                                    }
                                    $address_id = $path_db . $file->getName();
                                }
                            } else {
                                $address_id = $old_address_id;
                            }
                        } else {
                            $address_id = $old_address_id;
                        }
                        if (!empty($_FILES['passport']) && isset($_FILES['passport'])) {
                            $file =  $this->request->getFile('passport');
                            $path =  './public/backend/assets/passport/';
                            $path_db =  'public/backend/assets/passport/';
                            if ($file->isValid()) {
                                if ($file->move($path)) {
                                    if (!empty($old_passport)) {
                                        if (file_exists($old_passport) && !empty($old_passport))
                                            unlink($old_passport);
                                    }
                                    $passport = $path_db . $file->getName();
                                }
                            } else {
                                $passport = $old_passport;
                            }
                        } else {
                            $passport = $old_passport;
                        }
                        $uploadedFiles = $this->request->getFiles('filepond');
                        $old_other_images = fetch_details('partner_details', ['partner_id' =>  $this->userId], ['other_images']);
                        $path = "public/uploads/partner/";
                        if (!empty($uploadedFiles['other_service_image_selector_edit'][0]) && $uploadedFiles['other_service_image_selector_edit'][0]->getError() === UPLOAD_ERR_OK) {
                            $imagefile = $uploadedFiles['other_service_image_selector_edit'];
                            $other_service_image_selector = [];
                            foreach ($imagefile as $key => $img) {
                                if ($img->isValid()) {
                                    $name = $img->getRandomName();
                                    if ($img->move($path, $name)) {
                                        if (!empty($old_other_images)) {
                                            $old_other_images_array = is_string($old_other_images) ? json_decode($old_other_images, true) : $old_other_images;
                                        }
                                        $other_image_name = $name;
                                        $other_service_image_selector[$key] = "public/uploads/partner/" . $other_image_name;
                                    }
                                }
                            }
                            $other_images[0] = ['other_images' => !empty($other_service_image_selector) ? json_encode($other_service_image_selector) : ""];
                        } else {
                            $other_images[0]['other_images'] = ($old_other_images);
                        }
                        $partnerIDS = [
                            'address_id' => $address_id,
                            'national_id' => $national_id,
                            'passport' => $passport,
                            'banner' => $banner,
                        ];
                        if ($partnerIDS) {
                            update_details($partnerIDS, ['partner_id' => $this->userId], 'partner_details', false);
                        }
                        $userData = [
                            'username' => $this->request->getPost('username'),
                            'email' => $this->request->getPost('email'),
                            'phone' => $this->request->getPost('phone'),
                            'image' => $image,
                            'latitude' => $this->request->getPost('latitude'),
                            'longitude' => $this->request->getPost('longitude'),
                            'city' => $this->request->getPost('city'),
                        ];
                        if ($userData) {
                            update_details($userData, ['id' => $this->userId], 'users');
                        }
                        $partner_details = [
                            'company_name' => $this->request->getPost('company_name'),
                            'type' => $this->request->getPost('type'),
                            'visiting_charges' => $this->request->getPost('visiting_charges'),
                            'about' => $this->request->getPost('about'),
                            'advance_booking_days' => $this->request->getPost('advance_booking_days'),
                            'bank_name' => $this->request->getPost('bank_name'),
                            'account_number' => $this->request->getPost('account_number'),
                            'account_name' => $this->request->getPost('account_name'),
                            'account_name' => $this->request->getPost('account_name'),
                            'bank_code' => $this->request->getPost('bank_code'),
                            'tax_name' => $this->request->getPost('bank_code'),
                            'tax_number' => $this->request->getPost('tax_number'),
                            'swift_code' => $this->request->getPost('swift_code'),
                            'number_of_members' => $this->request->getPost('number_of_members'),
                            'long_description' => (isset($_POST['long_description'])) ? $_POST['long_description'] : "",
                            'address' => $this->request->getPost('address'),
                            'at_store' => (isset($_POST['at_store'])) ? 1 : 0,
                            'at_doorstep' => (isset($_POST['at_doorstep'])) ? 1 : 0,
                            'chat' => (isset($_POST['chat'])) ? 1 : 0,
                            'pre_chat' => (isset($_POST['pre_chat'])) ? 1 : 0,
                        ];
                        if ($partner_details) {
                            update_details($partner_details, ['partner_id' => $this->userId], 'partner_details', false);
                        }
                        $days = [
                            0 => 'monday',
                            1 => 'tuesday',
                            2 => 'wednesday',
                            3 => 'thursday',
                            4 => 'friday',
                            5 => 'saturday',
                            6 => 'sunday'
                        ];
                        for ($i = 0; $i < count($_POST['start_time']); $i++) {
                            $partner_timing = [];
                            $partner_timing['day'] = $days[$i];
                            if (isset($_POST['start_time'][$i])) {
                                $partner_timing['opening_time'] = $_POST['start_time'][$i];
                            }
                            if (isset($_POST['end_time'][$i])) {
                                $partner_timing['closing_time'] = $_POST['end_time'][$i];
                            }
                            $partner_timing['is_open'] = (isset($_POST[$days[$i]])) ? 1 : 0;
                            $timing_data = fetch_details('partner_timings', ['partner_id' => $this->userId, 'day' => $days[$i]]);
                            if (count($timing_data) > 0) {
                                update_details($partner_timing, ['partner_id' => $this->userId, 'day' => $days[$i]], 'partner_timings');
                            } else {
                                $partner_timing['partner_id'] = $this->userId;
                                insert_details($partner_timing, 'partner_timings');
                            }
                        }
                        return successResponse("Profile updated successfully!", false, [], [], 200, csrf_token(), csrf_hash());
                    }
                } catch (\Throwable $th) {
                    throw $th;
                }
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/Profile.php - update_profile()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function update()
    {
        try {
            $national_id = $this->request->getFile('national_id');
            $address_id = $this->request->getFile('address_id');
            $passport = $this->request->getFile('passport');
            if ($this->isLoggedIn) {
                if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                    $response['error'] = true;
                    $response['message'] = DEMO_MODE_ERROR;
                    $response['csrfName'] = csrf_token();
                    $response['csrfHash'] = csrf_hash();
                    return $this->response->setJSON($response);
                }
                if ($this->request->getFile('national_id') && !empty($this->request->getFile('national_id'))) {
                    $file = $this->request->getFile('national_id');
                    if (!$file->isValid()) {
                        return ErrorResponse("Something went wrong please try after some time", true, [], [], 200, csrf_token(), csrf_hash());
                    }
                    $type = $file->getMimeType();
                    if ($type == 'image/jpeg' || $type == 'image/png' || $type == 'image/jpg') {
                        $path = FCPATH . 'public/backend/assets/kyc-details/';
                        if (!empty($check_image)) {
                            $image_name = $check_image[0]['image'];
                            unlink($path . '' . $image_name);
                        }
                        $image = $file->getName();
                        $newName = $file->getRandomName();
                        $file->move($path, $newName);
                        $data['national_id'] =  $newName;
                    } else {
                        return ErrorResponse("Please attach a valid image file.", true, [], [], 200, csrf_token(), csrf_hash());
                    }
                }
                if ($this->request->getFile('address_id') && !empty($this->request->getFile('address_id'))) {
                    $file = $this->request->getFile('address_id');
                    if (!$file->isValid()) {
                        return ErrorResponse("Something went wrong please try after some time.", true, [], [], 200, csrf_token(), csrf_hash());
                    }
                    $type = $file->getMimeType();
                    if ($type == 'image/jpeg' || $type == 'image/png' || $type == 'image/jpg') {
                        $path = FCPATH . 'public/backend/assets/kyc-details/';
                        if (!empty($check_image)) {
                            $image_name = $check_image[0]['image'];
                            unlink($path . '' . $image_name);
                        }
                        $image = $file->getName();
                        $newName = $file->getRandomName();
                        $file->move($path, $newName);
                        $data['address_id'] =  $newName;
                    } else {
                        return ErrorResponse("Please attach a valid image file.", true, [], [], 200, csrf_token(), csrf_hash());
                    }
                }
                if ($this->request->getFile('passport') && !empty($this->request->getFile('passport'))) {
                    $file = $this->request->getFile('passport');
                    if (!$file->isValid()) {
                        return ErrorResponse("Something went wrong please try after some time.", true, [], [], 200, csrf_token(), csrf_hash());
                    }
                    $type = $file->getMimeType();
                    if ($type == 'image/jpeg' || $type == 'image/png' || $type == 'image/jpg') {
                        $path = FCPATH . 'public/backend/assets/kyc-details/';
                        if (!empty($check_image)) {
                            $image_name = $check_image[0]['image'];
                            unlink($path . '' . $image_name);
                        }
                        $image = $file->getName();
                        $newName = $file->getRandomName();
                        $file->move($path, $newName);
                        $data['passport'] =  $newName;
                    } else {
                        return ErrorResponse("Please attach a valid image file.", true, [], [], 200, csrf_token(), csrf_hash());
                    }
                }
                if (isset($_POST['bank_name']) && !empty($_POST['bank_name'])) {
                    $data['bank_name'] = $_POST['bank_name'];
                }
                if (isset($_POST['account_number']) && !empty($_POST['account_number'])) {
                    $data['account_number'] = $_POST['account_number'];
                }
                if (isset($_POST['account_name']) && !empty($_POST['account_name'])) {
                    $data['account_name'] = $_POST['account_name'];
                }
                if (isset($_POST['bank_code']) && !empty($_POST['bank_code'])) {
                    $data['bank_code'] = $_POST['bank_code'];
                }
                if (isset($_POST['advance_booking_days']) && !empty($_POST['advance_booking_days'])) {
                    $data['advance_booking_days'] = $_POST['advance_booking_days'];
                }
                if (isset($_POST['type']) && !empty($_POST['type'])) {
                    $data['type'] = $_POST['type'];
                }
                if (isset($_POST['visiting_charges']) && !empty($_POST['visiting_charges'])) {
                    $data['visiting_charges'] = $_POST['visiting_charges'];
                }
                $days = [
                    0 => 'monday',
                    1 => 'tuesday',
                    2 => 'wednsday',
                    3 => 'thursday',
                    4 => 'friday',
                    5 => 'staturday',
                    6 => 'sunday'
                ];
                for ($i = 0; $i < count($_POST['start_time']); $i++) {
                    $partner_timing = [];
                    $partner_timing['day'] = $days[$i];
                    if (isset($_POST['start_time'][$i])) {
                        $partner_timing['opening_time'] = $_POST['start_time'][$i];
                    }
                    if (isset($_POST['end_time'][$i])) {
                        $partner_timing['closing_time'] = $_POST['end_time'][$i];
                    }
                    $partner_timing['is_open'] = (isset($_POST[$days[$i]])) ? 1 : 0;
                    if (exists(['partner_id' => $this->userId, 'day' => $days[$i]], 'partner_timings')) {
                        update_details($partner_timing, ['partner_id' => $this->userId, 'day' => $days[$i]], 'partner_timings');
                    } else {
                        $partner_timing['partner_id'] = $this->userId;
                        insert_details($partner_timing, 'partner_timings');
                    }
                }
                if (exists(['partner_id' => $this->userId], 'partner_details')) {
                    update_details($data, ['partner_id' => $this->userId], 'partner_details');
                } else {
                    $data['partner_id'] = $this->userId;
                    insert_details($data, 'partner_details');
                }
                $data = [
                    'username' => $_POST['username'],
                    'email' => $_POST['email'],
                    'phone' => $_POST['phone'],
                ];
                if ($this->request->getPost('profile')) {
                    $img = $this->request->getPost('profile');
                    $f = finfo_open();
                    $mime_type = finfo_buffer($f, $img, FILEINFO_MIME_TYPE);
                    if ($mime_type != 'text/plain') {
                        $response['error'] = true;
                        return $this->response->setJSON([
                            'csrfName' => csrf_token(),
                            'csrfHash' => csrf_hash(),
                            'error' => true,
                            'message' => "Please Insert valid image",
                            "data" => []
                        ]);
                    }
                    $data_photo = $img;
                    $img_dir = './public/backend/assets/profiles/';
                    list($type, $data_photo) = explode(';', $data_photo);
                    list(, $data_photo) = explode(',', $data_photo);
                    $data_photo = base64_decode($data_photo);
                    $filename = microtime(true) . '.jpg';
                    if (!is_dir($img_dir)) {
                        mkdir($img_dir, 0777, true);
                    }
                    if (file_put_contents($img_dir . $filename, $data_photo)) {
                        $profile = $filename;
                        $data['image'] = $filename;
                        $old_image = fetch_details('users', ['id' => $this->userId], ['image']);
                        if ($old_image[0]['image'] != "") {
                            if (is_readable("public/backend/assets/profiles/" . $old_image[0]['image']) && unlink("public/backend/assets/profiles/" . $old_image[0]['image'])) {
                            }
                        }
                    } else {
                        $data['image'] = $this->input->post('old_profile');
                        $profile = $this->input->post('old_profile');
                    }
                }
                $status = update_details(
                    $data,
                    ['id' => $this->userId],
                    'users'
                );
                if ($status) {
                    if (isset($_POST['old']) && isset($_POST['new']) && ($_POST['new'] != "") && ($_POST['old'] != "")) {
                        $identity = $this->session->get('identity');
                        $change = $this->ionAuth->changePassword($identity, $this->request->getPost('old'), $this->request->getPost('new'), $this->userId);
                        if ($change) {
                            $this->ionAuth->logout();
                            return successResponse("User updated successfully", false, $_POST, [], 200, csrf_token(), csrf_hash());
                        } else {
                            return ErrorResponse("Old password did not matched.", true, [], [], 200, csrf_token(), csrf_hash());
                        }
                    }
                    return successResponse("User updated successfully", false, $_POST, [], 200, csrf_token(), csrf_hash());
                } else {
                    return ErrorResponse("Something went wrong...", true, [], [], 200, csrf_token(), csrf_hash());
                }
            } else {
                return ErrorResponse("unauthorized", true, [], [], 200, csrf_token(), csrf_hash());
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/Profile.php - update()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
}
