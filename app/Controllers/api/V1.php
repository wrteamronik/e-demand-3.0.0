<?php

namespace App\Controllers\api;

require_once  'vendor/autoload.php';

use App\Controllers\BaseController;
use App\Libraries\Flutterwave;
use App\Libraries\JWT;
use App\Libraries\Paypal;
use App\Libraries\Paystack;
use App\Libraries\Razorpay;
use App\Models\Addresses_model;
use App\Models\Bookmarks_model;
use App\Models\Category_model;
use App\Models\Faqs_model;
use App\Models\Notification_model;
use App\Models\Orders_model;
use App\Models\Partner_subscription_model;
use App\Models\Partners_model;
use App\Models\Promo_code_model;
use App\Models\Service_model;
use App\Models\Service_ratings_model;
use App\Models\Slider_model;
use App\Models\Transaction_model;
use Config\ApiResponseAndNotificationStrings;
use DateTime;
use Razorpay\Api\Api;

class V1 extends BaseController
{
    protected $request;
    public $bank_transfer, $paytm;
    protected $excluded_routes =
    [
        "/api/v1/index",
        "/api/v1",
        "/api/v1/get_services",
        "/api/v1/manage_user",
        "/api/v1/verify_user",
        "/api/v1/get_sliders",
        "/api/v1/get_categories",
        "/api/v1/get_sub_categories",
        "/api/v1/flutterwave",
        "/api/v1/get_providers",
        "/api/v1/get_home_screen_data",
        "/api/v1/get_settings",
        "/api/v1/get_faqs",
        "/api/v1/get_ratings",
        "/api/v1/provider_check_availability",
        "/api/v1/invoice-download",
        "/api/v1/get_paypal_link",
        "/api/v1/paypal_transaction_webview",
        "/api/v1/app_payment_status",
        "/api/v1/ipn",
        "/api/v1/get-time-slots",
        "/api/v1/get_promo_codes",
        "/api/v1/contact_us_api",
        "/api/v1/search",
        "/api/v1/search_services_providers",
        "/api/v1/capturePayment",
        "/api/v1/verify_otp",
        "/api/v1/paystack_transaction_webview",
        "/api/v1/app_paystack_payment_status",
        "/api/v1/flutterwave_webview",
        "/api/v1/flutterwave_payment_status",
        "/api/v1/resend_otp",
        "/api/v1/get_web_landing_page_settings",
        "/api/v1/get_places_for_app",
        "/api/v1/get_place_details_for_app",
        "/api/v1/get_places_for_web",
        "/api/v1/get_place_details_for_web",
    ];
    private $user_details = [];
    private $allowed_settings = ["general_settings", "terms_conditions", "privacy_policy", "about_us", 'payment_gateways_settings'];
    private $user_data = ['id', 'username', 'phone', 'email', 'fcm_id', 'web_fcm_id', 'image', 'latitude', 'longitude', 'friends_code', 'referral_code', 'city', 'country_code'];
    public function __construct()
    {
        helper('api');
        helper("function");
        helper('ResponceServices');
        $this->paypal_lib = new Paypal();
        $this->request = \Config\Services::request();
        $this->flutterwave = new Flutterwave();
        $this->paystack = new paystack();
        $this->razorpay = new Razorpay();
        $this->JWT = new JWT();
        $current_uri = uri_string();
        if (!in_array($current_uri, $this->excluded_routes)) {
            $token = verify_app_request();
            if ($token['error']) {
                header('Content-Type: application/json');
                http_response_code($token['status']);
                print_r(json_encode($token));
                die();
            }
            $this->user_details = $token['data'];
        } else {
            $token = verify_app_request();
            if (!$token['error'] && isset($token['data']) && !empty($token['data'])) {
                $this->user_details = $token['data'];
            }
        }
        $this->trans = new ApiResponseAndNotificationStrings();
    }
    public function index()
    {
        $response = \Config\Services::response();
        helper("filesystem");
        $response->setHeader('content-type', 'Text');
        return $response->setBody(file_get_contents(base_url('apidocs.txt')));
    }
    public function manage_user()
    {
        try {
            $config = new \Config\IonAuth();
            $validation = \Config\Services::validation();
            $request = \Config\Services::request();
            $identity_column = $config->identity;
            if (isset($_POST['mobile']) && $_POST['mobile'] != '') {
                $identity = $request->getPost('mobile');
                $identity_column = 'phone';
                $validation->setRule('mobile', 'mobile', 'required|numeric');
            } else if (isset($_POST['uid']) && $_POST['uid'] != '') {
                $identity = $request->getPost('uid');
                $identity_column = 'uid';
                $validation->setRule('uid', 'uid', 'required');
            } else {
                $validation->setRule('identity', 'Mobile or uid feild is required', 'required');
            }
            if ($request->getPost('fcm_id')) {
                $validation->setRule('fcm_id', 'FCM ID', 'permit_empty');
            }
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            if (isset($_POST['mobile']) && $_POST['mobile'] != '') {
                if (isset($_POST['country_code']) && $_POST['country_code'] != '') {
                    // $userCheck = fetch_details('users', ['phone' => $_POST['mobile'], 'country_code' => $_POST['country_code']]);
                    $db      = \Config\Database::connect();
                    $builder = $db->table('users u');
                    $builder->select('u.*,ug.group_id')
                        ->join('users_groups ug', 'ug.user_id = u.id')
                        ->where('ug.group_id', 2)
                        ->where(['phone' => $_POST['mobile']])->where(['country_code' => $_POST['country_code']]);
                    $userCheck = $builder->get()->getResultArray();
                } else {
                    // $userCheck = fetch_details('users', ['phone' => $_POST['mobile']]);
                    $db      = \Config\Database::connect();
                    $builder = $db->table('users u');
                    $builder->select('u.*,ug.group_id')
                        ->join('users_groups ug', 'ug.user_id = u.id')
                        ->where('ug.group_id', 2)
                        ->where(['phone' => $_POST['mobile']]);
                    $userCheck = $builder->get()->getResultArray();
                }
            } elseif (isset($_POST['uid']) && $_POST['uid'] != '') {
                $userCheck = fetch_details('users', ['uid' => $_POST['uid']]);
            }
            if (!empty($userCheck)) {
                $user_group = fetch_details('users_groups', ['user_id' => $userCheck[0]['id'], 'group_id' => '2']);
            } else {
                $user_group = [];
            }
            if (!empty($userCheck) && !empty($user_group)) {
                //Login
                if (isset($_POST['mobile']) && $_POST['mobile'] != '') {
                    $identity = $_POST['mobile'];
                    $field = 'phone';
                } elseif (isset($_POST['uid']) && $_POST['uid'] != '') {
                    $identity = $_POST['uid'];
                    $field = 'uid';
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Enter Mobile or uid';
                    return $this->response->setJSON($response);
                }
                $db = \Config\Database::connect();
                $builder = $db->table('users u');
                $data = fetch_details('users', ['id' => $userCheck[0]['id']])[0];
                if (empty($data)) {
                    $response['error'] = true;
                    $response['message'] = 'User not found';
                    return $this->response->setJSON($response);
                }
                $update_data = [];
                $token_data = [];
                if ($fcm_id = $this->request->getPost('fcm_id')) {
                    $update_data['fcm_id'] = $fcm_id;
                }
                if ($latitude = $this->request->getPost('latitude')) {
                    $data['latitude'] = $update_data['latitude'] = $latitude;
                }
                if ($longitude = $this->request->getPost('longitude')) {
                    $data['longitude'] = $update_data['longitude'] = $longitude;
                }
                if ($country_code = $this->request->getPost('country_code')) {
                    $data['country_code'] = $update_data['country_code'] = $country_code;
                }
                if ($web_fcm_id = $this->request->getPost('web_fcm_id')) {
                    $data['web_fcm_id'] = $update_data['web_fcm_id'] = $web_fcm_id;
                }
                if ($platform = $this->request->getPost('platform')) {
                    $data['platform'] = $update_data['platform'] = $platform;
                }
                if ($loginType = $this->request->getPost('loginType')) {
                    $data['loginType'] = $update_data['loginType'] = $loginType;
                }
                if ($countryCodeName = $this->request->getPost('countryCodeName')) {
                    $data['countryCodeName'] = $update_data['countryCodeName'] = $countryCodeName;
                }
                if ($uid = $this->request->getPost('uid')) {
                    $data['uid'] = $update_data['uid'] = $uid;
                }
                if ($email = $this->request->getPost('email')) {
                    $data['email'] = $update_data['email'] = $email;
                }
                $token_data['user_id'] = $data['id'];
                if (isset($_POST['mobile']) && $_POST['mobile'] != '') {
                    update_details($update_data, ['id' => $data['id']], "users", false);
                }
                if (isset($_POST['uid']) && $_POST['uid'] != '') {
                    update_details($update_data, ['id' => $data['id']], "users", false);
                }
                $token_data['token'] = generate_tokens($data['phone'], 2, isset($_POST['uid']) ? $_POST['uid'] : "", $data['loginType']);
                insert_details($token_data, 'users_tokens');
                $data['image'] = isset($data['image']) && !empty($data['image']) ? base_url('public/backend/assets/profiles/' . $data['image']) : "";
                $data = remove_null_values($data);
                $response = [
                    'error' => false,
                    'token' => $token_data['token'],
                    'message' => 'User Logged successfully',
                    'data' => $data,
                ];
                log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Responce => " . $token_data['token'], date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - manage_user()');
                return $this->response->setJSON($response);
            } else {
                //Registration
                $mobile = $this->request->getPost('mobile');
                $uid = $this->request->getPost('uid');
                if (empty($mobile) && empty($uid)) {
                    return response('Mobile number or uid is required');
                }
                $data = [];
                if (!empty($_FILES['image']) && isset($_FILES['image'])) {
                    $file = $this->request->getFile('image');
                    $path = 'public/backend/assets/profiles/';
                    $newName = $file->getRandomName();
                    $file->move($path, $newName);
                    $data['image'] = $path . $newName;
                }
                $data['phone'] = $mobile;
                $data['active'] = 1;
                $data['username'] = $this->request->getPost('username');
                $data['email'] = $this->request->getPost('email');
                $data['fcm_id'] = $this->request->getPost('fcm_id');
                $data['friends_code'] = $this->request->getPost('friends_code');
                $data['referral_code'] = $this->request->getPost('referral_code');
                $data['city'] = $this->request->getPost('city');
                $data['country_code'] = $this->request->getPost('country_code') ?? "";
                $data['uid'] = $uid;
                $data['loginType'] = $this->request->getPost('loginType');
                $data['countryCodeName'] = $this->request->getPost('countryCodeName');
                $data['email'] = $this->request->getPost('email');
                if ($latitude = $this->request->getPost('latitude')) {
                    $data['latitude'] = $latitude;
                }
                if ($longitude = $this->request->getPost('longitude')) {
                    $data['longitude'] = $longitude;
                }
                if ($web_fcm_id = $this->request->getPost('web_fcm_id')) {
                    $data['web_fcm_id'] = $web_fcm_id;
                }
                if ($platform = $this->request->getPost('platform')) {
                    $data['platform'] = $platform;
                }
                if ($insert_user = insert_details($data, 'users')) {
                    if (!exists(["user_id" => $insert_user['id'], "group_id" => 2], 'users_groups')) {
                        $group_data['user_id'] = $insert_user['id'];
                        $group_data['group_id'] = 2;
                        insert_details($group_data, 'users_groups');
                    }
                    $data = fetch_details('users', ['id' => $insert_user['id']])[0];
                    $token = generate_tokens($data['phone'], 2,  isset($_POST['uid']) ? $_POST['uid'] : "",  $data['loginType']);
                    $token_data['user_id'] = $data['id'];
                    $token_data['token'] = $token;
                    if (isset($token_data) && !empty($token_data)) {
                        insert_details($token_data, 'users_tokens');
                    }
                    $response = [
                        'error' => false,
                        "token" => $token,
                        'message' => 'User Registered successfully',
                        'data' => remove_null_values($data),
                    ];
                    log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Responce => " . $token_data['token'], date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - manage_user()');
                    return $this->response->setJSON($response);
                }
                $response['error'] = true;
                $response['message'] = 'Incorrect password !';
                return $this->response->setJSON($response);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - manage_user()');
            return $this->response->setJSON($response);
        }
    }
    public function update_user()
    {
        try {
            helper(['form', 'url']);
            if (!isset($_POST)) {
                $response = [
                    'error' => true,
                    'message' => "Please use Post request",
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $validation = \Config\Services::validation();
            $config = new \Config\IonAuth();
            $tables = $config->tables;
            $validation->setRules(
                [
                    'email' => 'permit_empty|valid_email',
                    'phone' => 'permit_empty|numeric|is_unique[' . $tables['users'] . '.phone]',
                    'username' => 'permit_empty',
                    'referral_code' => 'permit_empty',
                    'friends_code' => 'permit_empty',
                    'city_id' => 'permit_empty',
                    'latitude' => 'permit_empty',
                    'longitude' => 'permit_empty',
                ],
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            //Data
            $arr = [];
            if ($this->request->getPost('username') && !empty($this->request->getPost('username'))) {
                $arr['username'] = $this->request->getPost('username');
            }
            if ($this->request->getPost('email') && !empty($this->request->getPost('email'))) {
                $arr['email'] = $this->request->getPost('email');
            }
            if ($this->request->getPost('mobile') && !empty($this->request->getPost('mobile'))) {
                $arr['phone'] = $this->request->getPost('mobile');
            }
            if ($this->request->getPost('referral_code') && !empty($this->request->getPost('referral_code'))) {
                $arr['referral_code'] = $this->request->getPost('referral_code');
            }
            if ($this->request->getPost('friends_code') && !empty($this->request->getPost('friends_code'))) {
                $arr['friends_code'] = $this->request->getPost('friends_code');
            }
            if ($this->request->getPost('city_id') && !empty($this->request->getPost('city_id'))) {
                $arr['city'] = $this->request->getPost('city_id');
            }
            if ($this->request->getPost('latitude') && !empty($this->request->getPost('latitude'))) {
                $arr['latitude'] = $this->request->getPost('latitude');
            }
            if ($this->request->getPost('longitude') && !empty($this->request->getPost('longitude'))) {
                $arr['longitude'] = $this->request->getPost('longitude');
            }
            $user_id = $this->user_details['id'];
            if (!exists(['id' => $user_id], 'users')) {
                $response = [
                    'error' => true,
                    'message' => 'Invalid User Id',
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            if ($this->request->getFile('image')) {
                $file = $this->request->getFile('image');
                if (!$file->isValid()) {
                    $response = [
                        'error' => true,
                        'message' => 'Something went wrong please try after some time.',
                        'data' => [],
                    ];
                    return $this->response->setJSON($response);
                }
                $type = $file->getMimeType();
                if ($type == 'image/jpeg' || $type == 'image/png' || $type == 'image/jpg') {
                    $path = FCPATH . 'public/backend/assets/profiles/';
                    $check_image = fetch_details('users', ['id' => $this->user_details['id']], 'image');
                    if (!empty($check_image)) {
                        $image_name = $check_image[0]['image'];
                        $profile_image = (file_exists($path . $image_name)) ?
                            $path . $image_name : ((file_exists(FCPATH . $image_name)) ? $image_name : null);
                        if ((!empty($check_image[0]['image']) || $check_image[0]['image'] != '') && !empty($profile_image)) {
                            if (check_exists(base_url('public/backend/assets/profiles/' . $profile_image)) || check_exists(base_url('/public/uploads/users/partners/' . $profile_image)) || check_exists($profile_image)) {
                                unlink($profile_image);
                            }
                        }
                    }
                    $image = $file->getName();
                    $newName = $file->getRandomName();
                    $file->move($path, $newName);
                    $arr['image'] = $newName;
                } else {
                    $response = [
                        'error' => true,
                        'message' => 'Please attach a valid image file.',
                        'data' => [],
                    ];
                    return $this->response->setJSON($response);
                }
            }
            if (!empty($arr)) {
                $status = update_details($arr, ['id' => $user_id], 'users');
                if ($status) {
                    $data = fetch_details('users', ['id' => $user_id])[0];
                    $data['image'] = base_url('public/backend/assets/profiles/' . $data['image']);
                    $response = [
                        'error' => false,
                        'message' => 'User updated successfully.',
                        'data' => remove_null_values($data),
                    ];
                    return $this->response->setJSON($response);
                }
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Please insert any one field to update.',
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - update_user()');
            return $this->response->setJSON($response);
        }
    }
    public function update_fcm()
    {
        try {
            $validation = \Config\Services::validation();
            $request = \Config\Services::request();
            $validation->setRules(
                [
                    'platform' => 'required'
                ],
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $fcm_id = $this->request->getPost('fcm_id');
            $platform = $this->request->getPost('platform');
            if (update_details(['fcm_id' => $fcm_id, 'platform' => $platform], ['id' => $this->user_details['id']], 'users')) {
                return response('fcm id updated succesfully', true, ['fcm_id' => $fcm_id]);
            } else {
                return response();
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - update_fcm()');
            return $this->response->setJSON($response);
        }
    }
    public function get_settings()
    {
        try {
            $validation = \Config\Services::validation();
            $request = \Config\Services::request();
            $variable = (isset($_POST['variable']) && !empty($_POST['variable'])) ? $_POST['variable'] : 'all';
            $setting = array();
            $setting = fetch_details('settings', '', 'variable', '', '', '', 'ASC');
            if (isset($variable) && !empty($variable) && in_array(trim($variable), $this->allowed_settings)) {
                $setting_res[$variable] = get_settings($variable, true);
            } else {
                foreach ($setting as $type) {
                    $notallowed_settings = ["languages", "email_settings", "country_codes", "api_key_settings", "test"];
                    if (!in_array($type['variable'], $notallowed_settings)) {
                        $setting_res[$type['variable']] = get_settings($type['variable'], true);
                    }
                }
            }
            $this->toDateTime = date('Y-m-d H:i');
            $general_settings = $setting_res['general_settings'];
            $this->db = \Config\Database::connect();
            $this->builder = $this->db->table('settings');
            $system_time_zone = isset($setting_res['general_settings']['system_timezone']) ? $setting_res['general_settings']['system_timezone'] : "Asia/Kolkata";
            date_default_timezone_set($system_time_zone);
            $customer_app_maintenance_mode_schedule_date = isset($setting_res['general_settings']['customer_app_maintenance_schedule_date']) ? (explode("to", $setting_res['general_settings']['customer_app_maintenance_schedule_date'])) : null;
            if (!empty($customer_app_maintenance_mode_schedule_date)) {
                $customer_app_maintenance_mode_start_date = isset($customer_app_maintenance_mode_schedule_date[0]) ? $customer_app_maintenance_mode_schedule_date[0] : "";
                $customer_app_maintenance_mode_end_date = isset($customer_app_maintenance_mode_schedule_date[1]) ? $customer_app_maintenance_mode_schedule_date[1] : "";
            } else {
                $customer_app_maintenance_mode_start_date = null;
                $customer_app_maintenance_mode_end_date = null;
            }
            if (isset($setting_res['general_settings']['customer_app_maintenance_mode']) && $setting_res['general_settings']['customer_app_maintenance_mode'] == 1) {
                $today = strtotime(date('Y-m-d H:i'));
                $start_time = strtotime(date('Y-m-d H:i', strtotime($customer_app_maintenance_mode_start_date)));
                $expiry_time = strtotime(date('Y-m-d H:i', strtotime($customer_app_maintenance_mode_end_date)));
                if (($today >= $start_time) && ($today <= $expiry_time)) {
                    $setting_res['general_settings']['customer_app_maintenance_mode'] = "1";
                } else {
                    $setting_res['general_settings']['customer_app_maintenance_mode'] = "0";
                }
            } else {
                $setting_res['general_settings']['customer_app_maintenance_mode'] = "0";
            }
            $setting_res['general_settings']['favicon'] =  isset($setting_res['general_settings']['favicon']) ? base_url("public/uploads/site/" . ($setting_res['general_settings']['favicon'])) : "";
            $setting_res['general_settings']['logo'] =  isset($setting_res['general_settings']['logo']) ? base_url("public/uploads/site/" . ($setting_res['general_settings']['logo'])) : "";
            $setting_res['general_settings']['half_logo'] =  isset($setting_res['general_settings']['half_logo']) ? base_url("public/uploads/site/" . ($setting_res['general_settings']['half_logo'])) : "";
            $setting_res['general_settings']['partner_favicon'] =  isset($setting_res['general_settings']['partner_favicon']) ? base_url("public/uploads/site/" . ($setting_res['general_settings']['partner_favicon'])) : "";
            $setting_res['general_settings']['partner_logo'] =  isset($setting_res['general_settings']['partner_logo']) ? base_url("public/uploads/site/" . ($setting_res['general_settings']['partner_logo'])) : "";
            $setting_res['general_settings']['partner_half_logo'] =  isset($setting_res['general_settings']['partner_half_logo']) ? base_url("public/uploads/site/" . ($setting_res['general_settings']['partner_half_logo'])) : "";
            $provider_app_maintenance_mode_schedule_date = isset($setting_res['general_settings']['provider_app_maintenance_schedule_date']) ? (explode("to", $setting_res['general_settings']['provider_app_maintenance_schedule_date'])) : null;
            if (!empty($provider_app_maintenance_mode_schedule_date)) {
                $provider_app_maintenance_mode_start_date = isset($provider_app_maintenance_mode_schedule_date[0]) ? $provider_app_maintenance_mode_schedule_date[0] : "";
                $provider_app_maintenance_mode_end_date = isset($provider_app_maintenance_mode_schedule_date[1]) ? $provider_app_maintenance_mode_schedule_date[1] : "";
            } else {
                $provider_app_maintenance_mode_start_date = null;
                $provider_app_maintenance_mode_end_date = null;
            }
            if (isset($setting_res['general_settings']['provider_app_maintenance_mode']) && $setting_res['general_settings']['provider_app_maintenance_mode'] == 1) {
                $today = strtotime(date('Y-m-d H:i'));
                $start_time = strtotime(date('Y-m-d H:i', strtotime($provider_app_maintenance_mode_start_date)));
                $expiry_time = strtotime(date('Y-m-d H:i', strtotime($provider_app_maintenance_mode_end_date)));
                if (($today >= $start_time) && ($today <= $expiry_time)) {
                    $setting_res['general_settings']['provider_app_maintenance_mode'] = "1";
                } else {
                    $setting_res['general_settings']['provider_app_maintenance_mode'] = "0";
                }
            } else {
                $setting_res['general_settings']['provider_app_maintenance_mode'] = "0";
            }
            if (isset($setting_res['general_settings']['provider_location_in_provider_details']) && $setting_res['general_settings']['provider_location_in_provider_details'] == 1) {
                $setting_res['general_settings']['provider_location_in_provider_details'] = "1";
            } else {
                $setting_res['general_settings']['provider_location_in_provider_details'] = "0";
            }
            $setting_res['web_settings']['web_logo'] =  isset($setting_res['web_settings']['web_logo']) ? base_url("public/uploads/web_settings/" . ($setting_res['web_settings']['web_logo'])) : "";
            $setting_res['web_settings']['web_favicon'] =  isset($setting_res['web_settings']['web_favicon']) ? base_url("public/uploads/web_settings/" . ($setting_res['web_settings']['web_favicon'])) : "";
            $setting_res['web_settings']['footer_logo'] =  isset($setting_res['web_settings']['footer_logo']) ? base_url("public/uploads/web_settings/" . ($setting_res['web_settings']['footer_logo'])) : "";
            $setting_res['web_settings']['landing_page_logo'] =  isset($setting_res['web_settings']['landing_page_logo']) ? base_url("public/uploads/web_settings/" . ($setting_res['web_settings']['landing_page_logo'])) : "";
            $setting_res['web_settings']['landing_page_backgroud_image'] =  isset($setting_res['web_settings']['landing_page_backgroud_image']) ? base_url("public/uploads/web_settings/" . ($setting_res['web_settings']['landing_page_backgroud_image'])) : "";
            $setting_res['web_settings']['web_half_logo'] =  isset($setting_res['web_settings']['web_half_logo']) ? base_url("public/uploads/web_settings/" . ($setting_res['web_settings']['web_half_logo'])) : "";
            $setting_res['web_settings']['step_1_image'] =  isset($setting_res['web_settings']['step_1_image']) ? base_url("public/uploads/web_settings/" . ($setting_res['web_settings']['step_1_image'])) : "";
            $setting_res['web_settings']['step_2_image'] =  isset($setting_res['web_settings']['step_2_image']) ? base_url("public/uploads/web_settings/" . ($setting_res['web_settings']['step_2_image'])) : "";
            $setting_res['web_settings']['step_3_image'] =  isset($setting_res['web_settings']['step_3_image']) ? base_url("public/uploads/web_settings/" . ($setting_res['web_settings']['step_3_image'])) : "";
            $setting_res['web_settings']['step_4_image'] =  isset($setting_res['web_settings']['step_4_image']) ? base_url("public/uploads/web_settings/" . ($setting_res['web_settings']['step_4_image'])) : "";
            if (!empty($setting_res['web_settings']['social_media'])) {
                foreach ($setting_res['web_settings']['social_media'] as &$row) {
                    $row['file'] = isset($row['file']) ? base_url("public/uploads/web_settings/" . $row['file']) : "";
                }
            } else {
                $setting_res['web_settings']['social_media'] = [];
            }
            $setting_res['server_time'] = $this->toDateTime;
            $setting_res['general_settings']['demo_mode'] = (ALLOW_MODIFICATION == 1) ? "0" : "1";
            //app settings 
            $keys = [
                'customer_current_version_android_app',
                'customer_current_version_ios_app',
                'customer_compulsary_update_force_update',
                'provider_current_version_android_app',
                'provider_current_version_ios_app',
                'provider_compulsary_update_force_update',
                'message_for_customer_application',
                'customer_app_maintenance_mode',
                'message_for_provider_application',
                'provider_app_maintenance_mode',
                'country_currency_code',
                'currency',
                'decimal_point',
                'customer_playstore_url',
                'customer_appstore_url',
                'provider_playstore_url',
                'provider_appstore_url',
                'android_google_interstitial_id',
                'android_google_banner_id',
                'android_google_ads_status',
                'ios_google_interstitial_id',
                'ios_google_banner_id',
                'ios_google_ads_status'
            ];
            foreach ($keys as $key) {
                $setting_res['app_settings'][$key] = isset($setting_res['general_settings'][$key]) ? $setting_res['general_settings'][$key] : "";
                unset($setting_res['general_settings'][$key]);
            }
            //app settings
            if (array_key_exists('refund_policy', $setting_res)) {
                unset($setting_res['refund_policy']);
            }
            if (isset($setting_res) && !empty($setting_res)) {
                $response = [
                    'error' => false,
                    'message' => "setting recieved Successfully",
                    'data' => $setting_res,
                ];
            } else {
                $response = [
                    'error' => true,
                    'message' => "No data found in setting",
                    'data' => $setting_res,
                ];
            }
            return $this->response->setJSON($response);
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_settings()');
            return $this->response->setJSON($response);
        }
    }
    // public function get_home_screen_data()
    // {
    //     try {
    //         $validation = \Config\Services::validation();
    //         $validation->setRules(
    //             [
    //                 'latitude' => 'required',
    //                 'longitude' => 'required',
    //             ]
    //         );
    //         if (!$validation->withRequest($this->request)->run()) {
    //             $errors = $validation->getErrors();
    //             return ApiErrorResponse($errors, false, []);
    //         }
    //         $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
    //         $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
    //         $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
    //         $where = $additional_data = [];
    //         $multipleWhere = $partner_ids = '';
    //         $db = \Config\Database::connect();
    //         $builder = $db->table('sections');
    //         $sortable_fields = ['id' => 'id', 'title' => 'title', 'categories' => 'categories', 'style' => 'style', 'service_type' => 'service_type'];
    //         if (isset($search) and $search != '') {
    //             $multipleWhere = ['`id`' => $search, '`title`' => $search];
    //         }
    //         if ($this->request->getPost('id')) {
    //             $where['id'] = $this->request->getPost('id');
    //         }
    //         // count of section
    //         if (!empty($multipleWhere)) {
    //             $builder->orWhere($multipleWhere);
    //         }
    //         if (isset($where) && !empty($where)) {
    //             $builder->where($where);
    //         }
    //         $total = $builder->select(' COUNT(id) as `total` ');
    //         $offer_count = $builder->get()->getResultArray();
    //         $total = $offer_count[0]['total'];
    //         // get section data
    //         $builder->select();
    //         if (!empty($multipleWhere)) {
    //             $builder->orWhere($multipleWhere);
    //         }
    //         if (!empty($where)) {
    //             $builder->where($where);
    //         }
    //         $offer_recorded = $builder->where('status', 1)->orderBy('rank', $order)
    //             ->get()->getResultArray();
    //         $bulkData = array();
    //         $rows = array();
    //         $tempRow = array();
    //         foreach ($offer_recorded as $row) {
    //             $partners = $sub_category_ids = $partners_ids = [];
    //             //CATEGORY
    //             if ($row['section_type'] == 'categories') {
    //                 if (!is_null($row['category_ids'])) {
    //                     $partners = $db->table('categories c');
    //                     $category_ids = explode(',', $row['category_ids']);
    //                     $ids = (!empty($sub_category_ids)) ? $sub_category_ids : $category_ids;
    //                     $partners = $partners->Select('c.*')
    //                         ->whereIn('c.id', $ids)
    //                         ->where('c.status', 1)
    //                         ->get()
    //                         ->getResultArray();
    //                     /* Sagar : Foreach loop will perform better here */
    //                     foreach ($partners as &$partner) {
    //                         $partner['image'] = (!empty($partner['image'])) ? base_url('/public/uploads/categories/' . $partner['image']) : "";
    //                         $partner['discount'] = $partner['upto'] = "";
    //                         unset($partner['created_at']);
    //                         unset($partner['updated_at']);
    //                         unset($partner['deleted_at']);
    //                         unset($partner['slug']);
    //                         unset($partner['admin_commission']);
    //                         unset($partner['status']);
    //                     }
    //                     unset($partner); // Unset the reference variable to prevent unwanted variable modification
    //                     $parent_ids = array_values(array_unique(array_column($partners, "parent_id")));
    //                     $parent_ids = implode(", ", $parent_ids);
    //                 }
    //                 $type = 'sub_categories';
    //             }
    //             //PREVIOUS ORDER
    //             else if ($row['section_type'] == 'previous_order') {
    //                 if (!empty($this->user_details['id'])) {
    //                     $order_limit = !empty($row['limit']) ? $row['limit'] : 10;
    //                     $orders = new Orders_model();
    //                     $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
    //                     $where['o.status'] = 'completed';
    //                     $where['o.user_id'] =  $this->user_details['id'];
    //                     $order_data = $orders->list(true, $search, $order_limit, $offset, $sort, "DESC", $where, '', '', '', '', '', false);
    //                     if (!empty($order_data)) {
    //                         $order_data_id = array_values(array_unique(array_column($order_data['data'], "id")));
    //                     } else {
    //                         $order_data_id = ""; // Assign a default value if $order_data is empty
    //                     }
    //                 }
    //                 $type = 'previous_order';
    //             }
    //             //  ONGOING ORDER
    //             else if ($row['section_type'] == 'ongoing_order') {
    //                 if (!empty($this->user_details['id'])) {
    //                     $order_limit = !empty($row['limit']) ? $row['limit'] : 10;
    //                     $ongoing_orders = new Orders_model();
    //                     $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
    //                     $where1['o.status '] = ['started'];
    //                     $where1['o.user_id'] =  $this->user_details['id'];
    //                     $ongoing_order_data = $ongoing_orders->list(true, $search, $order_limit, $offset, $sort, "DESC", $where1, '', '', '', '', '', false);
    //                     if (!empty($ongoing_order_data)) {
    //                         $onging_order_data_id = array_values(array_unique(array_column($ongoing_order_data['data'], "id")));
    //                     } else {
    //                         $onging_order_data_id = ""; // Assign a default value if $order_data is empty
    //                     }
    //                 }
    //                 $type = 'ongoing_order';
    //             }
    //             //TOP RATED PARTNER
    //             else if ($row['section_type'] == 'top_rated_partner') {
    //                 $is_latitude_set = "";
    //                 $rated_provider_limit = !empty($row['limit']) ? $row['limit'] : 10;
    //                 $settings = get_settings('general_settings', true);
    //                 $additional_data = [
    //                     'latitude' => $this->request->getPost('latitude'),
    //                     'longitude' => $this->request->getPost('longitude'),
    //                     'max_serviceable_distance' => $settings['max_serviceable_distance'],
    //                 ];
    //                 if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
    //                     $latitude1 = $this->request->getPost('latitude');
    //                     $longitude1 = $this->request->getPost('longitude');
    //                     $is_latitude_set1 = "st_distance_sphere(POINT($longitude1, $latitude1), POINT(`longitude`, `latitude` ))/1000  as distance";
    //                 }
    //                 // $rating_data = $db->table('partner_details pd')->select('p.id,p.username,p.company,pc.minimum_order_amount,p.image,pd.banner,pc.discount,pc.discount_type,pd.company_name,
    //                 //     ps.status as subscription_status,' . $is_latitude_set1)
    //                 //     ->join('services s', 's.id=pd.partner_id', 'left')
    //                 //     ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
    //                 //     ->join('users p', 'p.id=pd.partner_id')
    //                 //     ->join('partner_subscriptions ps', 'ps.partner_id=pd.partner_id')
    //                 //     ->join('users_groups ug', 'ug.user_id = p.id')
    //                 //     ->join('promo_codes pc', 'pc.partner_id=pd.id', 'left')
    //                 //     ->where('ps.status', 'active')
    //                 //     ->having('distance < ' . $additional_data['max_serviceable_distance'])
    //                 //     ->orderBy('pd.ratings', 'desc')
    //                 //     ->limit($rated_provider_limit)->get()->getResultArray();
    //                 $rating_data = $db->table('partner_details pd')
    //                     ->select('p.id, p.username, p.company, pc.minimum_order_amount, p.image, 
    //                         pd.banner, pc.discount, pc.discount_type, pd.company_name,
    //                         ps.status as subscription_status,' . $is_latitude_set1)
    //                     ->join('users p', 'p.id=pd.partner_id')
    //                     ->join('partner_subscriptions ps', 'ps.partner_id=pd.partner_id')
    //                     ->join('users_groups ug', 'ug.user_id = p.id')
    //                     ->join('promo_codes pc', 'pc.partner_id=pd.id', 'left')
    //                     // Services ratings
    //                     ->join('services s', 's.user_id=pd.partner_id', 'left')
    //                     ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
    //                     // Custom services ratings
    //                     ->join('partner_bids pb', 'pb.partner_id=pd.partner_id', 'left')
    //                     ->join('custom_job_requests cj', 'cj.id = pb.custom_job_request_id', 'left')
    //                     ->join('services_ratings sr2', 'sr2.custom_job_request_id = cj.id', 'left')
    //                     ->where('ps.status', 'active')
    //                     ->having('distance < ' . $additional_data['max_serviceable_distance'])
    //                     ->orderBy('pd.ratings', 'desc')
    //                     ->limit($rated_provider_limit)
    //                     ->get()->getResultArray();
    //                 $rating_data = array_values($rating_data);
    //                 $ids2 = [];
    //                 foreach ($rating_data as $key => $row2) {
    //                     $ids2[] = $row2['id'];
    //                 }
    //                 //new added for order - start
    //                 foreach ($ids2 as $key2 => $id) {
    //                     $partner_subscription = fetch_details('partner_subscriptions', ['partner_id' => $id, 'status' => 'active']);
    //                     if ($partner_subscription) {
    //                         $subscription_purchase_date = $partner_subscription[0]['updated_at'];
    //                         $partner_order_limit = fetch_details('orders', ['partner_id' => $id, 'parent_id' => null, 'created_at >' => $subscription_purchase_date]);
    //                         $partners_subscription = $db->table('partner_subscriptions ps');
    //                         $partners_subscription_data = $partners_subscription->select('ps.*')->where('ps.status', 'active')
    //                             ->get()
    //                             ->getResultArray();
    //                         $subscription_order_limit = $partners_subscription_data[0]['max_order_limit'];
    //                         if ($partners_subscription_data[0]['order_type'] == "limited") {
    //                             if (count($partner_order_limit) >= $subscription_order_limit) {
    //                                 unset($rating_data[$key2]);
    //                             }
    //                         }
    //                     } else {
    //                         unset($rating_data[$key2]);
    //                     }
    //                 }
    //                 $rating_data = array_values($rating_data);
    //                 if (!empty($rating_data)) {
    //                     $rate_parent_ids = array_values(array_unique(array_column($rating_data, "id")));
    //                     if (is_array($rate_parent_ids) && !empty($rate_parent_ids)) {
    //                         //     $partners = $db->table('services s');
    //                         //     $rating_data = $partners->Select('p.id,p.username,p.company,pc.minimum_order_amount,p.image,pd.banner,pc.discount,pc.discount_type,
    //                         // count(sr.rating) as number_of_rating, 
    //                         // SUM(sr.rating) as total_rating,
    //                         // (SUM(sr.rating) / count(sr.rating)) as average_rating,
    //                         //  (SELECT COUNT(*) FROM orders o WHERE o.partner_id = p.id AND o.parent_id IS NULL AND o.status="completed") as number_of_orders,pd.company_name,' . $is_latitude_set)
    //                         //         ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
    //                         //         ->join('users p', 'p.id=s.user_id')
    //                         //         ->join('partner_details pd', 'pd.partner_id=s.user_id')
    //                         //         ->join('promo_codes pc', 'pc.partner_id=p.id', 'left')
    //                         //         ->whereIn('s.user_id', $rate_parent_ids)
    //                         //         ->where('pd.is_approved', '1')
    //                         //         ->groupBy('p.id')
    //                         //         ->get()
    //                         //         ->getResultArray();
    //                         $partners = $db->table('services_ratings sr');
    //                         $rating_data = $partners->select('
    //                             p.id, p.username, p.company,
    //                             pc.minimum_order_amount, p.image, 
    //                             pd.banner, pc.discount, pc.discount_type,
    //                             pd.company_name,
    //                             COUNT(sr.rating) as number_of_rating,
    //                             SUM(sr.rating) as total_rating,
    //                             (SUM(sr.rating) / COUNT(sr.rating)) as average_rating,
    //                             (SELECT COUNT(*) FROM orders o 
    //                              WHERE o.partner_id = p.id 
    //                              AND o.parent_id IS NULL 
    //                              AND o.status="completed") as number_of_orders,'
    //                             . $is_latitude_set)
    //                             ->join('services s', 'sr.service_id = s.id', 'left')
    //                             ->join('custom_job_requests cj', 'sr.custom_job_request_id = cj.id', 'left')
    //                             ->join('partner_bids pb', 'pb.custom_job_request_id = cj.id', 'left')
    //                             ->join('users p', 'p.id = COALESCE(s.user_id, pb.partner_id)')
    //                             ->join('partner_details pd', 'pd.partner_id = p.id')
    //                             ->join('promo_codes pc', 'pc.partner_id = p.id', 'left')
    //                             ->whereIn('p.id', $rate_parent_ids)
    //                             ->where('pd.is_approved', '1')
    //                             ->where('(s.user_id IS NOT NULL OR pb.partner_id IS NOT NULL)')
    //                             ->groupBy('p.id')
    //                             ->get()
    //                             ->getResultArray();
    //                         for ($i = 0; $i < count($rating_data); $i++) {
    //                             $rating_data[$i]['upto'] = $rating_data[$i]['minimum_order_amount'];
    //                             if (!empty($rating_data[$i]['image'])) {
    //                                 $image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $rating_data[$i]['image'])) ? base_url('public/backend/assets/profiles/' . $rating_data[$i]['image']) : ((file_exists(FCPATH . $rating_data[$i]['image'])) ? base_url($rating_data[$i]['image']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $rating_data[$i]['image'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $rating_data[$i]['image'])));
    //                                 $rating_data[$i]['image'] = $image;
    //                                 $banner_image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $rating_data[$i]['banner'])) ? base_url('public/backend/assets/profiles/' . $rating_data[$i]['banner']) : ((file_exists(FCPATH . $rating_data[$i]['banner'])) ? base_url($rating_data[$i]['banner']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $rating_data[$i]['banner'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $rating_data[$i]['banner'])));
    //                                 $rating_data[$i]['banner_image'] = $banner_image;
    //                                 unset($rating_data[$i]['banner']);
    //                                 if ($rating_data[$i]['discount_type'] == 'percentage') {
    //                                     unset($rating_data[$i]['discount_type']);
    //                                 }
    //                             }
    //                             unset($rating_data[$i]['minimum_order_amount']);
    //                         }
    //                         for ($i = 0; $i < count($rating_data); $i++) {
    //                             $rate_parent_ids = array_values(array_unique(array_column($rating_data, "id")));
    //                             $rate_parent_ids = implode(", ", $rate_parent_ids);
    //                         }
    //                     } else {
    //                         $rate_parent_ids = "";
    //                     }
    //                     $type = 'top_rated_partner';
    //                 }
    //             }
    //             //NEAR BY PROVIDER
    //             else if ($row['section_type'] == 'near_by_provider') {
    //                 $is_latitude_set = "";
    //                 $rated_provider_limit = !empty($row['limit']) ? $row['limit'] : 10;
    //                 $settings = get_settings('general_settings', true);
    //                 $additional_data = [
    //                     'latitude' => $this->request->getPost('latitude'),
    //                     'longitude' => $this->request->getPost('longitude'),
    //                     'max_serviceable_distance' => $settings['max_serviceable_distance'],
    //                 ];
    //                 if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
    //                     $latitude1 = $this->request->getPost('latitude');
    //                     $longitude1 = $this->request->getPost('longitude');
    //                     $is_latitude_set1 = "st_distance_sphere(POINT($longitude1, $latitude1), POINT(`longitude`, `latitude` ))/1000  as distance";
    //                 }
    //                 $rating_data = $db->table('partner_details pd')->select('p.id,p.username,p.company,pc.minimum_order_amount,p.image,pd.banner,pc.discount,pc.discount_type,pd.company_name,
    //                     ps.status as subscription_status,' . $is_latitude_set1)
    //                     ->join('services s', 's.id=pd.partner_id', 'left')
    //                     ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
    //                     ->join('users p', 'p.id=pd.partner_id')
    //                     ->join('partner_subscriptions ps', 'ps.partner_id=pd.partner_id')
    //                     ->join('users_groups ug', 'ug.user_id = p.id')
    //                     ->join('promo_codes pc', 'pc.partner_id=pd.id', 'left')
    //                     ->where('ps.status', 'active')
    //                     ->having('distance < ' . $additional_data['max_serviceable_distance'])
    //                     ->orderBy('pd.ratings', 'desc')
    //                     ->limit($rated_provider_limit)->get()->getResultArray();
    //                 $rating_data = array_values($rating_data);
    //                 $ids2 = [];
    //                 foreach ($rating_data as $key => $row2) {
    //                     $ids2[] = $row2['id'];
    //                 }
    //                 //new added for order - start
    //                 foreach ($ids2 as $key2 => $id) {
    //                     $partner_subscription = fetch_details('partner_subscriptions', ['partner_id' => $id, 'status' => 'active']);
    //                     if ($partner_subscription) {
    //                         $subscription_purchase_date = $partner_subscription[0]['updated_at'];
    //                         $partner_order_limit = fetch_details('orders', ['partner_id' => $id, 'parent_id' => null, 'created_at >' => $subscription_purchase_date]);
    //                         $partners_subscription = $db->table('partner_subscriptions ps');
    //                         $partners_subscription_data = $partners_subscription->select('ps.*')->where('ps.status', 'active')
    //                             ->get()
    //                             ->getResultArray();
    //                         $subscription_order_limit = $partners_subscription_data[0]['max_order_limit'];
    //                         if ($partners_subscription_data[0]['order_type'] == "limited") {
    //                             if (count($partner_order_limit) >= $subscription_order_limit) {
    //                                 unset($rating_data[$key2]);
    //                             }
    //                         }
    //                     } else {
    //                         unset($rating_data[$key2]);
    //                     }
    //                 }
    //                 $rating_data = array_values($rating_data);
    //                 if (!empty($rating_data)) {
    //                     $rate_parent_ids = array_values(array_unique(array_column($rating_data, "id")));
    //                     if (is_array($rate_parent_ids) && !empty($rate_parent_ids)) {
    //                         //     $partners = $db->table('services s');
    //                         //     $rating_data = $partners->Select('p.id,p.username,p.company,pc.minimum_order_amount,p.image,pd.banner,pc.discount,pc.discount_type,
    //                         // count(sr.rating) as number_of_rating, 
    //                         // SUM(sr.rating) as total_rating,
    //                         // (SUM(sr.rating) / count(sr.rating)) as average_rating,
    //                         //  (SELECT COUNT(*) FROM orders o WHERE o.partner_id = p.id AND o.parent_id IS NULL AND o.status="completed") as number_of_orders,pd.company_name,' . $is_latitude_set)
    //                         //         ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
    //                         //         ->join('users p', 'p.id=s.user_id')
    //                         //         ->join('partner_details pd', 'pd.partner_id=s.user_id')
    //                         //         ->join('promo_codes pc', 'pc.partner_id=p.id', 'left')
    //                         //         ->whereIn('s.user_id', $rate_parent_ids)
    //                         //         ->where('pd.is_approved', '1')
    //                         //         ->groupBy('p.id')
    //                         //         ->get()
    //                         //         ->getResultArray();
    //                         $partners = $db->table('users p');
    //                         $rating_data = $partners->select('
    //                             p.id, p.username, p.company,
    //                             pc.minimum_order_amount, p.image, pd.banner,
    //                             pc.discount, pc.discount_type, pd.company_name,
    //                             (COUNT(DISTINCT CASE 
    //                                 WHEN sr.rating IS NOT NULL THEN sr.id 
    //                                 WHEN sr2.rating IS NOT NULL THEN sr2.id 
    //                             END)) as number_of_rating,
    //                             (COALESCE(SUM(sr.rating), 0) + COALESCE(SUM(sr2.rating), 0)) as total_rating,
    //                             ((COALESCE(SUM(sr.rating), 0) + COALESCE(SUM(sr2.rating), 0)) / 
    //                             NULLIF(COUNT(DISTINCT CASE 
    //                                 WHEN sr.rating IS NOT NULL THEN sr.id 
    //                                 WHEN sr2.rating IS NOT NULL THEN sr2.id 
    //                             END), 0)) as average_rating,
    //                             (SELECT COUNT(*) FROM orders o 
    //                             WHERE o.partner_id = p.id 
    //                             AND o.parent_id IS NULL 
    //                             AND o.status="completed") as number_of_orders,'
    //                             . $is_latitude_set)
    //                             // Regular service ratings
    //                             ->join('services s', 's.user_id = p.id', 'left')
    //                             ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
    //                             // Custom job ratings
    //                             ->join('partner_bids pb', 'pb.partner_id = p.id', 'left')
    //                             ->join('custom_job_requests cj', 'cj.id = pb.custom_job_request_id', 'left')
    //                             ->join('services_ratings sr2', 'sr2.custom_job_request_id = cj.id', 'left')
    //                             // Other joins
    //                             ->join('partner_details pd', 'pd.partner_id = p.id')
    //                             ->join('promo_codes pc', 'pc.partner_id = p.id', 'left')
    //                             ->whereIn('p.id', $rate_parent_ids)
    //                             ->where('pd.is_approved', '1')
    //                             ->groupBy('p.id')
    //                             ->get()
    //                             ->getResultArray();
    //                         for ($i = 0; $i < count($rating_data); $i++) {
    //                             $rating_data[$i]['upto'] = $rating_data[$i]['minimum_order_amount'];
    //                             if (!empty($rating_data[$i]['image'])) {
    //                                 $image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $rating_data[$i]['image'])) ? base_url('public/backend/assets/profiles/' . $rating_data[$i]['image']) : ((file_exists(FCPATH . $rating_data[$i]['image'])) ? base_url($rating_data[$i]['image']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $rating_data[$i]['image'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $rating_data[$i]['image'])));
    //                                 $rating_data[$i]['image'] = $image;
    //                                 $banner_image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $rating_data[$i]['banner'])) ? base_url('public/backend/assets/profiles/' . $rating_data[$i]['banner']) : ((file_exists(FCPATH . $rating_data[$i]['banner'])) ? base_url($rating_data[$i]['banner']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $rating_data[$i]['banner'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $rating_data[$i]['banner'])));
    //                                 $rating_data[$i]['banner_image'] = $banner_image;
    //                                 unset($rating_data[$i]['banner']);
    //                                 if ($rating_data[$i]['discount_type'] == 'percentage') {
    //                                     unset($rating_data[$i]['discount_type']);
    //                                 }
    //                             }
    //                             unset($rating_data[$i]['minimum_order_amount']);
    //                         }
    //                         for ($i = 0; $i < count($rating_data); $i++) {
    //                             $rate_parent_ids = array_values(array_unique(array_column($rating_data, "id")));
    //                             $rate_parent_ids = implode(", ", $rate_parent_ids);
    //                         }
    //                     } else {
    //                         $rate_parent_ids = "";
    //                     }
    //                 } else {
    //                     $rate_parent_ids = "";
    //                 }
    //                 $type = 'near_by_provider';
    //             }
    //             //BANNER
    //             else if ($row['section_type'] == "banner") {
    //                 $type = 'banner';
    //                 if ($row['banner_type'] == "banner_default" || $row['banner_type'] == "banner_url") {
    //                     $parent_ids = [];
    //                 } elseif ($row['banner_type'] == "banner_category") {
    //                     $parent_ids = $row['category_ids'];
    //                 } elseif ($row['banner_type'] == "banner_provider") {
    //                     $parent_ids = $row['partners_ids'];
    //                 }
    //                 $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 50;
    //                 $offset = !empty($this->request->getPost('offset')) ? $this->request->getPost('offset') : 0;
    //                 $db = \Config\Database::connect();
    //                 $builder = $db->table('sections fs');
    //                 $total = $builder->select('COUNT(id) as total')->get()->getRowArray()['total'];
    //                 $builder->select('fs.*, c.name as category_name,c.parent_id as category_parent_id, pd.company_name as provider_name');
    //                 $feature_section_record = $builder
    //                     ->join('categories c', 'c.id = fs.category_ids', 'left')
    //                     ->where('fs.id', $row['id'])
    //                     ->join('partner_details pd', 'pd.partner_id = fs.partners_ids', 'left')
    //                     ->orderBy($sort, $order)
    //                     ->limit($limit, $offset)
    //                     ->get()
    //                     ->getResultArray();
    //                 foreach ($feature_section_record as &$record) {
    //                     $app_image = check_exists(base_url('/public/uploads/feature_section/' . $record['app_banner_image']))
    //                         ? base_url('/public/uploads/feature_section/' . $record['app_banner_image'])
    //                         : 'nothing found';
    //                     $web_image = check_exists(base_url('/public/uploads/feature_section/' . $record['web_banner_image']))
    //                         ? base_url('/public/uploads/feature_section/' . $record['web_banner_image'])
    //                         : 'nothing found';
    //                     $banner_type_id = $record['banner_type'] == "banner_category"
    //                         ? $record['category_ids']
    //                         : ($record['banner_type'] == "banner_provider"
    //                             ? $record['partners_ids']
    //                             : "");
    //                     // $banner_data = [
    //                     //     'id' => $record['id'],
    //                     //     'type' => $record['banner_type'],
    //                     //     'type_id' => $banner_type_id,
    //                     //     'slider_app_image' => $app_image,
    //                     //     'slider_web_image' => $web_image,
    //                     //     'category_name' => $record['category_name'] ?? '',
    //                     //     'provider_name' => $record['provider_name'] ?? '',
    //                     //     'url' => $record['banner_url'] ?? ''
    //                     // ];
    //                     $record['type'] = $record['banner_type'];
    //                     $record['type_id'] = $banner_type_id;
    //                     $record['app_banner_image'] = $app_image;
    //                     $record['web_banner_image'] = $web_image;
    //                     $record['category_name'] = $record['category_name'] ?? '';
    //                     $record['provider_name'] = $record['provider_name'] ?? '';
    //                 }
    //                 unset($record);
    //                 // $banner_data = $banner_rows;
    //             }
    //             //PARTNERS
    //             else {
    //                 if (!is_null($row['partners_ids'])) {
    //                     $partners_ids = explode(',', $row['partners_ids']);
    //                 }
    //                 $settings = get_settings('general_settings', true);
    //                 $Partners_model = new Partners_model();
    //                 if (($this->request->getPost('latitude') && !empty($this->request->getPost('latitude')) && ($this->request->getPost('longitude') && !empty($this->request->getPost('longitude'))))) {
    //                     $additional_data = [
    //                         'latitude' => $this->request->getPost('latitude'),
    //                         'longitude' => $this->request->getPost('longitude'),
    //                         'max_serviceable_distance' => $settings['max_serviceable_distance'],
    //                     ];
    //                 }
    //                 $is_latitude_set = "";
    //                 if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
    //                     $latitude = $this->request->getPost('latitude');
    //                     $longitude = $this->request->getPost('longitude');
    //                     $is_latitude_set = " st_distance_sphere(POINT(' $longitude','$latitude'), POINT(`p`.`longitude`, `p`.`latitude` ))/1000  as distance";
    //                 }
    //                 $builder1 = $db->table('users u1');
    //                 $partners1 = $builder1->Select("u1.username,u1.city,u1.latitude,u1.longitude,u1.id,pc.minimum_order_amount,
    //                  (SELECT COUNT(*) FROM orders o WHERE o.partner_id = u1.id AND o.parent_id IS NULL AND o.status='completed') as number_of_orders,st_distance_sphere(POINT($longitude, $latitude),
    //                  POINT(`longitude`, `latitude` ))/1000  as distance")
    //                     ->join('users_groups ug1', 'ug1.user_id=u1.id')
    //                     ->join('services s', 's.id=u1.id', 'left')
    //                     ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
    //                     ->join('partner_subscriptions ps', 'ps.partner_id=u1.id')
    //                     ->join('promo_codes pc', 'pc.partner_id=u1.id', 'left')
    //                     ->where('ps.status', 'active')
    //                     ->where('ug1.group_id', '3')
    //                     ->whereIn('u1.id', $partners_ids)
    //                     ->having('distance < ' . $additional_data['max_serviceable_distance'])
    //                     ->orderBy('distance')
    //                     ->get()->getResultArray();
    //                 $ids = [];
    //                 foreach ($partners1 as $key => $row1) {
    //                     $ids[] = $row1['id'];
    //                 }
    //                 //new added for order - start
    //                 foreach ($ids as $key => $id) {
    //                     $partner_subscription = fetch_details('partner_subscriptions', ['partner_id' => $id, 'status' => 'active']);
    //                     if ($partner_subscription) {
    //                         $subscription_purchase_date = $partner_subscription[0]['updated_at'];
    //                         $partner_order_limit = fetch_details('orders', ['partner_id' => $id, 'parent_id' => null, 'created_at >' => $subscription_purchase_date]);
    //                         $partners_subscription = $db->table('partner_subscriptions ps');
    //                         $partners_subscription_data = $partners_subscription->select('ps.*')->where('ps.status', 'active')
    //                             ->get()
    //                             ->getResultArray();
    //                         $subscription_order_limit = $partners_subscription_data[0]['max_order_limit'];
    //                         if ($partners_subscription_data[0]['order_type'] == "limited") {
    //                             if (count($partner_order_limit) >= $subscription_order_limit) {
    //                                 unset($ids[$key]);
    //                             }
    //                         }
    //                     } else {
    //                         unset($ids[$key]);
    //                     }
    //                 }
    //                 $parent_ids = array_values($ids);
    //                 if (is_array($ids) && !empty($ids)) {
    //                     // $partners = $db->table('services s');
    //                     // $partners = $partners->Select('p.id,p.username,p.company,pc.minimum_order_amount,p.image,pd.banner,pc.discount,pc.discount_type,
    //                     //     count(sr.rating) as number_of_rating, 
    //                     //     SUM(sr.rating) as total_rating,
    //                     //     (SUM(sr.rating) / count(sr.rating)) as average_rating,
    //                     //      (SELECT COUNT(*) FROM orders o WHERE o.partner_id = p.id AND o.parent_id IS NULL AND o.status="completed") as number_of_orders,pd.company_name,' . $is_latitude_set)
    //                     //     ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
    //                     //     ->join('users p', 'p.id=s.user_id')
    //                     //     ->join('partner_details pd', 'pd.partner_id=s.user_id')
    //                     //     ->join('promo_codes pc', 'pc.partner_id=p.id', 'left')
    //                     //     ->whereIn('s.user_id', $ids)
    //                     //     ->where('pd.is_approved', '1')
    //                     //     ->groupBy('p.id')
    //                     //     ->get()
    //                     //     ->getResultArray();
    //                     $partners = $db->table('users p');
    //                     $partners = $partners->select('
    //                         p.id, p.username, p.company,
    //                         pc.minimum_order_amount, p.image, pd.banner,
    //                         pc.discount, pc.discount_type, pd.company_name,
    //                         (COUNT(sr.rating) + COUNT(sr2.rating)) as number_of_rating,
    //                         (COALESCE(SUM(sr.rating), 0) + COALESCE(SUM(sr2.rating), 0)) as total_rating,
    //                         ((COALESCE(SUM(sr.rating), 0) + COALESCE(SUM(sr2.rating), 0)) / 
    //                         NULLIF((COUNT(sr.rating) + COUNT(sr2.rating)), 0)) as average_rating,
    //                         (SELECT COUNT(*) FROM orders o 
    //                         WHERE o.partner_id = p.id 
    //                         AND o.parent_id IS NULL 
    //                         AND o.status="completed") as number_of_orders,'
    //                         . $is_latitude_set)
    //                         // Regular service ratings
    //                         ->join('services s', 's.user_id = p.id', 'left')
    //                         ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
    //                         // Custom job ratings
    //                         ->join('partner_bids pb', 'pb.partner_id = p.id', 'left')
    //                         ->join('custom_job_requests cj', 'cj.id = pb.custom_job_request_id', 'left')
    //                         ->join('services_ratings sr2', 'sr2.custom_job_request_id = cj.id', 'left')
    //                         // Other joins
    //                         ->join('partner_details pd', 'pd.partner_id = p.id')
    //                         ->join('promo_codes pc', 'pc.partner_id = p.id', 'left')
    //                         ->whereIn('p.id', $ids)
    //                         ->where('pd.is_approved', '1')
    //                         ->groupBy('p.id')
    //                         ->get()
    //                         ->getResultArray();
    //                     for ($i = 0; $i < count($partners); $i++) {
    //                         $partners[$i]['upto'] = $partners[$i]['minimum_order_amount'];
    //                         if (!empty($partners[$i]['image'])) {
    //                             $image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $partners[$i]['image'])) ? base_url('public/backend/assets/profiles/' . $partners[$i]['image']) : ((file_exists(FCPATH . $partners[$i]['image'])) ? base_url($partners[$i]['image']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $partners[$i]['image'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $partners[$i]['image'])));
    //                             $partners[$i]['image'] = $image;
    //                             $banner_image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $partners[$i]['banner'])) ? base_url('public/backend/assets/profiles/' . $partners[$i]['banner']) : ((file_exists(FCPATH . $partners[$i]['banner'])) ? base_url($partners[$i]['banner']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $partners[$i]['banner'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $partners[$i]['banner'])));
    //                             $partners[$i]['banner_image'] = $banner_image;
    //                             unset($partners[$i]['banner']);
    //                             if ($partners[$i]['discount_type'] == 'percentage') {
    //                                 $upto = $partners[$i]['minimum_order_amount'];
    //                                 unset($partners[$i]['discount_type']);
    //                             }
    //                         }
    //                         unset($partners[$i]['minimum_order_amount']);
    //                     }
    //                     $parent_ids = implode(", ", $parent_ids);
    //                     $type = 'partners';
    //                 }
    //                 // else {
    //                 //     $data1['sections'] = [];
    //                 //     $data1['sliders'] = [];
    //                 //     $data1['categories'] = [];
    //                 //     $data = $data1;
    //                 //     $message = "data not found";
    //                 //     $error = true;
    //                 //     return response($message, $error, $data, 200);
    //                 // }
    //             }
    //             $tempRow['id'] = $row['id'];
    //             $tempRow['title'] = $row['title'];
    //             $tempRow['section_type'] = $type;
    //             if ($type == 'partners') {
    //                 $tempRow['parent_ids'] = $parent_ids;
    //                 $tempRow['partners'] = $partners;
    //                 $tempRow['sub_categories'] = [];
    //                 $tempRow['previous_order'] = [];
    //                 $tempRow['ongoing_order'] = [];
    //                 $tempRow['banner'] = [];
    //             } else if ($type == 'sub_categories') {
    //                 $tempRow['sub_categories'] = $partners;
    //                 $tempRow['parent_ids'] = (isset($parent_ids) && !empty($parent_ids)) ? $parent_ids : "";
    //                 $tempRow['partners'] = [];
    //                 $tempRow['previous_order'] = [];
    //                 $tempRow['ongoing_order'] = [];
    //                 $tempRow['banner'] = [];
    //             } else if ($type == 'top_rated_partner') {
    //                 $tempRow['parent_ids'] = $rate_parent_ids;
    //                 $tempRow['sub_categories'] = [];
    //                 $tempRow['partners'] = $rating_data;
    //                 $tempRow['previous_order'] =  [];
    //                 $tempRow['ongoing_order'] = [];
    //                 $tempRow['banner'] = [];
    //             } else if ($type == 'previous_order') {
    //                 if (!empty($this->user_details['id'])) {
    //                     $tempRow['parent_ids'] = $order_data_id;
    //                     $tempRow['previous_order'] = $order_data['data'];
    //                     $tempRow['sub_categories'] = [];
    //                     $tempRow['partners'] = [];
    //                     $tempRow['ongoing_order'] = [];
    //                     $tempRow['banner'] = [];
    //                 } else {
    //                     $tempRow['parent_ids'] = [];
    //                     $tempRow['ongoing_order'] = [];
    //                     $tempRow['sub_categories'] = [];
    //                     $tempRow['partners'] = [];
    //                     $tempRow['previous_order'] = [];
    //                     $tempRow['banner'] = [];
    //                 }
    //             } else if ($type == 'ongoing_order') {
    //                 if (!empty($this->user_details['id'])) {
    //                     $tempRow['parent_ids'] = $onging_order_data_id;
    //                     $tempRow['ongoing_order'] = $ongoing_order_data['data'];
    //                     $tempRow['sub_categories'] = [];
    //                     $tempRow['partners'] = [];
    //                     $tempRow['previous_order'] = [];
    //                     $tempRow['banner'] = [];
    //                 } else {
    //                     $tempRow['parent_ids'] = [];
    //                     $tempRow['ongoing_order'] = [];
    //                     $tempRow['sub_categories'] = [];
    //                     $tempRow['partners'] = [];
    //                     $tempRow['previous_order'] = [];
    //                     $tempRow['banner'] = [];
    //                 }
    //             } else if ($type == "near_by_provider") {
    //                 $tempRow['parent_ids'] = $rate_parent_ids;
    //                 $tempRow['sub_categories'] = [];
    //                 $tempRow['partners'] = $rating_data;
    //                 $tempRow['previous_order'] =  [];
    //                 $tempRow['ongoing_order'] = [];
    //                 $tempRow['banner'] = [];
    //             } else if ($type == "banner") {
    //                 $tempRow['parent_ids'] = $parent_ids;
    //                 $tempRow['sub_categories'] = [];
    //                 $tempRow['partners'] = [];
    //                 $tempRow['previous_order'] =  [];
    //                 $tempRow['ongoing_order'] = [];
    //                 $tempRow['banner'] =  $feature_section_record;
    //             }
    //             $rows[] = $tempRow;
    //         }
    //         $section_data = remove_null_values($rows);
    //         $slider = new Slider_model();
    //         $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 50;
    //         $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
    //         $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'sl.id';
    //         $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
    //         $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
    //         $where = [];
    //         if ($this->request->getPost('id')) {
    //             $where['id'] = $this->request->getPost('id');
    //         }
    //         if ($this->request->getPost('type')) {
    //             $where['type'] = $this->request->getPost('type');
    //         }
    //         if ($this->request->getPost('type_id')) {
    //             $where['type_id'] = $this->request->getPost('type_id');
    //         }
    //         $slider_data = $slider->list(true, $search, $limit, $offset, $sort, $order, $where);
    //         $categories = new Category_model();
    //         $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
    //         $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
    //         $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
    //         $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
    //         $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
    //         $where = [];
    //         if ($this->request->getPost('id')) {
    //             $where['id'] = $this->request->getPost('id');
    //         }
    //         if ($this->request->getPost('slug')) {
    //             $where['slug'] = $this->request->getPost('slug');
    //         }
    //         $where['parent_id'] = 0;
    //         $category_data = $categories->list(true, $search, null, null, $sort, $order, $where);
    //         $data['sections'] = $section_data;
    //         $data['sliders'] = remove_null_values($slider_data['data']);
    //         $data['categories'] = remove_null_values($category_data['data']);
    //         if (!empty($rows)) {
    //             $error = false;
    //             $message = 'sections fetched successfully';
    //         } else {
    //             $error = true;
    //             $message = 'data not found';
    //         }
    //         return response($message, $error, $data, 200);
    //     } catch (\Exception $th) {
    //         throw $th;
    //         $response['error'] = true;
    //         log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_home_screen_data()');
    //         $response['message'] = 'Something went wrong';
    //         return $this->response->setJSON($response);
    //     }
    // }
    public function get_home_screen_data()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'latitude' => 'required',
                    'longitude' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                return ApiErrorResponse($errors, false, []);
            }
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = $additional_data = [];
            $multipleWhere = $partner_ids = '';
            $db = \Config\Database::connect();
            $builder = $db->table('sections');
            $sortable_fields = ['id' => 'id', 'title' => 'title', 'categories' => 'categories', 'style' => 'style', 'service_type' => 'service_type'];
            if (isset($search) and $search != '') {
                $multipleWhere = ['`id`' => $search, '`title`' => $search];
            }
            if ($this->request->getPost('id')) {
                $where['id'] = $this->request->getPost('id');
            }
            // count of section
            if (!empty($multipleWhere)) {
                $builder->orWhere($multipleWhere);
            }
            if (isset($where) && !empty($where)) {
                $builder->where($where);
            }
            $total = $builder->select(' COUNT(id) as `total` ');
            $offer_count = $builder->get()->getResultArray();
            $total = $offer_count[0]['total'];
            // get section data
            $builder->select();
            if (!empty($multipleWhere)) {
                $builder->orWhere($multipleWhere);
            }
            if (!empty($where)) {
                $builder->where($where);
            }
            $offer_recorded = $builder->where('status', 1)->orderBy('rank', $order)
                ->get()->getResultArray();
            $bulkData = array();
            $rows = array();
            $tempRow = array();
            
            
            foreach ($offer_recorded as $row) {
                $partners = $sub_category_ids = $partners_ids = [];
                //CATEGORY
                if ($row['section_type'] == 'categories') {
                    if (!is_null($row['category_ids'])) {
                        $partners = $db->table('categories c');
                        $category_ids = explode(',', $row['category_ids']);
                        $ids = (!empty($sub_category_ids)) ? $sub_category_ids : $category_ids;
                        $partners = $partners->Select('c.*')
                            ->whereIn('c.id', $ids)
                            ->where('c.status', 1)
                            ->get()
                            ->getResultArray();
                        /* Sagar : Foreach loop will perform better here */
                        foreach ($partners as &$partner) {
                            $partner['image'] = (!empty($partner['image'])) ? base_url('/public/uploads/categories/' . $partner['image']) : "";
                            $partner['discount'] = $partner['upto'] = "";
                            unset($partner['created_at']);
                            unset($partner['updated_at']);
                            unset($partner['deleted_at']);
                            unset($partner['slug']);
                            unset($partner['admin_commission']);
                            unset($partner['status']);
                        }
                        unset($partner); // Unset the reference variable to prevent unwanted variable modification
                        $parent_ids = array_values(array_unique(array_column($partners, "parent_id")));
                        $parent_ids = implode(", ", $parent_ids);
                    }
                    $type = 'sub_categories';
                }
                //PREVIOUS ORDER
                else if ($row['section_type'] == 'previous_order') {
                    if (!empty($this->user_details['id'])) {
                        $order_limit = !empty($row['limit']) ? $row['limit'] : 10;
                        $orders = new Orders_model();
                        $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
                        $where['o.status'] = 'completed';
                        $where['o.user_id'] =  $this->user_details['id'];
                        $order_data = $orders->list(true, $search, $order_limit, $offset, $sort, "DESC", $where, '', '', '', '', '', false);
                        if (!empty($order_data)) {
                            $order_data_id = array_values(array_unique(array_column($order_data['data'], "id")));
                        } else {
                            $order_data_id = ""; // Assign a default value if $order_data is empty
                        }
                    }
                    $type = 'previous_order';
                }
                //  ONGOING ORDER
                else if ($row['section_type'] == 'ongoing_order') {
                    if (!empty($this->user_details['id'])) {
                        $order_limit = !empty($row['limit']) ? $row['limit'] : 10;
                        $ongoing_orders = new Orders_model();
                        $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
                        $where1['o.status '] = ['started'];
                        $where1['o.user_id'] =  $this->user_details['id'];
                        $ongoing_order_data = $ongoing_orders->list(true, $search, $order_limit, $offset, $sort, "DESC", $where1, '', '', '', '', '', false);
                        if (!empty($ongoing_order_data)) {
                            $onging_order_data_id = array_values(array_unique(array_column($ongoing_order_data['data'], "id")));
                        } else {
                            $onging_order_data_id = ""; // Assign a default value if $order_data is empty
                        }
                    }
                    $type = 'ongoing_order';
                }
                //TOP RATED PARTNER
                else if ($row['section_type'] == 'top_rated_partner') {
                    $is_latitude_set = "";
                    $rated_provider_limit = !empty($row['limit']) ? $row['limit'] : 10;
                    $settings = get_settings('general_settings', true);
                    $additional_data = [
                        'latitude' => $this->request->getPost('latitude'),
                        'longitude' => $this->request->getPost('longitude'),
                        'max_serviceable_distance' => $settings['max_serviceable_distance'],
                    ];
                    if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
                        $latitude1 = $this->request->getPost('latitude');
                        $longitude1 = $this->request->getPost('longitude');
                        $is_latitude_set1 = "st_distance_sphere(POINT($longitude1, $latitude1), POINT(`longitude`, `latitude` ))/1000  as distance";
                    }
                    // $rating_data = $db->table('partner_details pd')->select('p.id,p.username,p.company,pc.minimum_order_amount,p.image,pd.banner,pc.discount,pc.discount_type,pd.company_name,
                    //     ps.status as subscription_status,' . $is_latitude_set1)
                    //     ->join('services s', 's.id=pd.partner_id', 'left')
                    //     ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
                    //     ->join('users p', 'p.id=pd.partner_id')
                    //     ->join('partner_subscriptions ps', 'ps.partner_id=pd.partner_id')
                    //     ->join('users_groups ug', 'ug.user_id = p.id')
                    //     ->join('promo_codes pc', 'pc.partner_id=pd.id', 'left')
                    //     ->where('ps.status', 'active')
                    //     ->having('distance < ' . $additional_data['max_serviceable_distance'])
                    //     ->orderBy('pd.ratings', 'desc')
                    //     ->limit($rated_provider_limit)->get()->getResultArray();
                    $rating_data = $db->table('partner_details pd')
                        ->select('p.id, p.username, p.company, pc.minimum_order_amount, p.image, 
                            pd.banner, pc.discount, pc.discount_type, pd.company_name,
                            ps.status as subscription_status,' . $is_latitude_set1)
                        ->join('users p', 'p.id=pd.partner_id')
                        ->join('partner_subscriptions ps', 'ps.partner_id=pd.partner_id')
                        ->join('users_groups ug', 'ug.user_id = p.id')
                        ->join('promo_codes pc', 'pc.partner_id=pd.id', 'left')
                        // Services ratings
                        ->join('services s', 's.user_id=pd.partner_id', 'left')
                        ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
                        // Custom services ratings
                        ->join('partner_bids pb', 'pb.partner_id=pd.partner_id', 'left')
                        ->join('custom_job_requests cj', 'cj.id = pb.custom_job_request_id', 'left')
                        ->join('services_ratings sr2', 'sr2.custom_job_request_id = cj.id', 'left')
                        ->where('ps.status', 'active')
                        ->having('distance < ' . $additional_data['max_serviceable_distance'])
                        ->orderBy('pd.ratings', 'desc')
                        ->limit($rated_provider_limit)
                        ->get()->getResultArray();
                    $rating_data = array_values($rating_data);
                    $ids2 = [];
                    foreach ($rating_data as $key => $row2) {
                        $ids2[] = $row2['id'];
                    }
                    //new added for order - start
                    foreach ($ids2 as $key2 => $id) {
                        $partner_subscription = fetch_details('partner_subscriptions', ['partner_id' => $id, 'status' => 'active']);
                        if ($partner_subscription) {
                            $subscription_purchase_date = $partner_subscription[0]['updated_at'];
                            $partner_order_limit = fetch_details('orders', ['partner_id' => $id, 'parent_id' => null, 'created_at >' => $subscription_purchase_date]);
                            $partners_subscription = $db->table('partner_subscriptions ps');
                            $partners_subscription_data = $partners_subscription->select('ps.*')->where('ps.status', 'active')
                                ->get()
                                ->getResultArray();
                            $subscription_order_limit = $partners_subscription_data[0]['max_order_limit'];
                            if ($partners_subscription_data[0]['order_type'] == "limited") {
                                if (count($partner_order_limit) >= $subscription_order_limit) {
                                    unset($rating_data[$key2]);
                                }
                            }
                        } else {
                            unset($rating_data[$key2]);
                        }
                    }
                    $rating_data = array_values($rating_data);
                    if (!empty($rating_data)) {
                        $rate_parent_ids = array_values(array_unique(array_column($rating_data, "id")));
                        if (is_array($rate_parent_ids) && !empty($rate_parent_ids)) {
                            //     $partners = $db->table('services s');
                            //     $rating_data = $partners->Select('p.id,p.username,p.company,pc.minimum_order_amount,p.image,pd.banner,pc.discount,pc.discount_type,
                            // count(sr.rating) as number_of_rating, 
                            // SUM(sr.rating) as total_rating,
                            // (SUM(sr.rating) / count(sr.rating)) as average_rating,
                            //  (SELECT COUNT(*) FROM orders o WHERE o.partner_id = p.id AND o.parent_id IS NULL AND o.status="completed") as number_of_orders,pd.company_name,' . $is_latitude_set)
                            //         ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
                            //         ->join('users p', 'p.id=s.user_id')
                            //         ->join('partner_details pd', 'pd.partner_id=s.user_id')
                            //         ->join('promo_codes pc', 'pc.partner_id=p.id', 'left')
                            //         ->whereIn('s.user_id', $rate_parent_ids)
                            //         ->where('pd.is_approved', '1')
                            //         ->groupBy('p.id')
                            //         ->get()
                            //         ->getResultArray();
                            $partners = $db->table('services_ratings sr');
                            $rating_data = $partners->select('
                                p.id, p.username, p.company,
                                pc.minimum_order_amount, p.image, 
                                pd.banner, pc.discount, pc.discount_type,
                                pd.company_name,
                                COUNT(sr.rating) as number_of_rating,
                                SUM(sr.rating) as total_rating,
                                (SUM(sr.rating) / COUNT(sr.rating)) as average_rating,
                                (SELECT COUNT(*) FROM orders o 
                                 WHERE o.partner_id = p.id 
                                 AND o.parent_id IS NULL 
                                 AND o.status="completed") as number_of_orders,'
                                . $is_latitude_set)
                                ->join('services s', 'sr.service_id = s.id', 'left')
                                ->join('custom_job_requests cj', 'sr.custom_job_request_id = cj.id', 'left')
                                ->join('partner_bids pb', 'pb.custom_job_request_id = cj.id', 'left')
                                ->join('users p', 'p.id = COALESCE(s.user_id, pb.partner_id)')
                                ->join('partner_details pd', 'pd.partner_id = p.id')
                                ->join('promo_codes pc', 'pc.partner_id = p.id', 'left')
                                ->whereIn('p.id', $rate_parent_ids)
                                ->where('pd.is_approved', '1')
                                ->where('(s.user_id IS NOT NULL OR pb.partner_id IS NOT NULL)')
                                ->groupBy('p.id')
                                ->get()
                                ->getResultArray();
                            for ($i = 0; $i < count($rating_data); $i++) {
                                $rating_data[$i]['upto'] = $rating_data[$i]['minimum_order_amount'];
                                if (!empty($rating_data[$i]['image'])) {
                                    $image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $rating_data[$i]['image'])) ? base_url('public/backend/assets/profiles/' . $rating_data[$i]['image']) : ((file_exists(FCPATH . $rating_data[$i]['image'])) ? base_url($rating_data[$i]['image']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $rating_data[$i]['image'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $rating_data[$i]['image'])));
                                    $rating_data[$i]['image'] = $image;
                                    $banner_image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $rating_data[$i]['banner'])) ? base_url('public/backend/assets/profiles/' . $rating_data[$i]['banner']) : ((file_exists(FCPATH . $rating_data[$i]['banner'])) ? base_url($rating_data[$i]['banner']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $rating_data[$i]['banner'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $rating_data[$i]['banner'])));
                                    $rating_data[$i]['banner_image'] = $banner_image;
                                    unset($rating_data[$i]['banner']);
                                    if ($rating_data[$i]['discount_type'] == 'percentage') {
                                        unset($rating_data[$i]['discount_type']);
                                    }
                                }
                                unset($rating_data[$i]['minimum_order_amount']);
                            }
                            for ($i = 0; $i < count($rating_data); $i++) {
                                $rate_parent_ids = array_values(array_unique(array_column($rating_data, "id")));
                                $rate_parent_ids = implode(", ", $rate_parent_ids);
                            }
                        } else {
                            $rate_parent_ids = "";
                        }
                        
                    }
                    $type = 'top_rated_partner';
                }
                //NEAR BY PROVIDER
                else if ($row['section_type'] == 'near_by_provider') {
                    $is_latitude_set = "";
                    $rated_provider_limit = !empty($row['limit']) ? $row['limit'] : 10;
                    $settings = get_settings('general_settings', true);
                    $additional_data = [
                        'latitude' => $this->request->getPost('latitude'),
                        'longitude' => $this->request->getPost('longitude'),
                        'max_serviceable_distance' => $settings['max_serviceable_distance'],
                    ];
                    if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
                        $latitude1 = $this->request->getPost('latitude');
                        $longitude1 = $this->request->getPost('longitude');
                        $is_latitude_set1 = "st_distance_sphere(POINT($longitude1, $latitude1), POINT(`longitude`, `latitude` ))/1000  as distance";
                    }
                    $rating_data = $db->table('partner_details pd')->select('p.id,p.username,p.company,pc.minimum_order_amount,p.image,pd.banner,pc.discount,pc.discount_type,pd.company_name,
                        ps.status as subscription_status,' . $is_latitude_set1)
                        ->join('services s', 's.id=pd.partner_id', 'left')
                        ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
                        ->join('users p', 'p.id=pd.partner_id')
                        ->join('partner_subscriptions ps', 'ps.partner_id=pd.partner_id')
                        ->join('users_groups ug', 'ug.user_id = p.id')
                        ->join('promo_codes pc', 'pc.partner_id=pd.id', 'left')
                        ->where('ps.status', 'active')
                        ->having('distance < ' . $additional_data['max_serviceable_distance'])
                        ->orderBy('pd.ratings', 'desc')
                        ->limit($rated_provider_limit)->get()->getResultArray();
                    $rating_data = array_values($rating_data);
                    $ids2 = [];
                    foreach ($rating_data as $key => $row2) {
                        $ids2[] = $row2['id'];
                    }
                    //new added for order - start
                    foreach ($ids2 as $key2 => $id) {
                        $partner_subscription = fetch_details('partner_subscriptions', ['partner_id' => $id, 'status' => 'active']);
                        if ($partner_subscription) {
                            $subscription_purchase_date = $partner_subscription[0]['updated_at'];
                            $partner_order_limit = fetch_details('orders', ['partner_id' => $id, 'parent_id' => null, 'created_at >' => $subscription_purchase_date]);
                            $partners_subscription = $db->table('partner_subscriptions ps');
                            $partners_subscription_data = $partners_subscription->select('ps.*')->where('ps.status', 'active')
                                ->get()
                                ->getResultArray();
                            $subscription_order_limit = $partners_subscription_data[0]['max_order_limit'];
                            if ($partners_subscription_data[0]['order_type'] == "limited") {
                                if (count($partner_order_limit) >= $subscription_order_limit) {
                                    unset($rating_data[$key2]);
                                }
                            }
                        } else {
                            unset($rating_data[$key2]);
                        }
                    }
                    $rating_data = array_values($rating_data);
                    if (!empty($rating_data)) {
                        $rate_parent_ids = array_values(array_unique(array_column($rating_data, "id")));
                        if (is_array($rate_parent_ids) && !empty($rate_parent_ids)) {
                            //     $partners = $db->table('services s');
                            //     $rating_data = $partners->Select('p.id,p.username,p.company,pc.minimum_order_amount,p.image,pd.banner,pc.discount,pc.discount_type,
                            // count(sr.rating) as number_of_rating, 
                            // SUM(sr.rating) as total_rating,
                            // (SUM(sr.rating) / count(sr.rating)) as average_rating,
                            //  (SELECT COUNT(*) FROM orders o WHERE o.partner_id = p.id AND o.parent_id IS NULL AND o.status="completed") as number_of_orders,pd.company_name,' . $is_latitude_set)
                            //         ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
                            //         ->join('users p', 'p.id=s.user_id')
                            //         ->join('partner_details pd', 'pd.partner_id=s.user_id')
                            //         ->join('promo_codes pc', 'pc.partner_id=p.id', 'left')
                            //         ->whereIn('s.user_id', $rate_parent_ids)
                            //         ->where('pd.is_approved', '1')
                            //         ->groupBy('p.id')
                            //         ->get()
                            //         ->getResultArray();
                            $partners = $db->table('users p');
                            $rating_data = $partners->select('
                                p.id, p.username, p.company,
                                pc.minimum_order_amount, p.image, pd.banner,
                                pc.discount, pc.discount_type, pd.company_name,
                                (COUNT(DISTINCT CASE 
                                    WHEN sr.rating IS NOT NULL THEN sr.id 
                                    WHEN sr2.rating IS NOT NULL THEN sr2.id 
                                END)) as number_of_rating,
                                (COALESCE(SUM(sr.rating), 0) + COALESCE(SUM(sr2.rating), 0)) as total_rating,
                                ((COALESCE(SUM(sr.rating), 0) + COALESCE(SUM(sr2.rating), 0)) / 
                                NULLIF(COUNT(DISTINCT CASE 
                                    WHEN sr.rating IS NOT NULL THEN sr.id 
                                    WHEN sr2.rating IS NOT NULL THEN sr2.id 
                                END), 0)) as average_rating,
                                (SELECT COUNT(*) FROM orders o 
                                WHERE o.partner_id = p.id 
                                AND o.parent_id IS NULL 
                                AND o.status="completed") as number_of_orders,'
                                . $is_latitude_set)
                                // Regular service ratings
                                ->join('services s', 's.user_id = p.id', 'left')
                                ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
                                // Custom job ratings
                                ->join('partner_bids pb', 'pb.partner_id = p.id', 'left')
                                ->join('custom_job_requests cj', 'cj.id = pb.custom_job_request_id', 'left')
                                ->join('services_ratings sr2', 'sr2.custom_job_request_id = cj.id', 'left')
                                // Other joins
                                ->join('partner_details pd', 'pd.partner_id = p.id')
                                ->join('promo_codes pc', 'pc.partner_id = p.id', 'left')
                                ->whereIn('p.id', $rate_parent_ids)
                                ->where('pd.is_approved', '1')
                                ->groupBy('p.id')
                                ->get()
                                ->getResultArray();
                            for ($i = 0; $i < count($rating_data); $i++) {
                                $rating_data[$i]['upto'] = $rating_data[$i]['minimum_order_amount'];
                                if (!empty($rating_data[$i]['image'])) {
                                    $image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $rating_data[$i]['image'])) ? base_url('public/backend/assets/profiles/' . $rating_data[$i]['image']) : ((file_exists(FCPATH . $rating_data[$i]['image'])) ? base_url($rating_data[$i]['image']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $rating_data[$i]['image'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $rating_data[$i]['image'])));
                                    $rating_data[$i]['image'] = $image;
                                    $banner_image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $rating_data[$i]['banner'])) ? base_url('public/backend/assets/profiles/' . $rating_data[$i]['banner']) : ((file_exists(FCPATH . $rating_data[$i]['banner'])) ? base_url($rating_data[$i]['banner']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $rating_data[$i]['banner'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $rating_data[$i]['banner'])));
                                    $rating_data[$i]['banner_image'] = $banner_image;
                                    unset($rating_data[$i]['banner']);
                                    if ($rating_data[$i]['discount_type'] == 'percentage') {
                                        unset($rating_data[$i]['discount_type']);
                                    }
                                }
                                unset($rating_data[$i]['minimum_order_amount']);
                            }
                            for ($i = 0; $i < count($rating_data); $i++) {
                                $rate_parent_ids = array_values(array_unique(array_column($rating_data, "id")));
                                $rate_parent_ids = implode(", ", $rate_parent_ids);
                            }
                        } else {
                            $rate_parent_ids = "";
                        }
                    } else {
                        $rate_parent_ids = "";
                    }
                    $type = 'near_by_provider';
                }
                //BANNER
                else if ($row['section_type'] == "banner") {
                    $type = 'banner';
                    if ($row['banner_type'] == "banner_default" || $row['banner_type'] == "banner_url") {
                        $parent_ids = [];
                    } elseif ($row['banner_type'] == "banner_category") {
                        $parent_ids = $row['category_ids'];
                    } elseif ($row['banner_type'] == "banner_provider") {
                        $parent_ids = $row['partners_ids'];
                    }
                    $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 50;
                    $offset = !empty($this->request->getPost('offset')) ? $this->request->getPost('offset') : 0;
                    $db = \Config\Database::connect();
                    $builder = $db->table('sections fs');
                    $total = $builder->select('COUNT(id) as total')->get()->getRowArray()['total'];
                    $builder->select('fs.*, c.name as category_name,c.parent_id as category_parent_id, pd.company_name as provider_name');
                    $feature_section_record = $builder
                        ->join('categories c', 'c.id = fs.category_ids', 'left')
                        ->where('fs.id', $row['id'])
                        ->join('partner_details pd', 'pd.partner_id = fs.partners_ids', 'left')
                        ->orderBy($sort, $order)
                        ->limit($limit, $offset)
                        ->get()
                        ->getResultArray();
                    foreach ($feature_section_record as &$record) {
                        $app_image = check_exists(base_url('/public/uploads/feature_section/' . $record['app_banner_image']))
                            ? base_url('/public/uploads/feature_section/' . $record['app_banner_image'])
                            : 'nothing found';
                        $web_image = check_exists(base_url('/public/uploads/feature_section/' . $record['web_banner_image']))
                            ? base_url('/public/uploads/feature_section/' . $record['web_banner_image'])
                            : 'nothing found';
                        $banner_type_id = $record['banner_type'] == "banner_category"
                            ? $record['category_ids']
                            : ($record['banner_type'] == "banner_provider"
                                ? $record['partners_ids']
                                : "");
                        // $banner_data = [
                        //     'id' => $record['id'],
                        //     'type' => $record['banner_type'],
                        //     'type_id' => $banner_type_id,
                        //     'slider_app_image' => $app_image,
                        //     'slider_web_image' => $web_image,
                        //     'category_name' => $record['category_name'] ?? '',
                        //     'provider_name' => $record['provider_name'] ?? '',
                        //     'url' => $record['banner_url'] ?? ''
                        // ];
                        $record['type'] = $record['banner_type'];
                        $record['type_id'] = $banner_type_id;
                        $record['app_banner_image'] = $app_image;
                        $record['web_banner_image'] = $web_image;
                        $record['category_name'] = $record['category_name'] ?? '';
                        $record['provider_name'] = $record['provider_name'] ?? '';
                    }
                    unset($record);
                    // $banner_data = $banner_rows;
                }
                //PARTNERS
                else {
                    if (!is_null($row['partners_ids'])) {
                        $partners_ids = explode(',', $row['partners_ids']);
                    }
                    $settings = get_settings('general_settings', true);
                    $Partners_model = new Partners_model();
                    if (($this->request->getPost('latitude') && !empty($this->request->getPost('latitude')) && ($this->request->getPost('longitude') && !empty($this->request->getPost('longitude'))))) {
                        $additional_data = [
                            'latitude' => $this->request->getPost('latitude'),
                            'longitude' => $this->request->getPost('longitude'),
                            'max_serviceable_distance' => $settings['max_serviceable_distance'],
                        ];
                    }
                    $is_latitude_set = "";
                    if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
                        $latitude = $this->request->getPost('latitude');
                        $longitude = $this->request->getPost('longitude');
                        $is_latitude_set = " st_distance_sphere(POINT(' $longitude','$latitude'), POINT(`p`.`longitude`, `p`.`latitude` ))/1000  as distance";
                    }
                    $builder1 = $db->table('users u1');
                    $partners1 = $builder1->Select("u1.username,u1.city,u1.latitude,u1.longitude,u1.id,pc.minimum_order_amount,
                     (SELECT COUNT(*) FROM orders o WHERE o.partner_id = u1.id AND o.parent_id IS NULL AND o.status='completed') as number_of_orders,st_distance_sphere(POINT($longitude, $latitude),
                     POINT(`longitude`, `latitude` ))/1000  as distance")
                        ->join('users_groups ug1', 'ug1.user_id=u1.id')
                        ->join('services s', 's.id=u1.id', 'left')
                        ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
                        ->join('partner_subscriptions ps', 'ps.partner_id=u1.id')
                        ->join('promo_codes pc', 'pc.partner_id=u1.id', 'left')
                        ->where('ps.status', 'active')
                        ->where('ug1.group_id', '3')
                        ->whereIn('u1.id', $partners_ids)
                        ->having('distance < ' . $additional_data['max_serviceable_distance'])
                        ->orderBy('distance')
                        ->get()->getResultArray();
                    $ids = [];
                    foreach ($partners1 as $key => $row1) {
                        $ids[] = $row1['id'];
                    }
                    //new added for order - start
                    foreach ($ids as $key => $id) {
                        $partner_subscription = fetch_details('partner_subscriptions', ['partner_id' => $id, 'status' => 'active']);
                        if ($partner_subscription) {
                            $subscription_purchase_date = $partner_subscription[0]['updated_at'];
                            $partner_order_limit = fetch_details('orders', ['partner_id' => $id, 'parent_id' => null, 'created_at >' => $subscription_purchase_date]);
                            $partners_subscription = $db->table('partner_subscriptions ps');
                            $partners_subscription_data = $partners_subscription->select('ps.*')->where('ps.status', 'active')
                                ->get()
                                ->getResultArray();
                            $subscription_order_limit = $partners_subscription_data[0]['max_order_limit'];
                            if ($partners_subscription_data[0]['order_type'] == "limited") {
                                if (count($partner_order_limit) >= $subscription_order_limit) {
                                    unset($ids[$key]);
                                }
                            }
                        } else {
                            unset($ids[$key]);
                        }
                    }
                    $parent_ids = array_values($ids);
                    if (is_array($ids) && !empty($ids)) {
                        // $partners = $db->table('services s');
                        // $partners = $partners->Select('p.id,p.username,p.company,pc.minimum_order_amount,p.image,pd.banner,pc.discount,pc.discount_type,
                        //     count(sr.rating) as number_of_rating, 
                        //     SUM(sr.rating) as total_rating,
                        //     (SUM(sr.rating) / count(sr.rating)) as average_rating,
                        //      (SELECT COUNT(*) FROM orders o WHERE o.partner_id = p.id AND o.parent_id IS NULL AND o.status="completed") as number_of_orders,pd.company_name,' . $is_latitude_set)
                        //     ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
                        //     ->join('users p', 'p.id=s.user_id')
                        //     ->join('partner_details pd', 'pd.partner_id=s.user_id')
                        //     ->join('promo_codes pc', 'pc.partner_id=p.id', 'left')
                        //     ->whereIn('s.user_id', $ids)
                        //     ->where('pd.is_approved', '1')
                        //     ->groupBy('p.id')
                        //     ->get()
                        //     ->getResultArray();
                        $partners = $db->table('users p');
                        $partners = $partners->select('
                            p.id, p.username, p.company,
                            pc.minimum_order_amount, p.image, pd.banner,
                            pc.discount, pc.discount_type, pd.company_name,
                            (COUNT(sr.rating) + COUNT(sr2.rating)) as number_of_rating,
                            (COALESCE(SUM(sr.rating), 0) + COALESCE(SUM(sr2.rating), 0)) as total_rating,
                            ((COALESCE(SUM(sr.rating), 0) + COALESCE(SUM(sr2.rating), 0)) / 
                            NULLIF((COUNT(sr.rating) + COUNT(sr2.rating)), 0)) as average_rating,
                            (SELECT COUNT(*) FROM orders o 
                            WHERE o.partner_id = p.id 
                            AND o.parent_id IS NULL 
                            AND o.status="completed") as number_of_orders,'
                            . $is_latitude_set)
                            // Regular service ratings
                            ->join('services s', 's.user_id = p.id', 'left')
                            ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
                            // Custom job ratings
                            ->join('partner_bids pb', 'pb.partner_id = p.id', 'left')
                            ->join('custom_job_requests cj', 'cj.id = pb.custom_job_request_id', 'left')
                            ->join('services_ratings sr2', 'sr2.custom_job_request_id = cj.id', 'left')
                            // Other joins
                            ->join('partner_details pd', 'pd.partner_id = p.id')
                            ->join('promo_codes pc', 'pc.partner_id = p.id', 'left')
                            ->whereIn('p.id', $ids)
                            ->where('pd.is_approved', '1')
                            ->groupBy('p.id')
                            ->get()
                            ->getResultArray();
                        for ($i = 0; $i < count($partners); $i++) {
                            $partners[$i]['upto'] = $partners[$i]['minimum_order_amount'];
                            if (!empty($partners[$i]['image'])) {
                                $image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $partners[$i]['image'])) ? base_url('public/backend/assets/profiles/' . $partners[$i]['image']) : ((file_exists(FCPATH . $partners[$i]['image'])) ? base_url($partners[$i]['image']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $partners[$i]['image'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $partners[$i]['image'])));
                                $partners[$i]['image'] = $image;
                                $banner_image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $partners[$i]['banner'])) ? base_url('public/backend/assets/profiles/' . $partners[$i]['banner']) : ((file_exists(FCPATH . $partners[$i]['banner'])) ? base_url($partners[$i]['banner']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $partners[$i]['banner'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $partners[$i]['banner'])));
                                $partners[$i]['banner_image'] = $banner_image;
                                unset($partners[$i]['banner']);
                                if ($partners[$i]['discount_type'] == 'percentage') {
                                    $upto = $partners[$i]['minimum_order_amount'];
                                    unset($partners[$i]['discount_type']);
                                }
                            }
                            unset($partners[$i]['minimum_order_amount']);
                        }
                        $parent_ids = implode(", ", $parent_ids);
                        $type = 'partners';
                    }
                    // else {
                    //     $data1['sections'] = [];
                    //     $data1['sliders'] = [];
                    //     $data1['categories'] = [];
                    //     $data = $data1;
                    //     $message = "data not found";
                    //     $error = true;
                    //     return response($message, $error, $data, 200);
                    // }
                }
                $tempRow['id'] = $row['id'];
                $tempRow['title'] = $row['title'];
                $tempRow['section_type'] = $type;
                if ($type == 'partners') {
                    $tempRow['parent_ids'] = $parent_ids;
                    $tempRow['partners'] = $partners;
                    $tempRow['sub_categories'] = [];
                    $tempRow['previous_order'] = [];
                    $tempRow['ongoing_order'] = [];
                    $tempRow['banner'] = [];
                } else if ($type == 'sub_categories') {
                    $tempRow['sub_categories'] = $partners;
                    $tempRow['parent_ids'] = (isset($parent_ids) && !empty($parent_ids)) ? $parent_ids : "";
                    $tempRow['partners'] = [];
                    $tempRow['previous_order'] = [];
                    $tempRow['ongoing_order'] = [];
                    $tempRow['banner'] = [];
                } else if ($type == 'top_rated_partner') {
                    $tempRow['parent_ids'] = $rate_parent_ids??"";
                    $tempRow['sub_categories'] = [];
                    $tempRow['partners'] = $rating_data;
                    $tempRow['previous_order'] =  [];
                    $tempRow['ongoing_order'] = [];
                    $tempRow['banner'] = [];
                } else if ($type == 'previous_order') {
                    if (!empty($this->user_details['id'])) {
                        $tempRow['parent_ids'] = $order_data_id;
                        $tempRow['previous_order'] = $order_data['data'];
                        $tempRow['sub_categories'] = [];
                        $tempRow['partners'] = [];
                        $tempRow['ongoing_order'] = [];
                        $tempRow['banner'] = [];
                    } else {
                        $tempRow['parent_ids'] = [];
                        $tempRow['ongoing_order'] = [];
                        $tempRow['sub_categories'] = [];
                        $tempRow['partners'] = [];
                        $tempRow['previous_order'] = [];
                        $tempRow['banner'] = [];
                    }
                } else if ($type == 'ongoing_order') {
                    if (!empty($this->user_details['id'])) {
                        $tempRow['parent_ids'] = $onging_order_data_id;
                        $tempRow['ongoing_order'] = $ongoing_order_data['data'];
                        $tempRow['sub_categories'] = [];
                        $tempRow['partners'] = [];
                        $tempRow['previous_order'] = [];
                        $tempRow['banner'] = [];
                    } else {
                        $tempRow['parent_ids'] = [];
                        $tempRow['ongoing_order'] = [];
                        $tempRow['sub_categories'] = [];
                        $tempRow['partners'] = [];
                        $tempRow['previous_order'] = [];
                        $tempRow['banner'] = [];
                    }
                } else if ($type == "near_by_provider") {
                    $tempRow['parent_ids'] = $rate_parent_ids;
                    $tempRow['sub_categories'] = [];
                    $tempRow['partners'] = $rating_data;
                    $tempRow['previous_order'] =  [];
                    $tempRow['ongoing_order'] = [];
                    $tempRow['banner'] = [];
                } else if ($type == "banner") {
                    $tempRow['parent_ids'] = $parent_ids;
                    $tempRow['sub_categories'] = [];
                    $tempRow['partners'] = [];
                    $tempRow['previous_order'] =  [];
                    $tempRow['ongoing_order'] = [];
                    $tempRow['banner'] =  $feature_section_record;
                }
                $rows[] = $tempRow;
            }
            $section_data = remove_null_values($rows);
            $slider = new Slider_model();
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 50;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'sl.id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = [];
            if ($this->request->getPost('id')) {
                $where['id'] = $this->request->getPost('id');
            }
            if ($this->request->getPost('type')) {
                $where['type'] = $this->request->getPost('type');
            }
            if ($this->request->getPost('type_id')) {
                $where['type_id'] = $this->request->getPost('type_id');
            }
            $slider_data = $slider->list(true, $search, $limit, $offset, $sort, $order, $where);
            $categories = new Category_model();
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = [];
            if ($this->request->getPost('id')) {
                $where['id'] = $this->request->getPost('id');
            }
            if ($this->request->getPost('slug')) {
                $where['slug'] = $this->request->getPost('slug');
            }
            $where['parent_id'] = 0;
            $category_data = $categories->list(true, $search, null, null, $sort, $order, $where);
            $data['sections'] = $section_data;
            $data['sliders'] = remove_null_values($slider_data['data']);
            $data['categories'] = remove_null_values($category_data['data']);
            if (!empty($rows)) {
                $error = false;
                $message = 'sections fetched successfully';
            } else {
                $error = true;
                $message = 'data not found';
            }
            return response($message, $error, $data, 200);
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_home_screen_data()');
            $response['message'] = 'Something went wrong';
            return $this->response->setJSON($response);
        }
    }
    public function add_transaction()
    {
        try {
            $validation = service('validation');
            $validation->setRules([
                'order_id' => 'required|numeric',
                'status' => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $transaction_model = new Transaction_model();
            $order_id = (int) $this->request->getVar('order_id');
            $status = $this->request->getVar('status');
            $data['status'] = $status;
            $user = fetch_details('users', ['id' => $this->user_details['id']]);
            if (empty($user)) {
                $response = [
                    'error' => true,
                    'message' => "User not found!",
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $order = fetch_details('orders', ['id' => $this->request->getVar('order_id')]);
            if ($this->request->getVar('is_additional_charge') == 1) {
                $transaction_id = $this->request->getVar('transaction_id');
                if ($transaction_id) {
                    $transaction_check_for_additional_charge = fetch_details('transactions', ['order_id' => $this->request->getVar('order_id'), 'id' => $transaction_id]);
                    update_details(['status' => $status], ['id' => $transaction_check_for_additional_charge[0]['id']], 'transactions');
                    $t_id = $transaction_check_for_additional_charge[0]['id'];
                } else {
                    $data = [
                        'transaction_type' => 'transaction',
                        'user_id' => $this->user_details['id'],
                        'partner_id' => "",
                        'order_id' => $order_id,
                        'type' => $this->request->getVar('payment_method'),
                        'txn_id' => "",
                        'amount' => $order[0]['total_additional_charge'] ?? 0,
                        'status' => 'pending',
                        'currency_code' => "",
                        'message' => 'payment for additional charges',
                    ];
                    $t_id = add_transaction($data);
                }
                $fetch_transaction = fetch_details('transactions', ['id' => $t_id]);
                if ($this->request->getVar('is_additional_charge') == 1) {
                    $payment_method = $this->request->getVar('payment_method');
                    if ($payment_method == "paystack") {
                        $response['paystack_link'] = ($payment_method == "paystack") ? base_url() . '/api/v1/paystack_transaction_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $order_id . '&additional_charges_transaction_id=' . $t_id . '&amount=' . (number_format(strval($order[0]['total_additional_charge']), 2)) . '' : "";
                    } else if ($payment_method == "paypal") {
                        $response['paypal_link'] = ($payment_method == "paypal") ? base_url() . '/api/v1/paypal_transaction_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $order_id . '&additional_charges_transaction_id=' . $t_id . '&amount=' . number_format(strval($order[0]['total_additional_charge']), 2) . '' : "";
                    } else if ($payment_method == "flutterwave") {
                        $response['flutterwave_link'] = ($payment_method == "flutterwave") ? base_url() . '/api/v1/flutterwave_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $order_id . '&additional_charges_transaction_id=' . $t_id . '&amount=' . number_format(strval($order[0]['total_additional_charge']), 2) . '' : "";
                    }
                }
                $response['data'] = $fetch_transaction[0];
            }
            $transaction = fetch_details('transactions', ['order_id' => $this->request->getVar('order_id')]);
            if (!empty($order)) {
                $data['status'] = $status;
                $is_additional_charge = $this->request->getVar('is_additional_charge') == 1;
                $transaction = fetch_details('transactions', [
                    'order_id' => $order[0]['id'],
                    'id' => $transaction_check_for_additional_charge[0]['id'] ?? null,
                    'user_id' => $this->user_details['id']
                ]);
                if ($is_additional_charge) {
                    if ($this->request->getVar('transaction_id')) {
                        $transaction = fetch_details('transactions', [
                            'order_id' => $order[0]['id'],
                            'id' => $this->request->getVar('transaction_id') ?? null,
                            'user_id' => $this->user_details['id']
                        ]);
                        handleAdditionalCharge($status, $transaction[0], $order, $order_id, $this->user_details['id']);
                    } else {
                        handleAdditionalCharge($status, $transaction, $order, $order_id, $this->user_details['id']);
                    }
                    if ($this->request->getVar('payment_method') == "cod") {
                        update_details(['payment_status_of_additional_charge' => '0', 'payment_method_of_additional_charge' => $this->request->getVar('payment_method')], ['id' => $order_id], 'orders');
                    } else {
                        update_details(['payment_method_of_additional_charge' => $this->request->getVar('payment_method')], ['id' => $order_id], 'orders');
                    }
                    // update_details(['payment_status_of_additional_charge' => 1,'payment_method_of_additional_charges'=>$this->request->getVar('payment_method') ], ['id' => $order_id], 'orders');
                    $response['error'] = false;
                    $response['message'] = 'Status Updated';
                } else {
                    $update = update_details(['status' => "awaiting"], [
                        'id' => $order_id,
                        'status' => 'awaiting',
                        'user_id' => $this->user_details['id'],
                    ], 'orders');
                    if ($status == "success") {
                        handleSuccessfulTransaction($transaction, $order, $order_id, $this->user_details['id']);
                    } else {
                        handleFailedTransaction($transaction, $order, $order_id, $this->user_details['id']);
                    }
                    $response['error'] = false;
                    $response['message'] = 'Status Updated';
                }
            }
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - add_transaction()');
        }
        return $this->response->setJSON($response);
    }
    public function get_transactions()
    {
        try {
            $request = \Config\Services::request();
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'DESC';
            $user_id = $this->user_details['id'];
            if (!exists(['id' => $user_id], 'users')) {
                $response = [
                    'error' => true,
                    'message' => 'Invalid User Id.',
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $res = fetch_details('transactions', ['user_id' => $user_id], ['id', 'user_id', 'order_id', 'type', 'txn_id', 'amount', 'status', 'message', 'transaction_date', 'status'], $limit, $offset, $sort, $order);
            $res_total = fetch_details('transactions', ['user_id' => $user_id], ['id', 'user_id', 'order_id', 'type', 'txn_id', 'amount', 'status', 'message', 'transaction_date', 'status']);
            $total = count($res_total);
            if (!empty($res)) {
                $response = [
                    'error' => false,
                    'message' => 'Transactions recieved successfully.',
                    'total' => $total,
                    'data' => $res,
                ];
                return $this->response->setJSON($response);
            } else {
                $response = [
                    'error' => true,
                    'message' => 'No data found',
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_transactions()');
            return $this->response->setJSON($response);
        }
    }
    public function add_address()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'address_id' => 'permit_empty',
                    'mobile' => 'required|numeric',
                    'address' => 'required|',
                    'city_name' => 'required',
                    'lattitude' => 'required|numeric',
                    'longitude' => 'required|numeric',
                    'area' => 'required',
                    'type' => 'required',
                    'country_code' => 'permit_empty',
                    'alternate_mobile' => 'permit_empty|numeric',
                    'landmark' => 'permit_empty',
                    'pincode' => 'permit_empty|numeric',
                    'state' => 'permit_empty',
                    'country' => 'permit_empty',
                    'is_default' => 'permit_empty',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $data = [
                'user_id' => $this->user_details['id'],
                'type' => $this->request->getPost('type'),
                'address' => $this->request->getPost('address'),
                'area' => $this->request->getPost('area'),
                'mobile' => $this->request->getPost('mobile'),
                'city' => $this->request->getPost('city_name'),
                'lattitude' => $this->request->getPost('lattitude'),
                'longitude' => $this->request->getPost('longitude'),
                'alternate_mobile' => ($this->request->getPost('alternate_mobile') && !empty($this->request->getPost('alternate_mobile'))) ? $this->request->getPost('alternate_mobile') : null,
                'pincode' => ($this->request->getPost('pincode') && !empty($this->request->getPost('pincode'))) ? $this->request->getPost('pincode') : null,
                'landmark' => ($this->request->getPost('landmark') && !empty($this->request->getPost('landmark'))) ? $this->request->getPost('landmark') : null,
                'state' => ($this->request->getPost('state') && !empty($this->request->getPost('state'))) ? $this->request->getPost('state') : null,
                'country' => ($this->request->getPost('country') && !empty($this->request->getPost('country'))) ? $this->request->getPost('country') : null,
                'is_default' => ($this->request->getPost('is_default') && !empty($this->request->getPost('is_default'))) ? $this->request->getPost('is_default') : 0,
            ];
            if ($this->request->getPost('address_id')) {
                if (!exists(['id' => $this->request->getPost('address_id')], 'addresses')) {
                    return response('address not exist');
                }
                $address_id = $this->request->getPost('address_id');
                if (isset($data['is_default']) && $data['is_default'] == 1) {
                    $address = fetch_details('addresses', ['id' => $address_id]);
                    update_details(['is_default' => '0'], ['user_id' => $address[0]['user_id']], 'addresses');
                    update_details(['is_default' => '1'], ['id' => $address_id], 'addresses');
                }
                if (update_details($data, ['id' => $address_id], 'addresses', false)) {
                    $action = true;
                    $message = "address updated successfully";
                } else {
                    $action = false;
                    $message = "address not updated";
                }
            } else {
                if ($address = insert_details($data, 'addresses')) {
                    $last_added_id = $address['id'];
                    if (isset($data['is_default']) && $data['is_default'] == 1) {
                        update_details(['is_default' => '0'], ['user_id' => $data['user_id']], 'addresses');
                        update_details(['is_default' => '1'], ['id' => $last_added_id], 'addresses');
                    }
                    $action = true;
                    $message = "address added successfully";
                    $address_id = $address['id'];
                } else {
                    $action = false;
                    $message = "address not added";
                }
            }
            if ($action) {
                $data = [];
                return response($message, false, $data);
            } else {
                return response($message);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - add_address()');
            return $this->response->setJSON($response);
        }
    }
    public function delete_address()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'address_id' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $address_id = $this->request->getPost('address_id');
            $data1 = [];
            if (!exists(['id' => $this->request->getPost('address_id'), 'user_id' => $this->user_details['id']], 'addresses')) {
                return response('address not exist');
            }
            if (delete_details(['id' => $address_id], 'addresses')) {
                $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 20;
                $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
                $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
                $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
                $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
                $where = [];
                $where['a.user_id'] = $this->user_details['id'];
                if ($this->request->getPost('address_id')) {
                    $where['a.id'] = $this->request->getPost('address_id');
                }
                if (!empty($address_id)) {
                    $where['a.id'] = $address_id;
                }
                $is_default_counter = fetch_details('addresses', ['user_id' => $this->user_details['id'], 'is_default' => '1']);
                if (empty($is_default_counter)) {
                    $data = fetch_details('addresses', ['user_id' => $this->user_details['id']]);
                    if (!empty($data[0])) {
                        update_details(['is_default' => '1'], ['id' => $data[0]['id']], 'addresses');
                    }
                    $data1 = fetch_details('addresses', ['user_id' => $this->user_details['id']]);
                }
                return response('Address Deleted successfully', false, $data1);
            } else {
                return response('Address not deleted');
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - delete_address()');
            return $this->response->setJSON($response);
        }
    }
    public function get_address($address_id = 0)
    {
        try {
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 20;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = [];
            $where['a.user_id'] = $this->user_details['id'];
            if ($this->request->getPost('address_id')) {
                $where['a.id'] = $this->request->getPost('address_id');
            }
            if (!empty($address_id)) {
                $where['a.id'] = $address_id;
            }
            $address_model = new Addresses_model();
            $address = $address_model->list(true, $search, $limit, $offset, $sort, $order, $where);
            $is_default_counter = array_count_values(array_column($address['data'], 'is_default'));
            if (!isset($is_default_counter['1']) && !empty($address['data'])) {
                update_details(['is_default' => '1'], ['id' => $address['data'][0]['id']], 'addresses');
            }
            if (!empty($address_id)) {
                return remove_null_values($address['data']);
            }
            if (!empty($address['data'])) {
                return response('addresses fetched successfully', false, remove_null_values($address['data']), 200, ['total' => $address['total']]);
            } else {
                return response('address not found', false);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_address()');
            return $this->response->setJSON($response);
        }
    }
    public function validate_promo_code()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'promo_code_id' => 'required',
                    'final_total' => 'required|numeric',
                    
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $promo_code = $this->request->getPost('promo_code_id');
            $final_total = $this->request->getPost('final_total');
            // if (!exists(['promo_code' => $promo_code], 'promo_codes')) {
            //     return response('promo code not exist');
            // }
            $fetch_promococde = fetch_details('promo_codes', ['id' => $promo_code]);
            $promo_code = validate_promo_code($this->user_details['id'], $fetch_promococde[0]['id'], $final_total);
            if ($promo_code['error'] == false) {
                return response($promo_code['message'], false, remove_null_values($promo_code['data']));
            } else {
                return response($promo_code['message']);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            return $this->response->setJSON($response);
        }
    }
    public function get_promo_codes()
    {
        if (!empty($this->user_details)) {
            $user_id = $this->user_details['id'];
        } else {
            $user_id = 0;
        }
        try {
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = [];
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'partner_id' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $partner_id = $this->request->getPost('partner_id');
            $where = ['pc.partner_id' => $partner_id, 'pc.status' => 1, ' start_date <= ' => date('Y-m-d'), '  end_date >= ' => date('Y-m-d')];
            $promo_codes_model = new Promo_code_model();
            $promo_codes = $promo_codes_model->list(true, $search, null, null, $limit, $order, $where);
            if (!empty($promo_codes['data'])) {
                return response('promo codes fetched successfully', false, remove_null_values($promo_codes['data']), 200, ['total' => $promo_codes['total']]);
            } else {
                return response('Data Not Found');
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_promo_codes()');
            return $this->response->setJSON($response);
        }
    }
    public function get_categories()
    {
        try {
            $is_landing_page = !empty($this->request->getPost('is_landing_page')) ? $this->request->getPost('is_landing_page') : 0;
            if ($is_landing_page != 1) {
                $validation = \Config\Services::validation();
                $validation->setRules(
                    [
                        'latitude' => 'required',
                        'longitude' => 'required',
                    ]
                );
                if (!$validation->withRequest($this->request)->run()) {
                    $errors = $validation->getErrors();
                    $response = [
                        'error' => true,
                        'message' => $errors,
                        'data' => [],
                    ];
                    return $this->response->setJSON($response);
                }
            }
            $categories = new Category_model();
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = [];
            if ($this->request->getPost('id')) {
                $where['id'] = $this->request->getPost('id');
            }
            if ($this->request->getPost('slug')) {
                $where['slug'] = $this->request->getPost('slug');
            }
            $where['parent_id'] = 0;
            $data = $categories->list(true, $search, null, null, $sort, $order, $where);
            $db = \Config\Database::connect();
            $customer_latitude = $this->request->getPost('latitude') ?? "";
            $customer_longitude = $this->request->getPost('longitude') ?? "";
            $settings = get_settings('general_settings', true);
            $builder = $db->table('users u');
            $distance = isset($settings['max_serviceable_distance']) ? $settings['max_serviceable_distance'] : "50";
            if ($is_landing_page == 1) {
                $partners = $builder->Select("u.username,u.city,u.latitude,u.longitude,u.id")
                    ->join('users_groups ug', 'ug.user_id=u.id')
                    ->where('ug.group_id', '3')
                    ->where('u.latitude is  NOT NULL')
                    ->where('u.longitude is  NOT NULL')
                    ->get()->getResultArray();
            } else {
                $partners = $builder->Select("u.username,u.city,u.latitude,u.longitude,u.id,st_distance_sphere(POINT($customer_longitude, $customer_latitude),POINT(`u`.`longitude`, `u`.`latitude` ))/1000 as distance")
                    ->join('users_groups ug', 'ug.user_id=u.id')
                    ->where('ug.group_id', '3')
                    ->where('u.latitude is  NOT NULL')
                    ->where('u.longitude is  NOT NULL')
                    ->having('distance < ' . $distance)
                    ->orderBy('distance')
                    ->get()->getResultArray();
            }
            if (!empty($partners)) {
                if (!empty($data['data'])) {
                    return response('Categories fetched successfully', false, $data['data'], 200, ['total' => $data['total']]);
                } else {
                    return response('categories not found', false);
                }
            } else {
                return response('categories not found', false);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_categories()');
            return $this->response->setJSON($response);
        }
    }
    public function get_sub_categories()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'category_id' => 'required',
                    'latitude' => 'required',
                    'longitude' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $categories = new Category_model();
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = [];
            if ($this->request->getPost('id')) {
                $where['id'] = $this->request->getPost('id');
            }
            if ($this->request->getPost('id')) {
                $where['status'] = 1;
            }
            if ($this->request->getPost('slug')) {
                $where['slug'] = $this->request->getPost('slug');
            }
            if ($this->request->getPost('category_id')) {
                $where['parent_id'] = $this->request->getPost('category_id');
            }
            if (!exists(['parent_id' => $this->request->getPost('category_id')], 'categories')) {
                return response('no sub categories found');
            }
            $data = $categories->list(true, $search, null, null, $sort, $order, $where);
            $db = \Config\Database::connect();
            $customer_latitude = $this->request->getPost('latitude');
            $customer_longitude = $this->request->getPost('longitude');
            $settings = get_settings('general_settings', true);
            $builder = $db->table('users u');
            $distance = $settings['max_serviceable_distance'];
            $partners = $builder->Select("u.username,u.city,u.latitude,u.longitude,u.id,st_distance_sphere(POINT($customer_longitude, $customer_latitude),POINT(`u`.`longitude`, `u`.`latitude` ))/1000 as distance")
                ->join('users_groups ug', 'ug.user_id=u.id')
                ->where('ug.group_id', '3')
                ->having('distance < ' . $distance)
                ->orderBy('distance')
                ->get()->getResultArray();
            if (!empty($partners)) {
                if (!empty($data['data'])) {
                    return response('Sub Categories fetched successfully', false, $data['data'], 200, ['total' => $data['total']]);
                } else {
                    return response('Sub categories not found', false);
                }
            } else {
                return response('Sub categories not found', false);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_sub_categories()');
            return $this->response->setJSON($response);
        }
    }
    public function get_sliders()
    {
        try {
            $slider = new Slider_model();
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = [];
            if ($this->request->getPost('id')) {
                $where['id'] = $this->request->getPost('id');
            }
            if ($this->request->getPost('type')) {
                $where['type'] = $this->request->getPost('type');
            }
            if ($this->request->getPost('type_id')) {
                $where['type_id'] = $this->request->getPost('type_id');
            }
            $data = $slider->list(true, $search, $limit, $offset, $sort, $order, $where);
            if (!empty($data['data'])) {
                return response('slider fetched successfully', false, $data['data'], 200, ['total' => $data['total']]);
            } else {
                return response('slider not found');
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_sliders()');
            return $this->response->setJSON($response);
        }
    }
    public function get_providers()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'latitude' => 'required',
                    'longitude' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $Partners_model = new Partners_model();
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 0;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('sort'))) ? $this->request->getPost('sort') : 'pd.id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $filter = ($this->request->getPost('filter') && !empty($this->request->getPost('filter'))) ? $this->request->getPost('filter') : '';
            $where = $additional_data = [];
            $customer_id = '';
            $city_id = '';
            $token = verify_app_request();
            $settings = get_settings('general_settings', true);
            if (empty($settings)) {
                $response = [
                    'error' => true,
                    'message' => "Finish the general settings in panel",
                ];
                return $this->response->setJSON($response);
            }
            if ($token['error'] == 0) {
                $customer_id = $token['data']['id'];
                $additional_data = [
                    'customer_id' => $customer_id,
                ];
                $settings = get_settings('general_settings', true);
                if (empty($settings)) {
                    $response = [
                        'error' => true,
                        'message' => "Finish the general settings in panel",
                    ];
                    return $this->response->setJSON($response);
                }
                if (empty($settings['max_serviceable_distance'])) {
                    $response = [
                        'error' => true,
                        'message' => "First set Max serviceable distance in panel",
                    ];
                    return $this->response->setJSON($response);
                }
                if (($this->request->getPost('latitude') && !empty($this->request->getPost('latitude')) && ($this->request->getPost('longitude') && !empty($this->request->getPost('longitude'))))) {
                    $additional_data = [
                        'latitude' => $this->request->getPost('latitude'),
                        'longitude' => $this->request->getPost('longitude'),
                        'max_serviceable_distance' => $settings['max_serviceable_distance'],
                    ];
                }
            }
            $settings = get_settings('general_settings', true);
            if (($this->request->getPost('latitude') && !empty($this->request->getPost('latitude')) && ($this->request->getPost('longitude') && !empty($this->request->getPost('longitude'))))) {
                if (empty($settings)) {
                    $response = [
                        'error' => true,
                        'message' => "Finish the general settings in panel",
                    ];
                    return $this->response->setJSON($response);
                }
                if (empty($settings['max_serviceable_distance'])) {
                    $response = [
                        'error' => true,
                        'message' => "First set Max serviceable distance in panel",
                    ];
                    return $this->response->setJSON($response);
                }
                $additional_data = [
                    'latitude' => $this->request->getPost('latitude'),
                    'longitude' => $this->request->getPost('longitude'),
                    'max_serviceable_distance' => $settings['max_serviceable_distance'],
                ];
            }
            if ($this->request->getPost('partner_id') && !empty($this->request->getPost('partner_id'))) {
                $where['pd.partner_id'] = $this->request->getPost('partner_id');
                $where_condition_for_max_order_limit = '';
                $where['ps.status'] = 'active';
            }
            $where['ps.status'] = 'active';
            $where['pd.is_approved'] = "1";
            if ($this->request->getPost('category_id') && !empty($this->request->getPost('category_id'))) {
                $category_id[] = $this->request->getPost('category_id');
                // $subcategory_data = fetch_details('categories', ['id' => $category_id], ['id', 'parent_id']);
                $subcategory_data = fetch_details('categories', ['parent_id' => $category_id], ['id', 'parent_id']);
                foreach ($subcategory_data as $res) {
                    array_push($category_id, $res['id']);
                }
                $c_id = implode(",", $category_id);
                $formatted_ids = array_map(function ($item) {
                    return "$item";
                }, explode(',', $c_id));
                $partner_ids = get_partner_ids('category', 'category_id', $formatted_ids, true);
                $where['ps.status'] = 'active';
                $data = (!empty($partner_ids)) ? $Partners_model->list(true, $search, $limit, $offset, $sort, $order, $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes') : [];
                if ((!empty($partner_ids)) && ($filter != '' && $filter == 'ratings')) {
                    $where['ps.status'] = 'active';
                    $data = $Partners_model->list(true, $search, $limit, $offset, ' pd.ratings', 'desc', $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes');
                }
                if ((!empty($partner_ids)) && ($filter != '' && $filter == 'discount')) {
                    $where['ps.status'] = 'active';
                    $data = $Partners_model->list(true, $search, $limit, $offset, ' maximum_discount_up_to', 'desc', $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes');
                }
                if ((!empty($partner_ids)) && ($filter != '' && $filter == 'popularity')) {
                    $where['ps.status'] = 'active';
                    $data = $Partners_model->list(true, $search, $limit, $offset, ' number_of_orders', 'desc', $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes');
                }
                $where_condition_for_max_order_limit = '';
            } else if ($this->request->getPost('service_id') && !empty($this->request->getPost('service_id'))) {
                $where['ps.status'] = 'active';
                $service_id[] = $this->request->getPost('service_id');
                $partner_ids = get_partner_ids('service', 'id', $service_id, true);
                $data = (!empty($partner_ids)) ? $Partners_model->list(true, $search, $limit, $offset, $sort, $order, $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes') :
                    [];
                if ((!empty($partner_ids)) && ($filter != '' && $filter == 'ratings')) {
                    $data = $Partners_model->list(true, $search, $limit, $offset, ' pd.ratings', $order, $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes');
                }
                if ((!empty($partner_ids)) && ($filter != '' && $filter == 'discount')) {
                    $data = $Partners_model->list(true, $search, $limit, $offset, ' maximum_discount_up_to', $order, $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes');
                }
                if ((!empty($partner_ids)) && ($filter != '' && $filter == 'popularity')) {
                    $data = $Partners_model->list(true, $search, $limit, $offset, ' number_of_orders', $order, $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes');
                }
                $where_condition_for_max_order_limit = '';
                $where['ps.status'] = 'active';
            } else if ($this->request->getPost('sub_category_id') && !empty($this->request->getPost('sub_category_id'))) {
                $where['ps.status'] = 'active';
                $sub_category_id[] = $this->request->getPost('sub_category_id');
                $partner_ids = get_partner_ids('category', 'category_id', $sub_category_id, true);
                $data = (!empty($partner_ids)) ? $Partners_model->list(true, $search, $limit, $offset, $sort, $order, $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes') : [];
                if ((!empty($partner_ids)) && ($filter != '' && $filter == 'ratings')) {
                    $data = $Partners_model->list(true, $search, $limit, $offset, 'pd.ratings', $order, $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes');
                }
                if ((!empty($partner_ids)) && ($filter != '' && $filter == 'discount')) {
                    $data = $Partners_model->list(true, $search, $limit, $offset, 'maximum_discount_up_to', $order, $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes');
                }
                if ((!empty($partner_ids)) && ($filter != '' && $filter == 'popularity')) {
                    $data = $Partners_model->list(true, $search, $limit, $offset, 'number_of_orders', $order, $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes');
                }
                $where_condition_for_max_order_limit = '';
                $where['ps.status'] = 'active';
            } elseif ($filter != '' && $filter == 'popularity') {
                $where['ps.status'] = 'active';
                $data = $Partners_model->list(true, $search, $limit, $offset, 'number_of_orders', 'desc', $where, 'partner_id', [], $additional_data, 'yes');
            } elseif ($filter != '' && $filter == 'ratings') {
                $where['ps.status'] = 'active';
                $data = $Partners_model->list(true, $search, $limit, $offset, ' pd.ratings', 'desc', $where, 'pd.partner_id', [], $additional_data, 'yes');
            } elseif ($filter != '' && $filter == 'discount') {
                $data = $Partners_model->list(true, $search, $limit, $offset, 'maximum_discount_up_to', 'desc', $where, 'pd.partner_id', [], $additional_data, 'yes');
            } else {
                $additional_data = [
                    'latitude' => $this->request->getPost('latitude'),
                    'longitude' => $this->request->getPost('longitude'),
                    'max_serviceable_distance' => $settings['max_serviceable_distance'],
                ];
                $where_condition_for_max_order_limit = '';
                $where['ps.status'] = 'active';
                $data = $Partners_model->list(true, $search, $limit, $offset, $sort, $order, $where, 'pd.id', [], $additional_data, 'yes');
            }
            $where['ps.status'] = 'active';
            if (!empty($data['data'])) {
                for ($i = 0; $i < count($data['data']); $i++) {
                    unset($data['data'][$i]['national_id']);
                    unset($data['data'][$i]['passport']);
                    unset($data['data'][$i]['tax_name']);
                    unset($data['data'][$i]['tax_number']);
                    unset($data['data'][$i]['bank_name']);
                    unset($data['data'][$i]['account_number']);
                    unset($data['data'][$i]['account_name']);
                    unset($data['data'][$i]['bank_code']);
                    unset($data['data'][$i]['swift_code']);
                    unset($data['data'][$i]['type']);
                    unset($data['data'][$i]['admin_commission']);
                }
                return response('partners fetched successfully', false, remove_null_values($data['data']), 200, ['total' => $data['total']]);
            } else {
                return response('partners fetched successfully', false, remove_null_values(isset($data['data']) ? $data['data'] : array()), 200, ['total' => isset($data['total']) ? $data['total'] : 0]);
                return response('partners not found..', false);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_providers()');
            return $this->response->setJSON($response);
        }
    }
    public function get_services()
    {
        try {
            $Service_model = new Service_model();
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $db      = \Config\Database::connect();
            $where = $additional_data = [];
            $where = [];
            $where['s.status'] = 1;
            $where['s.approved_by_admin'] = 1;
            $at_store = 0;
            $at_doorstep = 0;
            if ($this->request->getPost('partner_id') && !empty($this->request->getPost('partner_id'))) {
                $partner_details = fetch_details('partner_details', ['partner_id' => $this->request->getPost('partner_id')]);
                if (isset($partner_details[0]['at_store']) && $partner_details[0]['at_store'] == 1) {
                    $at_store = 1;
                }
                if (isset($partner_details[0]['at_doorstep']) && $partner_details[0]['at_doorstep'] == 1) {
                    $at_doorstep = 1;
                }
                $where['s.user_id'] = $this->request->getPost('partner_id');
            }
            if ($this->request->getPost('category_id') && !empty($this->request->getPost('category_id'))) {
                $where['category_id'] = $this->request->getPost('category_id');
            }
            if (isset($this->user_details['id']) && $this->user_details['id']) {
                $additional_data = ['s.user_id' => $this->user_details['id']];
            }
            $data = $Service_model->list(true, $search, $limit, $offset, $sort, $order, $where, $additional_data, '', '', '', $at_store, $at_doorstep);
            if (isset($data['error'])) {
                return response($data['message']);
            }
            if (!empty($data['data'])) {
                return response('services fetched successfully', false, $data['data'], 200, ['total' => $data['total']]);
            } else {
                return response('services not found');
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_services()');
            return $this->response->setJSON($response);
        }
    }
    public function manage_cart()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'service_id' => 'required|numeric',
                    'qty' => 'required|numeric|greater_than[0]',
                    'is_saved_for_later' => 'permit_empty|numeric',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $service = fetch_details('services', ['id' => $this->request->getPost('service_id')], ['max_quantity_allowed']);
            if (empty($service)) {
                return response('service not found');
            }
            if ($service[0]['max_quantity_allowed'] < $this->request->getPost('qty')) {
                return response('max quanity allowed ' . $service[0]['max_quantity_allowed']);
            }
            $current_service_id = $this->request->getPost('service_id');
            $get_service_id = fetch_details('services', ['id' => $current_service_id]);
            $has_booked_before = fetch_details('cart', ['user_id' => $this->user_details['id']], ['id', 'service_id']);
            $cart_data = fetch_details('cart', ['service_id' => $this->request->getPost('service_id'), 'user_id' => $this->user_details['id']], ['id', 'is_saved_for_later']);
            if (exists(['service_id' => $this->request->getPost('service_id'), 'user_id' => $this->user_details['id']], 'cart')) {
                if (update_details(
                    [
                        'qty' => $this->request->getPost('qty'),
                        'is_saved_for_later' => ($this->request->getPost('is_saved_for_later') == '') ? $cart_data[0]['is_saved_for_later']
                            : $this->request->getPost('is_saved_for_later'),
                    ],
                    ['service_id' => $this->request->getPost('service_id'), 'user_id' => $this->user_details['id']],
                    'cart'
                )) {
                    $error = false;
                    $message = 'cart updated successfully';
                    $user_id = $this->user_details['id'];
                    $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 0;
                    $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
                    $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
                    $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
                    $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
                    $where = [];
                    $cart_data = fetch_details('cart', ['user_id' => $user_id]);
                    if (empty($cart_data)) {
                        return response('item not found');
                    } else {
                        $cart_details = fetch_cart(true, $this->user_details['id'], $search, $limit, $offset, $sort, $order, $where);
                        if (!empty($cart_details['data'])) {
                            return response(
                                $message,
                                $error,
                                remove_null_values($cart_details['data']),
                                200,
                                remove_null_values(
                                    [
                                        'provider_id' => $cart_details['provider_id'],
                                        'provider_names' => $cart_details['provider_names'],
                                        'service_ids' => $cart_details['service_ids'],
                                        'qtys' => $cart_details['qtys'],
                                        'visiting_charges' => $cart_details['visiting_charges'],
                                        'advance_booking_days' => $cart_details['advance_booking_days'],
                                        'company_name' => $cart_details['company_name'],
                                        'total_duration' => $cart_details['total_duration'],
                                        'is_pay_later_allowed' => $cart_details['is_pay_later_allowed'],
                                        'total_quantity' => $cart_details['total_quantity'],
                                        'sub_total' => $cart_details['sub_total'],
                                        'overall_amount' => $cart_details['overall_amount'],
                                        'total' => $cart_details['total'],
                                        "at_store" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['at_store'] : "0",
                                        "at_doorstep" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['at_doorstep'] : "0",
                                        "is_online_payment_allowed" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['is_online_payment_allowed'] : "0",
                                    ]
                                )
                            );
                        } else {
                            return response('item not found');
                        }
                    }
                } else {
                    $error = true;
                    $message = 'cart not updated';
                    return response($message, $error);
                }
            } else {
                if (sizeof($has_booked_before) > 0) {
                    $current_partner_id = $get_service_id[0]['user_id'];
                    $pervious_service_id = $has_booked_before[0]['service_id'];
                    $pervious_user_id = fetch_details('services', ['id' => $pervious_service_id], ['user_id']);
                    if (empty($pervious_user_id)) {
                        $pervious_user_id = 0;
                    } else {
                        $pervious_user_id = fetch_details('services', ['id' => $pervious_service_id], ['user_id'])[0]['user_id'];
                    }
                    if ($current_partner_id == $pervious_user_id) {
                        if (insert_details(['service_id' => $this->request->getPost('service_id'), 'qty' => $this->request->getPost('qty'), 'is_saved_for_later' => ($this->request->getPost('is_saved_for_later' != '')) ? $this->request->getPost('is_saved_for_later') : 0, 'user_id' => $this->user_details['id']], 'cart')) {
                            $error = false;
                            $message = 'cart added successfully';
                            $user_id = $this->user_details['id'];
                            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 0;
                            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
                            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
                            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
                            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
                            $where = [];
                            $cart_data = fetch_details('cart', ['user_id' => $user_id]);
                            if (empty($cart_data)) {
                                return response('item not found');
                            } else {
                                $cart_details = fetch_cart(true, $this->user_details['id'], $search, $limit, $offset, $sort, $order, $where);
                                if (!empty($cart_details['data'])) {
                                    return response(
                                        $message,
                                        $error,
                                        remove_null_values($cart_details['data']),
                                        200,
                                        remove_null_values(
                                            [
                                                'provider_id' => $cart_details['provider_id'],
                                                'provider_names' => $cart_details['provider_names'],
                                                'service_ids' => $cart_details['service_ids'],
                                                'qtys' => $cart_details['qtys'],
                                                'visiting_charges' => $cart_details['visiting_charges'],
                                                'advance_booking_days' => $cart_details['advance_booking_days'],
                                                'company_name' => $cart_details['company_name'],
                                                'total_duration' => $cart_details['total_duration'],
                                                'is_pay_later_allowed' => $cart_details['is_pay_later_allowed'],
                                                'total_quantity' => $cart_details['total_quantity'],
                                                'sub_total' => $cart_details['sub_total'],
                                                'overall_amount' => $cart_details['overall_amount'],
                                                'total' => $cart_details['total'],
                                                "at_store" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['at_store'] : "0",
                                                "at_doorstep" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['at_doorstep'] : "0",
                                                "is_online_payment_allowed" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['is_online_payment_allowed'] : "0",
                                            ]
                                        )
                                    );
                                } else {
                                    return response('item not found');
                                }
                            }
                        } else {
                            $error = true;
                            $message = 'cart not added';
                            return response($message, $error);
                        }
                    } else {
                        $user_id = $this->user_details['id'];
                        delete_details(['user_id' => $user_id], 'cart');
                        insert_details(['service_id' => $this->request->getPost('service_id'), 'qty' => $this->request->getPost('qty'), 'is_saved_for_later' => ($this->request->getPost('is_saved_for_later' != '')) ? $this->request->getPost('is_saved_for_later') : 0, 'user_id' => $this->user_details['id']], 'cart');
                        $cart_details = fetch_cart(true, $this->user_details['id'], '', 10, 0, '', '', '');
                        $error = false;
                        $message = 'cart added successfully';
                        if (!empty($cart_details['data'])) {
                            return response(
                                $message,
                                $error,
                                remove_null_values($cart_details['data']),
                                200,
                                remove_null_values(
                                    [
                                        'provider_id' => $cart_details['provider_id'],
                                        'provider_names' => $cart_details['provider_names'],
                                        'service_ids' => $cart_details['service_ids'],
                                        'qtys' => $cart_details['qtys'],
                                        'visiting_charges' => $cart_details['visiting_charges'],
                                        'advance_booking_days' => $cart_details['advance_booking_days'],
                                        'company_name' => $cart_details['company_name'],
                                        'total_duration' => $cart_details['total_duration'],
                                        'is_pay_later_allowed' => $cart_details['is_pay_later_allowed'],
                                        'total_quantity' => $cart_details['total_quantity'],
                                        'sub_total' => $cart_details['sub_total'],
                                        'overall_amount' => $cart_details['overall_amount'],
                                        'total' => $cart_details['total'],
                                        "at_store" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['at_store'] : "0",
                                        "at_doorstep" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['at_doorstep'] : "0",
                                        "is_online_payment_allowed" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['is_online_payment_allowed'] : "0",
                                    ]
                                )
                            );
                        } else {
                            return response('item not found');
                        }
                    }
                } else {
                    if (insert_details(
                        [
                            'service_id' => $this->request->getPost('service_id'),
                            'qty' => $this->request->getPost('qty'),
                            'is_saved_for_later' => ($this->request->getPost('is_saved_for_later') != '') ? $this->request->getPost('is_saved_for_later') : '0',
                            'user_id' => $this->user_details['id'],
                        ],
                        'cart'
                    )) {
                        $error = false;
                        $message = 'cart added successfully';
                        $user_id = $this->user_details['id'];
                        $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
                        $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
                        $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
                        $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
                        $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
                        $where = [];
                        $cart_data = fetch_details('cart', ['user_id' => $user_id]);
                        if (empty($cart_data)) {
                            return response('item not found');
                        } else {
                            $cart_details = fetch_cart(true, $this->user_details['id'], $search, $limit, $offset, $sort, $order, $where);
                            if (!empty($cart_details['data'])) {
                                return response(
                                    $message,
                                    $error,
                                    remove_null_values($cart_details['data']),
                                    200,
                                    remove_null_values(
                                        [
                                            'provider_id' => $cart_details['provider_id'],
                                            'provider_names' => $cart_details['provider_names'],
                                            'service_ids' => $cart_details['service_ids'],
                                            'qtys' => $cart_details['qtys'],
                                            'visiting_charges' => $cart_details['visiting_charges'],
                                            'advance_booking_days' => $cart_details['advance_booking_days'],
                                            'company_name' => $cart_details['company_name'],
                                            'total_duration' => $cart_details['total_duration'],
                                            'is_pay_later_allowed' => $cart_details['is_pay_later_allowed'],
                                            'total_quantity' => $cart_details['total_quantity'],
                                            'sub_total' => $cart_details['sub_total'],
                                            'overall_amount' => $cart_details['overall_amount'],
                                            'total' => $cart_details['total'],
                                            "at_store" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['at_store'] : "0",
                                            "at_doorstep" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['at_doorstep'] : "0",
                                            "is_online_payment_allowed" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['is_online_payment_allowed'] : "0",
                                        ]
                                    )
                                );
                            } else {
                                return response('item not found');
                            }
                        }
                    } else {
                        $error = true;
                        $message = 'cart not added';
                        return response($message, $error);
                    }
                }
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - manage_cart()');
            return $this->response->setJSON($response);
        }
    }
    public function remove_from_cart()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'cart_id' => 'permit_empty',
                    'service_id' => 'permit_empty|numeric',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $tax = get_settings('system_tax_settings', true)['tax'];
            $db = \Config\Database::connect();
            if (!empty($this->request->getPost('provider_id')) && empty($this->request->getPost('service_id'))) {
                $user_id = $this->user_details['id'];
                $providerid = $this->request->getPost('provider_id');
                $cart = fetch_details('cart', ['user_id' => $user_id]);
                $is_provider = true;
                $error = false;
                $message = '';
                foreach ($cart as $row) {
                    $check_service_provider = fetch_details('services', ['id' => $row['service_id']], ['user_id']);
                    if ($check_service_provider[0]['user_id'] != $providerid) {
                        $is_provider = false;
                        $db = \Config\Database::connect();
                        $builder = $db->table('cart');
                        $builder->delete(['id' => $row['id']]);
                    }
                }
                // If all services are from the specified provider, delete the entire cart
                if ($is_provider) {
                    $db = \Config\Database::connect();
                    $builder = $db->table('cart');
                    $builder->delete(['user_id' => $user_id]); // Assuming 'user_id' is the field for identifying the user's cart
                    $message = 'Cart deleted successfully!';
                } else {
                    $error = true;
                    $message = 'Some items were not from the specified provider and have been removed from the cart!';
                }
                return response($message, $error);
            } else {
                if (!exists(['service_id' => $this->request->getPost('service_id'), 'user_id' => $this->user_details['id']], 'cart')) {
                    return response('service not exist in cart');
                }
                if (delete_details(['service_id' => $this->request->getPost('service_id')], 'cart')) {
                    $error = false;
                    $message = 'service removed from cart';
                    $user_id = $this->user_details['id'];
                    $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 0;
                    $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
                    $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
                    $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
                    $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
                    $where = [];
                    $cart_data = fetch_details('cart', ['user_id' => $user_id]);
                    if (empty($cart_data)) {
                        return response($message, $error);
                    } else {
                        $cart_details = fetch_cart(true, $this->user_details['id'], $search, $limit, $offset, $sort, $order, $where);
                        if (!empty($cart_details['data'])) {
                            return response(
                                $message,
                                $error,
                                remove_null_values($cart_details['data']),
                                200,
                                remove_null_values(
                                    [
                                        'provider_id' => $cart_details['provider_id'],
                                        'provider_names' => $cart_details['provider_names'],
                                        'service_ids' => $cart_details['service_ids'],
                                        'qtys' => $cart_details['qtys'],
                                        'visiting_charges' => $cart_details['visiting_charges'],
                                        'advance_booking_days' => $cart_details['advance_booking_days'],
                                        'company_name' => $cart_details['company_name'],
                                        'total_duration' => $cart_details['total_duration'],
                                        'is_pay_later_allowed' => $cart_details['is_pay_later_allowed'],
                                        'total_quantity' => $cart_details['total_quantity'],
                                        'sub_total' => $cart_details['sub_total'],
                                        'overall_amount' => $cart_details['overall_amount'],
                                        'total' => $cart_details['total'],
                                        "at_store" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['at_store'] : "0",
                                        "at_doorstep" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['at_doorstep'] : "0",
                                        "is_online_payment_allowed" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['is_online_payment_allowed'] : "0",
                                    ]
                                )
                            );
                        } else {
                            return response('item not found');
                        }
                    }
                } else {
                    $error = true;
                    $message = 'service not removed from cart';
                    return response($message, $error);
                }
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - remove_from_cart()');
            return $this->response->setJSON($response);
        }
    }
    public function get_cart()
    {
        try {
            $user_id = $this->user_details['id'];
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 0;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = [];
            $cart_data = fetch_details('cart', ['user_id' => $user_id]);
            $reorder_details = fetch_cart(true, $this->user_details['id'], $search, $limit, $offset, $sort, $order, $where, null, 'yes', $this->request->getPost('order_id'));
            if (empty($cart_data) && empty($reorder_details)) {
                return response('item not found');
            } else {
                $cart_details = fetch_cart(true, $this->user_details['id'], $search, $limit, $offset, $sort, $order, $where, []);
                if (!empty($cart_details)) {
                    foreach ($cart_details['data'] as $key => $row) {
                        $check_service_status = fetch_details('services', ['id' => $row['service_id']], ['status']);
                        if ($check_service_status[0]['status'] == 0) {
                            unset($cart_details['data'][$key]);
                        }
                    }
                    $check_provider_status = fetch_details('partner_details', ['partner_id' => $cart_details['provider_id']], ['is_approved']);
                    if ($check_provider_status[0]['is_approved'] == 0) {
                        return response('item not found');
                    }
                    $is_already_subscribe = fetch_details('partner_subscriptions', ['partner_id' => $cart_details['provider_id']]);
                    if (isset($is_already_subscribe[0]['status']) && $is_already_subscribe[0]['status'] != "active") {
                        return response('item not found');
                    }
                    if (!empty($this->request->getPost('order_id'))) {
                        $reorder_details = fetch_cart(true, $this->user_details['id'], $search, $limit, $offset, $sort, $order, $where, null, 'yes', $this->request->getPost('order_id'));
                        if ($check_provider_status[0]['is_approved'] == 0) {
                            return response('item not found');
                        }
                        if (empty($reorder_details)) {
                            $response['error'] = true;
                            $response['message'] = 'order not found';
                            return $this->response->setJSON($response);
                        }
                    }
                }
                $data = array();
                $data['cart_data'] = [
                    "data" => (!empty($cart_details) && isset($cart_details)) ? remove_null_values($cart_details['data']) : "",
                    "provider_id" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['provider_id'] : "",
                    "provider_names" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['provider_names'] : "",
                    "service_ids" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['service_ids'] : "",
                    "qtys" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['qtys'] : "",
                    "visiting_charges" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['visiting_charges'] : "",
                    "advance_booking_days" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['advance_booking_days'] : "",
                    "company_name" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['company_name'] : "",
                    "total_duration" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['total_duration'] : "",
                    "is_pay_later_allowed" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['is_pay_later_allowed'] : "",
                    "total_quantity" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['total_quantity'] : "",
                    "sub_total" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['sub_total'] : "",
                    "overall_amount" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['overall_amount'] : "",
                    "total" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['total'] : "",
                    "at_store" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['at_store'] : "0",
                    "at_doorstep" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['at_doorstep'] : "0",
                    "is_online_payment_allowed" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['is_online_payment_allowed'] : "0",
                ];
                if ($this->request->getPost('order_id')) {
                    $data['reorder_data'] = [
                        "data" => (!empty($reorder_details) && isset($reorder_details)) ? remove_null_values($reorder_details['data']) : "",
                        "provider_id" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['provider_id'] : "",
                        "provider_names" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['provider_names'] : "",
                        "service_ids" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['service_ids'] : "",
                        "qtys" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['qtys'] : "",
                        "visiting_charges" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['visiting_charges'] : "",
                        "advance_booking_days" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['advance_booking_days'] : "",
                        "company_name" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['company_name'] : "",
                        "total_duration" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['total_duration'] : "",
                        "is_pay_later_allowed" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['is_pay_later_allowed'] : "",
                        "total_quantity" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['total_quantity'] : "",
                        "sub_total" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['sub_total'] : "",
                        "overall_amount" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['overall_amount'] : "",
                        "total" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['total'] : "",
                        "at_store" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['at_store'] : "0",
                        "at_doorstep" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['at_doorstep'] : "0",
                        "is_online_payment_allowed" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['is_online_payment_allowed'] : "0",
                    ];
                } else {
                    $data['reorder_data'] = (object)[];
                }
                return response(
                    'cart fetched successfully',
                    false,
                    $data,
                    200,
                );
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_cart()');
            return $this->response->setJSON($response);
        }
    }
    // public function place_order()
    // {
    //     try {
    //         $validation = \Config\Services::validation();
    //         $rules = [
    //             'promo_code_id' => 'permit_empty',
    //             'payment_method' => 'required',
    //             'status' => 'required',
    //             'date_of_service' => 'required|valid_date[Y-m-d]',
    //             'starting_time' => 'required',
    //         ];
    //         $at_store = $this->request->getVar('at_store');
    //         if ($at_store == 1) {
    //             $rules['address_id'] = 'permit_empty|numeric';
    //         } else {
    //             $rules['address_id'] = 'required|numeric';
    //         }
    //         $validation->setRules($rules);
    //         if (!$validation->withRequest($this->request)->run()) {
    //             $errors = $validation->getErrors();
    //             $response = [
    //                 'error' => true,
    //                 'message' => $errors,
    //                 'data' => ['type' => 'neworder'],
    //             ];
    //             return $this->response->setJSON($response);
    //         }
    //         if (empty($this->request->getVar('order_id'))) {
    //             $cart_data = fetch_cart(true, $this->user_details['id']);
    //         }
    //         if (empty($this->request->getVar('order_id'))) {
    //             if (empty($cart_data)) {
    //                 return response("Please add some item in cart", true);
    //             }
    //         }
    //         $db = \Config\Database::connect();
    //         if ((empty($this->request->getVar('order_id')))) {
    //             $service_ids = $cart_data['service_ids'];
    //             $quantity = $cart_data['qtys'];
    //             $total = $cart_data['sub_total'];
    //         } else {
    //             $order = fetch_details('order_services', ['order_id' => $this->request->getPost('order_id')]);
    //             $service_ids = [];
    //             foreach ($order as $row) {
    //                 $service_ids[] = $row['service_id'];
    //             }
    //             $all_service_data = array();
    //             foreach ($service_ids as $row2) {
    //                 $service_data_array = fetch_details('services', ['id' => $row2]);
    //                 $service_data = $service_data_array[0];
    //                 $all_service_data[] = $service_data;
    //             }
    //             $quantities = [];
    //             foreach ($order as $row) {
    //                 $quantities[] = $row['quantity'];
    //             }
    //             $quantity = implode(',', $quantities);
    //             $total = 0;
    //             $tax_value = 0;
    //             $sub_total = 0;
    //             $duartion = 0;
    //             $builder = $db->table('order_services os');
    //             $service_record = $builder
    //                 ->select('os.id as order_service_id,os.service_id,os.quantity,s.*,s.title as service_name,p.username as partner_name,pd.visiting_charges as visiting_charges,cat.name as category_name')
    //                 ->join('services s', 'os.service_id=s.id', 'left')
    //                 ->join('users p', 'p.id=s.user_id', 'left')
    //                 ->join('categories cat', 'cat.id=s.category_id', 'left')
    //                 ->join('partner_details pd', 'pd.partner_id=s.user_id', 'left')
    //                 ->where('os.order_id',  $this->request->getPost('order_id'))->get()->getResultArray();
    //             foreach ($service_record as $s1) {
    //                 $taxPercentageData = fetch_details('taxes', ['id' => $s1['tax_id']], ['percentage']);
    //                 if (!empty($taxPercentageData)) {
    //                     $taxPercentage = $taxPercentageData[0]['percentage'];
    //                 } else {
    //                     $taxPercentage = 0;
    //                 }
    //                 if ($s1['discounted_price'] == "0") {
    //                     $tax_value = ($s1['tax_type'] == "excluded") ? number_format(((($s1['price'] * ($taxPercentage) / 100))), 2) : 0;
    //                     $price = number_format($s1['price'], 2);
    //                 } else {
    //                     $tax_value = ($s1['tax_type'] == "excluded") ? number_format(((($s1['discounted_price'] * ($taxPercentage) / 100))), 2) : 0;
    //                     $price = number_format($s1['discounted_price'], 2);
    //                 }
    //                 $sub_total = $sub_total + (floatval(str_replace(",", "", $price)) + $tax_value) * $s1['quantity'];
    //                 $duartion = $duartion + $s1['duration'] * $s1['quantity'];
    //             }
    //             $total = $sub_total;
    //         }
    //         if ($at_store == "1") {
    //             $visiting_charges = 0;
    //         } else {
    //             if (empty($this->request->getPost('order_id'))) {
    //                 $visiting_charges = $cart_data['visiting_charges'];
    //             } else {
    //                 $builder = $db->table('services s');
    //                 $extra_data = $builder
    //                     ->select('SUM(IF(s.discounted_price  > 0 , (s.discounted_price * os1.quantity) , (s.price *  os1.quantity))) as subtotal,
    //                 SUM( os1.quantity) as total_quantity,pd.visiting_charges as visiting_charges,SUM(s.duration *  os1.quantity) as total_duration,pd.advance_booking_days as advance_booking_days,
    //                 pd.company_name as company_name')
    //                     ->join('order_services os1', 'os1.service_id = s.id')
    //                     ->join('partner_details pd', 'pd.partner_id=s.user_id')
    //                     ->where('os1.order_id',  $this->request->getPost('order_id'))
    //                     ->whereIn('s.id', $service_ids)->get()->getResultArray();
    //                 $visiting_charges = $extra_data[0]['visiting_charges'];
    //             }
    //         }
    //         $promo_code = $this->request->getVar('promo_code_id');
    //         $payment_method = $this->request->getVar('payment_method');
    //         $address_id = ($at_store == 1) ? 0 : $this->request->getVar('address_id');
    //         // $status = strtolower($this->request->getVar('status'));
    //         $status = "awaiting";
    //         $date_of_service = $this->request->getVar('date_of_service');
    //         $starting_time = ($this->request->getVar('starting_time'));
    //         $order_note = ($this->request->getVar('order_note')) ? $this->request->getVar('order_note') : "";
    //         if (empty($this->request->getPost('order_id'))) {
    //             $minutes = strtotime($starting_time) + ($cart_data['total_duration'] * 60);
    //         } else {
    //             $minutes = strtotime($starting_time) + ($duartion * 60);
    //         }
    //         $ending_time = date('H:i:s', $minutes);
    //         if ($at_store != 1) {
    //             if (!exists(['id' => $address_id], 'addresses')) {
    //                 return response('Address not exist');
    //             }
    //         }
    //         $final_total = ($total) + ($visiting_charges);
    //         if (empty($this->request->getPost('order_id'))) {
    //             $ids = explode(',', $service_ids ?? '');
    //         } else {
    //             $ids = $service_ids;
    //         }
    //         $qtys = explode(',', $quantity ?? '');
    //         $service_data = fetch_details('services', [], '', '', '', '', '', 'id', $ids);
    //         $partner_id = $service_data[0]['user_id'];
    //         $current_date = date('Y-m-d');
    //         $service_total_duration = 0;
    //         $service_duration = 0;
    //         if (empty($this->request->getPost('order_id'))) {
    //             foreach ($cart_data['data'] as $main_data) {
    //                 $service_duration = ($main_data['servic_details']['duration']) * $main_data['qty'];
    //                 $service_total_duration = $service_total_duration + $service_duration;
    //             }
    //         } else {
    //             $service_total_duration = $duartion;
    //         }
    //         $availability =  checkPartnerAvailability($partner_id, $date_of_service . ' ' . $starting_time, $service_total_duration, $date_of_service, $starting_time);
    //         $insert_order = "";
    //         if (isset($availability) && $availability['error'] == "0") {
    //             $location_data = fetch_details('addresses', ['id' => $address_id]);
    //             $address['mobile'] = isset($location_data) && !empty($location_data) ? $location_data[0]['mobile'] : '';
    //             $address['address'] = isset($location_data) && !empty($location_data) ? $location_data[0]['address'] : '';
    //             $address['area'] = isset($location_data) && !empty($location_data) ? $location_data[0]['area'] : '';
    //             $address['city'] = isset($location_data) && !empty($location_data) ? $location_data[0]['city'] : '';
    //             $address['state'] = isset($location_data) && !empty($location_data) ? $location_data[0]['state'] : '';
    //             $address['country'] = isset($location_data) && !empty($location_data) ? $location_data[0]['country'] : '';
    //             $address['pincode'] = isset($location_data) && !empty($location_data) ? $location_data[0]['pincode'] : '';
    //             $city_id = isset($location_data) && !empty($location_data) ? $location_data[0]['city'] : '';
    //             $outputArray = array(
    //                 $address['address'],
    //                 $address['area'],
    //                 $address['city'],
    //                 $address['state'],
    //                 $address['country'],
    //                 $address['pincode'],
    //                 $address['mobile']
    //             );
    //             $finaladdress = implode(',', $outputArray);
    //             $service_total_duration = 0;
    //             $service_duration = 0;
    //             if (empty($this->request->getPost('order_id'))) {
    //                 foreach ($cart_data['data'] as $main_data) {
    //                     $service_duration = ($main_data['servic_details']['duration']) * $main_data['qty'];
    //                     $service_total_duration = $service_total_duration + $service_duration;
    //                 }
    //             } else {
    //                 $service_total_duration = $duartion;
    //             }
    //             $time_slots = get_slot_for_place_order($partner_id, $date_of_service, $service_total_duration, $starting_time);
    //             // $timestamp = date('Y-m-d h:i:s ');
    //             $timestamp = date('Y-m-d H:i:s');
    //             if ($time_slots['slot_avaialble']) {
    //                 $duration_minutes = $service_total_duration;
    //                 if ($time_slots['suborder']) {
    //                     $end_minutes = strtotime($starting_time) + ((sizeof($time_slots['order_data']) * 30) * 60);
    //                     $ending_time = date('H:i:s', $end_minutes);
    //                     $day = date('l', strtotime($date_of_service));
    //                     $timings = getTimingOfDay($partner_id, $day);
    //                     $closing_time = $timings['closing_time'];
    //                     if ($ending_time > $closing_time) {
    //                         $ending_time = $closing_time;
    //                     }
    //                     $start_timestamp = strtotime($starting_time);
    //                     $ending_timestamp = strtotime($ending_time);
    //                     $duration_seconds = $ending_timestamp - $start_timestamp;
    //                     $duration_minutes = $duration_seconds / 60;
    //                 }
    //                 $order = [
    //                     'partner_id' => $partner_id,
    //                     'user_id' => $this->user_details['id'],
    //                     'city' => $city_id,
    //                     'total' => $total,
    //                     'payment_method' => $payment_method,
    //                     'address_id' => isset($address_id) ? $address_id : "0",
    //                     'visiting_charges' => $visiting_charges,
    //                     'address' => isset($finaladdress) ? $finaladdress : "",
    //                     'date_of_service' => $date_of_service,
    //                     'starting_time' => $starting_time,
    //                     'ending_time' => $ending_time,
    //                     'duration' => $duration_minutes,
    //                     'status' => $status,
    //                     'remarks' => $order_note,
    //                     'otp' => random_int(100000, 999999),
    //                     'order_latitude' =>  isset($location_data) && !empty($location_data) ? $location_data[0]['lattitude'] : $this->user_details['latitude'],
    //                     'order_longitude' => isset($location_data) && !empty($location_data) ? $location_data[0]['longitude'] : $this->user_details['longitude'],
    //                     'created_at' => $timestamp,
    //                 ];
    //                 if (!empty($promo_code)) {
    //                     $fetch_promococde = fetch_details('promo_codes', ['id' => $promo_code]);
    //                     $promo_code = validate_promo_code($this->user_details['id'], $fetch_promococde[0]['id'], $total);
    //                     if ($promo_code['error']) {
    //                         return $response['message'] = ($promo_code['message']);
    //                     }
    //                     $final_total = $promo_code['data'][0]['final_total'] + $visiting_charges;
    //                     $order['promo_code'] = $promo_code['data'][0]['promo_code'];
    //                     $order['promo_discount'] = $promo_code['data'][0]['final_discount'];
    //                     $order['promocode_id'] = $fetch_promococde[0]['id'];
    //                 }
    //                 $order['final_total'] = $final_total;
    //                 $insert_order = insert_details($order, 'orders');
    //             }
    //             if ($time_slots['suborder']) {
    //                 $next_day_date = date('Y-m-d', strtotime($date_of_service . ' +1 day'));
    //                 $next_day_slots = get_next_days_slots($closing_time, $date_of_service, $partner_id, $service_total_duration, $current_date);
    //                 $next_day_available_slots = $next_day_slots['available_slots'];
    //                 $next_Day_minutes = strtotime($next_day_available_slots[0]) + (($service_total_duration - $duration_minutes) * 60);
    //                 $next_day_ending_time = date('H:i:s', $next_Day_minutes);
    //                 $next_day_ending_time = date('H:i:s', $next_Day_minutes);
    //                 $sub_order = [
    //                     'partner_id' => $partner_id,
    //                     'user_id' => $this->user_details['id'],
    //                     'city' => $city_id,
    //                     'total' => $total,
    //                     'payment_method' => $payment_method,
    //                     'address_id' => isset($address_id) ? $address_id : "",
    //                     'visiting_charges' => $visiting_charges,
    //                     'address' => isset($finaladdress) ? $finaladdress : "",
    //                     'date_of_service' =>   $next_day_date,
    //                     'starting_time' => isset($next_day_available_slots[0]) ? $next_day_available_slots[0] : 00,
    //                     'ending_time' => $next_day_ending_time,
    //                     'duration' => $service_total_duration - $duration_minutes,
    //                     'status' => $status,
    //                     'remarks' => "sub_order",
    //                     'otp' => random_int(100000, 999999),
    //                     'parent_id' => $insert_order['id'],
    //                     'order_latitude' =>  isset($location_data) && !empty($location_data) ? $location_data[0]['lattitude'] : $this->user_details['latitude'],
    //                     'order_longitude' => isset($location_data) && !empty($location_data) ? $location_data[0]['longitude'] : $this->user_details['longitude'],
    //                     'created_at' => $timestamp,
    //                 ];
    //                 if (!empty($this->request->getVar('promo_code'))) {
    //                     $fetch_promococde = fetch_details('promo_codes', ['id' => $this->request->getVar('promo_code_id')]);
    //                     $promo_code = validate_promo_code($this->user_details['id'], $fetch_promococde[0]['id'], $total);
    //                     if ($promo_code['error']) {
    //                         return $response['message'] = ($promo_code['message']);
    //                     }
    //                     $final_total = $promo_code['data'][0]['final_total'] + $visiting_charges;
    //                     $sub_order['promo_code'] = $promo_code['data'][0]['promo_code'];
    //                     $sub_order['promo_discount'] = $promo_code['data'][0]['final_discount'];
    //                 }
    //                 $sub_order['final_total'] = $final_total;
    //                 $sub_order = insert_details($sub_order, 'orders');
    //             }
    //             if ($insert_order) {
    //                 for ($i = 0; $i < count($ids); $i++) {
    //                     $service_details = get_taxable_amount($ids[$i]);
    //                     $data = [
    //                         'order_id' => $insert_order['id'],
    //                         'service_id' => $ids[$i],
    //                         'service_title' => $service_details['title'],
    //                         'tax_percentage' => $service_details['tax_percentage'],
    //                         'tax_amount' => number_format(($service_details['tax_amount']), 2),
    //                         'price' => $service_details['price'],
    //                         'discount_price' => $service_details['discounted_price'],
    //                         'quantity' => $qtys[$i],
    //                         'sub_total' =>  strval(str_replace(',', '', number_format(strval(($service_details['taxable_amount'] * ($qtys[$i]))), 2))),
    //                         'status' => $status,
    //                     ];
    //                     insert_details($data, 'order_services');
    //                     $orderId['order_id'] = $insert_order['id'];
    //                     // if ($payment_method == "stripe") {
    //                     //     $stripe_intent = create_stripe_payment_intent();
    //                     //     $txn_id = $stripe_intent['id'];
    //                     //     $reference = "";
    //                     //     $orderId['stripe_intent'] = ($payment_method == "stripe") ?  $stripe_intent : "";
    //                     //     add_transaction_for_place_order($this->user_details['id'], $insert_order['id'], $payment_method, ceil(number_format(strval($final_total), 2)), $txn_id, $reference);
    //                     // } else if ($payment_method == "razorpay") {
    //                     //     $razorpay_order = razorpay_create_order_for_place_order($insert_order['id']);
    //                     //     $txn_id = $razorpay_order['data']['id'];
    //                     //     $reference = "";
    //                     //     $orderId['razorpay_order'] = ($payment_method == "razorpay") ?  $razorpay_order : "";
    //                     //     add_transaction_for_place_order($this->user_details['id'], $insert_order['id'], $payment_method, ceil(number_format(strval($final_total), 2)), $txn_id, $reference);
    //                     // } else if ($payment_method == "paystack") {
    //                     //     $reference = "ChargedFromAndroid_" . rand();
    //                     //     $orderId['reference'] = ($payment_method == "paystack") ?  $reference : "";
    //                     //     $txn_id = "-";
    //                     //     add_transaction_for_place_order($this->user_details['id'], $insert_order['id'], $payment_method, ceil(number_format(strval($final_total), 2)), $txn_id, $reference);
    //                     // }
    //                     $orderId['paystack_link'] = ($payment_method == "paystack") ? base_url() . '/api/v1/paystack_transaction_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $insert_order['id'] . '&amount=' . (number_format(strval($final_total), 2)) . '' : "";
    //                     $orderId['paypal_link'] = ($payment_method == "paypal") ? base_url() . '/api/v1/paypal_transaction_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $insert_order['id'] . '&amount=' . (number_format(strval($final_total), 2)) . '' : "";
    //                     $orderId['flutterwave'] = ($payment_method == "flutterwave") ? base_url() . '/api/v1/flutterwave_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $insert_order['id'] . '&amount=' . (number_format(strval($final_total), 2)) . '' : "";
    //                     // $orderId['paypal_link'] = ($payment_method == "paypal") ? base_url() . '/api/v1/paypal_transaction_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $insert_order['id'] . '&amount=' . ceil(number_format(strval($final_total), 2)) . '' : "";
    //                 }
    //                 if ($payment_method == 'cod') {
    //                     // send_web_notification('New Order', 'Please check new order ' . $insert_order['id'], $partner_id);
    //                     $db      = \Config\Database::connect();
    //                     $to_send_id = $partner_id;
    //                     $builder = $db->table('users')->select('fcm_id,email,username,platform');
    //                     $users_fcm = $builder->where('id', $to_send_id)->get()->getResultArray();
    //                     $fcm_ids = [];
    //                     foreach ($users_fcm as $ids) {
    //                         if ($ids['fcm_id'] != "") {
    //                             $fcm_ids['fcm_id'] = $ids['fcm_id'];
    //                             $fcm_ids['platform'] = $ids['platform'];
    //                             $email = $ids['email'];
    //                         }
    //                     }
    //                     if (!empty($fcm_ids) && check_notification_setting('new_booking_received_for_provider', 'notification')) {
    //                         $registrationIDs_chunks = array_chunk($users_fcm, 1000);
    //                         $fcmMsg = array(
    //                             'content_available' => "true",
    //                             'title' => " New Order Notification",
    //                             'body' => "We are pleased to inform you that you have received a new order. ",
    //                             'type' => 'order',
    //                             'order_id' => "{$insert_order['id']}",
    //                             'type_id' => "$to_send_id",
    //                             'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
    //                         );
    //                         send_notification($fcmMsg, $registrationIDs_chunks);
    //                     }
    //                 }
    //                 $this->checkAndUpdateSubscriptionStatus($partner_id);
    //                 return response('Order Placed successfully', false, remove_null_values($orderId));
    //             } else {
    //                 return response('order not placed');
    //             }
    //         } else {
    //             return response($availability['message'], true);
    //         }
    //     } catch (\Exception $th) {
    //         $response['error'] = true;
    //         $response['message'] = 'Something went wrong';
    //         log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - place_order()');
    //         return $this->response->setJSON($response);
    //     }
    // }
    public function place_order()
    {
        try {
            $validation = \Config\Services::validation();
            $rules = [
                'promo_code_id' => 'permit_empty',
                'payment_method' => 'required',
                'status' => 'required',
                'date_of_service' => 'required|valid_date[Y-m-d]',
                'starting_time' => 'required',
            ];
            $at_store = $this->request->getVar('at_store');
            if ($at_store == 1) {
                $rules['address_id'] = 'permit_empty|numeric';
            } else {
                $rules['address_id'] = 'required|numeric';
            }
            $validation->setRules($rules);
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => ['type' => 'neworder'],
                ];
                return $this->response->setJSON($response);
            }
            if (empty($this->request->getVar('order_id')) || empty($this->request->getVar('custom_job_request_id'))) {
                $cart_data = fetch_cart(true, $this->user_details['id']);
            }
            if (empty($this->request->getVar('order_id'))  && empty($this->request->getVar('custom_job_request_id'))) {
                if (empty($cart_data)) {
                    return response("Please add some item in cart", true);
                }
            }
            if (!empty($this->request->getVar('custom_job_request_id'))) {
                $db = \Config\Database::connect();
                $custom_job_data = $db->table('partner_bids pb')
                    ->select('pb.*, cj.*, cj.id as custom_job_id,pd.visiting_charges, u.username, u.image, c.id as category_id, c.name as category_name, c.image as category_image')
                    ->join('custom_job_requests cj', 'cj.id = pb.custom_job_request_id')
                    ->join('users u', 'u.id = cj.user_id')
                    ->join('partner_details pd', 'pd.partner_id = pb.partner_id')
                    ->join('categories c', 'c.id = cj.category_id')
                    ->where('pb.partner_id', $this->request->getVar('bidder_id'))
                    ->where('cj.id', $this->request->getVar('custom_job_request_id'))
                    ->orderBy('pb.id', 'DESC')
                    ->get()
                    ->getResultArray();
            }
            $db = \Config\Database::connect();
            if ((empty($this->request->getVar('order_id'))) && empty($this->request->getVar('custom_job_request_id'))) {
                $service_ids = $cart_data['service_ids'];
                $quantity = $cart_data['qtys'];
                $total = $cart_data['sub_total'];
            } else if (!empty($this->request->getVar('custom_job_request_id'))) {


                if ($custom_job_data[0]['tax_amount'] == "" || $custom_job_data[0]['tax_amount'] == null) {

                    $total = $custom_job_data[0]['counter_price'];
                } else {

                    $total = $custom_job_data[0]['counter_price'] + $custom_job_data[0]['tax_amount'];
                }
            } else {
                $order = fetch_details('order_services', ['order_id' => $this->request->getPost('order_id')]);
                $service_ids = [];
                foreach ($order as $row) {
                    $service_ids[] = $row['service_id'];
                }
                $all_service_data = array();
                foreach ($service_ids as $row2) {
                    $service_data_array = fetch_details('services', ['id' => $row2]);
                    $service_data = $service_data_array[0];
                    $all_service_data[] = $service_data;
                }
                $quantities = [];
                foreach ($order as $row) {
                    $quantities[] = $row['quantity'];
                }
                $quantity = implode(',', $quantities);
                $total = 0;
                $tax_value = 0;
                $sub_total = 0;
                $duartion = 0;
                $builder = $db->table('order_services os');
                $service_record = $builder
                    ->select('os.id as order_service_id,os.service_id,os.quantity,s.*,s.title as service_name,p.username as partner_name,pd.visiting_charges as visiting_charges,cat.name as category_name')
                    ->join('services s', 'os.service_id=s.id', 'left')
                    ->join('users p', 'p.id=s.user_id', 'left')
                    ->join('categories cat', 'cat.id=s.category_id', 'left')
                    ->join('partner_details pd', 'pd.partner_id=s.user_id', 'left')
                    ->where('os.order_id',  $this->request->getPost('order_id'))->get()->getResultArray();
                foreach ($service_record as $s1) {
                    $taxPercentageData = fetch_details('taxes', ['id' => $s1['tax_id']], ['percentage']);
                    if (!empty($taxPercentageData)) {
                        $taxPercentage = $taxPercentageData[0]['percentage'];
                    } else {
                        $taxPercentage = 0;
                    }
                    if ($s1['discounted_price'] == "0") {
                        $tax_value = ($s1['tax_type'] == "excluded") ? number_format(((($s1['price'] * ($taxPercentage) / 100))), 2) : 0;
                        $price = number_format($s1['price'], 2);
                    } else {
                        $tax_value = ($s1['tax_type'] == "excluded") ? number_format(((($s1['discounted_price'] * ($taxPercentage) / 100))), 2) : 0;
                        $price = number_format($s1['discounted_price'], 2);
                    }
                    $sub_total = $sub_total + (floatval(str_replace(",", "", $price)) + $tax_value) * $s1['quantity'];
                    $duartion = $duartion + $s1['duration'] * $s1['quantity'];
                }
                $total = $sub_total;
            }
            if ($at_store == "1") {
                $visiting_charges = 0;
            } else {
                if (empty($this->request->getPost('order_id'))  && (empty($this->request->getVar('custom_job_request_id')))) {
                    $visiting_charges = $cart_data['visiting_charges'];
                } else if (!empty($this->request->getVar('custom_job_request_id'))) {
                    $visiting_charges = $custom_job_data[0]['visiting_charges'];
                } else {
                    $builder = $db->table('services s');
                    $extra_data = $builder
                        ->select('SUM(IF(s.discounted_price  > 0 , (s.discounted_price * os1.quantity) , (s.price *  os1.quantity))) as subtotal,
                    SUM( os1.quantity) as total_quantity,pd.visiting_charges as visiting_charges,SUM(s.duration *  os1.quantity) as total_duration,pd.advance_booking_days as advance_booking_days,
                    pd.company_name as company_name')
                        ->join('order_services os1', 'os1.service_id = s.id')
                        ->join('partner_details pd', 'pd.partner_id=s.user_id')
                        ->where('os1.order_id',  $this->request->getPost('order_id'))
                        ->whereIn('s.id', $service_ids)->get()->getResultArray();
                    $visiting_charges = $extra_data[0]['visiting_charges'];
                }
            }
            $promo_code = $this->request->getVar('promo_code_id');
            $payment_method = $this->request->getVar('payment_method');
            $address_id = ($at_store == 1) ? 0 : $this->request->getVar('address_id');
            // $status = strtolower($this->request->getVar('status'));
            $status = "awaiting";
            $date_of_service = $this->request->getVar('date_of_service');
            $starting_time = ($this->request->getVar('starting_time'));
            $order_note = ($this->request->getVar('order_note')) ? $this->request->getVar('order_note') : "";
            if (empty($this->request->getPost('order_id'))  && empty($this->request->getPost('custom_job_request_id'))) {
                $minutes = strtotime($starting_time) + ($cart_data['total_duration'] * 60);
            } else if (!empty($this->request->getPost('custom_job_request_id'))) {
                $minutes =  strtotime($starting_time) + ($custom_job_data[0]['duration'] * 60);
            } else {
                $minutes = strtotime($starting_time) + ($duartion * 60);
            }
            $ending_time = date('H:i:s', $minutes);
            if ($at_store != 1) {
                if (!exists(['id' => $address_id], 'addresses')) {
                    return response('Address not exist');
                }
            }
            $final_total = ($total) + ($visiting_charges);
            if (empty($this->request->getPost('order_id'))) {
                $ids = explode(',', $service_ids ?? '');
            } else {
                $ids = $service_ids;
            }
            if (!empty($this->request->getPost('custom_job_request_id'))) {
                $qtys = 1;
                $partner_id = $custom_job_data[0]['partner_id'];
                $current_date = date('Y-m-d');
                $service_total_duration = $custom_job_data[0]['duration'];
                $duartion = $custom_job_data[0]['duration'];
            } else {
                $qtys = explode(',', $quantity ?? '');
                $service_data = fetch_details('services', [], '', '', '', '', '', 'id', $ids);
                $partner_id = $service_data[0]['user_id'];
                $current_date = date('Y-m-d');
                $service_total_duration = 0;
                $service_duration = 0;
                if (empty($this->request->getPost('order_id'))) {
                    foreach ($cart_data['data'] as $main_data) {
                        $service_duration = ($main_data['servic_details']['duration']) * $main_data['qty'];
                        $service_total_duration = $service_total_duration + $service_duration;
                    }
                } else {
                    $service_total_duration = $duartion;
                }
            }
            $availability =  checkPartnerAvailability($partner_id, $date_of_service . ' ' . $starting_time, $service_total_duration, $date_of_service, $starting_time);
            $insert_order = "";
            if (isset($availability) && $availability['error'] == "0") {
                $location_data = fetch_details('addresses', ['id' => $address_id]);
                $address['mobile'] = isset($location_data) && !empty($location_data) ? $location_data[0]['mobile'] : '';
                $address['address'] = isset($location_data) && !empty($location_data) ? $location_data[0]['address'] : '';
                $address['area'] = isset($location_data) && !empty($location_data) ? $location_data[0]['area'] : '';
                $address['city'] = isset($location_data) && !empty($location_data) ? $location_data[0]['city'] : '';
                $address['state'] = isset($location_data) && !empty($location_data) ? $location_data[0]['state'] : '';
                $address['country'] = isset($location_data) && !empty($location_data) ? $location_data[0]['country'] : '';
                $address['pincode'] = isset($location_data) && !empty($location_data) ? $location_data[0]['pincode'] : '';
                $city_id = isset($location_data) && !empty($location_data) ? $location_data[0]['city'] : '';
                $outputArray = array(
                    $address['address'],
                    $address['area'],
                    $address['city'],
                    $address['state'],
                    $address['country'],
                    $address['pincode'],
                    $address['mobile']
                );
                $finaladdress = implode(',', $outputArray);
                $service_total_duration = 0;
                $service_duration = 0;
                if (!empty($this->request->getPost('custom_job_request_id'))) {
                    $service_total_duration = $custom_job_data[0]['duration'];
                    $duartion = $custom_job_data[0]['duration'];
                } else {
                    if (empty($this->request->getPost('order_id'))) {
                        foreach ($cart_data['data'] as $main_data) {
                            $service_duration = ($main_data['servic_details']['duration']) * $main_data['qty'];
                            $service_total_duration = $service_total_duration + $service_duration;
                        }
                    } else {
                        $service_total_duration = $duartion;
                    }
                }
                $time_slots = get_slot_for_place_order($partner_id, $date_of_service, $service_total_duration, $starting_time);
                // $timestamp = date('Y-m-d h:i:s ');
                $timestamp = date('Y-m-d H:i:s');
                if ($time_slots['slot_avaialble']) {
                    $duration_minutes = $service_total_duration;
                    if ($time_slots['suborder']) {
                        $end_minutes = strtotime($starting_time) + ((sizeof($time_slots['order_data']) * 30) * 60);
                        $ending_time = date('H:i:s', $end_minutes);
                        $day = date('l', strtotime($date_of_service));
                        $timings = getTimingOfDay($partner_id, $day);
                        $closing_time = $timings['closing_time'];
                        if ($ending_time > $closing_time) {
                            $ending_time = $closing_time;
                        }
                        $start_timestamp = strtotime($starting_time);
                        $ending_timestamp = strtotime($ending_time);
                        $duration_seconds = $ending_timestamp - $start_timestamp;
                        $duration_minutes = $duration_seconds / 60;
                    }
                    $order = [
                        'partner_id' => $partner_id,
                        'user_id' => $this->user_details['id'],
                        'city' => $city_id,
                        'total' => $total,
                        'payment_method' => $payment_method,
                        'address_id' => isset($address_id) ? $address_id : "0",
                        'visiting_charges' => $visiting_charges,
                        'address' => isset($finaladdress) ? $finaladdress : "",
                        'date_of_service' => $date_of_service,
                        'starting_time' => $starting_time,
                        'ending_time' => $ending_time,
                        'duration' => $duration_minutes,
                        'status' => $status,
                        'remarks' => $order_note,
                        'otp' => random_int(100000, 999999),
                        'order_latitude' =>  isset($location_data) && !empty($location_data) ? $location_data[0]['lattitude'] : $this->user_details['latitude'],
                        'order_longitude' => isset($location_data) && !empty($location_data) ? $location_data[0]['longitude'] : $this->user_details['longitude'],
                        'created_at' => $timestamp,
                    ];
                    if (!empty($this->request->getPost('custom_job_request_id'))) {
                        $order['custom_job_request_id'] = $custom_job_data[0]['id'];
                    }
                    if (!empty($promo_code)) {
                        $fetch_promococde = fetch_details('promo_codes', ['id' => $promo_code]);
                        $promo_code = validate_promo_code($this->user_details['id'], $fetch_promococde[0]['id'], $total);
                        if ($promo_code['error']) {
                            return $response['message'] = ($promo_code['message']);
                        }
                        $final_total = $promo_code['data'][0]['final_total'] + $visiting_charges;
                        $order['promo_code'] = $promo_code['data'][0]['promo_code'];
                        $order['promo_discount'] = $promo_code['data'][0]['final_discount'];
                        $order['promocode_id'] = $fetch_promococde[0]['id'];
                    }
                    $order['final_total'] = $final_total;
                    $insert_order = insert_details($order, 'orders');
                }
                if ($time_slots['suborder']) {
                    $next_day_date = date('Y-m-d', strtotime($date_of_service . ' +1 day'));
                    $next_day_slots = get_next_days_slots($closing_time, $date_of_service, $partner_id, $service_total_duration, $current_date);
                    $next_day_available_slots = $next_day_slots['available_slots'];
                    $next_Day_minutes = strtotime($next_day_available_slots[0]) + (($service_total_duration - $duration_minutes) * 60);
                    $next_day_ending_time = date('H:i:s', $next_Day_minutes);
                    $next_day_ending_time = date('H:i:s', $next_Day_minutes);
                    $sub_order = [
                        'partner_id' => $partner_id,
                        'user_id' => $this->user_details['id'],
                        'city' => $city_id,
                        'total' => $total,
                        'payment_method' => $payment_method,
                        'address_id' => isset($address_id) ? $address_id : "",
                        'visiting_charges' => $visiting_charges,
                        'address' => isset($finaladdress) ? $finaladdress : "",
                        'date_of_service' =>   $next_day_date,
                        'starting_time' => isset($next_day_available_slots[0]) ? $next_day_available_slots[0] : 00,
                        'ending_time' => $next_day_ending_time,
                        'duration' => $service_total_duration - $duration_minutes,
                        'status' => $status,
                        'remarks' => "sub_order",
                        'otp' => random_int(100000, 999999),
                        'parent_id' => $insert_order['id'],
                        'order_latitude' =>  isset($location_data) && !empty($location_data) ? $location_data[0]['lattitude'] : $this->user_details['latitude'],
                        'order_longitude' => isset($location_data) && !empty($location_data) ? $location_data[0]['longitude'] : $this->user_details['longitude'],
                        'created_at' => $timestamp,
                    ];
                    if (!empty($this->request->getPost('custom_job_request_id'))) {
                        $sub_order['custom_job_request_id'] = $custom_job_data[0]['id'];
                    }
                    if (!empty($this->request->getVar('promo_code'))) {
                        $fetch_promococde = fetch_details('promo_codes', ['id' => $this->request->getVar('promo_code_id')]);
                        $promo_code = validate_promo_code($this->user_details['id'], $fetch_promococde[0]['id'], $total);
                        if ($promo_code['error']) {
                            return $response['message'] = ($promo_code['message']);
                        }
                        $final_total = $promo_code['data'][0]['final_total'] + $visiting_charges;
                        $sub_order['promo_code'] = $promo_code['data'][0]['promo_code'];
                        $sub_order['promo_discount'] = $promo_code['data'][0]['final_discount'];
                    }
                    $sub_order['final_total'] = $final_total;
                    $sub_order = insert_details($sub_order, 'orders');
                }
                if ($insert_order) {
                    if (!empty($this->request->getPost('custom_job_request_id'))) {


                        if ($custom_job_data[0]['tax_amount'] == "" || $custom_job_data[0]['tax_amount'] == null) {
                            $tax_amount = 0;
                        } else {
                            $tax_amount = $custom_job_data[0]['tax_amount'];
                        }
                        $data = [
                            'order_id' => $insert_order['id'],
                            'service_id' => '-',
                            'service_title' => $custom_job_data[0]['service_title'],
                            'tax_percentage' => $custom_job_data[0]['tax_percentage'] ?? 0,
                            'tax_amount' =>  $custom_job_data[0]['tax_amount'] ?? 0,
                            'price' => $custom_job_data[0]['counter_price'],
                            'discount_price' => 0,
                            'quantity' => 1,
                            'sub_total' =>  strval(str_replace(',', '', number_format(strval(($custom_job_data[0]['counter_price'] * (1) + $tax_amount)), 2))),

                            'sub_total' =>  strval(str_replace(',', '', number_format(strval(($custom_job_data[0]['counter_price'] * (1) + $tax_amount)), 2))),
                            'status' => $status,
                            'custom_job_request_id' => $custom_job_data[0]['id'],
                        ];
                        insert_details($data, 'order_services');
                        $orderId['order_id'] = $insert_order['id'];
                        $orderId['paystack_link'] = ($payment_method == "paystack") ? base_url() . '/api/v1/paystack_transaction_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $insert_order['id'] . '&amount=' . (number_format(strval($final_total), 2)) . '' : "";
                        $orderId['paypal_link'] = ($payment_method == "paypal") ? base_url() . '/api/v1/paypal_transaction_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $insert_order['id'] . '&amount=' . (number_format(strval($final_total), 2)) . '' : "";
                        $orderId['flutterwave'] = ($payment_method == "flutterwave") ? base_url() . '/api/v1/flutterwave_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $insert_order['id'] . '&amount=' . (number_format(strval($final_total), 2)) . '' : "";
                    } else {
                        for ($i = 0; $i < count($ids); $i++) {
                            $service_details = get_taxable_amount($ids[$i]);
                            $data = [
                                'order_id' => $insert_order['id'],
                                'service_id' => $ids[$i],
                                'service_title' => $service_details['title'],
                                'tax_percentage' => $service_details['tax_percentage'],
                                'tax_amount' => number_format(($service_details['tax_amount']), 2),
                                'price' => $service_details['price'],
                                'discount_price' => $service_details['discounted_price'],
                                'quantity' => $qtys[$i],
                                'sub_total' =>  strval(str_replace(',', '', number_format(strval(($service_details['taxable_amount'] * ($qtys[$i]))), 2))),
                                'status' => $status,
                            ];
                            insert_details($data, 'order_services');
                            $orderId['order_id'] = $insert_order['id'];
                            $orderId['paystack_link'] = ($payment_method == "paystack") ? base_url() . '/api/v1/paystack_transaction_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $insert_order['id'] . '&amount=' . (number_format(strval($final_total), 2)) . '' : "";
                            $orderId['paypal_link'] = ($payment_method == "paypal") ? base_url() . '/api/v1/paypal_transaction_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $insert_order['id'] . '&amount=' . (number_format(strval($final_total), 2)) . '' : "";
                            $orderId['flutterwave'] = ($payment_method == "flutterwave") ? base_url() . '/api/v1/flutterwave_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $insert_order['id'] . '&amount=' . (number_format(strval($final_total), 2)) . '' : "";
                        }
                    }
                    if ($payment_method == 'cod') {
                        // send_web_notification('New Order', 'Please check new order ' . $insert_order['id'], $partner_id);
                        $db      = \Config\Database::connect();
                        $to_send_id = $partner_id;
                        $builder = $db->table('users')->select('fcm_id,email,username,platform');
                        $users_fcm = $builder->where('id', $to_send_id)->get()->getResultArray();
                        $fcm_ids = [];
                        foreach ($users_fcm as $ids) {
                            if ($ids['fcm_id'] != "") {
                                $fcm_ids['fcm_id'] = $ids['fcm_id'];
                                $fcm_ids['platform'] = $ids['platform'];
                                $email = $ids['email'];
                            }
                        }
                        if (!empty($fcm_ids) && check_notification_setting('new_booking_received_for_provider', 'notification')) {
                            $registrationIDs_chunks = array_chunk($users_fcm, 1000);
                            $fcmMsg = array(
                                'content_available' => "true",
                                'title' => $this->trans->newBookingNotification,
                                'body' => $this->trans->newBookingReceivedMessage,
                                'type' => 'order',
                                'order_id' => "{$insert_order['id']}",
                                'type_id' => "$to_send_id",
                                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            );
                            send_notification($fcmMsg, $registrationIDs_chunks);
                        }
                        if (!empty($this->request->getPost('custom_job_request_id'))) {
                            update_custom_job_status($insert_order['id'], 'booked');
                        }
                    }
                    $this->checkAndUpdateSubscriptionStatus($partner_id);
                    return response('Order Placed successfully', false, remove_null_values($orderId));
                } else {
                    return response('order not placed');
                }
            } else {
                return response($availability['message'], true);
            }
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - place_order()');
            return $this->response->setJSON($response);
        }
    }
    public function get_orders()
    {
        try {
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'DESC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $download_invoice = ($this->request->getPost('download_invoice') && !empty($this->request->getPost('download_invoice'))) ? $this->request->getPost('download_invoice') : 1;
            $where = $additional_data = [];
            if (!empty($this->request->getPost('custom_request_orders'))) {
                $where['o.custom_job_request_id !='] = "";
                if ($this->request->getPost('id') && !empty($this->request->getPost('id'))) {
                    $where['o.id'] = $this->request->getPost('id');
                }
                if ($this->request->getPost('status') && !empty($this->request->getPost('status'))) {
                    $where['o.status'] = $this->request->getPost('status');
                }
                if ($this->user_details['id'] != '') {
                    $where['o.user_id'] = $this->user_details['id'];
                }
                $orders = new Orders_model();
                $order_detail = $orders->custom_booking_list(true, $search, $limit, $offset, $sort, $order, $where, $download_invoice, '', '', '', '', false);
                if (!empty($order_detail['data'])) {
                    return response('Custom booking fetched successfully', false, remove_null_values($order_detail['data']), 200, ['total' => $order_detail['total']]);
                } else {
                    return response('Order not found');
                }
            } else {
                if ($this->request->getPost('id') && !empty($this->request->getPost('id'))) {
                    $where['o.id'] = $this->request->getPost('id');
                }
                if ($this->request->getPost('id') && !empty($this->request->getPost('id'))) {
                    // $where['o.custom_job_request_id'] = NULL;
                } else {
                    $where['o.custom_job_request_id'] = NULL;
                }
                if ($this->request->getPost('status') && !empty($this->request->getPost('status'))) {
                    $where['o.status'] = $this->request->getPost('status');
                }
                if ($this->user_details['id'] != '') {
                    $where['o.user_id'] = $this->user_details['id'];
                }
                $orders = new Orders_model();
                $order_detail = $orders->list(true, $search, $limit, $offset, $sort, $order, $where, $download_invoice, '', '', '', '', false);
                if (!empty($order_detail['data'])) {
                    return response('Order fetched successfully', false, remove_null_values($order_detail['data']), 200, ['total' => $order_detail['total']]);
                } else {
                    return response('Order not found');
                }
            }
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_orders()');
            return $this->response->setJSON($response);
        }
    }
    public function manage_notification()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'notification_id' => 'required',
                    'is_readed' => 'permit_empty|numeric',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $nfcs = fetch_details('notifications', ['id' => $this->request->getPost('notification_id')]);
            if (empty($nfcs)) {
                return response('notification not found!');
            }
            if ($this->request->getPost('delete_notification') && $this->request->getPost('delete_notification') == 1) {
                $data = ['id' => $this->request->getPost('notification_id'), 'user_id' => $this->user_details['id']];
                if (exists(['id' => $this->request->getPost('notification_id'), 'notification_type' => 'general'], 'notifications')) {
                    if (exists(['notification_id' => $this->request->getPost('notification_id'), 'user_id' => $this->user_details['id']], 'delete_general_notification')) {
                        update_details(['is_deleted' => 1], ['notification_id' => $this->request->getPost('notification_id'), 'user_id' => $this->user_details['id']], 'delete_general_notification');
                        return response('Notification deleted successfully', false);
                    } else {
                        insert_details(['is_deleted' => 1, 'notification_id' => $this->request->getPost('notification_id'), 'user_id' => $this->user_details['id']], 'delete_general_notification');
                        return response('Notification deleted successfully', false);
                    }
                }
                if (!exists($data, 'notifications')) {
                    return response('notification not found');
                }
                if (delete_details($data, 'notifications')) {
                    return response('Notification deleted successfully', false);
                } else {
                    return response('Something get wrong');
                }
            }
            $data = ['id' => $this->request->getPost('notification_id'), 'user_id' => $this->user_details['id']];
            if (!exists($data, 'notifications')) {
                return response('notification not found..');
            }
            if (exists(['id' => $this->request->getPost('notification_id'), 'notification_type' => 'general'], 'notifications')) {
                if (exists(['notification_id' => $this->request->getPost('notification_id'), 'user_id' => $this->user_details['id']], 'delete_general_notification')) {
                    update_details(['is_deleted' => !empty($this->request->getPost('is_readed')) ? 1 : 0], ['notification_id' => $this->request->getPost('notification_id'), 'user_id' => $this->user_details['id']], 'delete_general_notification');
                    return response('Notification updated successfully', false);
                } else {
                    $set = [
                        'is_readed' => $this->request->getPost('is_readed') != '' ? 1 : 0,
                        'notification_id' => $this->request->getPost('notification_id'),
                        'user_id' => $this->user_details['id'],
                    ];
                    insert_details($set, 'delete_general_notification');
                    return response('Notification updated successfully', false);
                }
            }
            $update_notifications = update_details(
                ['is_readed' => $this->request->getPost('is_readed') != '' ? 1 : 0],
                ['id' => $this->request->getPost('notification_id'), 'user_id' => $this->user_details['id']],
                'notifications'
            );
            if ($update_notifications == true) {
                $notifcations = $this->get_notifications($this->request->getPost('notification_id'));
                if (!empty($notifcations)) {
                    $error = false;
                    $message = 'notification updated successfully';
                } else {
                    $error = true;
                    $message = 'notification not found';
                }
                return response($message, $error, remove_null_values($notifcations));
            } else {
                return response('something get wrong');
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - manage_notification()');
            return $this->response->setJSON($response);
        }
    }
    public function get_notifications($id = 0)
    {
        try {
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'DESC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = $additional_data = [];
            if ($this->request->getPost('id') && !empty($this->request->getPost('id'))) {
                $where['id'] = $this->request->getPost('id');
            }
            if (!empty($id)) {
                $where['id'] = $id;
            }
            $whereIn['target'] = ['all_users', 'specific_user', 'customer'];
            $notifications = new Notification_model();
            $get_notifications = $notifications->list(true, $search, $limit, $offset, $sort, $order, $where, $whereIn);
            foreach ($get_notifications['data'] as $key => $row) {
                if (is_array(json_decode($row['user_id']))) {
                    $decodedArray = json_decode($row['user_id']);
                    if (!in_array($this->user_details['id'], $decodedArray)) {
                        unset($get_notifications['data'][$key]);
                    }
                }
            }
            foreach ($get_notifications['data'] as $key => $notifcation) {
                $dateTime = new DateTime($notifcation['date_sent']);
                $currentDateTime = new DateTime(); // Current date and time
                $date = $dateTime->format('Y-m-d');
                $time = $dateTime->format('H:i');
                if ($date == $currentDateTime->format('Y-m-d')) {
                    $notificationTime = strtotime($time);
                    $currentTime = time();
                    $timeDifferenceSeconds = $currentTime - $notificationTime;
                    if ($timeDifferenceSeconds < 60) {
                        $duration = 'just now';
                    } elseif ($timeDifferenceSeconds < 3600) {
                        $minutesAgo = floor($timeDifferenceSeconds / 60);
                        $duration = $minutesAgo . ' minutes ago';
                    } else {
                        $hoursAgo = floor($timeDifferenceSeconds / 3600);
                        $duration = $hoursAgo . ' hours ago';
                    }
                } else {
                    $notificationDate = $dateTime->format('Y-m-d');
                    $currentDate = $currentDateTime->format('Y-m-d');
                    $dateDifference = $currentDateTime->diff($dateTime);
                    $daysAgo = $dateDifference->days;
                    $duration = $daysAgo . ' days ago';
                }
                $get_notifications['data'][$key]['duration'] = $duration;
            }
            if (!empty($id)) {
                return $get_notifications['data'];
            }
            if (!empty($get_notifications['data'])) {
                return response('Notifications fetched successfully', false, remove_null_values($get_notifications['data']), 200, ['total' => ($get_notifications['total'])]);
            } else {
                return response('Notification Not Found');
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_notifications()');
            return $this->response->setJSON($response);
        }
    }
    public function book_mark()
    {
        try {
            $book_marks = new Bookmarks_model();
            $validation = \Config\Services::validation();
            $user_id = $this->user_details['id'];
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = ['b.user_id' => $user_id];
            $rules = [
                'type' => [
                    "rules" => 'required|in_list[add,remove,list]',
                    "errors" => [
                        "required" => "Type is required",
                        "in_list" => "Type value is incorrect",
                    ],
                ],
            ];
            if ($this->request->getPost('type') == "list") {
                $rules['latitude'] = [
                    "rules" => 'required',
                ];
                $rules['longitude'] = [
                    "rules" => 'required',
                ];
            }
            $validation->setRules($rules);
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $type = $this->request->getPost('type');
            if ($type == 'add' || $type == "remove") {
                $validation->setRules(
                    [
                        'partner_id' => 'required',
                    ]
                );
                if (!$validation->withRequest($this->request)->run()) {
                    $errors = $validation->getErrors();
                    $response = [
                        'error' => true,
                        'message' => $errors,
                        'data' => [],
                    ];
                    return $this->response->setJSON($response);
                }
            }
            $partner_id = $this->request->getPost('partner_id');
            $is_booked = is_bookmarked($user_id, $partner_id)[0]['total'];
            $partner_details = fetch_details('partner_details', ['partner_id' => $partner_id]);
            $data = [
                'user_id' => $user_id,
                'partner_id' => $partner_id,
            ];
            if ($type == 'add' && !empty($partner_details)) {
                if ($is_booked == 0) {
                    if ($book_marks->save($data)) {
                        return response('Added to book marks', false, [], 200);
                    } else {
                        return response('Could not add to the book marks', true, [], 200);
                    }
                } else {
                    return response('This partner is already bookmarked', true, [], 200);
                }
            } else if ($type == 'remove' && !empty($partner_details)) {
                $remove = delete_bookmark($user_id, $partner_id);
                if ($is_booked > 0) {
                    if ($remove) {
                        return response('Removed from book marks', false, [], 200);
                    } else {
                        return response('Could not remove form', true, [], 200);
                    }
                } else {
                    return response('No partner selected', true, [], 200);
                }
            } elseif ($type == "list") {
                $Partners_model = new Partners_model();
                $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
                $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
                $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('sort'))) ? $this->request->getPost('sort') : 'id';
                $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
                $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
                $where = $additional_data = [];
                $where['is_approved'] = 1;
                $filter = ($this->request->getPost('filter') && !empty($this->request->getPost('filter'))) ? $this->request->getPost('filter') : '';
                $customer_id = $this->user_details['id'];
                $settings = get_settings('general_settings', true);
                if (($this->request->getPost('latitude') && !empty($this->request->getPost('latitude')) && ($this->request->getPost('longitude') && !empty($this->request->getPost('longitude')))) && $customer_id != '') {
                    $additional_data = [
                        'latitude' => $this->request->getPost('latitude'),
                        'longitude' => $this->request->getPost('longitude'),
                        'customer_id' => $customer_id,
                        'max_serviceable_distance' => $settings['max_serviceable_distance'],
                    ];
                }
                $partner_ids = favorite_list($user_id);
                if (!empty($partner_ids)) {
                    $data = $Partners_model->list(true, $search, $limit, $offset, $sort, $order, $where, 'pd.partner_id', $partner_ids, $additional_data);
                }
                $user = ['user_id' => $user_id];
                if (!empty($data['data'])) {
                    for ($i = 0; $i < count($data['data']); $i++) {
                        unset($data['data'][$i]['national_id']);
                        unset($data['data'][$i]['passport']);
                        unset($data['data'][$i]['tax_name']);
                        unset($data['data'][$i]['tax_number']);
                        unset($data['data'][$i]['bank_name']);
                        unset($data['data'][$i]['account_number']);
                        unset($data['data'][$i]['account_name']);
                        unset($data['data'][$i]['bank_code']);
                        unset($data['data'][$i]['swift_code']);
                        unset($data['data'][$i]['type']);
                        unset($data['data'][$i]['advance_booking_days']);
                        unset($data['data'][$i]['admin_commission']);
                        array_merge($data['data'][$i], $user);
                    }
                    return response('Bookmarks Retrieved successfully', false, remove_null_values($data['data']), 200, ['total' => $data['total']]);
                } else {
                    return response("No Bookmarks found", false);
                }
                $data = $book_marks->list(true, $search, $limit, $offset, $sort, $order, $where);
                return response('Data Retrived successfully', false, remove_null_values($data['data']), 200, ['total' => $data['total']]);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - book_mark()');
            return $this->response->setJSON($response);
        }
    }
    public function update_order_status()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'order_id' => 'required|numeric',
                    'status' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $order_id = $this->request->getPost('order_id');
            $customer_id = $this->user_details['id'];
            $status = $this->request->getPost('status');
            $date = $this->request->getPost('date');
            $selected_time = $this->request->getPost('time');
            if ($status == "rescheduled") {
                $validate = validate_status($order_id, $status, $date, $selected_time);
                $where['o.id'] = $order_id;
                $orders = new Orders_model();
                $order_detail = $orders->list(true, '', 10, 0, 'o.id', 'DESC', $where, '', '', '', '', '', false);
                $response['error'] = $validate['error'];
                $response['message'] = $validate['message'];
                $response['data'] = $order_detail;
                return $this->response->setJSON($response);
            } else {
                $validate = validate_status($order_id, $status);
            }
            if ($validate['error']) {
                $response['error'] = true;
                $response['message'] = $validate['message'];
                return $this->response->setJSON($response);
            } else {
                if ($validate['error']) {
                    $response['error'] = true;
                    $response['message'] = $validate['message'];
                    $response['csrfName'] = csrf_token();
                    $response['csrfHash'] = csrf_hash();
                    $response['data'] = array();
                    return $this->response->setJSON($response);
                }
                if ($status == "awaiting") {
                    $response = [
                        'error' => false,
                        'message' => "Order is in Awaiting!",
                    ];
                    return $this->response->setJSON($response);
                }
                if ($status == "confirmed") {
                    $response = [
                        'error' => false,
                        'message' => "Order is Confirmed!",
                    ];
                    return $this->response->setJSON($response);
                }
                if ($status == "cancelled") {
                    $orders = new Orders_model();
                    $where['o.id'] = $order_id;
                    $order_detail = $orders->list(true, '', 10, 0, 'o.id', 'DESC', $where, '', '', '', '', '', false);
                    $response = [
                        'error' => false,
                        'message' => "Order is cancelled!",
                        'data' => $order_detail,
                    ];
                    return $this->response->setJSON($response);
                }
                if ($status == "completed") {
                    $commision = unsettled_commision($this->userId);
                    update_details(['balance' => $commision], ['id' => $this->userId], 'users');
                    $response = [
                        'error' => false,
                        'message' => "Order Completed successfully!",
                    ];
                    return $this->response->setJSON($response);
                }
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - update_order_status()');
            return $this->response->setJSON($response);
        }
    }
    public function get_available_slots()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'partner_id' => 'required|numeric',
                    'date' => 'required|valid_date[Y-m-d]',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $days = [
                'Mon' => 'monday',
                'Tue' => 'tuesday',
                'Wed' => 'wednesday',
                'Thu' => 'thursday',
                'Fri' => 'friday',
                'Sat' => 'saturday',
                'Sun' => 'sunday',
            ];
            $partner_id = $this->request->getPost('partner_id');
            $date = $this->request->getPost('date');
            $time = $this->request->getPost('date');
            $date = new DateTime($date);
            $date = $date->format('Y-m-d');
            $day = date('D', strtotime($date));
            $whole_day = $days[$day];
            $partner_data = fetch_details('partner_details', ['partner_id' => $partner_id], ['advance_booking_days']);
            $cart_data = fetch_cart(true, $this->user_details['id']);
            $duration = 0;
            if ($this->request->getPost('order_id')) {
                $order = fetch_details('order_services', ['order_id' => $this->request->getPost('order_id')]);
                $service_ids = [];
                foreach ($order as $row) {
                    $service_ids[] = $row['service_id'];
                }
                $total_duration = 0;
                foreach ($service_ids as $row) {
                    $service_data = fetch_details('services', ['id' => $row])[0];
                    $total_duration = $total_duration + $service_data['duration'];
                }
                $time_slots = get_available_slots($partner_id, $date, isset($total_duration) ? $total_duration : 0); //working
            } else if ($this->request->getPost('custom_job_request_id')) {
                $custom_job_data = fetch_details('partner_bids', ['partner_id' => $this->request->getPost('partner_id'), 'custom_job_request_id' => $this->request->getPost('custom_job_request_id')]);
                $time_slots = get_available_slots($partner_id, $date, isset($custom_job_data[0]['duration']) ? $custom_job_data[0]['duration'] : 0); //working
            } else {
                $time_slots = get_available_slots($partner_id, $date, isset($cart_data['total_duration']) ? $cart_data['total_duration'] : 0); //working
            }
            $available_slots = $busy_slots = $time_slots['all_slots'] = [];
            if (isset($time_slots['available_slots']) && !empty($time_slots['available_slots'])) {
                $available_slots = array_map(function ($time_slot) {
                    return ["time" => $time_slot, "is_available" => 1];
                }, $time_slots['available_slots']);
            }
            if (isset($time_slots['busy_slots']) && !empty($time_slots['busy_slots'])) {
                $busy_slots = array_map(function ($time_slot) {
                    return ["time" => $time_slot, "is_available" => 0];
                }, $time_slots['busy_slots']);
            }
            $time_slots['all_slots'] = array_merge($available_slots, $busy_slots);
            array_sort_by_multiple_keys($time_slots['all_slots'], ["time" => SORT_ASC]);
            if ($this->request->getPost('custom_job_request_id')) {
                $remaining_duration = isset($custom_job_data[0]['duration']) ? $custom_job_data[0]['duration'] : 0;
            } else {
                $remaining_duration = isset($cart_data['total_duration']) ? $cart_data['total_duration'] : 0;
            }
            $day = date('l', strtotime($date));
            $timings = getTimingOfDay($partner_id, $day);
            if (empty($timings)) {
                $response = [
                    'error' => true,
                    'message' => 'Provider is closed!',
                    'data' => [],
                ];
                return $this->response->setJSON(remove_null_values($response));
            }
            $closing_time = $timings['closing_time'];
            $current_date = date('Y-m-d');
            if ($this->request->getPost('custom_job_request_id')) {
                $next_day_slots = get_next_days_slots($closing_time, $date, $partner_id, isset($custom_job_data[0]['duration']) ? $custom_job_data[0]['duration'] : 0, $current_date);
            } else {
                $next_day_slots = get_next_days_slots($closing_time, $date, $partner_id, isset($cart_data['total_duration']) ? $cart_data['total_duration'] : 0, $current_date);
            }
            if (count($next_day_slots) > 0) {
                $remaining_duration = $remaining_duration - 30;
                $number_of_slot = $remaining_duration / 30;
                $last_slot = count($time_slots['all_slots']) - 1;
                $loop_count = count($time_slots['all_slots']);
                for ($i = $loop_count - 1; $i >= max(0, $loop_count - $number_of_slot); $i--) {
                    if ($time_slots['all_slots'][$i]['is_available'] == "1") {
                        $time_slots['all_slots'][$i]['message'] = "Order scheduled for the multiple days";
                    }
                }
            }
            $partner_timing = fetch_details('partner_timings', ['partner_id' => $partner_id, "day" => $whole_day]);
            if (!empty($partner_data) && $partner_data[0]['advance_booking_days'] > 0) {
                $allowed_advanced_booking_days = $partner_data[0]['advance_booking_days'];
                $current_date = new DateTime();
                $max_available_date = $current_date->modify("+ $allowed_advanced_booking_days day")->format('Y-m-d');
                if ($date > $max_available_date) {
                    $response = [
                        'error' => true,
                        'message' => "You'can not choose date beyond available booking days which is + $allowed_advanced_booking_days days",
                        'data' => [],
                    ];
                    return $this->response->setJSON(remove_null_values($response));
                }
            } else if (!empty($partner_data) && $partner_data[0]['advance_booking_days'] == 0) {
                $current_date = new DateTime();
                if ($date > $current_date->format('Y-m-d')) {
                    $response = [
                        'error' => true,
                        'message' => "Advanced Booking for this partner is not available",
                        'data' => [],
                    ];
                    return $this->response->setJSON(remove_null_values($response));
                }
            } else {
                $response = [
                    'error' => true,
                    'message' => "No Partner Found",
                    'data' => [],
                ];
                return $this->response->setJSON(remove_null_values($response));
            }
            if (!empty($time_slots)) {
                $response = [
                    'error' => $time_slots['error'],
                    'message' => ($time_slots['error'] == false) ? 'Found Time slots' : $time_slots['message'],
                    'data' => [
                        'all_slots' => (!empty($time_slots) && $time_slots['error'] == false) ? $time_slots['all_slots'] : [],
                    ],
                ];
                return $this->response->setJSON(remove_null_values($response));
            } else {
                $response = [
                    'error' => true,
                    'message' => 'No slot is available on this date!',
                    'data' => [],
                ];
                return $this->response->setJSON(remove_null_values($response));
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_available_slots()');
            return $this->response->setJSON($response);
        }
    }
    public function get_ratings()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'partner_id' => 'permit_empty',
                ],
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $limit = (isset($_POST['limit']) && !empty($_POST['limit'])) ? $_POST['limit'] : 10;
            $offset = (isset($_POST['offset']) && !empty($_POST['offset'])) ? $_POST['offset'] : 0;
            $sort = (isset($_POST['sort']) && !empty($_POST['sort'])) ? $_POST['sort'] : 'id';
            $order = (isset($_POST['order']) && !empty($_POST['order'])) ? $_POST['order'] : 'ASC';
            $search = (isset($_POST['search']) && !empty($_POST['search'])) ? $_POST['search'] : '';
            $partner_id = ($this->request->getPost('partner_id') != '') ? $this->request->getPost('partner_id') : '';
            $defaultSort = 'id';
            $defaultOrder = 'ASC';
            $validSortColumns = ['id', 'rating', 'created_at'];
            if (in_array($sort, $validSortColumns)) {
                $defaultSort = $sort;
            }
            $validOrders = ['ASC', 'DESC'];
            if (in_array($order, $validOrders)) {
                $defaultOrder = $order;
            }
            // if (!empty($this->request->getPost('service_id'))) {
            //     $where = "s.user_id={$partner_id} AND service_id={$this->request->getPost('service_id')}";
            // } else {
            //     $where = "s.user_id={$partner_id} ";
            // }
            if (!empty($this->request->getPost('service_id'))) {
                $where = "(s.user_id = {$partner_id} AND sr.service_id = {$this->request->getPost('service_id')}) OR (pb.partner_id = {$partner_id} AND sr.custom_job_request_id IS NOT NULL)";
            } else {
                $where = "(s.user_id = {$partner_id}) OR (pb.partner_id = {$partner_id} AND sr.custom_job_request_id IS NOT NULL)";
            }
            $ratings = new Service_ratings_model();
            if ($partner_id != '') {
                $data = $ratings->ratings_list(true, $search, $limit, $offset, $sort, $order, $where);
            } else {
                $data = $ratings->ratings_list(true, $search, $limit, $offset, $sort, $order);
            }
            return response('Data Retrieved successfully', false, remove_null_values($data['data']), 200, ['total' => $data['total']]);
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_ratings()');
            return $this->response->setJSON($response);
        }
    }
    public function add_rating()
    {
        try {
            $validation = \Config\Services::validation();
            $ratings_model = new Service_ratings_model();
            $validation->setRules(
                [
                    // 'service_id' => 'required|numeric',
                    'rating' => 'required|numeric|greater_than[0]|less_than_equal_to[5]',
                    'comment' => 'permit_empty',
                ],
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $user_id = $this->user_details['id'];
            $service_id = $this->request->getPost('service_id');
            $custom_job_request_id = $this->request->getPost('custom_job_request_id');
            if ($service_id) {
                $orders = has_ordered($user_id, $service_id);
                if ($orders['error'] == true) {
                    return response($orders['message'], true, [], 200);
                }
            } else if ($custom_job_request_id) {
                $orders = has_ordered($user_id, $service_id, $custom_job_request_id);
                if ($orders['error'] == true) {
                    return response($orders['message'], true, [], 200);
                }
            }
            if (isset($custom_job_request_id)) {
                $rd = fetch_details('services_ratings', ['user_id' => $user_id, 'custom_job_request_id' => $custom_job_request_id]);
            } else {
                $rd = fetch_details('services_ratings', ['user_id' => $user_id, 'service_id' => $service_id]);
            }
            if (empty($rd)) {
                $rating = $this->request->getPost('rating');
                $comment = (isset($_POST['comment']) && $_POST['comment'] != "") ? $this->request->getPost('comment') : "";
                $uploaded_images = $this->request->getFiles('images');
                $data = [];
                if (isset($custom_job_request_id)) {
                    $data['custom_job_request_id'] = $custom_job_request_id;
                } else {
                    $data['service_id'] = $service_id;
                }
                // Merge user_id, rating, and comment into the existing $data array
                $data = array_merge($data, [
                    'user_id' => $user_id,
                    'rating' => $rating,
                    'comment' => $comment,
                ]);



                $names = "";
                $image_names['name'] = [];
                $data['images'] = [];
                $path = "public/uploads/ratings/";
                if (isset($uploaded_images['images'])) {
                    foreach ($uploaded_images['images'] as $images) {
                        $validate_image = valid_image($images);
                        if ($validate_image == true) {
                            return response("Invalid Image", true, []);
                        }
                        $newName = $images->getRandomName();
                        if ($newName != null) {
                            move_file($images, $path, $newName);
                            $name = "public/uploads/ratings/$newName";
                            array_push($image_names['name'], $name);
                        }
                    }
                    $names = json_encode($image_names['name']);
                }
                $data['images'] = $names;


                $saved_data = $ratings_model->save($data);
                if ($saved_data) {
                    update_ratings($service_id, $rating);
                    if (!empty($data['images'])) {
                        $images_array = json_decode($data['images'], true);
                        foreach ($images_array as $key => $img) {
                            $images_array[$key] = base_url($img);
                        }
                        $data['images'] = ($images_array);
                    }
                    $customer_details = fetch_details('users', ['id' => $user_id]);
                    $partner_id = fetch_details('services', ['id' => $service_id], ['user_id']);
                    if (!empty($customer_details[0]['email']) && check_notification_setting('new_rating_given_by_customer', 'email') && is_unsubscribe_enabled($customer_details[0]['id']) == 1) {
                        send_custom_email('new_rating_given_by_customer', $user_id, $customer_details[0]['email']);
                    }
                    if (check_notification_setting('new_rating_given_by_customer', 'sms')) {
                        send_custom_sms('new_rating_given_by_customer',  $customer_details[0]['id'], $customer_details[0]['email']);
                    }
                    return response("Rating Saved", false, remove_null_values($data), 200);
                } else {
                    return response("Could not save ratings", true, [], 200);
                }
            } else {
                $rating_id = $rd[0]['id'];
                $rating = (isset($_POST['rating'])) ? $this->request->getPost('rating') : "";
                $comment = (isset($_POST['comment'])) ? $this->request->getPost('comment') : "";
                $data = [
                    'rating' => ($rating != "") ? $rating : $rd[0]['rating'],
                    'comment' => ($comment != "") ? $comment : $rd[0]['comment'],
                ];
                $data['images'] = [];
                $uploaded_images = $this->request->getFiles('images');
                $path = "public/uploads/ratings/";
                if (isset($uploaded_images['images'])) {
                    foreach ($uploaded_images['images'] as $images) {
                        $validate_image = valid_image($images);
                        if ($validate_image == true) {
                            return response("Invalid Image", true, []);
                        }
                        $newName = $images->getRandomName();
                        if ($newName == null) {
                            $image = null;
                        } else {
                            move_file($images, $path, $newName);
                            $name = "public/uploads/ratings/$newName";
                            array_push($data['images'], $name);
                        }
                    }
                    $data['images'] = json_encode($data['images']);
                    $old_images = json_decode($rd[0]['images']);
                    foreach ($old_images as $row) {
                        if (!empty($row) && file_exists($row)) {
                            unlink($row);
                        }
                    }
                } else {
                    $data['images'] = $rd[0]['images'];
                }
                $updated_data = $ratings_model->update($rating_id, $data);
                if ($updated_data) {
                    update_ratings($service_id, $rating);
                    if (!empty($data['images'])) {
                        $images_array = json_decode($data['images'], true);
                        foreach ($images_array as $key => $img) {
                            $images_array[$key] = base_url($img);
                        }
                        $data['images'] = ($images_array);
                    }
                    $customer_details = fetch_details('users', ['id' => $user_id]);
                    $partner_id = fetch_details('services', ['id' => $service_id], ['user_id']);
                    if (!empty($customer_details[0]['email']) && check_notification_setting('new_rating_given_by_customer', 'email') && is_unsubscribe_enabled($customer_details[0]['id']) == 1) {
                        send_custom_email('new_rating_given_by_customer', $user_id, $customer_details[0]['email']);
                    }
                    if (check_notification_setting('new_rating_given_by_customer', 'sms')) {
                        send_custom_sms('new_rating_given_by_customer',  $customer_details[0]['id'], $customer_details[0]['email']);
                    }
                    return response("Rating Updated Successfully", false, remove_null_values($data), 200);
                } else {
                    return response("Rating couldn't be Updated", true, [], 200);
                }
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - add_rating()');
            return $this->response->setJSON($response);
        }
    }
    public function update_rating()
    {
        try {
            $validation = \Config\Services::validation();
            $ratings_model = new Service_ratings_model();
            $validation->setRules(
                [
                    'rating_id' => 'required',
                    'rating' => 'permit_empty',
                    'comment' => 'permit_empty',
                    'image' => 'permit_empty',
                ],
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $user_id = $this->user_details['id'];
            $rating_id = $this->request->getPost('rating_id');
            $ratings = has_rated($user_id, $rating_id);
            if ($ratings['error']) {
                return response($ratings['message'], true, [], 200);
            }
            $rating = (isset($_POST['rating'])) ? $this->request->getPost('rating') : "";
            $comment = (isset($_POST['comment'])) ? $this->request->getPost('comment') : "";
            if ($rating > 5) {
                return response("Can not rate More than 5", true, [], 200);
            }
            $data = [
                'rating' => ($rating != "") ? $rating : $ratings['data'][0]['rating'],
                'comment' => ($comment != "") ? $comment : $ratings['data'][0]['comment'],
            ];
            $data['images'] = [];
            $uploaded_images = $this->request->getFiles('images');
            $path = "public/uploads/ratings/";
            if (isset($uploaded_images['images'])) {
                foreach ($uploaded_images['images'] as $images) {
                    $validate_image = valid_image($images);
                    if ($validate_image == true) {
                        return response("Invalid Image", true, []);
                    }
                    $newName = $images->getRandomName();
                    if ($newName == null) {
                        $image = null;
                    } else {
                        move_file($images, $path, $newName);
                        $name = "public/uploads/ratings/$newName";
                        array_push($data['images'], $name);
                    }
                }
                $data['images'] = json_encode($data['images']);
            } else {
                $data['images'] = $ratings['data'][0]['images'];
            }
            $updated_data = $ratings_model->update($rating_id, $data);
            if ($updated_data) {
                return response("Ranking Updated Successfully", false, [], 200);
            } else {
                return response("Ranking Updated UnSuccessful", true, [], 200);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - update_rating()');
            return $this->response->setJSON($response);
        }
    }
    public function check_available_slot()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'partner_id' => 'required|numeric',
                    'date' => 'required|valid_date[Y-m-d]',
                    'time' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $partner_id = $this->request->getPost('partner_id');
            $date = $this->request->getPost('date');
            $time = $this->request->getPost('time');
            if ($this->request->getPost('order_id')) {
                if ($this->request->getPost('custom_job_request_id')) {
                    $custom_job_data = fetch_details('partner_bids', ['partner_id' => $this->request->getPost('partner_id'), 'custom_job_request_id' => $this->request->getPost('custom_job_request_id')]);
                    if (empty($custom_job_data)) {
                        return response("There is no data", true);
                    }
                    $service_total_duration = $custom_job_data[0]['duration'];
                } else {
                    $order = fetch_details('order_services', ['order_id' => $this->request->getPost('order_id')]);
                    $service_ids = [];
                    foreach ($order as $row) {
                        $service_ids[] = $row['service_id'];
                    }
                    $service_total_duration = 0;
                    foreach ($service_ids as $row) {
                        $service_data = fetch_details('services', ['id' => $row])[0];
                        $service_total_duration = $service_total_duration + $service_data['duration'];
                    }
                }
            } else if ($this->request->getPost('custom_job_request_id')) {
                $custom_job_data = fetch_details('partner_bids', ['partner_id' => $this->request->getPost('partner_id'), 'custom_job_request_id' => $this->request->getPost('custom_job_request_id')]);
                if (empty($custom_job_data)) {
                    return response("There is no data", true);
                }
                $service_total_duration = $custom_job_data[0]['duration'];
            } else {
                $cart_data = fetch_cart(true, $this->user_details['id']);
                if (empty($cart_data)) {
                    return response("Please add some item in cart", true);
                }
                $service_total_duration = 0;
                $service_duration = 0;
                foreach ($cart_data['data'] as $main_data) {
                    $service_duration = ($main_data['servic_details']['duration']) * $main_data['qty'];
                    $service_total_duration = $service_total_duration + $service_duration;
                }
            }
            $data = checkPartnerAvailability($partner_id, $date . ' ' . $time, $service_total_duration, $date, $time);
            return $this->response->setJSON($data);
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - check_available_slot()');
            return $this->response->setJSON($response);
        }
    }
    public function razorpay_create_order()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'order_id' => 'required|numeric',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $order_id = $this->request->getPost('order_id');
            if ($this->request->getPost('order_id') && !empty($this->request->getPost('order_id'))) {
                $where['o.id'] = $this->request->getPost('order_id');
            }
            $orders = new Orders_model();
            $order_detail = $orders->list(true, "", null, null, "", "", $where);
            $settings = get_settings('payment_gateways_settings', true);
            if (!empty($order_detail) && !empty($settings)) {
                $currency = $settings['razorpay_currency'];
                $price = $order_detail['data'][0]['final_total'];
                $amount = intval($price * 100);
                $create_order = $this->razorpay->create_order($amount, $order_id, $currency);
                if (!empty($create_order)) {
                    $response = [
                        'error' => false,
                        'message' => 'razorpay order created',
                        'data' => $create_order,
                    ];
                } else {
                    $response = [
                        'error' => true,
                        'message' => 'razorpay order not created',
                        'data' => [],
                    ];
                }
            } else {
                $response = [
                    'error' => true,
                    'message' => 'details not found"',
                    'data' => [],
                ];
            }
            return $this->response->setJSON($response);
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - razorpay_create_order()');
            return $this->response->setJSON($response);
        }
    }
    public function update_service_status()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'service_id' => 'required|numeric',
                    'status' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $order_id = $this->request->getPost('order_id');
            $service_id = $this->request->getPost('service_id');
            $status = strtolower($this->request->getPost('status'));
            $all_status = ['pending', 'awaiting', 'confirmed', 'rescheduled', 'cancelled', 'completed'];
            if (in_array(strtolower($status), $all_status)) {
                $res = update_details(['status' => $status], ['service_id' => $service_id, 'order_id' => $order_id], 'order_services');
                $data = fetch_details('order_services', ['service_id' => $service_id, 'order_id' => $order_id]);
                if ($res) {
                    $response = [
                        'error' => false,
                        'message' => 'Service status updated successfully!',
                        'data' => $data,
                    ];
                    return $this->response->setJSON($response);
                } else {
                    $response = [
                        'error' => true,
                        'message' => 'Service status cant be changed!',
                        'data' => [],
                    ];
                    return $this->response->setJSON($response);
                }
            } else {
                $response = [
                    'error' => true,
                    'message' => 'Please enter valid status!',
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - update_service_status()');
            return $this->response->setJSON($response);
        }
    }
    public function get_faqs()
    {
        try {
            $Faqs_model = new Faqs_model();
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $data = $Faqs_model->list(true, $search, $limit, $offset, $sort, $order);
            if (!empty($data['data'])) {
                return response('faqs fetched successfully', false, remove_null_values($data['data']), 200, ['total' => $data['total']]);
            } else {
                return response('faqs not found');
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_faqs()');
            return $this->response->setJSON($response);
        }
    }
    //wroking without UID
    // public function verify_user()
    // {
    //     // 101:- Mobile number already registered and Active
    //     // 102:- Mobile number is not registered
    //     // 103:- Mobile number is Deactive (edited) 
    //     try {
    //         $config = new \Config\IonAuth();
    //         $validation = \Config\Services::validation();
    //         $request = \Config\Services::request();
    //         $identity_column = $config->identity;
    //         $identity = $request->getPost('mobile');
    //         $country_code = $request->getPost('country_code');
    //         $db      = \Config\Database::connect();
    //         $builder = $db->table('users u');
    //         $builder->select('u.*,ug.group_id')
    //             ->join('users_groups ug', 'ug.user_id = u.id')
    //             ->where('ug.group_id', "2")
    //             ->where('u.phone', $identity);
    //         $user = $builder->get()->getResultArray();
    //         if (!empty($user)) {
    //             $fetched_country_code = $user[0]['country_code'];
    //             $fetched_user_mobile = $user[0]['phone'];
    //             if (($fetched_user_mobile == $identity) && ($fetched_country_code == $country_code)) {
    //                 if (($user[0]['active'] == 1)) {
    //                     $response = [
    //                         'error' => true,
    //                         'message_code' => "101",
    //                     ];
    //                 } else {
    //                     $response = [
    //                         'error' => true,
    //                         'message_code' => "103",
    //                     ];
    //                 }
    //             } else if (($fetched_user_mobile == $identity)) {
    //                 $data = fetch_details('users', ["phone" => $identity], $this->user_data)[0];
    //                 $data['country_code'] = $update_data['country_code'] = $this->request->getPost('country_code');
    //                 update_details($update_data, ['phone' => $identity], "users", false);
    //                 if (($user[0]['active'] == 1)) {
    //                     $response = [
    //                         'error' => true,
    //                         'message_code' => "101",
    //                     ];
    //                 } else {
    //                     $response = [
    //                         'error' => true,
    //                         'message_code' => "103",
    //                     ];
    //                 }
    //             } else if (($fetched_user_mobile != $identity)) {
    //                 $response = [
    //                     'error' => false,
    //                     'message_code' => "102",
    //                 ];
    //             } else if (($fetched_user_mobile != $identity) && ($fetched_country_code != $country_code)) {
    //                 $response = [
    //                     'error' => false,
    //                     'message_code' => "102",
    //                 ];
    //             }
    //         } else {
    //             $response = [
    //                 'error' => false,
    //                 'message_code' => "102",
    //             ];
    //         }
    //         return $this->response->setJSON($response);
    //     } catch (\Exception $th) {
    //         $response['error'] = true;
    //         $response['message'] = 'Something went wrong';
    //         return $this->response->setJSON($response);
    //     }
    // }
    public function verify_user()
    {
        // 101:- Mobile number already registered and Active
        // 102:- Mobile number is not registered
        // 103:- Mobile number is Deactive (edited) 
        try {
            $request = \Config\Services::request();
            $country_code = $request->getPost('country_code');
            $db      = \Config\Database::connect();
            $builder = $db->table('users u');
            if (isset($_POST['mobile']) && ($_POST['mobile']) != "") {
                $identity = $request->getPost('mobile');
                $field = 'u.phone';
            } else if (isset($_POST['uid'])  && ($_POST['uid']) != "") {
                $identity = $request->getPost('uid');
                $field = 'u.uid';
            } else {
                $response['error'] = true;
                $response['message'] = 'Enter Mobile or uid';
                return $this->response->setJSON($response);
            }
            if (isset($_POST['mobile']) && $_POST['mobile'] != '') {
                if (isset($_POST['country_code']) && $_POST['country_code'] != '') {
                    $builder->select('u.*,ug.group_id')
                        ->join('users_groups ug', 'ug.user_id = u.id')
                        ->where('ug.group_id', "2")
                        ->where('u.phone', $_POST['mobile'])->where('u.country_code', $_POST['country_code']);
                } else {
                    $builder->select('u.*,ug.group_id')
                        ->join('users_groups ug', 'ug.user_id = u.id')
                        ->where('ug.group_id', "2")
                        ->where('u.phone', $_POST['mobile']);
                }
            } elseif (isset($_POST['uid']) && $_POST['uid'] != '') {
                $builder->select('u.*,ug.group_id')
                    ->join('users_groups ug', 'ug.user_id = u.id')
                    ->where('ug.group_id', "2")
                    ->where('u.uid', $_POST['uid']);
            }
            $user = $builder->get()->getResultArray();
            if (!empty($user)) {
                if (isset($_POST['mobile']) && $_POST['mobile'] != "") {
                    $fetched_country_code = $user[0]['country_code'];
                    $fetched_user_mobile = $user[0]['phone'];
                    if ($fetched_user_mobile == $identity) {
                        if ($fetched_country_code == $country_code) {
                            $response = [
                                'error' => false,
                                'message_code' => $user[0]['active'] == 1 ? "101" : "103",
                            ];
                        } else {
                            $data = fetch_details('users', ["phone" => $identity], $this->user_data)[0];
                            $data['country_code'] = $update_data['country_code'] = $this->request->getPost('country_code');
                            update_details($update_data, ['phone' => $identity], "users", false);
                            $response = [
                                'error' => false,
                                'message_code' => "102",
                            ];
                        }
                    } else {
                        $response = [
                            'error' => false,
                            'message_code' => "102",
                        ];
                    }
                } else if (isset($_POST['uid']) && $_POST['uid'] != "") {
                    $response = [
                        'error' => false,
                        'message_code' => $user[0]['active'] == 1 ? "101" : "103",
                    ];
                }
            } else {
                $response = [
                    'error' => false,
                    'message_code' => "102",
                ];
            }
            $authentication_mode = get_settings('general_settings', true);
            if (empty($user)) {
                if (!empty($country_code)) {
                    $fetched_country_code = $country_code;
                } elseif (!empty($_POST['uid'])) {
                    $uid_user = fetch_details('users', ['uid' => $_POST['uid']]);
                    $fetched_country_code = !empty($uid_user) && !empty($uid_user[0]['country_code'])
                        ? $uid_user[0]['country_code']
                        : '';
                }
            }
            if ($authentication_mode['authentication_mode'] == "sms_gateway" && ($response['message_code'] == 101 || $response['message_code'] == 102) && isset($_POST['mobile'])) {
                $mobile = isset($_POST['mobile']) ? $_POST['mobile'] : "";
                $is_exist = fetch_details('otps', ['mobile' => $fetched_country_code . $mobile]);
                if (isset($mobile) &&  empty($is_exist)) {
                    $mobile_data = array(
                        'mobile' => $fetched_country_code . $mobile,
                        'created_at' => date('Y-m-d H:i:s'),
                    );
                    insert_details($mobile_data, 'otps');
                }
                $otp = random_int(100000, 999999);
                $send_otp_response = set_user_otp($mobile, $otp, $mobile, $fetched_country_code);
                if ($send_otp_response['error'] == false) {
                    $response['message'] = "OTP send successfully";
                } else {
                    $response['error'] = true;
                    $response['message'] = $send_otp_response['message'];
                }
            }
            $response['authentication_mode'] = $authentication_mode['authentication_mode'];
            return $this->response->setJSON($response);
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - verify_user()');
            return $this->response->setJSON($response);
        }
    }
    public function delete_user_account()
    {
        try {
            $user_id = $this->user_details['id'];
            if (!exists(['id' => $user_id], 'users')) {
                return response('user does not exist please enter valid user ID!', true);
            }
            $user_data = fetch_details('users_groups', ['user_id' => $user_id]);
            if (!empty($user_data) && isset($user_data[0]['group_id']) && !empty($user_data[0]['group_id']) && $user_data[0]['group_id'] == 2) {
                if (delete_details(['id' => $user_id], 'users') && delete_details(['user_id' => $user_id], 'users_groups')) {
                    delete_details(['user_id' => $user_id], 'users_tokens');
                    return response('User account deleted successfully', false);
                } else {
                    return response('User account does not delete', true);
                }
            } else {
                return response("This user's account can't delete ", true);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - delete_user_account()');
            return $this->response->setJSON($response);
        }
    }
    public function provider_check_availability()
    {
        try {
            $db = \Config\Database::connect();
            $customer_latitude = $this->request->getPost('latitude');
            $customer_longitude = $this->request->getPost('longitude');
            $settings = get_settings('general_settings', true);
            $general_settings = fetch_details('settings', ['variable' => 'general_settings']);
            $builder = $db->table('users u');
            $sql_distance = $having = '';
            $distance = $settings['max_serviceable_distance'];
            if ($this->request->getPost('is_checkout_process') == '1') {
                $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
                $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
                $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
                $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
                $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
                $where = [];
                if (!empty($this->request->getPost('order_id'))) {
                    $order_details = fetch_details('orders', ['id' => ($this->request->getPost('order_id')), 'user_id' => $this->user_details['id']]);
                } else {
                    $cart_details = fetch_cart(true, $this->user_details['id'], $search, $limit, $offset, $sort, $order, $where);
                }
                if (!empty($this->request->getPost('order_id'))) {
                    $provider_data = fetch_details('users', ['id' => $order_details[0]['partner_id']]);
                } else if (!empty($this->request->getPost('custom_job_request_id'))) {
                    $provider_data = fetch_details('users', ['id' => $this->request->getPost('bidder_id')]);
                } else {
                    $provider_data = fetch_details('users', ['id' => $cart_details['provider_id']]);
                }
                $provider_latitude = $provider_data[0]['latitude'];
                $provider_longitude = $provider_data[0]['longitude'];
                $partners = $builder->Select("u.username,u.city,u.latitude,u.longitude,u.id,p.company_name,u.image ,st_distance_sphere(POINT($customer_longitude, $customer_latitude), POINT($provider_longitude, $provider_latitude ))/1000  as distance")
                    ->join('users_groups ug', 'ug.user_id=u.id')
                    ->join('partner_details p', 'p.partner_id=u.id')
                    ->where('p.is_approved', '1')
                    ->where('ug.group_id', '3')
                    ->where('u.id', $provider_data[0]['id'])
                    ->having('distance < ' . $distance)
                    ->orderBy('distance')
                    ->get()->getResultArray();
                foreach ($partners as &$partner) {
                    if (!empty($partner['image'])) {
                        $partner['image'] = base_url() . '/' . $partner['image'];
                    }
                }
                if (!empty($partners)) {
                    $response = [
                        'error' => false,
                        'message' => "Provider is available",
                        "data" => $partners
                    ];
                } else {
                    $response = [
                        'error' => true,
                        'message' => "Provider is not available",
                    ];
                }
            } else {
                $partners = $builder->Select("u.username, u.city, u.latitude, u.longitude, p.company_name, u.image, u.id, st_distance_sphere(POINT($customer_longitude, $customer_latitude), POINT(`longitude`, `latitude`)) / 1000 as distance,
            (SELECT COUNT(*) FROM orders o WHERE o.partner_id = u.id AND o.parent_id IS NULL AND o.created_at > ps.purchase_date) as number_of_orders, ps.max_order_limit, ps.order_type")
                    ->join('users_groups ug', 'ug.user_id=u.id')
                    ->join('partner_subscriptions ps', 'ps.partner_id = u.id', 'left')
                    ->join('partner_details p', 'p.partner_id=u.id')
                    ->where('ps.status', 'active')
                    ->where('ug.group_id', '3')
                    ->having('(number_of_orders < max_order_limit OR number_of_orders = 0 OR order_type = "unlimited")')
                    ->having('distance < ' . $distance)
                    ->orderBy('distance')
                    ->get()->getResultArray();
                foreach ($partners as &$partner) {
                    if (!empty($partner['image'])) {
                        $partner['image'] = base_url() . '/' . $partner['image'];
                    }
                }
                if (!empty($partners)) {
                    $response = [
                        'error' => false,
                        'message' => "Providers are available",
                        "data" => $partners
                    ];
                } else {
                    $response = [
                        'error' => true,
                        'message' => "Providers are not available",
                    ];
                }
            }
            return $this->response->setJSON($response);
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - provider_check_availability()');
            return $this->response->setJSON($response);
        }
    }
    public function invoice_download()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'order_id' => 'required|numeric',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $db      = \Config\Database::connect();
            $order_id = $this->request->getPost('order_id');
            $this->orders = new Orders_model();
            $orders  = fetch_details('orders', ['id' => $order_id]);
            if (isset($orders) && empty($orders)) {
                return redirect('admin/orders');
            }
            $order_details = $this->orders->invoice($order_id)['order'];
            $partner_id = $order_details['partner_id'];
            $partner_details = $db
                ->table('partner_details pd')
                ->select('pd.company_name,pd.address, u.*')
                ->join('users u', 'u.id = pd.partner_id')
                ->where('partner_id', $partner_id)->get()->getResultArray();
            $user_id = $order_details['user_id'];
            $user_details = $db
                ->table('users u')
                ->select('u.*')
                ->where('u.id', $user_id)
                ->get()->getResultArray();
            $data = get_settings('general_settings', true);
            $this->data['currency'] = $data['currency'];
            $this->data['order'] = $order_details;
            $this->data['partner_details'] = $partner_details[0];
            $this->data['user_details'] = $user_details[0];
            $settings = get_settings('general_settings', true);
            $this->data['data'] = $settings;
            $orders  = fetch_details('orders', ['id' => $this->request->getPost('order_id')]);
            if (isset($orders) && empty($orders)) {
                return redirect('admin/orders');
            }
            $orders_model = new Orders_model();
            $data = get_settings('general_settings', true);
            $currency = $data['currency'];
            $tax = get_settings('system_tax_settings', true);
            $orders = $orders_model->invoice($order_id)['order'];
            $services = $orders['services'];
            $total =  count($services);
            if (!empty($orders)) {
                $i = 0;
                $total_tax_amount = 0;
                foreach ($services as $service) {
                    // print_R($service);
                    $rows[$i] = [
                        'service_title' => ucwords($service['service_title']),
                        'price' => $currency . number_format($service['price']),
                        'discount' => $currency . (($service['discount_price'] == 0) ? "0" : ($service['price'] - $service['discount_price'])),
                        'net_amount' => $currency . ($service['discount_price'] != 0) ? $currency . number_format($service['discount_price']) : $currency . ($service['price']),
                        'tax' => ($service['tax_type'] == "excluded") ? $service['tax_percentage'] . '%' : '0%',
                        'tax_amount' => $currency . (($service['tax_type'] == "excluded") ? $service['tax_amount'] : 0),
                        'quantity' => ucwords($service['quantity']),
                        'subtotal' => $currency . (($service['sub_total']))
                    ];
                    $i++;
                }
                $total_tax_amount =  ($orders['total'] * $tax['tax']) / 100;
                $empty_row = [
                    'service_title' => "",
                    'price' => "",
                    'discount' => "",
                    'net_amount' => "",
                    'tax' => "",
                    'tax_amount' => "",
                    'quantity' => "",
                    'subtotal' => "",
                ];
                $row = [
                    'service_title' => "",
                    'price' => "",
                    'discount' => "",
                    'net_amount' => "",
                    'tax' => "",
                    'tax_amount' => "",
                    'quantity' => "<strong class='text-dark  '>Total</strong>",
                    'subtotal' => "<strong class='text-dark '>" . $currency . (intval($orders['total'])) . "</strong>",
                ];
                $tax = [
                    'service_title' => "",
                    'price' => "",
                    'discount' => "",
                    'net_amount' => "",
                    'tax' => "",
                    'tax_amount' => "",
                    'quantity' => "<strong class='text-dark '>Tax Amount</strong>",
                    'subtotal' => "<strong class='text-dark '>" . $currency . $total_tax_amount . "</strong>",
                ];
                $visiting_charges = [
                    'service_title' => "",
                    'price' => "",
                    'discount' => "",
                    'net_amount' => "",
                    'tax' => "",
                    'tax_amount' => "",
                    'quantity' => "<strong class='text-dark '>Visiting Charges</strong>",
                    'subtotal' => "<strong class='text-dark '>" . $currency . $orders['visiting_charges'] . "</strong>",
                ];
                $promo_code_discount = [
                    'service_title' => "",
                    'price' => "",
                    'discount' => "",
                    'net_amount' => "",
                    'tax' => "",
                    'tax_amount' => "",
                    'quantity' => "<strong class='text-dark '>Promo Code Discount</strong>",
                    'subtotal' => "<strong class='text-dark '>" . $currency . $orders['promo_discount'] . "</strong>",
                ];
                $payble_amount = $orders['total']  - $orders['promo_discount'];
                $final_total = [
                    'service_title' => "",
                    'price' => "",
                    'discount' => "",
                    'net_amount' => "",
                    'tax' => "",
                    'tax_amount' => "",
                    'quantity' => "<strong class='text-dark '>Final Total</strong>",
                    'subtotal' => "<strong class='text-dark '>" . $currency . $payble_amount . "</strong>",
                ];
                $array['total'] = $total;
                $array['rows'] = $rows;
                $this->data['rows'] = $rows;
                $this->data['currency'] = $currency;
                try {
                    $html =  view('backend/admin/pages/invoice_from_api', $this->data);
                    $path = "public/uploads/";
                    $mpdf = new \Mpdf\Mpdf(['tempDir' => $path]);
                    $stylesheet = file_get_contents('public/backend/assets/css/vendor/bootstrap-table.css');
                    $mpdf->WriteHTML($stylesheet, 1); // CSS Script goes here.
                    $mpdf->WriteHTML($html);
                    $this->response->setHeader("Content-Type", "application/pdf");
                    $mpdf->Output('order-ID-' . $order_details['id'] . "-invoice.pdf", 'I');
                } catch (\Mpdf\MpdfException $e) {
                    print "Creating an mPDF object failed with" . $e->getMessage();
                }
            } else {
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - invoice_download()');
            return $this->response->setJSON($response);
        }
    }
    public function get_paypal_link()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'user_id' => 'required|numeric',
                    'order_id' => 'required',
                    'amount' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $user_id = $_POST['user_id'];
            $order_id = $_POST['order_id'];
            $amount = $_POST['amount'];
            $response = [
                'error' => false,
                'message' => 'Order Detail Founded !',
                'data' => base_url('/api/v1/paypal_transaction_webview?' . 'user_id=' . $user_id . '&order_id=' . $order_id . '&amount=' . intval($amount)),
            ];
            $token = $this->paypal_lib->generate_token();
            return $this->response->setJSON($token);
            print_r($token);
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_paypal_link()');
            return $this->response->setJSON($response);
        }
    }
    public function paypal_transaction_webview()
    {
        try {
            header("Content-Type: html");
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'user_id' => 'required|numeric',
                    'order_id' => 'required',
                    'amount' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $user_id = $_GET['user_id'];
            $order_id = $_GET['order_id'];
            $amount = $_GET['amount'];
            $user = fetch_details('users', ['id' => $user_id]);
            if (empty($user)) {
                echo "user not found";
                return false;
            }
            $order_res = fetch_details('orders', ['id' => $order_id]);
            $data['user'] = $user[0];
            $data['order'] = $order_res[0];
            $data['payment_type'] = "paypal";
            $encryption = order_encrypt($user_id, $amount, $order_id);
            if (!empty($order_res)) {
                $data['user'] = $user[0];
                $data['order'] = $order_res[0];
                $data['payment_type'] = "paypal";
                // Set variables for paypal form
                $returnURL = base_url() . '/api/v1/app_payment_status';
                $payment_gateways_settings = get_settings('payment_gateways_settings', true);
                if ($payment_gateways_settings['paypal_website_url'] != "") {
                    $return_url = $payment_gateways_settings['paypal_website_url'] . "/payment-status?order_id=" . $this->request->getVar('order_id');
                } else {
                    $return_url =  base_url() . '/api/v1/app_payment_status';
                }
                if ($payment_gateways_settings['paypal_website_url'] != "") {
                    $cancel_url = $payment_gateways_settings['paypal_website_url'] . "/payment-status?order_id=" . $this->request->getVar('order_id');
                } else {
                    $cancel_url = base_url() . '/api/v1/app_payment_status?order_id=' . $encryption . '&payment_status=Failed';
                }
                $cancelURL = base_url() . '/api/v1/app_payment_status?order_id=' . $encryption . '&payment_status=Failed';
                $notifyURL = base_url() . '/api/webhooks/paypal';
                $txn_id = time() . "-" . rand();
                // Get current user ID from the session
                $userID = $data['user']['id'];
                $order_id = $data['order']['id'];
                $payeremail = $data['user']['email'];
                // $this->paypal_lib->add_field('return', $returnURL);
                $this->paypal_lib->add_field('return', $return_url);
                // $this->paypal_lib->add_field('cancel_return', $cancelURL);
                $this->paypal_lib->add_field('cancel_return', $cancel_url);
                $this->paypal_lib->add_field('notify_url', $notifyURL);
                $this->paypal_lib->add_field('item_name', 'Test');
                if (isset($_GET['additional_charges_transaction_id'])) {
                    $this->paypal_lib->add_field('custom', $userID . '|' . $payeremail . '|' . $_GET['additional_charges_transaction_id']);
                } else {
                    $this->paypal_lib->add_field('custom', $userID . '|' . $payeremail);
                }
                $this->paypal_lib->add_field('item_number', $order_id);
                $this->paypal_lib->add_field('amount', $amount);
                // Render paypal form
                $this->paypal_lib->paypal_auto_form();
            } else {
                $data['user'] = $user[0];
                $data['payment_type'] = "paypal";
                // Set variables for paypal form
                $returnURL = base_url() . '/api/v1/app_payment_status';
                $cancelURL = base_url() . '/api/v1/app_payment_status';
                $notifyURL = base_url() . '/api/webhooks/paypal';
                $txn_id = time() . "-" . rand();
                // Get current user ID from the session
                $userID = $data['user']['id'];
                $order_id = $order_id;
                $payeremail = $data['user']['email'];
                $this->paypal_lib->add_field('return', $returnURL);
                $this->paypal_lib->add_field('cancel_return', $cancelURL);
                $this->paypal_lib->add_field('notify_url', $notifyURL);
                $this->paypal_lib->add_field('item_name', 'Online shopping');
                if (isset($_GET['additional_charges_transaction_id'])) {
                    $this->paypal_lib->add_field('custom', $userID . '|' . $payeremail . '|' . $_GET['additional_charges_transaction_id']);
                } else {
                    $this->paypal_lib->add_field('custom', $userID . '|' . $payeremail);
                }
                $this->paypal_lib->add_field('item_number', $order_id);
                $this->paypal_lib->add_field('amount', $amount);
                // Render paypal form
                $this->paypal_lib->paypal_auto_form();
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - paypal_transaction_webview()');
            return $this->response->setJSON($response);
        }
    }
    public function app_payment_status()
    {
        try {
            $paypalInfo = $_GET;
            if (!empty($paypalInfo) && isset($_GET['st']) && strtolower($_GET['st']) == "completed") {
                $response['error'] = false;
                $response['message'] = "Payment Completed Successfully";
                $response['data'] = $paypalInfo;
                $response['payment_status'] = "Completed";
            } elseif (!empty($paypalInfo) && isset($_GET['st']) && strtolower($_GET['st']) == "authorized") {
                $response['error'] = false;
                $response['message'] = "Your payment is has been Authorized successfully. We will capture your transaction within 30 minutes, once we process your order. After successful capture coins wil be credited automatically.";
                $response['data'] = $paypalInfo;
            } elseif (!empty($paypalInfo) && isset($_GET['st']) && strtolower($_GET['st']) == "Pending") {
                $response['error'] = false;
                $response['message'] = "Your payment is pending and is under process. We will notify you once the status is updated.";
                $response['data'] = $paypalInfo;
                $response['payment_status'] = "Pending";
            } else {
                $order_id = order_decrypt($_GET['order_id']);
                update_details(['payment_status' => 2], ['id' => $order_id[2]], 'orders');
                update_details(['status' => 'cancelled'], ['id' => $order_id[2]], 'orders');
                $data = [
                    'transaction_type' => 'transaction',
                    'user_id' => $order_id[0],
                    'partner_id' => "",
                    'order_id' => $order_id[2],
                    'type' => 'paypal',
                    'txn_id' => "",
                    'amount' => $order_id[1],
                    'status' => 'failed',
                    'currency_code' => "",
                    'message' => 'Order is cancelled',
                ];
                $insert_id = add_transaction($data);
                $response['error'] = true;
                $response['message'] = "Payment Cancelled / Declined ";
                $response['payment_status'] = "Failed";
                $response['data'] = $_GET;
            }
            print_r(json_encode($response));
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - app_payment_status()');
            return $this->response->setJSON($response);
        }
    }
    public function checkAndUpdateSubscriptionStatus($partnerId)
    {
        try {
            $partnerSubscriptionModel = new Partner_subscription_model();
            $subscriptionData = $partnerSubscriptionModel
                ->where('partner_id', $partnerId)
                ->where('status', 'active')
                ->where('order_type', 'limited')
                ->where('price !=', 0)
                ->first();
            if (!$subscriptionData) {
                return;
            }
            $orderModel = new Orders_model();
            $subscriptionCount = $orderModel
                ->where('partner_id', $partnerId)
                ->where('created_at >=', $subscriptionData['updated_at'])
                ->countAllResults();
            if ($subscriptionCount >= $subscriptionData['max_order_limit']) {
                $data['status'] = 'deactive';
                $where['partner_id'] = $partnerId;
                $where['status'] = 'active';
                update_details($data, $where, 'partner_subscriptions');
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - checkAndUpdateSubscriptionStatus()');
            return $this->response->setJSON($response);
        }
    }
    public function verify_transaction()
    {
        $validation = service('validation');
        $validation->setRules([
            'order_id' => 'required|numeric',
        ]);
        if (!$validation->withRequest($this->request)->run()) {
            $errors = $validation->getErrors();
            $response = [
                'error' => true,
                'message' => $errors,
                'data' => [],
            ];
            return $this->response->setJSON($response);
        }
        $transaction_model = new Transaction_model();
        $order_id = (int) $this->request->getVar('order_id');
        $transaction = fetch_details('transactions', ['order_id' => $order_id, 'user_id' => $this->user_details['id']]);
        $settings = get_settings('payment_gateways_settings', true);
        if (!empty($transaction)) {
            $transaction_id = $transaction[0]['txn_id'];
            $payment_gateways = $transaction[0]['type'];
            if ($payment_gateways == 'razorpay') {
                $razorpay = new Razorpay;
                $credentials = $razorpay->get_credentials();
                $secret = $credentials['secret'];
                $api = new Api($credentials['key'], $secret);
                $data = $api->payment->fetch($transaction_id);
                $status = $data->status;
                if ($status == "captured") {
                    $cart_data = fetch_cart(true, $this->user_details['id']);
                    if (!empty($cart_data)) {
                        foreach ($cart_data['data'] as $row) {
                            delete_details(['id' => $row['id']], 'cart');
                        }
                    }
                    $response = [
                        'error' => true,
                        'message' => 'verified',
                        'data' => [],
                    ];
                    return $this->response->setJSON($response);
                }
            }
            if ($payment_gateways == "paystack") {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . $transaction[0]['reference'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HTTPHEADER => array(
                        "Authorization: Bearer " . $settings['paystack_secret'],
                        "Cache-Control: no-cache",
                    ),
                ));
                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);
                $response = [
                    'error' => false,
                    'message' => 'verified',
                    'data' => json_decode($response),
                ];
                return $this->response->setJSON($response);
            }
            if ($payment_gateways == "paypal") {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api-m.sandbox.paypal.com/v2/payments/captures/' . $transaction[0]['txn_id'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Basic ' . base64_encode($settings['paypal_client_key'] . ':' . $settings['paypal_secret_key']),
                        'Content-Type: application/json',
                        'Cookie: l7_az=ccg14.slc'
                    ),
                ));
                $response1 = curl_exec($curl);
                curl_close($curl);
                $response = [
                    'error' => false,
                    'message' => 'verified',
                    'data' => json_decode($response1),
                ];
                return $this->response->setJSON($response);
                echo $response;
            }
        }
    }
    public function contact_us_api()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'name' => 'required',
                    'subject' => 'required',
                    'message' => 'required',
                    'email' => 'required'
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $name = $_POST['name'];
            $subject = $_POST['subject'];
            $message = $_POST['message'];
            $email = $_POST['email'];
            $admin_contact_query = [
                'name' => $name,
                'subject' => $subject,
                'message' => $message,
                'email' => isset($email) ? $email : "0",
            ];
            insert_details($admin_contact_query, 'admin_contact_query');
            $response['error'] = false;
            $response['message'] = "Query send successfully";
            $response['data'] = $admin_contact_query;
            return $this->response->setJSON($response);
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - contact_us_api()');
            return $this->response->setJSON($response);
        }
    }
    function search_services_providers()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'search' => 'required',
                    'latitude' => 'required',
                    'longitude' => 'required',
                    'type' => 'required'
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $search = $this->request->getPost('search') ?? '';
            $latitude = $this->request->getPost('latitude') ?? '';
            $longitude = $this->request->getPost('longitude') ?? '';
            $db = \Config\Database::connect();
            $limit = $this->request->getPost('limit') ?? '5';
            $offset = $this->request->getPost('offset') ?? '0';
            $type = $this->request->getPost('type');
            $data = [];
            if ($type == "provider") {
                $settings = get_settings('general_settings', true);
                if (($this->request->getPost('latitude') && !empty($this->request->getPost('latitude')) && ($this->request->getPost('longitude') && !empty($this->request->getPost('longitude'))))) {
                    $additional_data = [
                        'latitude' => $this->request->getPost('latitude'),
                        'longitude' => $this->request->getPost('longitude'),
                        'max_serviceable_distance' => $settings['max_serviceable_distance'],
                    ];
                }
                $is_latitude_set = "";
                if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
                    $latitude = $this->request->getPost('latitude');
                    $longitude = $this->request->getPost('longitude');
                    $is_latitude_set = " st_distance_sphere(POINT(' $longitude','$latitude'), POINT(`p`.`longitude`, `p`.`latitude` ))/1000  as distance";
                }
                $builder1 = $db->table('users u1');
                $partners1 = $builder1->Select("u1.username,u1.city,u1.latitude,u1.longitude,u1.id,pc.minimum_order_amount,pc.discount,pd.company_name,u1.image,pd.banner, pc.discount_type,u1.id as partner_id,
                    pd.number_of_ratings as number_of_rating,pd.ratings AS average_rating,
                    pd.ratings as ratings,
                    pd.visiting_charges as visiting_charges,
                    (SELECT COUNT(*) FROM orders o WHERE o.partner_id = u1.id AND o.parent_id IS NULL AND o.status='completed') as number_of_orders,st_distance_sphere(POINT($longitude, $latitude),
                    POINT(`longitude`, `latitude` ))/1000  as distance")
                    ->join('users_groups ug1', 'ug1.user_id=u1.id')
                    ->join('partner_details pd', 'pd.partner_id=u1.id')
                    ->join('services s', 's.user_id=pd.partner_id', 'left')
                    ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
                    ->join('partner_subscriptions ps', 'ps.partner_id=u1.id')
                    ->join('promo_codes pc', 'pc.partner_id=u1.id', 'left')
                    ->where('ps.status', 'active')
                    ->where('pd.is_approved', '1')
                    ->where('ug1.group_id', '3')
                    ->groupBy('pd.partner_id')
                    ->having('distance < ' . $additional_data['max_serviceable_distance'])
                    ->orderBy('distance')->limit($limit, $offset);
                if ($search and $search != '') {
                    $searchWhere = [
                        '`pd.id`' => $search,
                        '`pd.company_name`' => $search,
                        '`pd.tax_name`' => $search,
                        '`pd.tax_number`' => $search,
                        '`pd.bank_name`' => $search,
                        '`pd.account_number`' => $search,
                        '`pd.account_name`' => $search,
                        '`pd.bank_code`' => $search,
                        '`pd.swift_code`' => $search,
                        '`pd.created_at`' => $search,
                        '`pd.updated_at`' => $search,
                        '`u1.username`' => $search,
                    ];
                    if (isset($searchWhere) && !empty($searchWhere)) {
                        $builder1->groupStart();
                        $builder1->orLike($searchWhere);
                        $builder1->groupEnd();
                    }
                }
                $partners1 = $builder1->get()->getResultArray();
                for ($i = 0; $i < count($partners1); $i++) {
                    $partners1[$i]['upto'] = $partners1[$i]['minimum_order_amount'];
                    if (!empty($partners1[$i]['image'])) {
                        $image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $partners1[$i]['image'])) ? base_url('public/backend/assets/profiles/' . $partners1[$i]['image']) : ((file_exists(FCPATH . $partners1[$i]['image'])) ? base_url($partners1[$i]['image']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $partners1[$i]['image'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $partners1[$i]['image'])));
                        $partners1[$i]['image'] = $image;
                        $banner_image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $partners1[$i]['banner'])) ? base_url('public/backend/assets/profiles/' . $partners1[$i]['banner']) : ((file_exists(FCPATH . $partners1[$i]['banner'])) ? base_url($partners1[$i]['banner']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $partners1[$i]['banner'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $partners1[$i]['banner'])));
                        $partners1[$i]['banner_image'] = $banner_image;
                        unset($partners1[$i]['banner']);
                        if ($partners1[$i]['discount_type'] == 'percentage') {
                            $upto = $partners1[$i]['minimum_order_amount'];
                            unset($partners1[$i]['discount_type']);
                        }
                    }
                    unset($partners1[$i]['minimum_order_amount']);
                }
                $ids = [];
                foreach ($partners1 as $key => $row1) {
                    $ids[] = $row1['id'];
                }
                foreach ($ids as $key => $id) {
                    $partner_subscription = fetch_details('partner_subscriptions', ['partner_id' => $id, 'status' => 'active']);
                    if ($partner_subscription) {
                        $subscription_purchase_date = $partner_subscription[0]['updated_at'];
                        $partner_order_limit = fetch_details('orders', ['partner_id' => $id, 'parent_id' => null, 'created_at >' => $subscription_purchase_date]);
                        $partners_subscription = $db->table('partner_subscriptions ps');
                        $partners_subscription_data = $partners_subscription->select('ps.*')->where('ps.status', 'active')
                            ->get()
                            ->getResultArray();
                        $subscription_order_limit = $partners_subscription_data[0]['max_order_limit'];
                        if ($partners_subscription_data[0]['order_type'] == "limited") {
                            if (count($partner_order_limit) >= $subscription_order_limit) {
                                unset($ids[$key]);
                            }
                        }
                    } else {
                        unset($ids[$key]);
                    }
                }
                $parent_ids = array_values($ids);
                $parent_ids = implode(", ", $parent_ids);
                $data['providers'] = $partners1;
                // for total ------------------------------
                $builder1_total = $db->table('users u1');
                $partners1_total = $builder1_total->Select("u1.username,u1.city,u1.latitude,u1.longitude,u1.id,pc.minimum_order_amount,pc.discount,pd.company_name,u1.image,pd.banner, pc.discount_type,
                   ( count(sr.rating)) as number_of_rating,
                    ( SUM(sr.rating)) as total_rating,
                    ((SUM(sr.rating) / count(sr.rating))) as average_rating,
                        (SELECT COUNT(*) FROM orders o WHERE o.partner_id = u1.id AND o.parent_id IS NULL AND o.status='completed') as number_of_orders,st_distance_sphere(POINT($longitude, $latitude),
                        POINT(`longitude`, `latitude` ))/1000  as distance")
                    ->join('users_groups ug1', 'ug1.user_id=u1.id')
                    ->join('partner_details pd', 'pd.partner_id=u1.id')
                    ->join('services s', 's.user_id=pd.partner_id', 'left')
                    ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
                    ->join('partner_subscriptions ps', 'ps.partner_id=u1.id')
                    ->join('promo_codes pc', 'pc.partner_id=u1.id', 'left')
                    ->where('ps.status', 'active')
                    ->where('ug1.group_id', '3')
                    ->groupBy('pd.partner_id')
                    ->having('distance < ' . $additional_data['max_serviceable_distance'])
                    ->orderBy('distance');
                if ($search and $search != '') {
                    $searchWhere = [
                        '`pd.id`' => $search,
                        '`pd.company_name`' => $search,
                        '`pd.tax_name`' => $search,
                        '`pd.tax_number`' => $search,
                        '`pd.bank_name`' => $search,
                        '`pd.account_number`' => $search,
                        '`pd.account_name`' => $search,
                        '`pd.bank_code`' => $search,
                        '`pd.swift_code`' => $search,
                        '`pd.created_at`' => $search,
                        '`pd.updated_at`' => $search,
                        '`u1.username`' => $search,
                    ];
                    if (isset($searchWhere) && !empty($searchWhere)) {
                        $builder1_total->groupStart();
                        $builder1_total->orLike($searchWhere);
                        $builder1_total->groupEnd();
                    }
                }
                $partners1_total = $builder1_total->get()->getResultArray();
                for ($i = 0; $i < count($partners1_total); $i++) {
                    $partners1_total[$i]['upto'] = $partners1_total[$i]['minimum_order_amount'];
                    if (!empty($partners1_total[$i]['image'])) {
                        $image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $partners1_total[$i]['image'])) ? base_url('public/backend/assets/profiles/' . $partners1_total[$i]['image']) : ((file_exists(FCPATH . $partners1_total[$i]['image'])) ? base_url($partners1_total[$i]['image']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $partners1_total[$i]['image'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $partners1_total[$i]['image'])));
                        $partners1_total[$i]['image'] = $image;
                        $banner_image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $partners1_total[$i]['banner'])) ? base_url('public/backend/assets/profiles/' . $partners1_total[$i]['banner']) : ((file_exists(FCPATH . $partners1_total[$i]['banner'])) ? base_url($partners1_total[$i]['banner']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $partners1_total[$i]['banner'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $partners1_total[$i]['banner'])));
                        $partners1_total[$i]['banner_image'] = $banner_image;
                        unset($partners1_total[$i]['banner']);
                        if ($partners1_total[$i]['discount_type'] == 'percentage') {
                            $upto = $partners1_total[$i]['minimum_order_amount'];
                            unset($partners1_total[$i]['discount_type']);
                        }
                    }
                    unset($partners1_total[$i]['minimum_order_amount']);
                }
                $ids = [];
                foreach ($partners1_total as $key => $row1) {
                    $ids[] = $row1['id'];
                }
                foreach ($ids as $key => $id) {
                    $partner_subscription = fetch_details('partner_subscriptions', ['partner_id' => $id, 'status' => 'active']);
                    if ($partner_subscription) {
                        $subscription_purchase_date = $partner_subscription[0]['updated_at'];
                        $partner_order_limit = fetch_details('orders', ['partner_id' => $id, 'parent_id' => null, 'created_at >' => $subscription_purchase_date]);
                        $partners_subscription = $db->table('partner_subscriptions ps');
                        $partners_subscription_data = $partners_subscription->select('ps.*')->where('ps.status', 'active')
                            ->get()
                            ->getResultArray();
                        $subscription_order_limit = $partners_subscription_data[0]['max_order_limit'];
                        if ($partners_subscription_data[0]['order_type'] == "limited") {
                            if (count($partner_order_limit) >= $subscription_order_limit) {
                                unset($ids[$key]);
                            }
                        }
                    } else {
                        unset($ids[$key]);
                    }
                }
                $data['total'] = count($partners1_total);
                //end for total 
            } else if ($type == "service") {
                // services 
                $settings = get_settings('general_settings', true);
                if (($this->request->getPost('latitude') && !empty($this->request->getPost('latitude')) && ($this->request->getPost('longitude') && !empty($this->request->getPost('longitude'))))) {
                    $additional_data = [
                        'latitude' => $this->request->getPost('latitude'),
                        'longitude' => $this->request->getPost('longitude'),
                        'max_serviceable_distance' => $settings['max_serviceable_distance'],
                    ];
                }
                $is_latitude_set = "";
                if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
                    $latitude = $this->request->getPost('latitude');
                    $longitude = $this->request->getPost('longitude');
                    $is_latitude_set = " st_distance_sphere(POINT(' $longitude','$latitude'), POINT(`p`.`longitude`, `p`.`latitude` ))/1000  as distance";
                }
                $multipleWhere = '';
                $db      = \Config\Database::connect();
                $builder = $db->table('services s');
                $services = $builder->select("s.*,s.image as service_image, c.name as category_name, p.username as partner_name, c.parent_id, pd.company_name,
                     pd.at_store as provider_at_store, pd.at_doorstep as provider_at_doorstep, p.city,
                p.latitude, p.longitude, p.id as user_id, pd.banner, p.image as partner_image,
                COALESCE(COUNT(sr.rating), 0) as number_of_rating,
                COALESCE(SUM(sr.rating), 0) as provider_total_rating,
                (SELECT COUNT(*) FROM orders o WHERE o.partner_id = p.id AND o.parent_id IS NULL AND o.status='completed') as number_of_orders, st_distance_sphere(POINT($longitude, $latitude),
                POINT(p.longitude, p.latitude))/1000 as distance, pc.discount, pc.discount_type, pc.minimum_order_amount")
                    ->join('users p', 'p.id=s.user_id', 'left')
                    ->join('partner_details pd', 'pd.partner_id=s.user_id')
                    ->join('partner_subscriptions ps', 'ps.partner_id=s.user_id')
                    ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
                    ->join('promo_codes pc', 'pc.partner_id=p.id', 'left')
                    ->join('categories c', 'c.id=s.category_id', 'left')
                    ->where('pd.at_store', 's.at_store', false)
                    ->where('pd.at_doorstep', 's.at_doorstep', false)
                    ->where('s.approved_by_admin', '1', false)
                    ->where('s.status', '1', false)
                    ->where('ps.status', 'active')
                    ->where('pd.is_approved', '1')
                    ->having('distance < ' . $additional_data['max_serviceable_distance'])
                    ->groupBy('s.id');
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
                    if (isset($multipleWhere) && !empty($multipleWhere)) {
                        $services->groupStart();
                        $services->orLike($multipleWhere);
                        $services->groupEnd();
                    }
                }
                $service_result = $services->get()->getResultArray();
                $groupedServices = [];
                $groupedServices1 = [];
                $all_providers = [];
                foreach ($service_result as $row) {
                    $all_providers[] = $row['user_id'];
                    $providerId = $row['user_id'];
                    $average_rating = $db->table('services s')
                        ->select('(SUM(sr.rating) / COUNT(sr.rating)) as average_rating')
                        ->join('services_ratings sr', 'sr.service_id = s.id')
                        ->where('s.id', $row['id'])
                        ->get()->getRowArray();
                    $row['average_rating'] = isset($average_rating['average_rating']) ? number_format($average_rating['average_rating'], 2) : 0;
                    $rate_data = get_service_ratings($row['id']);
                    $row['total_ratings'] = $rate_data[0]['total_ratings'] ?? 0;
                    $row['rating_5'] = $rate_data[0]['rating_5'] ?? 0;
                    $row['rating_4'] = $rate_data[0]['rating_4'] ?? 0;
                    $row['rating_3'] = $rate_data[0]['rating_3'] ?? 0;
                    $row['rating_2'] = $rate_data[0]['rating_2'] ?? 0;
                    $row['rating_1'] = $rate_data[0]['rating_1'] ?? 0;
                    if (isset($row['service_image']) && !empty($row['service_image']) && check_exists(base_url($row['service_image']))) {
                        $images = base_url($row['service_image']);
                    } else {
                        $images = '';
                    }
                    $row['image_of_the_service'] = $images;
                    $tax_data = fetch_details('taxes', ['id' => $row['tax_id']], ['title', 'percentage']);
                    $taxPercentageData = fetch_details('taxes', ['id' => $row['tax_id']], ['percentage']);
                    if (!empty($taxPercentageData)) {
                        $taxPercentage = $taxPercentageData[0]['percentage'];
                    } else {
                        $taxPercentage = 0;
                    }
                    if (empty($tax_data)) {
                        $row['tax_title'] = "";
                        $row['tax_percentage'] = "";
                    } else {
                        $row['tax_title'] = $tax_data[0]['title'];
                        $row['tax_percentage'] = $tax_data[0]['percentage'];
                    }
                    if ($row['discounted_price'] == "0") {
                        if ($row['tax_type'] == "excluded") {
                            $row['tax_value'] = number_format((intval(($row['price'] * ($taxPercentage) / 100))), 2);
                            $row['price_with_tax']  = strval($row['price'] + ($row['price'] * ($taxPercentage) / 100));
                            $row['original_price_with_tax'] = strval($row['price'] + ($row['price'] * ($taxPercentage) / 100));
                        } else {
                            $row['tax_value'] = "";
                            $row['price_with_tax']  = strval($row['price']);
                            $row['original_price_with_tax'] = strval($row['price']);
                        }
                    } else {
                        if ($row['tax_type'] == "excluded") {
                            $row['tax_value'] = number_format((intval(($row['discounted_price'] * ($taxPercentage) / 100))), 2);
                            $row['price_with_tax']  = strval($row['discounted_price'] + ($row['discounted_price'] * ($taxPercentage) / 100));
                            $row['original_price_with_tax'] = strval($row['price'] + ($row['discounted_price'] * ($taxPercentage) / 100));
                        } else {
                            $row['tax_value'] = "";
                            $row['price_with_tax']  = strval($row['discounted_price']);
                            $row['original_price_with_tax'] = strval($row['price']);
                        }
                    }
                    if (!isset($groupedServices[$providerId])) {
                        $groupedServices[$providerId]['provider']['company_name'] = $row['company_name'];
                        $groupedServices[$providerId]['provider']['username'] = $row['partner_name'];
                        $groupedServices[$providerId]['provider']['city'] = $row['city'];
                        $groupedServices[$providerId]['provider']['latitude'] = $row['latitude'];
                        $groupedServices[$providerId]['provider']['longitude'] = $row['longitude'];
                        $groupedServices[$providerId]['provider']['id'] = $row['user_id'];
                        $groupedServices[$providerId]['provider']['image'] = $row['partner_image'];
                        $groupedServices[$providerId]['provider']['banner_image'] = $row['banner'];
                        $groupedServices[$providerId]['provider']['number_of_rating'] = $row['number_of_rating'];
                        $groupedServices[$providerId]['provider']['total_rating'] = $row['provider_total_rating'];
                        $groupedServices[$providerId]['provider']['average_rating'] = $row['average_rating'];
                        $groupedServices[$providerId]['provider']['number_of_orders'] = $row['number_of_orders'];
                        $groupedServices[$providerId]['provider']['distance'] = $row['distance'];
                        $groupedServices[$providerId]['provider']['discount_type'] = $row['discount_type'];
                        $groupedServices[$providerId]['provider']['discount'] = $row['discount'];
                        $groupedServices[$providerId]['provider']['upto'] = $row['minimum_order_amount'];
                        unset($row['minimum_order_amount']);
                        $groupedServices[$providerId]['provider']['services'] = [];
                    }
                    // Add the service to the provider's services array
                    $groupedServices[$providerId]['provider']['services'][] = $row;
                }
                $all_providers = array_unique($all_providers);
                $all_providers = array_slice(($all_providers), $offset, $limit);
                foreach ($service_result as $row) {
                    $providerId = $row['user_id'];
                    if (in_array($providerId, $all_providers)) {
                        $average_rating = $db->table('services s')
                            ->select('(SUM(sr.rating) / COUNT(sr.rating)) as average_rating')
                            ->join('services_ratings sr', 'sr.service_id = s.id')
                            ->where('s.id', $row['id'])
                            ->get()->getRowArray();
                        $row['average_rating'] = isset($average_rating['average_rating']) ? number_format($average_rating['average_rating'], 2) : 0;
                        $rate_data = get_service_ratings($row['id']);
                        $row['total_ratings'] = $rate_data[0]['total_ratings'] ?? 0;
                        $row['rating_5'] = $rate_data[0]['rating_5'] ?? 0;
                        $row['rating_4'] = $rate_data[0]['rating_4'] ?? 0;
                        $row['rating_3'] = $rate_data[0]['rating_3'] ?? 0;
                        $row['rating_2'] = $rate_data[0]['rating_2'] ?? 0;
                        $row['rating_1'] = $rate_data[0]['rating_1'] ?? 0;
                        if (isset($row['service_image']) && !empty($row['service_image']) && check_exists(base_url($row['service_image']))) {
                            $images = base_url($row['service_image']);
                        } else {
                            $images = '';
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
                            $row['faqs'] = [];
                        }
                        $row['image_of_the_service'] = $images;
                        $row['image'] = $images;
                        unset($row['service_image']);
                        $tax_data = fetch_details('taxes', ['id' => $row['tax_id']], ['title', 'percentage']);
                        $taxPercentageData = fetch_details('taxes', ['id' => $row['tax_id']], ['percentage']);
                        if (!empty($taxPercentageData)) {
                            $taxPercentage = $taxPercentageData[0]['percentage'];
                        } else {
                            $taxPercentage = 0;
                        }
                        if (empty($tax_data)) {
                            $row['tax_title'] = "";
                            $row['tax_percentage'] = "";
                        } else {
                            $row['tax_title'] = $tax_data[0]['title'];
                            $row['tax_percentage'] = $tax_data[0]['percentage'];
                        }
                        if ($row['discounted_price'] == "0") {
                            if ($row['tax_type'] == "excluded") {
                                $row['tax_value'] = number_format((intval(($row['price'] * ($taxPercentage) / 100))), 2);
                                $row['price_with_tax']  = strval($row['price'] + ($row['price'] * ($taxPercentage) / 100));
                                $row['original_price_with_tax'] = strval($row['price'] + ($row['price'] * ($taxPercentage) / 100));
                            } else {
                                $row['tax_value'] = "";
                                $row['price_with_tax']  = strval($row['price']);
                                $row['original_price_with_tax'] = strval($row['price']);
                            }
                        } else {
                            if ($row['tax_type'] == "excluded") {
                                $row['tax_value'] = number_format((intval(($row['discounted_price'] * ($taxPercentage) / 100))), 2);
                                $row['price_with_tax']  = strval($row['discounted_price'] + ($row['discounted_price'] * ($taxPercentage) / 100));
                                $row['original_price_with_tax'] = strval($row['price'] + ($row['discounted_price'] * ($taxPercentage) / 100));
                            } else {
                                $row['tax_value'] = "";
                                $row['price_with_tax']  = strval($row['discounted_price']);
                                $row['original_price_with_tax'] = strval($row['price']);
                            }
                        }
                        if (!isset($groupedServices1[$providerId])) {
                            $groupedServices1[$providerId]['provider']['company_name'] = $row['company_name'];
                            $groupedServices1[$providerId]['provider']['username'] = $row['partner_name'];
                            $groupedServices1[$providerId]['provider']['city'] = $row['city'];
                            $groupedServices1[$providerId]['provider']['latitude'] = $row['latitude'];
                            $groupedServices1[$providerId]['provider']['longitude'] = $row['longitude'];
                            $groupedServices1[$providerId]['provider']['id'] = $row['user_id'];
                            $groupedServices1[$providerId]['provider']['image'] = $row['image'];
                            $groupedServices1[$providerId]['provider']['banner_image'] = $row['banner'];
                            $groupedServices1[$providerId]['provider']['number_of_rating'] = $row['number_of_rating'];
                            $groupedServices1[$providerId]['provider']['total_rating'] = $row['provider_total_rating'];
                            $groupedServices1[$providerId]['provider']['average_rating'] = $row['average_rating'];
                            $groupedServices1[$providerId]['provider']['number_of_orders'] = $row['number_of_orders'];
                            $groupedServices1[$providerId]['provider']['distance'] = $row['distance'];
                            $groupedServices1[$providerId]['provider']['discount_type'] = $row['discount_type'];
                            $groupedServices1[$providerId]['provider']['discount'] = $row['discount'];
                            $groupedServices1[$providerId]['provider']['upto'] = $row['minimum_order_amount'];
                            if (!empty($row['image'])) {
                                if (check_exists(base_url('public/backend/assets/profiles/' . $row['partner_image'])) || check_exists(base_url('/public/uploads/users/partners/' . $row['partner_image'])) || check_exists($row['partner_image'])) {
                                    if (filter_var($row['partner_image'], FILTER_VALIDATE_URL)) {
                                        $image = $row['partner_image'];
                                    } else {
                                        $image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $row['partner_image'])) ? base_url('public/backend/assets/profiles/' . $row['partner_image']) : ((file_exists(FCPATH . $row['partner_image'])) ? base_url($row['partner_image']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $row['partner_image'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $row['partner_image'])));
                                        $image = $image;
                                    }
                                }
                                $groupedServices1[$providerId]['provider']['image'] = $image;
                                $banner_image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $row['banner'])) ? base_url('public/backend/assets/profiles/' . $row['banner']) : ((file_exists(FCPATH . $row['banner'])) ? base_url($row['banner']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $row['banner'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $row['banner'])));
                                $groupedServices1[$providerId]['provider']['banner_image']  = $banner_image;
                                if ($row['discount_type'] == 'percentage') {
                                    $groupedServices1[$providerId]['provider']['upto'] =  $row['minimum_order_amount'];
                                    unset($groupedServices1[$providerId]['provider']['discount_type']);
                                }
                            }
                            unset($row['minimum_order_amount']);
                            $groupedServices1[$providerId]['provider']['services'] = [];
                        }
                        $price = $row['price'];
                        $discountedPrice = $row['discounted_price'];
                        // Calculating the percentage off
                        $percentageOff = (($price - $discountedPrice) / $price) * 100;
                        // Rounding the result to 0 decimal places
                        $percentageOff = round($percentageOff);
                        $row['discount'] = strval($percentageOff);
                        $groupedServices1[$providerId]['provider']['services'][] = $row;
                    }
                }
                if (!empty($groupedServices1)) {
                    $data['total'] = count($groupedServices);
                    $data['Services'] = array_values($groupedServices1);
                } else {
                    $data['total'] = 0;
                    $data['Services'] = [];
                }
            }
            $response = [
                'error' => false,
                "data" => $data
            ];
            return $this->response->setJSON($response);
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - search_services_providers()');
            return $this->response->setJSON($response);
        }
    }
    public function capturePayment()
    {
        try {
            $apiEndpoint = 'https://api-m.sandbox.paypal.com';
            $requestData = json_encode([
                "intent" => "CAPTURE",
                "purchase_units" => [],
                "application_context" => [
                    "return_url" => "https://example.com/return",
                    "cancel_url" => "https://example.com/cancel"
                ]
            ]);
            $options = [
                CURLOPT_URL            => $apiEndpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $requestData,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                ],
            ];
            $ch = curl_init();
            curl_setopt_array($ch, $options);
            $response = curl_exec($ch);
            curl_close($ch);
            echo $response;
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            return $this->response->setJSON($response);
        }
    }
    public function send_chat_message()
    {
        try {
            $attachments = isset($_FILES['attachment']) ? $_FILES['attachment'] : null;
            if (!$attachments) {
                $validation = \Config\Services::validation();
                $validation->setRules(
                    [
                        'message' => 'required',
                    ]
                );
                if (!$validation->withRequest($this->request)->run()) {
                    $errors = $validation->getErrors();
                    $response = [
                        'error' => true,
                        'message' => $errors,
                        'data' => [],
                    ];
                    return $this->response->setJSON($response);
                }
            }
            $message = $this->request->getPost('message') ?? "";
            $receiver_id = $this->request->getPost('receiver_id');
            if ($receiver_id == null) {
                $user_group = fetch_details('users_groups', ['group_id' => '1']);
                $receiver_id = end($user_group)['group_id'];
            }
            $sender_id =  $this->user_details['id'];
            $receiver_type =  $this->request->getPost('receiver_type');
            $booking_id =  $this->request->getPost('booking_id') ?? null;
            if (isset($booking_id)) {
                $e_id = add_enquiry_for_chat("customer", $sender_id, true, $booking_id);
            } else {
                if ($receiver_type == 1) {
                    $enquiry = fetch_details('enquiries', ['customer_id' => $sender_id, 'userType' => 2, 'booking_id' => NULL, 'provider_id' => $receiver_id]);
                    if (empty($enquiry[0])) {
                        $customer = fetch_details('users', ['id' => $sender_id], ['username'])[0];
                        $data['title'] =  $customer['username'] . '_query';
                        $data['status'] =  1;
                        $data['userType'] =  2;
                        $data['customer_id'] = $sender_id;
                        $data['provider_id'] = $receiver_id;
                        $data['date'] =  now();
                        $store = insert_details($data, 'enquiries');
                        $e_id = $store['id'];
                    } else {
                        $e_id = $enquiry[0]['id'];
                    }
                } else {
                    $enquiry = fetch_details('enquiries', ['customer_id' => $sender_id, 'userType' => 2, 'booking_id' => NULL, 'provider_id' => NULL]);
                    if (empty($enquiry[0])) {
                        $customer = fetch_details('users', ['id' => $sender_id], ['username'])[0];
                        $data['title'] =  $customer['username'] . '_query';
                        $data['status'] =  1;
                        $data['userType'] =  2;
                        $data['customer_id'] = $sender_id;
                        $data['provider_id'] = NULL;
                        $data['date'] =  now();
                        $store = insert_details($data, 'enquiries');
                        $e_id = $store['id'];
                    } else {
                        $e_id = $enquiry[0]['id'];
                    }
                }
            }
            $last_date = getLastMessageDateFromChat($e_id);
            $attachment_image = null;
            $is_file = false;
            if (!empty($_FILES['attachment']['name'])) {
                $attachment_image = $_FILES['attachment'];
                $is_file = true;
            }
            $data = insert_chat_message_for_chat($sender_id, $receiver_id, $message, $e_id, 2, $receiver_type, date('Y-m-d H:i:s'), $is_file, $attachment_image, $booking_id);
            if (isset($booking_id)) {
                $new_data = getSenderReceiverDataForChatNotification($sender_id, $receiver_id, $data['id'], $last_date, 'provider_booking');
                send_app_chat_notification($new_data['sender_details']['username'], $message, $receiver_id, '', 'new_chat', $new_data);
                send_panel_chat_notification('Check New Messages', $message, $receiver_id, '', 'new_chat', $new_data);
            } else if ($receiver_type == 1) {
                $new_data = getSenderReceiverDataForChatNotification($sender_id, $receiver_id, $data['id'], $last_date, 'provider');
                send_app_chat_notification('Provider Support', $message, $receiver_id, '', 'new_chat', $new_data);
                send_panel_chat_notification('Check New Messages', $message, $receiver_id, '', 'new_chat', $new_data);
            } else if ($receiver_type == 0) {
                $new_data = getSenderReceiverDataForChatNotification($sender_id, $receiver_id, $data['id'], $last_date, 'admin');
                send_panel_chat_notification('Check New Messages', $message, $receiver_id, '', 'new_chat', $new_data);
            }
            return response('Sent message successfully ', false, $new_data, 200);
        } catch (\Throwable $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - send_chat_message()');
            return $this->response->setJSON($response);
        }
    }
    public function get_chat_history()
    {
        try {
            $validation = service('validation');
            $validation->setRules([
                'type' => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $type = $this->request->getPost('type');
            $e_id = $this->request->getPost('e_id');
            $limit = $this->request->getPost('limit') ?? '5';
            $offset = $this->request->getPost('offset') ?? '0';
            $current_user_id = $this->user_details['id'];
            $db = \Config\Database::connect();
            if ($type == "0") {
                $e_id_data = fetch_details('enquiries', ['customer_id' => $current_user_id, 'userType' => 2, 'provider_id' => null, 'booking_id' => null]);
                if (!empty($e_id_data)) {
                    $e_id = $e_id_data[0]['id'];
                    $countBuilder = $db->table('chats c');
                    $countBuilder->select('COUNT(*) as total')
                        ->where('c.booking_id', null)
                        ->where('c.e_id', $e_id);
                    $totalRecords = $countBuilder->get()->getRow()->total;
                    $mainBuilder = $db->table('chats c');
                    $mainBuilder->select('c.*')
                        ->where('c.e_id', $e_id)
                        ->where('c.booking_id', null)
                        ->limit($limit, $offset);
                    $chat_record = $mainBuilder->orderBy('c.created_at', 'DESC')->get()->getResultArray();
                    foreach ($chat_record as $key => $row) {
                        if (!empty($chat_record[$key]['file'])) {
                            $chat_record[$key]['file'] = array_map(function ($data) {
                                return [
                                    'file' => base_url('public/uploads/chat_attachment/' . $data['file']),
                                    'file_type' => $data['file_type'],
                                    'file_name' => $data['file_name'],
                                    'file_size' => $data['file_size'],
                                ];
                            }, json_decode($chat_record[$key]['file'], true));
                        } else {
                            $chat_record[$key]['file'] = is_array($chat_record[$key]['file']) ? [] : "";
                        }
                    }
                    return response('Retrived successfully ', false, $chat_record, 200, ['total' => $totalRecords]);
                } else {
                    return response('No data Found ', false, [], 200, ['total' => 0]);
                }
            } else if ($type = "1") {
                $booking_id = $this->request->getPost('booking_id');
                if ($booking_id == null) {
                    $enquiry = fetch_details('enquiries', ['customer_id' => $current_user_id, 'userType' => 2, 'booking_id' => NULL, 'provider_id' => $this->request->getPost('provider_id')]);
                } else {
                    $enquiry = fetch_details('enquiries', ['customer_id' => $current_user_id, 'userType' => 2, 'booking_id' => $booking_id]);
                }
                if (!empty($enquiry)) {
                    if ($enquiry[0]['booking_id'] != null) {
                        $e_id = $enquiry[0]['id'];
                        $booking_id = $enquiry[0]['booking_id'];
                        $countBuilder = $db->table('chats c');
                        $countBuilder->select('COUNT(*) as total')
                            ->where('c.e_id', $e_id)
                            ->where('c.booking_id', $booking_id);
                        $totalRecords = $countBuilder->get()->getRow()->total;
                        $mainBuilder = $db->table('chats c');
                        $mainBuilder->select('c.*')
                            ->where('c.e_id', $e_id)
                            ->where('c.booking_id', $booking_id)
                            ->limit($limit, $offset);
                        $chat_record = $mainBuilder->orderBy('c.created_at', 'DESC')->get()->getResultArray();
                        foreach ($chat_record as $key => $row) {
                            $new_data = getSenderReceiverDataForChatNotification($row['sender_id'], $row['receiver_id'], $row['id'], $row['created_at'], 'provider_booking', 'yes');
                            $chat_record[$key]['sender_details'] = $new_data['sender_details'];
                            $chat_record[$key]['receiver_details'] = $new_data['receiver_details'];
                            if (!empty($chat_record[$key]['file'])) {
                                $chat_record[$key]['file'] = array_map(function ($data) {
                                    return [
                                        'file' => base_url('public/uploads/chat_attachment/' . $data['file']),
                                        'file_type' => $data['file_type'],
                                        'file_name' => $data['file_name'],
                                        'file_size' => $data['file_size'],
                                    ];
                                }, json_decode($chat_record[$key]['file'], true));
                            } else {
                                $chat_record[$key]['file'] = is_array($chat_record[$key]['file']) ? [] : "";
                            }
                        }
                        return response('Retrived successfully ', false, $chat_record, 200, ['total' => $totalRecords]);
                    } else {
                        $e_id = $enquiry[0]['id'];
                        $countBuilder = $db->table('chats c');
                        $countBuilder->select('COUNT(*) as total')
                            ->where('c.e_id', $e_id);
                        $totalRecords = $countBuilder->get()->getRow()->total;
                        $mainBuilder = $db->table('chats c');
                        $mainBuilder->select('c.*')
                            ->where('c.e_id', $e_id)
                            ->limit($limit, $offset);
                        $chat_record = $mainBuilder->orderBy('c.created_at', 'DESC')->get()->getResultArray();
                        foreach ($chat_record as $key => $row) {
                            $new_data = getSenderReceiverDataForChatNotification($row['sender_id'], $row['receiver_id'], $row['id'], $row['created_at'], 'provider_booking', 'yes');
                            $chat_record[$key]['sender_details'] = $new_data['sender_details'];
                            $chat_record[$key]['receiver_details'] = $new_data['receiver_details'];
                            if (!empty($chat_record[$key]['file'])) {
                                $chat_record[$key]['file'] = array_map(function ($data) {
                                    return [
                                        'file' => base_url('public/uploads/chat_attachment/' . $data['file']),
                                        'file_type' => $data['file_type'],
                                        'file_name' => $data['file_name'],
                                        'file_size' => $data['file_size'],
                                    ];
                                }, json_decode($chat_record[$key]['file'], true));
                            } else {
                                $chat_record[$key]['file'] = is_array($chat_record[$key]['file']) ? [] : "";
                            }
                        }
                        return response('Retrived successfully ', false, $chat_record, 200, ['total' => $totalRecords]);
                    }
                } else {
                    return response('No data found ', false, [], 200, ['total' => 0]);
                }
            }
        } catch (\Throwable $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_chat_history()');
            return $this->response->setJSON($response);
        }
    }
    public function get_chat_providers_list()
    {
        try {
            $limit = $this->request->getPost('limit') ?? '5';
            $offset = $this->request->getPost('offset') ?? '0';
            $db = \Config\Database::connect();
            $builder = $db->table('users u');
            $builder->select('u.id, u.username as customer_name, MAX(c.created_at) AS last_chat_date, c.booking_id, o.id as order_id, o.status as order_status, pd.partner_id as partner_id, pd.company_name as partner_name, ps.image')
                ->join('chats c', "(c.sender_id = u.id AND c.sender_type = 2) OR (c.receiver_id = u.id AND c.receiver_type = 2)")
                ->join('orders o', "o.id = c.booking_id")
                ->join('partner_details pd', "pd.partner_id = o.partner_id")
                ->join('users ps', "ps.id = pd.partner_id")
                ->where('o.user_id', $this->user_details['id'])
                ->groupBy('c.booking_id')
                ->orderBy('last_chat_date', 'DESC')
                ->limit($limit, $offset);
            $totalCustomersQuery1 = $builder->countAllResults(false);
            $customers_with_chats = $builder->get()->getResultArray();
            foreach ($customers_with_chats as $key => $row) {
                if (isset($row['image'])) {
                    $imagePath = $row['image'];
                    $customers_with_chats[$key]['image'] = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $imagePath)) ? base_url('public/backend/assets/profiles/' . $imagePath) : ((file_exists(FCPATH . $imagePath)) ? base_url($imagePath) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $imagePath)) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $imagePath)));
                }
            }
            // $builder1 = $db->table('users u');
            // $builder1->select('u.id, u.username as customer_name, MAX(c.created_at) AS last_chat_date, c.booking_id, pd.partner_id as partner_id, pd.company_name as partner_name, ps.image')
            //     ->join('chats c', "(c.sender_id = u.id AND c.sender_type = 2) OR (c.receiver_id = u.id AND c.receiver_type = 2)")
            //     ->join('enquiries e', "e.id = c.e_id")
            //     ->join('partner_details pd', "pd.partner_id = e.provider_id")
            //     ->join('users ps', "ps.id = pd.partner_id")
            //     ->where('e.customer_id', $this->user_details['id'])
            //     ->groupBy('e.provider_id')
            //     ->orderBy('last_chat_date', 'DESC')
            //     ->limit($limit, $offset);
            // $totalCustomersQuery2 = $builder1->countAllResults(false);
            // $customer_pre_booking_queries = $builder1->get()->getResultArray();
            $db = \Config\Database::connect();
            // Subquery
            $subquery = $db->table('users u')
                ->select('u.id, u.username as customer_name, MAX(c.created_at) AS last_chat_date, c.booking_id, pd.partner_id as partner_id, pd.company_name as partner_name, ps.image')
                ->join('chats c', "(c.sender_id = u.id AND c.sender_type = 2) OR (c.receiver_id = u.id AND c.receiver_type = 2)")
                ->join('enquiries e', "e.id = c.e_id")
                ->join('partner_details pd', "pd.partner_id = e.provider_id")
                ->join('users ps', "ps.id = pd.partner_id")
                ->where('e.customer_id', $this->user_details['id'])
                ->groupBy('e.provider_id')
                ->orderBy('last_chat_date', 'DESC');
            // Convert subquery to SQL string
            $subquerySql = $subquery->getCompiledSelect(false);
            // Main query using string-based subquery
            $builder1 = $db->table("($subquerySql) as subquery");
            $builder1->limit($limit, $offset);
            $totalCustomersQuery2 = $builder1->countAllResults(false);
            $customer_pre_booking_queries = $builder1->get()->getResultArray();
            foreach ($customer_pre_booking_queries as $key => $row) {
                if (isset($row['image'])) {
                    $imagePath = $row['image'];
                    $customer_pre_booking_queries[$key]['order_id'] = "";
                    $customer_pre_booking_queries[$key]['order_status'] = "";
                    $customer_pre_booking_queries[$key]['image'] = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $imagePath)) ? base_url('public/backend/assets/profiles/' . $imagePath) : ((file_exists(FCPATH . $imagePath)) ? base_url($imagePath) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $imagePath)) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $imagePath)));
                }
            }
            $merged_array = array_merge($customers_with_chats, $customer_pre_booking_queries);
            $totalRecords = $totalCustomersQuery1 + $totalCustomersQuery2;
            if (empty($customers_with_chats)) {
                $merged_array = $merged_array;
            } else {
                $merged_array = array_slice($merged_array, $offset, $limit);
            }
            usort($merged_array, function ($a, $b) {
                return ($b['last_chat_date'] <=> $a['last_chat_date']);
            });
            return response('Retrived successfully ', false, $merged_array, 200, ['total' => $totalRecords]);
        } catch (\Throwable $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_chat_providers_list()');
            return $this->response->setJSON($response);
        }
    }
    public function get_user_info()
    {
        try {
            $db = \Config\Database::connect();
            $builder = $db->table('users u');
            $builder->select('u.*,ug.group_id')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->where('ug.group_id', 2)
                ->where(['u.id' =>  $this->user_details['id']]);
            $data = $builder->get()->getResultArray()[0];
            $data['image'] = (isset($data['image']) && !empty($data['image'])) ? base_url('public/backend/assets/profiles/' . $data['image']) : "";
            $data = remove_null_values($data);
            $response = [
                'error' => false,
                'message' => 'User fetched successfully',
                'data' => $data,
            ];
            return $this->response->setJSON($response);
        } catch (\Throwable $th) {
            $response['error'] = true;
            $response['message'] = 'Something went wrong';
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_user_info()');
            return $this->response->setJSON($response);
        }
    }
    public function verify_otp()
    {
        $validation = service('validation');
        $validation->setRules([
            'otp' => 'required',
            'phone' => 'required'
        ]);
        if (!$validation->withRequest($this->request)->run()) {
            $errors = $validation->getErrors();
            $response = [
                'error' => true,
                'message' => $errors,
                'data' => [],
            ];
            return $this->response->setJSON($response);
        }
        $mobile = $this->request->getPost('phone');
        $country_code = $this->request->getPost('country_code');
        $otp = $this->request->getPost('otp');
        $data = fetch_details('otps', ['mobile' => $country_code . $mobile, 'otp' => $otp]);
        if (!empty($data)) {
            $time = $data[0]['created_at'];
            $time_expire = checkOTPExpiration($time);
            if ($time_expire['error'] == 1) {
                $response['error'] = true;
                $response['message'] = $time_expire['message'];
                return $this->response->setJSON($response);
            }
        }
        if (!empty($data)) {
            $response['error'] = false;
            $response['message'] = "OTP verified";
            return $this->response->setJSON($response);
        } else {
            $response['error'] = true;
            $response['message'] = "OTP not verified";
            return $this->response->setJSON($response);
        }
    }
    public function paystack_transaction_webview()
    {
        header("Content-Type: text/html");
        $validation = \Config\Services::validation();
        $validation->setRules(
            [
                'user_id' => 'required|numeric',
                'order_id' => 'required',
                'amount' => 'required',
            ]
        );
        if (!$validation->withRequest($this->request)->run()) {
            $errors = $validation->getErrors();
            $response = [
                'error' => true,
                'message' => $errors,
                'data' => [],
            ];
            return $this->response->setJSON($response);
        }
        $user_id = $_GET['user_id'];
        $order_id = $_GET['order_id'];
        $amount = intval($_GET['amount']);
        $user_data = fetch_details('users', ['id' => $user_id])[0];
        $paystack = new Paystack();
        $paystack_credentials = $paystack->get_credentials();
        $secret_key = $paystack_credentials['secret'];
        $url = "https://api.paystack.co/transaction/initialize";
        $encryption = order_encrypt($user_id, $amount, $order_id);
        $fields = [
            'email' => $user_data['email'],
            'amount' => $amount,
            'currency' => $paystack_credentials['currency'],
            'callback_url' => base_url() . '/api/v1/app_paystack_payment_status?payment_status=Completed',
            'metadata' => [
                'cancel_action' => base_url() . '/api/v1/app_paystack_payment_status?order_id=' . $encryption . '&payment_status=Failed',
                'order_id' => $order_id,
            ]
        ];
        if (isset($_GET['additional_charges_transaction_id'])) {
            $transaction_id = $_GET['additional_charges_transaction_id'];
            $fields['metadata']['additional_charges_transaction_id'] = $transaction_id;
        }
        $fields_string = http_build_query($fields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . $secret_key,
            "Cache-Control: no-cache",
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $result_data = json_decode($result, true);
        if (isset($result_data['data']['authorization_url'])) {
            header('Location: ' . $result_data['data']['authorization_url']);
            exit;
        } else {
            $response = [
                'error' => true,
                'message' => 'Failed to initialize transaction',
                'data' => $result_data,
            ];
            return $this->response->setJSON($response);
        }
    }
    public function app_paystack_payment_status()
    {
        $data = $_GET;
        if (isset($data['reference']) && isset($data['trxref']) && isset($data['payment_status'])) {
            $response['error'] = false;
            $response['message'] = "Payment Completed Successfully";
            $response['payment_status'] = "Completed";
            $response['data'] = $data;
        } elseif (isset($data['order_id']) && isset($data['payment_status'])) {
            $order_id = order_decrypt($_GET['order_id']);
            update_details(['payment_status' => 2], ['id' => $order_id[2]], 'orders');
            update_details(['status' => 'cancelled'], ['id' => $order_id[2]], 'orders');
            $data = [
                'transaction_type' => 'transaction',
                'user_id' => $order_id[0],
                'partner_id' => "",
                'order_id' => $order_id[2],
                'type' => 'paystack',
                'txn_id' => "",
                'amount' => $order_id[1],
                'status' => 'failed',
                'currency_code' => "",
                'message' => 'Order is cancelled',
            ];
            $insert_id = add_transaction($data);
            $response['error'] = true;
            $response['message'] = "Payment Cancelled / Declined ";
            $response['payment_status'] = "Failed";
            $response['data'] = $_GET;
        }
        print_r(json_encode($response));
    }
    public function flutterwave_webview()
    {
        try {
            header("Content-Type: application/json");
            $validation = \Config\Services::validation();
            $validation->setRules([
                'user_id' => 'required|numeric',
                'order_id' => 'required',
                'amount' => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $settings = get_settings('general_settings', true);
            $logo = base_url("public/uploads/site/" . $settings['logo']);
            $user_id = $this->request->getVar('user_id');
            $user = fetch_details('users', ['id' => $user_id]);
            if (empty($user)) {
                $response = [
                    'error' => true,
                    'message' => "User not found!",
                ];
                return $this->response->setJSON($response);
            }
            $flutterwave = new Flutterwave();
            $flutterwave_credentials = $flutterwave->get_credentials();
            $payment_gateways_settings = get_settings('payment_gateways_settings', true);
            if ($payment_gateways_settings['flutterwave_website_url'] != "") {
                $return_url = $payment_gateways_settings['flutterwave_website_url'] . "/payment-status?order_id=" . $this->request->getVar('order_id');
            } else {
                $return_url = base_url('api/v1/flutterwave_payment_status');
            }
            $currency = $flutterwave_credentials['currency_code'] ?? "NGN";
            $meta_data = [
                'user_id' => $user_id,
                'order_id' => $this->request->getVar('order_id'),
            ];
            if (isset($_GET['additional_charges_transaction_id'])) {
                $transaction_id = $_GET['additional_charges_transaction_id'];
                $meta_data['additional_charges_transaction_id'] = $transaction_id;
            }
            $data = [
                'tx_ref' => "eDemand-" . time() . "-" . rand(1000, 9999),
                'amount' => $this->request->getVar('amount'),
                'currency' => $currency,
                'redirect_url' => $return_url,
                'payment_options' => 'card',
                'meta' => $meta_data,
                'customer' => [
                    'email' => (!empty($user[0]['email'])) ? $user[0]['email'] : $settings['support_email'],
                    'phonenumber' => $user[0]['phone'] ?? '',
                    'name' => $user[0]['username'] ?? '',
                ],
                'customizations' => [
                    'title' => $settings['company_title'] . " Payments",
                    'description' => "Online payments on " . $settings['company_title'],
                    'logo' => (!empty($logo)) ? $logo : "",
                ],
            ];
            $payment = $flutterwave->create_payment($data);
            if (!empty($payment)) {
                $payment = json_decode($payment, true);
                if (isset($payment['status']) && $payment['status'] == 'success' && isset($payment['data']['link'])) {
                    $response = [
                        'error' => false,
                        'message' => "Payment link generated. Follow the link to make the payment!",
                        'link' => $payment['data']['link'],
                    ];
                    header('Location: ' . $payment['data']['link']);
                    exit;
                } else {
                    $response = [
                        'error' => true,
                        'message' => "Could not initiate payment. " . $payment['message'],
                        'link' => "",
                    ];
                }
            } else {
                $response = [
                    'error' => true,
                    'message' => "Could not initiate payment. Try again later!",
                    'link' => "",
                ];
            }
            print_r(json_encode($response));
        } catch (\Throwable $th) {
            // Log the error
            log_message('error', 'Error in Flutterwave Webview: ' . $th->getMessage() . "\n" . $th->getTraceAsString());
            // Optionally, display the error message for debugging
            $response = [
                'error' => true,
                'message' => 'An error occurred. Please try again later.',
            ];
            // If you're in development mode, show the exact error message
            if (ENVIRONMENT === 'development') {
                $response['error_message'] = $th->getMessage();
                $response['error_trace'] = $th->getTraceAsString();
            }
            return $this->response->setJSON($response);
        }
    }
    public function flutterwave_payment_status()
    {
        if (isset($_GET['transaction_id']) && !empty($_GET['transaction_id'])) {
            $transaction_id = $_GET['transaction_id'];
            $flutterwave = new Flutterwave();
            $transaction = $flutterwave->verify_transaction($transaction_id);
            if (!empty($transaction)) {
                $transaction = json_decode($transaction, true);
                if ($transaction['status'] == 'error') {
                    $response['error'] = true;
                    $response['message'] = $transaction['message'];
                    $response['amount'] = 0;
                    $response['status'] = "failed";
                    $response['currency'] = "NGN";
                    $response['transaction_id'] = $transaction_id;
                    $response['reference'] = "";
                    print_r(json_encode($response));
                    return false;
                }
                if ($transaction['status'] == 'success' && $transaction['data']['status'] == 'successful') {
                    $response['error'] = false;
                    $response['message'] = "Payment has been completed successfully";
                    $response['amount'] = $transaction['data']['amount'];
                    $response['currency'] = $transaction['data']['currency'];
                    $response['status'] = $transaction['data']['status'];
                    $response['transaction_id'] = $transaction['data']['id'];
                    $response['reference'] = $transaction['data']['tx_ref'];
                    print_r(json_encode($response));
                    return false;
                } else if ($transaction['status'] == 'success' && $transaction['data']['status'] != 'successful') {
                    $response['error'] = true;
                    $response['message'] = "Payment is " . $transaction['data']['status'];
                    $response['amount'] = $transaction['data']['amount'];
                    $response['currency'] = $transaction['data']['currency'];
                    $response['status'] = $transaction['data']['status'];
                    $response['transaction_id'] = $transaction['data']['id'];
                    $response['reference'] = $transaction['data']['tx_ref'];
                    update_details(['payment_status' => 2, 'status' => 'cancelled'], ['id' => $transaction['meta']['order_id']], 'orders');
                    $data = [
                        'transaction_type' => 'transaction',
                        'user_id' =>  $transaction['meta']['order_id'],
                        'partner_id' => "",
                        'order_id' =>  $transaction['meta']['order_id'],
                        'type' => 'flutterwave',
                        'txn_id' => "",
                        'amount' => $transaction['data']['amount'],
                        'status' => 'failed',
                        'currency_code' => "",
                        'message' => 'Order is cancelled',
                    ];
                    $insert_id = add_transaction($data);
                    print_r(json_encode($response));
                    return false;
                }
            } else {
                $response['error'] = true;
                $response['message'] = "Transaction not found";
                print_r(json_encode($response));
            }
        } else {
            $response['error'] = true;
            $response['message'] = "Invalid request!";
            print_r(json_encode($response));
            return false;
        }
    }
    public function resend_otp()
    {
        $validation = \Config\Services::validation();
        $validation->setRules([
            'mobile' => 'required',
        ]);
        if (!$validation->withRequest($this->request)->run()) {
            $errors = $validation->getErrors();
            $response = [
                'error' => true,
                'message' => $errors,
                'data' => [],
            ];
            return $this->response->setJSON($response);
        }
        $request = \Config\Services::request();
        $mobile = $request->getPost('mobile');
        $authentication_mode = get_settings('general_settings', true);
        if ($authentication_mode['authentication_mode'] == "sms_gateway") {
            $otps = fetch_details('otps', ['mobile' => $mobile]);
            if (isset($mobile) &&  empty($otps)) {
                $mobile_data = array(
                    'mobile' => $mobile,
                    'created_at' => date('Y-m-d H:i:s'),
                );
                insert_details($mobile_data, 'otps');
            }
            $otp = random_int(100000, 999999);
            $response['error'] = false;
            $send_otp_response = set_user_otp($mobile, $otp, $mobile);
            if ($send_otp_response['error'] == false) {
                $response['message'] = "OTP send successfully";
            } else {
                $response['error'] = true;
                $response['message'] = $send_otp_response['message'];
            }
            $response['authentication_mode'] = $authentication_mode['authentication_mode'];
            return $this->response->setJSON($response);
        }
    }
    public function get_web_landing_page_settings1()
    {
        $web_settings = get_settings('web_settings', true);
        $categories_ids = $web_settings['category_ids'] ?? [];
        $rating_ids = $web_settings['rating_ids'] ?? [];
        $categories_data = fetch_details('categories', [], [], "", '', '', '', 'id', $categories_ids);
        $categories = array_map(function ($row) {
            if (check_exists(base_url('/public/uploads/categories/' . $row['image']))) {
                $category_image = base_url('/public/uploads/categories/' . $row['image']);
            } else {
                $category_image = '';
            }
            $tempRow = $row;
            $tempRow['image'] = $category_image;
            return $tempRow;
        }, $categories_data);
        $db = \Config\Database::connect();
        $builder = $db->table('services_ratings sr')
            ->select('COUNT(sr.id) as total')
            ->join('users u', 'u.id = sr.user_id')
            ->join('services s', 's.id = sr.service_id');
        $ratings_total_count = $builder->get()->getRowArray();
        $total = $ratings_total_count['total'] ?? 0;
        $builder = $db->table('services_ratings sr')
            ->select('sr.id, sr.rating, sr.comment, sr.created_at as rated_on, sr.images,
                u.image as profile_image, u.username as user_name, 
                s.title as service_name, s.user_id as partner_id')
            ->join('users u', 'u.id = sr.user_id')
            ->join('services s', 's.id = sr.service_id')
            ->whereIn('sr.id', $rating_ids)
            ->orderBy('sr.id', 'DESC');
        $rating_records = $builder->get()->getResultArray();
        $ratings = array_map(function ($row) {
            $tempRow = [
                'id' => $row['id'],
                'comment' => $row['comment'],
                'user_name' => $row['user_name'],
                'service_name' => $row['service_name'],
                'rated_on' => $row['rated_on'],
                'stars' => '<i class="fa-solid fa-star text-warning"></i> ' . $row['rating'],
                'partner_name' => fetch_details('users', ['id' => $row['partner_id']], ['username'])[0]['username'] ?? 'N/A',
            ];
            // Handle profile image
            if (!empty($row['profile_image'])) {
                $imagePath = 'public/backend/assets/profiles/' . $row['profile_image'];
                $publicPath = base_url($imagePath);
                // Check if the image exists in any of the possible locations
                if (check_exists($publicPath) || check_exists(base_url('/public/uploads/users/partners/' . $row['profile_image'])) || check_exists($imagePath)) {
                    $tempRow['profile_image'] = filter_var($row['profile_image'], FILTER_VALIDATE_URL)
                        ? base_url($row['profile_image'])
                        : base_url($imagePath);
                } else {
                    $tempRow['profile_image'] = base_url("public/backend/assets/profiles/default.png");
                }
            } else {
                $tempRow['profile_image'] = base_url("public/backend/assets/profiles/default.png");
            }
            if ($row['images'] != "") {
                $images =  rating_images($row['id'], false);
                $tempRow['images'] = $images;
            } else {
                $tempRow['images'] = array();
            }
            return $tempRow;
        }, $rating_records);
        $web_settings['categories'] = $categories;
        $web_settings['ratings'] = $ratings;
        $web_settings['web_logo'] =  isset($web_settings['web_logo']) ? base_url("public/uploads/web_settings/" . ($web_settings['web_logo'])) : "";
        $web_settings['web_favicon'] =  isset($web_settings['web_favicon']) ? base_url("public/uploads/web_settings/" . ($web_settings['web_favicon'])) : "";
        $web_settings['footer_logo'] =  isset($web_settings['footer_logo']) ? base_url("public/uploads/web_settings/" . ($web_settings['footer_logo'])) : "";
        $web_settings['landing_page_logo'] =  isset($web_settings['landing_page_logo']) ? base_url("public/uploads/web_settings/" . ($web_settings['landing_page_logo'])) : "";
        $web_settings['landing_page_backgroud_image'] =  isset($web_settings['landing_page_backgroud_image']) ? base_url("public/uploads/web_settings/" . ($web_settings['landing_page_backgroud_image'])) : "";
        $web_settings['web_half_logo'] =  isset($web_settings['web_half_logo']) ? base_url("public/uploads/web_settings/" . ($web_settings['web_half_logo'])) : "";
        $web_settings['step_1_image'] =  isset($web_settings['step_1_image']) ? base_url("public/uploads/web_settings/" . ($web_settings['step_1_image'])) : "";
        $web_settings['step_2_image'] =  isset($web_settings['step_2_image']) ? base_url("public/uploads/web_settings/" . ($web_settings['step_2_image'])) : "";
        $web_settings['step_3_image'] =  isset($web_settings['step_3_image']) ? base_url("public/uploads/web_settings/" . ($web_settings['step_3_image'])) : "";
        $web_settings['step_4_image'] =  isset($web_settings['step_4_image']) ? base_url("public/uploads/web_settings/" . ($web_settings['step_4_image'])) : "";
        $response = [
            'error' => empty($web_settings),
            'message' => empty($web_settings) ? "No data found in setting" : "Settings received successfully",
            'data' => $web_settings,
        ];
        return $this->response->setJSON($response);
    }
    public function get_web_landing_page_settings()
    {
        $web_settings = get_settings('web_settings', true);
        // Fetch Categories
        $categories_ids = $web_settings['category_ids'] ?? [];
        $categories = [];
        if (!empty($categories_ids)) {
            $categories_data = fetch_details('categories', [], [], '', '', '', '', 'id', $categories_ids);
            $categories = array_map(function ($row) {
                $row['image'] = check_exists(base_url('/public/uploads/categories/' . $row['image']))
                    ? base_url('/public/uploads/categories/' . $row['image'])
                    : '';
                return $row;
            }, $categories_data);
        }
        if (isset($web_settings['rating_ids']) && is_string($web_settings['rating_ids']) && !empty($web_settings['rating_ids'])) {
            $rating_ids = explode(',', $web_settings['rating_ids']);
        } else {
            $rating_ids = [];
        }
        $db = \Config\Database::connect();
        $ratings_total_count = $db->table('services_ratings')
            ->select('COUNT(id) as total')
            ->get()
            ->getRowArray();
        $total = $ratings_total_count['total'] ?? 0;
        $ratings = [];
        if (!empty($rating_ids)) {
            $rating_records = $db->table('services_ratings sr')
                ->select('sr.id, sr.rating, sr.comment, sr.created_at as rated_on, sr.images, 
                      u.image as profile_image, u.username as user_name, 
                      s.title as service_name, s.user_id as partner_id')
                ->join('users u', 'u.id = sr.user_id')
                ->join('services s', 's.id = sr.service_id')
                ->whereIn('sr.id', $rating_ids)
                ->orderBy('sr.id', 'DESC')
                ->get()
                ->getResultArray();
            $ratings = array_map(function ($row) {
                $profile_image_path = $this->getProfileImagePath($row['profile_image']);
                if ($row['images'] != "") {
                    $images =  rating_images($row['id'], true);
                } else {
                    $images = array();
                }
                return [
                    'id' => $row['id'],
                    'comment' => $row['comment'],
                    'user_name' => $row['user_name'],
                    'service_name' => $row['service_name'],
                    'rated_on' => $row['rated_on'],
                    'partner_name' => $this->getPartnerName($row['partner_id']),
                    'profile_image' => $profile_image_path,
                    'images' => $images,
                ];
            }, $rating_records);
        }
        $web_settings['categories'] = $categories;
        $web_settings['ratings'] = $ratings;
        $image_keys = [
            'web_logo',
            'web_favicon',
            'footer_logo',
            'landing_page_logo',
            'landing_page_backgroud_image',
            'web_half_logo',
            'step_1_image',
            'step_2_image',
            'step_3_image',
            'step_4_image'
        ];
        foreach ($image_keys as $key) {
            $web_settings[$key] = isset($web_settings[$key])
                ? base_url("public/uploads/web_settings/" . $web_settings[$key])
                : "";
        }
        $title_keys = [
            'step_1_title',
            'step_2_title',
            'step_3_title',
            'step_4_title'
        ];
        $description_keys = [
            'step_1_description',
            'step_2_description',
            'step_3_description',
            'step_4_description'
        ];
        $process_flow_images_keys = [
            'step_1_image',
            'step_2_image',
            'step_3_image',
            'step_4_image'
        ];
        $web_settings['process_flow_data'] = [];
        $num_steps = count($title_keys);
        for ($i = 0; $i < $num_steps; $i++) {
            $title_key = $title_keys[$i];
            $description_key = $description_keys[$i];
            $image_key = $process_flow_images_keys[$i];
            $web_settings['process_flow_data'][] = [
                'id' => $i + 1,
                'title' => $web_settings[$title_key],
                'description' => $web_settings[$description_key],
                'image' => $web_settings[$image_key],
            ];
            unset($web_settings[$title_key], $web_settings[$description_key], $web_settings[$image_key]);
        }
        $response = [
            'error' => empty($web_settings),
            'message' => empty($web_settings) ? "No data found in setting" : "Settings received successfully",
            'data' => $web_settings,
        ];
        return $this->response->setJSON($response);
    }
    private function getProfileImagePath($profile_image)
    {
        $default_image = base_url("public/backend/assets/profiles/default.png");
        if (empty($profile_image)) return $default_image;
        $image_paths = [
            base_url("public/backend/assets/profiles/" . $profile_image),
            base_url('/public/uploads/users/partners/' . $profile_image),
            "public/backend/assets/profiles/" . $profile_image
        ];
        foreach ($image_paths as $path) {
            if (check_exists($path)) {
                return filter_var($profile_image, FILTER_VALIDATE_URL) ? base_url($profile_image) : $path;
            }
        }
        return $default_image;
    }
    private function getPartnerName($partner_id)
    {
        return fetch_details('users', ['id' => $partner_id], ['username'])[0]['username'] ?? 'N/A';
    }
    public function make_custom_job_request()
    {
        log_the_responce(
            $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => ",
            date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - make_custom_job_request()'
        );
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'category_id'               => 'required',
                'service_title'             => 'required',
                'service_short_description' => 'required',
                'min_price'                 => 'required',
                'max_price'                 => 'required',
                'requested_start_date'      => 'required|valid_date[Y-m-d]',
                'requested_start_time'      => 'required',
                'requested_end_date'        => 'required|valid_date[Y-m-d]',
                'requested_end_time'        => 'required',
                'latitude'        => 'required',
                'longitude'        => 'required',


            ]);
            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => $validation->getErrors(),
                    'data'    => [],
                ]);
            }
            $today = date('Y-m-d');
            $startDate = $this->request->getVar('requested_start_date');
            $endDate = $this->request->getVar('requested_end_date');
            if ($startDate < $today) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => "Please select an upcoming start date!",
                ]);
            }
            if ($endDate < $today) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => "Please select an upcoming end date!",
                ]);
            }
            $user_id = $this->user_details['id'];
            $data = [
                'user_id'                   => $user_id,
                'category_id'               => $this->request->getVar('category_id'),
                'service_title'             => $this->request->getVar('service_title'),
                'service_short_description' => $this->request->getVar('service_short_description'),
                'min_price'                 => $this->request->getVar('min_price'),
                'max_price'                 => $this->request->getVar('max_price'),
                'requested_start_date'      => $startDate,
                'requested_start_time'      => $this->request->getVar('requested_start_time'),
                'requested_end_date'        => $endDate,
                'requested_end_time'        => $this->request->getVar('requested_end_time'),
                'status'                    => 'pending'
            ];
            $insert = insert_details($data, 'custom_job_requests');
            if ($insert) {
                send_notification_to_related_providers($this->request->getVar('category_id'), $insert, $this->request->getVar('latitude'), $this->request->getVar('longitude'));
            }
            $response = $insert ?
                ['error' => false, 'message' => "Request successful!"] :
                ['error' => true, 'message' => "Request failed!"];
            return $this->response->setJSON($response);
        } catch (\Throwable $th) {
            throw $th;
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - make_custom_job_request()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => 'Something went wrong',
            ]);
        }
    }
    public function fetch_my_custom_job_requests()
    {
        try {
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = !empty($this->request->getPost('offset')) ? $this->request->getPost('offset') : 0;
            $sort = !empty($this->request->getPost('sort')) ? $this->request->getPost('sort') : 'id';
            $order = !empty($this->request->getPost('order')) ? $this->request->getPost('order') : 'DESC';
            $db = \Config\Database::connect();
            $builder = $db->table('custom_job_requests cj');
            $total = $builder->select('COUNT(id) as total')->get()->getRowArray()['total'];
            $builder->select('cj.*, c.name as category_name, c.parent_id as category_parent_id,c.image as category_image');
            $data = $builder
                ->join('categories c', 'c.id = cj.category_id', 'left')
                ->orderBy($sort, $order)
                ->limit($limit, $offset)
                ->where('cj.user_id', $this->user_details['id'])
                ->get()
                ->getResultArray();
            foreach ($data as $index => $row) {
                if (check_exists(base_url('/public/uploads/categories/' . $row['category_image']))) {
                    $category_image = base_url('/public/uploads/categories/' . $row['category_image']);
                } else {
                    $category_image = '';
                }
                $data[$index]['total_bids'] = 0;  // Default value
                $data[$index]['bidders'] = [];    // Default empty array for bidders
                $data[$index]['category_image'] = $category_image;
                // $row['category_image']= $category_image ;
                $biddersBuilder = $db->table('partner_bids pb')
                    ->select('pd.banner as provider_image')
                    ->join('partner_details pd', 'pd.partner_id = pb.partner_id', 'left')
                    ->where('pb.custom_job_request_id', $row['id'])
                    ->get()
                    ->getResultArray();
                foreach ($biddersBuilder as $index1 => $row) {
                    $biddersBuilder[$index1]['provider_image'] = (file_exists($row['provider_image'])) ? base_url($row['provider_image']) : base_url('public/backend/assets/profiles/default.png');
                }
                $data[$index]['total_bids'] = count($biddersBuilder);
                $data[$index]['bidders'] = $biddersBuilder;
            }
            if (!empty($data)) {
                return response('My Custom Jobs fetched successfully', false, $data, 200, ['total' => $total]);
            } else {
                return response('categories not found', false);
            }
        } catch (\Throwable $th) {
            throw $th;
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - fetch_my_custom_job_requests()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => 'Something went wrong',
            ]);
        }
    }
    public function fetch_custom_job_bidders()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'custom_job_request_id' => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => $validation->getErrors(),
                    'data'    => [],
                ]);
            }
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = !empty($this->request->getPost('offset')) ? $this->request->getPost('offset') : 0;
            $sort = !empty($this->request->getPost('sort')) ? $this->request->getPost('sort') : 'id';
            $order = !empty($this->request->getPost('order')) ? $this->request->getPost('order') : 'DESC';
            $db = \Config\Database::connect();
            $totalBuilder = $db->table('partner_bids pb')
                ->select('COUNT(pb.id) as total_bidders')
                ->where('pb.custom_job_request_id', $this->request->getPost('custom_job_request_id'))
                ->get()
                ->getRowArray();
            $total = $totalBuilder['total_bidders'];
            $biddersBuilder = $db->table('partner_bids pb')
                ->select('pb.*, pd.company_name as company_name,u.username as provider_name,pd.advance_booking_days,pd.visiting_charges, pd.banner as provider_image,pd.at_store,pd.at_doorstep,u.payable_commision')
                ->join('partner_details pd', 'pd.partner_id = pb.partner_id', 'left')
                ->join('users u', 'u.id = pd.partner_id')
                ->where('pb.custom_job_request_id', $this->request->getPost('custom_job_request_id'))
                ->orderBy($sort, $order)
                ->limit($limit, $offset)
                ->get()
                ->getResultArray();
            $check_payment_gateway = get_settings('payment_gateways_settings', true);
            foreach ($biddersBuilder as $index => $row) {
                $rating_data = $db->table('services_ratings sr')
                    ->select('
                    COUNT(sr.rating) as number_of_rating,
                    SUM(sr.rating) as total_rating,
                    (SUM(sr.rating) / COUNT(sr.rating)) as average_rating
                    ')
                    ->join('services s', 'sr.service_id = s.id', 'left')
                    ->join('custom_job_requests cj', 'sr.custom_job_request_id = cj.id', 'left')
                    ->join('partner_bids pd', 'pd.custom_job_request_id = cj.id', 'left')
                    ->where("(s.user_id = {$row['partner_id']}) OR (pd.partner_id = {$row['partner_id']})")
                    ->get()->getResultArray();
                $biddersBuilder[$index]['rating'] = (($rating_data[0]['average_rating'] != "") ? sprintf('%0.1f', $rating_data[0]['average_rating']) : '0.0');
                $biddersBuilder[$index]['provider_image'] = (file_exists($row['provider_image'])) ? base_url($row['provider_image']) : base_url('public/backend/assets/profiles/default.png');
                $total_orders = $db->table('orders o')->where('partner_id', $row['partner_id'])->select('count(o.id) as `total`')->where('o.parent_id  IS NULL')->get()->getResultArray()[0]['total'];
                $biddersBuilder[$index]['total_orders'] = $total_orders;
                $biddersBuilder[$index]['is_online_payment_allowed'] = $check_payment_gateway['payment_gateway_setting'];
                $active_partner_subscription = fetch_details('partner_subscriptions', ['partner_id' => $row['partner_id'], 'status' => 'active']);
                if (!empty($active_partner_subscription)) {
                    if ($active_partner_subscription[0]['is_commision'] == "yes") {
                        $commission_threshold = $active_partner_subscription[0]['commission_threshold'];
                    } else {
                        $commission_threshold = 0;
                    }
                } else {
                    $commission_threshold = 0;
                }
                if ($check_payment_gateway['cod_setting'] == 1 && $check_payment_gateway['payment_gateway_setting'] == 0) {
                    $biddersBuilder[$index]['is_pay_later_allowed'] = 1;
                } else if ($check_payment_gateway['cod_setting'] == 0) {
                    $biddersBuilder[$index]['is_pay_later_allowed'] = 0;
                } else {
                    $payable_commission_of_provider = $biddersBuilder[$index]['payable_commision'];
                    if (($payable_commission_of_provider >= $commission_threshold) && $commission_threshold != 0) {
                        $biddersBuilder[$index]['is_pay_later_allowed'] = 0;
                    } else {
                        $biddersBuilder[$index]['is_pay_later_allowed'] = 1;
                    }
                }


                if ($biddersBuilder[$index]['tax_amount'] == "") {
                    $biddersBuilder[$index]['final_total'] =  $biddersBuilder[$index]['counter_price'];
                } else {
                    $biddersBuilder[$index]['final_total'] =  $biddersBuilder[$index]['counter_price'] + ($biddersBuilder[$index]['tax_amount']);
                }
            }
            $data['bidders'] = $biddersBuilder;
            $custom_job = $db->table('custom_job_requests cj')
                ->select('cj.*,c.name as category_name,c.image as category_image')
                ->join('categories c', 'c.id = cj.category_id', 'left')
                ->where('cj.id', $this->request->getPost('custom_job_request_id'))
                ->get()
                ->getResultArray();

                foreach ($custom_job as &$job) { // Use a reference to update the array directly
                    $image_path = base_url('/public/uploads/categories/' . $job['category_image']);
                    $job['category_image'] = check_exists($image_path) ? $image_path : '';
                }
                unset($job); // Unset the reference to avoid unintended side effects
                

            $data['custom_job'] = $custom_job[0];
            if (!empty($data)) {
                return $this->response->setJSON([
                    'error'   => false,
                    'message' => 'Bidders fetched successfully',
                    'data'    => $data,
                    'total'   => $total,
                    'status'  => 200
                ]);
            } else {
                return $this->response->setJSON([
                    'error'   => false,
                    'message' => 'No bidders found',
                    'data'    => [],
                    'total'   => 0,
                    'status'  => 200
                ]);
            }
        } catch (\Throwable $th) {
            throw $th;
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - fetch_custom_job_bidders()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => 'Something went wrong',
            ]);
        }
    }
    public  function  cancle_custom_job_request()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'custom_job_request_id' => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => $validation->getErrors(),
                    'data'    => [],
                ]);
            }
            $custom_job = fetch_details('custom_job_requests', ['id' => $this->request->getPost('custom_job_request_id')]);
            if ($custom_job[0]['status'] != "pending") {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => "You can not cancle service",
                    'data'    => [],
                ]);
            }
            $update = update_details(['status' => 'cancelled'], ['id' => $this->request->getPost('custom_job_request_id')], 'custom_job_requests');
            if ($update) {
                return $this->response->setJSON([
                    'error'   => false,
                    'message' => 'Custom Job Request cancelled successfully',
                    'status'  => 200
                ]);
            }
        } catch (\Throwable $th) {
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - cancle_custom_job_request()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => 'Something went wrong',
            ]);
        }
    }
    public function get_places_for_app()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'input' => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => $validation->getErrors(),
                    'data'    => [],
                ]);
            }
            $input = $_GET['input'];
            $key = get_settings('api_key_settings', true);
            if (!isset($key['google_map_api'])) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => 'MAP API key is not set',
                ]);
            }
            $google_map_api = $key['google_map_api'];
            $url = "https://maps.googleapis.com/maps/api/place/autocomplete/json?key=" . $google_map_api . "&input=" . $input;
            $response = file_get_contents($url);
            $responseData = json_decode($response, true);
            return $this->response->setJSON([
                'error' => false,
                'data'  => $responseData ?? [],
            ]);
        } catch (\Throwable $th) {
            throw $th;
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_places_for_app()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => 'Something went wrong',
            ]);
        }
    }
    public function get_place_details_for_app()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'placeid' => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => $validation->getErrors(),
                    'data'    => [],
                ]);
            }
            $placeid = $_GET['placeid'];
            $key = get_settings('api_key_settings', true);
            if (!isset($key['google_map_api'])) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => 'MAP API key is not set',
                ]);
            }
            $google_map_api = $key['google_map_api'];
            $url = "https://maps.googleapis.com/maps/api/place/details/json?key=" . $google_map_api . "&placeid=" . $placeid;
            $response = file_get_contents($url);
            $responseData = json_decode($response, true);
            return $this->response->setJSON([
                'error' => false,
                'data'  => $responseData ?? [],
            ]);
        } catch (\Throwable $th) {
            throw $th;
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_places_for_app()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => 'Something went wrong',
            ]);
        }
    }
    public function get_places_for_web()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'address' => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => $validation->getErrors(),
                    'data'    => [],
                ]);
            }
            $address = $_GET['address'];
            $key = get_settings('api_key_settings', true);
            if (!isset($key['google_map_api'])) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => 'MAP API key is not set',
                ]);
            }
            $google_map_api = $key['google_map_api'];
            $encoded_address = urlencode($address);
            $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . $encoded_address . "&key=" . $google_map_api;
            $response = file_get_contents($url);
            $responseData = json_decode($response, true);
            return $this->response->setJSON([
                'error' => false,
                'data'  => $responseData ?? [],
            ]);
        } catch (\Throwable $th) {
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_places_for_web()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => 'Something went wrong',
            ]);
        }
    }
    public function get_place_details_for_web()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'latitude' => 'required',
                'longitude' => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => $validation->getErrors(),
                    'data'    => [],
                ]);
            }
            $latitude = $_GET['latitude'];
            $longitude = $_GET['longitude'];
            $key = get_settings('api_key_settings', true);
            if (!isset($key['google_map_api'])) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => 'MAP API key is not set',
                ]);
            }
            $google_map_api = $key['google_map_api'];
            $encoded_longitude = urlencode($longitude);
            $encoded_latitude = urlencode($latitude);
            $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=" . $encoded_latitude . "," . $encoded_longitude . "&key=" . $google_map_api;
            $response = file_get_contents($url);
            $responseData = json_decode($response, true);
            return $this->response->setJSON([
                'error' => false,
                'data'  => $responseData ?? [],
            ]);
        } catch (\Throwable $th) {
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_places_for_web()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => 'Something went wrong',
            ]);
        }
    }
}
