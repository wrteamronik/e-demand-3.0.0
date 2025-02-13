<?php

namespace App\Controllers\admin;

use App\Models\Country_code_model;
use App\Models\Email_template_model;
use App\Models\Service_ratings_model;

class Settings extends Admin
{
    private $db, $builder;
    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
        $this->validation = \Config\Services::validation();
        $this->builder = $this->db->table('settings');
        $this->superadmin = $this->session->get('email');
        helper('ResponceServices');
    }
    public function __destruct()
    {
        $this->db->close();
        $this->data = [];
    }
    public function main_system_setting_page()
    {
        if (!$this->isLoggedIn || !$this->userIsAdmin) {
            return redirect('admin/login');
        }
        setPageInfo($this->data, 'System Settings | Admin Panel', 'main_system_settings');
        return view('backend/admin/template', $this->data);
    }
    public function general_settings()
    {
        try {
            helper('form');
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            if ($this->request->getPost('update')) {
                if ($this->superadmin == "superadmin@gmail.com") {
                    defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1;
                } else {
                    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                        $_SESSION['toastMessage'] = DEMO_MODE_ERROR;
                        $_SESSION['toastMessageType']  = 'error';
                        $this->session->markAsFlashdata('toastMessage');
                        $this->session->markAsFlashdata('toastMessageType');
                        return redirect()->to('admin/settings/general-settings')->withCookies();
                    }
                }
                $updatedData = $this->request->getPost();
                $flag = 0;
                $login_image = false;
                $favicon = false;
                $halfLogo = false;
                $logo = false;
                $partner_favicon = false;
                $partner_halfLogo = false;
                $partner_logo = false;
                $files = array();
                $data = get_settings('general_settings', true);
                if (!empty($_FILES['favicon'])) {
                    if ($_FILES['favicon']['name'] != "") {
                        if (!valid_image('favicon')) {
                            $flag = 1;
                        } else {
                            $favicon = true;
                        }
                    }
                }
                if (!empty($_FILES['halfLogo'])) {
                    if ($_FILES['halfLogo']['name'] != "") {
                        if (!valid_image('halfLogo')) {
                            $flag = 1;
                        } else {
                            $halfLogo = true;
                        }
                    }
                }
                if (!empty($_FILES['logo'])) {
                    if ($_FILES['logo']['name'] != "") {
                        if (!valid_image('logo')) {
                            $flag = 1;
                        } else {
                            $logo = true;
                        }
                    }
                }
                if (!empty($_FILES['logo'])) {
                    if ($_FILES['partner_favicon']['name'] != "") {
                        if (!valid_image('partner_favicon')) {
                            $flag = 1;
                        } else {
                            $partner_favicon = true;
                        }
                    }
                }
                if (!empty($_FILES['partner_halfLogo'])) {
                    if ($_FILES['partner_halfLogo']['name'] != "") {
                        if (!valid_image('partner_halfLogo')) {
                            $flag = 1;
                        } else {
                            $partner_halfLogo = true;
                        }
                    }
                }
                if (!empty($_FILES['partner_logo'])) {
                    if ($_FILES['partner_logo']['name'] != "") {
                        if (!valid_image('partner_logo')) {
                            $flag = 1;
                        } else {
                            $partner_logo = true;
                        }
                    }
                }
                if (!empty($_FILES['login_image'])) {
                    if ($_FILES['login_image']['name'] != "") {
                        if (!valid_image('login_image')) {
                            $flag = 1;
                        } else {
                            $login_image = true;
                        }
                    }
                }
                if ($login_image) {
                    $file = $this->request->getFile('login_image');
                    $path = FCPATH . 'public/frontend/retro/';
                    $newName = "Login_BG.jpg";
                    if (file_exists($path . $newName)) {
                        unlink($path . $newName);
                    }
                    $file->move($path, $newName);
                    $updatedData['login_image'] = $newName;
                } else {
                    $updatedData['login_image'] = isset($data['login_image']) ? $data['login_image'] : "";
                }
                if ($favicon) {
                    $file = $this->request->getFile('favicon');
                    $newName = $file->getRandomName();
                    $tempPath = $_FILES['favicon']['tmp_name'];
                    compressImage($tempPath, 'public/uploads/site/' . $newName, 70);
                    $updatedData['favicon'] = $newName;
                } else {
                    $updatedData['favicon'] = isset($data['favicon']) ? $data['favicon'] : "";
                }
                if ($logo) {
                    $file = $this->request->getFile('logo');
                    $newName = $file->getRandomName();
                    $tempPath = $_FILES['logo']['tmp_name'];
                    compressImage($tempPath, 'public/uploads/site/' . $newName, 70);
                    $updatedData['logo'] = $newName;
                } else {
                    $updatedData['logo'] = isset($data['logo']) ? $data['logo'] : "";
                }
                if ($halfLogo) {
                    $file = $this->request->getFile('halfLogo');
                    $newName = $file->getRandomName();
                    $tempPath = $_FILES['halfLogo']['tmp_name'];
                    compressImage($tempPath, 'public/uploads/site/' . $newName, 70);
                    $updatedData['half_logo'] = $newName;
                } else {
                    $updatedData['half_logo'] = isset($data['half_logo']) ? $data['half_logo'] : "";
                }
                if ($partner_favicon) {
                    $file = $this->request->getFile('partner_favicon');
                    $newName = $file->getRandomName();
                    $tempPath = $_FILES['partner_favicon']['tmp_name'];
                    compressImage($tempPath, 'public/uploads/site/' . $newName, 70);
                    $updatedData['partner_favicon'] = $newName;
                } else {
                    $updatedData['partner_favicon'] = isset($data['partner_favicon']) ? $data['partner_favicon'] : "";
                }
                if ($partner_logo) {
                    $file = $this->request->getFile('partner_logo');
                    $newName = $file->getRandomName();
                    $tempPath = $_FILES['partner_logo']['tmp_name'];
                    compressImage($tempPath, 'public/uploads/site/' . $newName, 70);
                    $updatedData['partner_logo'] = $newName;
                } else {
                    $updatedData['partner_logo'] = isset($data['partner_logo']) ? $data['partner_logo'] : "";
                }
                if ($partner_halfLogo) {
                    $file = $this->request->getFile('partner_halfLogo');
                    $newName = $file->getRandomName();
                    $tempPath = $_FILES['partner_halfLogo']['tmp_name'];
                    compressImage($tempPath, 'public/uploads/site/' . $newName, 70);
                    $updatedData['partner_half_logo'] = $newName;
                } else {
                    $updatedData['partner_half_logo'] = isset($data['partner_half_logo']) ? $data['partner_half_logo'] : '';
                }
                unset($updatedData['update']);
                unset($updatedData[csrf_token()]);
                $updatedData['currency'] = (!empty($this->request->getPost('currency'))) ? $this->request->getPost('currency') : (isset($data['currency']) ? $data['currency'] : "");
                $updatedData['country_currency_code'] = (!empty($this->request->getPost('country_currency_code'))) ? $this->request->getPost('country_currency_code') : (isset($data['country_currency_code']) ? $data['country_currency_code'] : "");
                if ($this->request->getPost('decimal_point') == 0) {
                    $updatedData['decimal_point'] = "0";
                } elseif (!empty($this->request->getPost('decimal_point'))) {
                    $updatedData['decimal_point'] = $this->request->getPost('decimal_point');
                } else {
                    $updatedData['decimal_point'] = $data['decimal_point'];
                }
                if ($updatedData['distance_unit'] == 'miles') {
                    $distanceInMiles = $this->request->getPost('max_serviceable_distance');
                    $updatedData['distance_unit'] = $this->request->getPost('distance_unit');
                    $distanceInKm = $distanceInMiles * 1.60934;
                    $updatedData['max_serviceable_distance'] = round($distanceInKm);
                }
                if (!empty($this->request->getPost('otp_system'))) {
                    $updatedData['otp_system'] = (!empty($this->request->getPost('otp_system'))) ? $this->request->getPost('otp_system') : (isset($data['otp_system']) ? ($data['otp_system']) : "");
                }
                if (!empty($this->request->getPost('allow_pre_booking_chat'))) {
                    $updatedData['allow_pre_booking_chat'] = (!empty($this->request->getPost('allow_pre_booking_chat'))) ? $this->request->getPost('allow_pre_booking_chat') : (isset($data['allow_pre_booking_chat']) ? ($data['allow_pre_booking_chat']) : "");
                }
                if (!empty($this->request->getPost('allow_post_booking_chat'))) {
                    $updatedData['allow_post_booking_chat'] = (!empty($this->request->getPost('allow_post_booking_chat'))) ? $this->request->getPost('allow_post_booking_chat') : (isset($data['allow_post_booking_chat']) ? ($data['allow_post_booking_chat']) : "");
                }
                $keys = [
                    'customer_current_version_android_app',
                    'customer_current_version_ios_app',
                    'customer_compulsary_update_force_update',
                    'provider_current_version_android_app',
                    'provider_current_version_ios_app',
                    'provider_compulsary_update_force_update',
                    'customer_app_maintenance_schedule_date',
                    'message_for_customer_application',
                    'customer_app_maintenance_mode',
                    'provider_app_maintenance_schedule_date',
                    'message_for_provider_application',
                    'provider_app_maintenance_mode',
                    'provider_location_in_provider_details',
                    'company_title',
                    'support_name',
                    'support_email',
                    'phone',
                    'system_timezone_gmt',
                    'system_timezone',
                    'primary_color',
                    'secondary_color',
                    'primary_shadow',
                    'address',
                    'short_description',
                    'copyright_details',
                    'booking_auto_cancle_duration',
                    'customer_playstore_url',
                    'customer_appstore_url',
                    'provider_playstore_url',
                    'provider_appstore_url',
                    'maxFilesOrImagesInOneMessage',
                    'maxFileSizeInMBCanBeSent',
                    'maxCharactersInATextMessage',
                    'android_google_interstitial_id',
                    'android_google_banner_id',
                    'ios_google_interstitial_id',
                    'ios_google_banner_id',
                    "android_google_ads_status",
                    "ios_google_ads_status",
                    'authentication_mode',
                    'company_map_location',
                    'support_hours',
                ];
                foreach ($keys as $key) {
                    $updatedData[$key] = (!empty($this->request->getPost($key))) ? $this->request->getPost($key) : (isset($data[$key]) ? ($data[$key]) : "");
                }
                if ($this->request->getPost('image_compression_preference') == 0) {
                    $updatedData['image_compression_preference'] = "0";
                    $updatedData['image_compression_quality'] = "0";
                } elseif (!empty($this->request->getPost('image_compression_preference'))) {
                    $updatedData['image_compression_preference'] = $this->request->getPost('image_compression_preference');
                } else {
                    $updatedData['image_compression_preference'] = $data['image_compression_preference'];
                }
                if (!empty($updatedData['system_timezone_gmt'])) {
                    if ($updatedData['system_timezone_gmt'] == " 00:00") {
                        $updatedData['system_timezone_gmt'] = '+' . trim($updatedData['system_timezone_gmt']);
                    }
                }
                $json_string = json_encode($updatedData);
                if ($flag == 0) {
                    if ($this->update_setting('general_settings', $json_string)) {
                        $_SESSION['toastMessage']  = 'Unable to update the settings.';
                        $_SESSION['toastMessageType']  = 'error';
                    } else {
                        $_SESSION['toastMessage'] = 'Settings has been successfuly updated.';
                        $_SESSION['toastMessageType']  = 'success';
                    }
                } else {
                    $_SESSION['toastMessage'] = 'please insert valid image.';
                    $_SESSION['toastMessageType']  = 'error';
                }
                $this->session->markAsFlashdata('toastMessage');
                $this->session->markAsFlashdata('toastMessageType');
                return redirect()->to('admin/settings/general-settings')->withCookies();
            }
            $this->builder->select('value');
            $this->builder->where('variable', 'general_settings');
            $query = $this->builder->get()->getResultArray();
            if (count($query) == 1) {
                $settings = $query[0]['value'];
                $settings = json_decode($settings, true);
                if (!empty($settings)) {
                    $this->data = array_merge($this->data, $settings);
                }
            }
            $settings['distance_unit'] = isset($settings['distance_unit']) ? $settings['distance_unit'] : 'km';
            if ($settings['distance_unit'] == "miles") {
                $this->data['max_serviceable_distance'] = round($settings['max_serviceable_distance'] * 0.621371);
            };
            $this->data['timezones'] = get_timezone_array();
            setPageInfo($this->data, 'General Settings | Admin Panel', 'general_settings');
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - general_settings()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function email_settings()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            if ($this->request->getGet('update')) {
                if ($this->superadmin == "superadmin@gmail.com") {
                    defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1;
                } else {
                    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                        $_SESSION['toastMessage'] = DEMO_MODE_ERROR;
                        $_SESSION['toastMessageType']  = 'error';
                        $this->session->markAsFlashdata('toastMessage');
                        $this->session->markAsFlashdata('toastMessageType');
                        return redirect()->to('admin/settings/general-settings')->withCookies();
                    }
                }
                $this->validation->setRules(
                    [
                        'smtpHost' => [
                            "rules" => 'required',
                            "errors" => [
                                "required" => "Please enter SMTP Host"
                            ]
                        ],
                        'smtpUsername' => [
                            "rules" => 'required',
                            "errors" => [
                                "required" => "Please enter SMTP Username"
                            ]
                        ],
                        'smtpPassword' => [
                            "rules" => 'required',
                            "errors" => [
                                "required" => "Please enter SMTP Password"
                            ]
                        ],
                        'smtpPort' => [
                            "rules" => 'required|numeric',
                            "errors" => [
                                "required" => "Please enter SMTP Port Number",
                                "numeric" => "Please enter numeric value for SMTP Port Number"
                            ]
                        ],
                    ],
                );
                if (!$this->validation->withRequest($this->request)->run()) {
                    $errors  = $this->validation->getErrors();
                    $response['error'] = true;
                    $response['message'] = $errors;
                    $response['csrfName'] = csrf_token();
                    $response['csrfHash'] = csrf_hash();
                    $response['data'] = [];
                    return $this->response->setJSON($response);
                }
                $updatedData = $this->request->getGet();
                $json_string = json_encode($updatedData);
                if ($this->update_setting('email_settings', $json_string)) {
                    $_SESSION['toastMessage']  = 'Unable to update the email settings.';
                    $_SESSION['toastMessageType']  = 'error';
                } else {
                    $_SESSION['toastMessage'] = 'Email settings has been successfuly updated.';
                    $_SESSION['toastMessageType']  = 'success';
                }
                $this->session->markAsFlashdata('toastMessage');
                $this->session->markAsFlashdata('toastMessageType');
                return redirect()->to('admin/settings/email-settings')->withCookies();
            }
            $this->builder->select('value');
            $this->builder->where('variable', 'email_settings');
            $query = $this->builder->get()->getResultArray();
            if (count($query) == 1) {
                $settings = $query[0]['value'];
                $settings = json_decode($settings, true);
                $this->data = array_merge($this->data, $settings);
            }
            setPageInfo($this->data, 'Email Settings | Admin Panel', 'email_settings');
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - email_settings()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function pg_settings()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            if ($this->request->getPost('update')) {
                if ($this->superadmin == "superadmin@gmail.com") {
                    defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1;
                } else {
                    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                        $_SESSION['toastMessage'] = DEMO_MODE_ERROR;
                        $_SESSION['toastMessageType']  = 'error';
                        $this->session->markAsFlashdata('toastMessage');
                        $this->session->markAsFlashdata('toastMessageType');
                        return redirect()->to('admin/settings/general-settings')->withCookies();
                    }
                }
                $updatedData = $this->request->getPost();
                $updatedData['cod_setting'] = isset($updatedData['cod_setting']) ? 1 : 0;
                $updatedData['payment_gateway_setting'] = isset($updatedData['payment_gateway_setting']) ? 1 : 0;
                $paypal_status = isset($updatedData['paypal_status']) ? 1 : 0;
                $razorpayApiStatus = isset($updatedData['razorpayApiStatus']) ? 1 : 0;
                $paystack_status = isset($updatedData['paystack_status']) ? 1 : 0;
                $stripe_status = isset($updatedData['stripe_status']) ? 1 : 0;
                $flutterwave_status = isset($updatedData['flutterwave_status']) ? 1 : 0;
                if ($paypal_status == 0 && $razorpayApiStatus == 0 && $paystack_status == 0 && $stripe_status == 0 && $flutterwave_status == 0) {
                    $_SESSION['toastMessage'] = 'At least one payment method must be enabled.';
                    $_SESSION['toastMessageType']  = 'error';
                    $this->session->markAsFlashdata('toastMessage');
                    $this->session->markAsFlashdata('toastMessageType');
                    return redirect()->to('admin/settings/pg-settings')->withCookies();
                }
                unset($updatedData['update']);
                unset($updatedData[csrf_token()]);

                if (isset($updatedData['paypal_website_url'])) {
                    $updatedData['paypal_website_url']= rtrim($updatedData['paypal_website_url'], '/');
                }
                if (isset($updatedData['flutterwave_website_url'])) {
                    $updatedData['flutterwave_website_url']=rtrim($updatedData['flutterwave_website_url'], '/');
                }
                if (isset($updatedData['flutterwave_webhook_secret_key'])) {

                    updateEnv('FLUTTERWAVE_SECRET_KEY', $updatedData['flutterwave_webhook_secret_key']);
                }


                $json_string = json_encode($updatedData);
                if ($this->update_setting('payment_gateways_settings', $json_string)) {
                    $_SESSION['toastMessage']  = 'Unable to update the payment gateways settings.';
                    $_SESSION['toastMessageType']  = 'error';
                } else {
                    $_SESSION['toastMessage'] = 'Payment gate ways settings has been successfuly updated.';
                    $_SESSION['toastMessageType']  = 'success';
                    $this->session->markAsFlashdata('toastMessage');
                    $this->session->markAsFlashdata('toastMessageType');
                }
                $this->session->markAsFlashdata('toastMessage');
                $this->session->markAsFlashdata('toastMessageType');
                return redirect()->to('admin/settings/pg-settings')->withCookies();
            } else {
                $this->builder->select('value');
                $this->builder->where('variable', 'payment_gateways_settings');
                $query = $this->builder->get()->getResultArray();
                if (count($query) == 1) {
                    $settings = $query[0]['value'];
                    $settings = json_decode($settings, true);
                    $this->data = array_merge($this->data, $settings);
                }
                setPageInfo($this->data, 'Payment Gateways Settings | Admin Panel', 'payment_gateways');
                return view('backend/admin/template', $this->data);
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - pg_settings()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }

    public function privacy_policy()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            if ($this->request->getPost('update')) {
                if ($this->superadmin == "superadmin@gmail.com") {
                    defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1;
                } else {
                    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                        $_SESSION['toastMessage'] = DEMO_MODE_ERROR;
                        $_SESSION['toastMessageType']  = 'error';
                        $this->session->markAsFlashdata('toastMessage');
                        $this->session->markAsFlashdata('toastMessageType');
                        return redirect()->to('admin/settings/general-settings')->withCookies();
                    }
                }
                $updatedData = $this->request->getPost();
                unset($updatedData['update']);
                unset($updatedData['files']);
                unset($updatedData[csrf_token()]);
                $json_string = json_encode($updatedData);
                if ($this->update_setting('privacy_policy', $json_string)) {
                    $_SESSION['toastMessage']  = 'Unable to update the privacy policy.';
                    $_SESSION['toastMessageType']  = 'error';
                } else {
                    $_SESSION['toastMessage'] = 'privacy Policy has been successfuly updated.';
                    $_SESSION['toastMessageType']  = 'success';
                }
                $this->session->markAsFlashdata('toastMessage');
                $this->session->markAsFlashdata('toastMessageType');
                return redirect()->to('admin/settings/privacy-policy')->withCookies();
            }
            $this->builder->select('value');
            $this->builder->where('variable', 'privacy_policy');
            $query = $this->builder->get()->getResultArray();
            if (count($query) == 1) {
                $settings = $query[0]['value'];
                $settings = json_decode($settings, true);
                $this->data = array_merge($this->data, $settings);
            }
            setPageInfo($this->data, 'Privacy Policy Settings | Admin Panel', 'privacy_policy');
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - privacy_policy()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function customer_privacy_policy_page()
    {
        $settings = get_settings('general_settings', true);
        $this->data['title'] = 'Privacy Policy | ' . $settings['company_title'];
        $this->data['meta_description'] = 'Privacy Policy | ' . $settings['company_title'];
        $this->data['privacy_policy'] = get_settings('customer_privacy_policy', true);
        $this->data['settings'] =  $settings;
        return view('backend/admin/pages/customer_app_privacy_policy', $this->data);
    }
    public function customer_tearms_and_condition()
    {
        $settings = get_settings('general_settings', true);
        $this->data['title'] = 'Customer Terms & Condition  | ' . $settings['company_title'];
        $this->data['meta_description'] = 'Customer Terms & Condition  | ' . $settings['company_title'];
        $this->data['customer_terms_conditions'] = get_settings('customer_terms_conditions', true);
        $this->data['settings'] =  $settings;
        return view('backend/admin/pages/customer_terms_and_condition_page', $this->data);
    }
    public function provider_terms_and_condition()
    {
        $settings = get_settings('general_settings', true);
        $this->data['title'] = 'Provider Privacy Policy  | ' . $settings['company_title'];
        $this->data['meta_description'] = 'Provider Privacy Policy  | ' . $settings['company_title'];
        $this->data['privacy_policy'] = get_settings('privacy_policy', true);
        $this->data['settings'] =  $settings;
        return view('backend/admin/pages/provider_terms_and_condition_page', $this->data);
    }
    public function partner_privacy_policy_page()
    {
        $settings = get_settings('general_settings', true);
        $this->data['title'] = 'Privacy Policy | ' . $settings['company_title'];
        $this->data['meta_description'] = 'Privacy Policy | ' . $settings['company_title'];
        $this->data['privacy_policy'] = get_settings('privacy_policy', true);
        $this->data['settings'] =  $settings;
        return view('backend/admin/pages/partner_app_privacy_policy', $this->data);
    }
    public function customer_privacy_policy()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            if ($this->request->getPost('update')) {
                if ($this->superadmin == "superadmin@gmail.com") {
                    defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1;
                } else {
                    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                        $_SESSION['toastMessage'] = DEMO_MODE_ERROR;
                        $_SESSION['toastMessageType']  = 'error';
                        $this->session->markAsFlashdata('toastMessage');
                        $this->session->markAsFlashdata('toastMessageType');
                        return redirect()->to('admin/settings/general-settings')->withCookies();
                    }
                }
                $updatedData = $this->request->getPost();
                unset($updatedData['update']);
                unset($updatedData['files']);
                unset($updatedData[csrf_token()]);
                $json_string = json_encode($updatedData);
                if ($this->update_setting('customer_privacy_policy', $json_string)) {
                    $_SESSION['toastMessage']  = 'Unable to update the privacy policy.';
                    $_SESSION['toastMessageType']  = 'error';
                } else {
                    $_SESSION['toastMessage'] = 'privacy Policy has been successfuly updated.';
                    $_SESSION['toastMessageType']  = 'success';
                }
                $this->session->markAsFlashdata('toastMessage');
                $this->session->markAsFlashdata('toastMessageType');
                return redirect()->to('admin/settings/customer-privacy-policy')->withCookies();
            }
            $this->builder->select('value');
            $this->builder->where('variable', 'customer_privacy_policy');
            $query = $this->builder->get()->getResultArray();
            if (count($query) == 1) {
                $settings = $query[0]['value'];
                $settings = json_decode($settings, true);
                $this->data = array_merge($this->data, $settings);
            }
            $this->data['title'] = 'Privacy Policy Settings | Admin Panel';
            $this->data['main_page'] = 'customer_privacy_policy';
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - customer_privacy_policy()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function refund_policy_page()
    {
        $settings = get_settings('general_settings', true);
        $this->data['title'] = 'Refund Policy | ' . $settings['company_title'];
        $this->data['meta_description'] = 'Refund Policy | ' . $settings['company_title'];
        $this->data['refund_policy'] = get_settings('refund_policy', true);
        $this->data['settings'] =  $settings;
        return view('backend/admin/pages/refund_policy_page', $this->data);
    }
    public function refund_policy()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            if ($this->request->getPost('update')) {
                if ($this->superadmin == "superadmin@gmail.com") {
                    defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1;
                } else {
                    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                        $_SESSION['toastMessage'] = DEMO_MODE_ERROR;
                        $_SESSION['toastMessageType']  = 'error';
                        $this->session->markAsFlashdata('toastMessage');
                        $this->session->markAsFlashdata('toastMessageType');
                        return redirect()->to('admin/settings/general-settings')->withCookies();
                    }
                }
                $updatedData = $this->request->getPost();
                unset($updatedData['update']);
                unset($updatedData['files']);
                unset($updatedData[csrf_token()]);
                $json_string = json_encode($updatedData);
                if ($this->update_setting('refund_policy', $json_string)) {
                    $_SESSION['toastMessage']  = 'Unable to update the refund policy.';
                    $_SESSION['toastMessageType']  = 'error';
                } else {
                    $_SESSION['toastMessage'] = 'refund Policy has been successfuly updated.';
                    $_SESSION['toastMessageType']  = 'success';
                }
                $this->session->markAsFlashdata('toastMessage');
                $this->session->markAsFlashdata('toastMessageType');
                return redirect()->to('admin/settings/refund-policy')->withCookies();
            }
            $this->builder->select('value');
            $this->builder->where('variable', 'refund_policy');
            $query = $this->builder->get()->getResultArray();
            if (count($query) == 1) {
                $settings = $query[0]['value'];
                $settings = json_decode($settings, true);
                $this->data = array_merge($this->data, $settings);
            }
            setPageInfo($this->data, 'Refund Policy Settings | Admin Panel', 'refund_policy');
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - refund_policy()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function updater()
    {
        if ($this->isLoggedIn && $this->userIsAdmin) {
            setPageInfo($this->data, 'Updater | Admin Panel', 'updater');
            return view('backend/admin/template', $this->data);
        } else {
            return redirect('admin/login');
        }
    }
    public function terms_and_conditions()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            if ($this->request->getPost('update')) {
                if ($this->superadmin == "superadmin@gmail.com") {
                    defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1;
                } else {
                    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                        $_SESSION['toastMessage'] = DEMO_MODE_ERROR;
                        $_SESSION['toastMessageType']  = 'error';
                        $this->session->markAsFlashdata('toastMessage');
                        $this->session->markAsFlashdata('toastMessageType');
                        return redirect()->to('admin/settings/general-settings')->withCookies();
                    }
                }
                $updatedData = $this->request->getPost();
                unset($updatedData['files']);
                unset($updatedData['update']);
                unset($updatedData[csrf_token()]);
                $json_string = json_encode($updatedData);
                if ($this->update_setting('terms_conditions', $json_string)) {
                    $_SESSION['toastMessage']  = 'Unable to update the terms & conditions.';
                    $_SESSION['toastMessageType']  = 'error';
                } else {
                    $_SESSION['toastMessage'] = 'Terms & Conditions has been successfuly updated.';
                    $_SESSION['toastMessageType']  = 'success';
                }
                $this->session->markAsFlashdata('toastMessage');
                $this->session->markAsFlashdata('toastMessageType');
                return redirect()->to('admin/settings/terms-and-conditions')->withCookies();
            }
            $this->builder->select('value');
            $this->builder->where('variable', 'terms_conditions');
            $query = $this->builder->get()->getResultArray();
            if (count($query) == 1) {
                $settings = $query[0]['value'];
                $settings = json_decode($settings, true);
                $this->data = array_merge($this->data, $settings);
            }
            setPageInfo($this->data, 'Terms & Conditions Settings | Admin Panel', 'terms_and_conditions');
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - terms_and_conditions()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function customer_terms_and_conditions()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            if ($this->request->getPost('update')) {
                if ($this->superadmin == "superadmin@gmail.com") {
                    defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1;
                } else {
                    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                        $_SESSION['toastMessage'] = DEMO_MODE_ERROR;
                        $_SESSION['toastMessageType']  = 'error';
                        $this->session->markAsFlashdata('toastMessage');
                        $this->session->markAsFlashdata('toastMessageType');
                        return redirect()->to('admin/settings/customer-terms-and-conditions')->withCookies();
                    }
                }
                $updatedData = $this->request->getPost();
                unset($updatedData['files']);
                unset($updatedData['update']);
                unset($updatedData[csrf_token()]);
                $json_string = json_encode($updatedData);
                if ($this->update_setting('customer_terms_conditions', $json_string)) {
                    $_SESSION['toastMessage']  = 'Unable to update the terms & conditions.';
                    $_SESSION['toastMessageType']  = 'error';
                } else {
                    $_SESSION['toastMessage'] = 'Terms & Conditions has been successfuly updated.';
                    $_SESSION['toastMessageType']  = 'success';
                }
                $this->session->markAsFlashdata('toastMessage');
                $this->session->markAsFlashdata('toastMessageType');
                return redirect()->to('admin/settings/customer-terms-and-conditions')->withCookies();
            }
            $this->builder->select('value');
            $this->builder->where('variable', 'customer_terms_conditions');
            $query = $this->builder->get()->getResultArray();
            if (count($query) == 1) {
                $settings = $query[0]['value'];
                $settings = json_decode($settings, true);
                $this->data = array_merge($this->data, $settings);
            }
            setPageInfo($this->data, 'Terms & Conditions Settings | Admin Panel', 'customer_terms_and_conditions');
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - customer_terms_and_conditions()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function about_us()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            if ($this->request->getPost('update')) {
                if ($this->superadmin == "superadmin@gmail.com") {
                    defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1;
                } else {
                    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                        $_SESSION['toastMessage'] = DEMO_MODE_ERROR;
                        $_SESSION['toastMessageType']  = 'error';
                        $this->session->markAsFlashdata('toastMessage');
                        $this->session->markAsFlashdata('toastMessageType');
                        return redirect()->to('admin/settings/general-settings')->withCookies();
                    }
                }
                $updatedData = $this->request->getPost();
                unset($updatedData['files']);
                unset($updatedData['update']);
                unset($updatedData[csrf_token()]);
                $json_string = json_encode($updatedData);
                if ($this->update_setting('about_us', $json_string)) {
                    $_SESSION['toastMessage']  = 'Unable to update about-us section.';
                    $_SESSION['toastMessageType']  = 'error';
                } else {
                    $_SESSION['toastMessage'] = 'About-us section has been successfuly updated.';
                    $_SESSION['toastMessageType']  = 'success';
                }
                $this->session->markAsFlashdata('toastMessage');
                $this->session->markAsFlashdata('toastMessageType');
                return redirect()->to('admin/settings/about-us')->withCookies();
            }
            $this->builder->select('value');
            $this->builder->where('variable', 'about_us');
            $query = $this->builder->get()->getResultArray();
            if (count($query) == 1) {
                $settings = $query[0]['value'];
                $settings = json_decode($settings, true);
                $this->data = array_merge($this->data, $settings);
            }
            setPageInfo($this->data, 'About us Settings | Admin Panel', 'about_us');
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - customer_terms_and_conditions()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function contact_us()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            if ($this->request->getPost('update')) {
                if ($this->superadmin == "superadmin@gmail.com") {
                    defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1;
                } else {
                    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                        $_SESSION['toastMessage'] = DEMO_MODE_ERROR;
                        $_SESSION['toastMessageType']  = 'error';
                        $this->session->markAsFlashdata('toastMessage');
                        $this->session->markAsFlashdata('toastMessageType');
                        return redirect()->to('admin/settings/general-settings')->withCookies();
                    }
                }
                $updatedData = $this->request->getPost();
                unset($updatedData['files']);
                unset($updatedData['update']);
                unset($updatedData[csrf_token()]);
                $json_string = json_encode($updatedData);
                if ($this->update_setting('contact_us', $json_string)) {
                    $_SESSION['toastMessage']  = 'Unable to update contact-us section.';
                    $_SESSION['toastMessageType']  = 'error';
                } else {
                    $_SESSION['toastMessage'] = 'Contact-us section has been successfuly updated.';
                    $_SESSION['toastMessageType']  = 'success';
                }
                $this->session->markAsFlashdata('toastMessage');
                $this->session->markAsFlashdata('toastMessageType');
                return redirect()->to('admin/settings/contact-us')->withCookies();
            }
            $this->builder->select('value');
            $this->builder->where('variable', 'contact_us');
            $query = $this->builder->get()->getResultArray();
            if (count($query) == 1) {
                $settings = $query[0]['value'];
                $settings = json_decode($settings, true);
                $this->data = array_merge($this->data, $settings);
            }
            setPageInfo($this->data, 'Contact us Settings | Admin Panel', 'contact_us');
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - contact_us()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function api_key_settings()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            if ($this->request->getPost('update')) {
                if ($this->superadmin == "superadmin@gmail.com") {
                    defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1;
                } else {
                    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                        $_SESSION['toastMessage'] = DEMO_MODE_ERROR;
                        $_SESSION['toastMessageType']  = 'error';
                        $this->session->markAsFlashdata('toastMessage');
                        $this->session->markAsFlashdata('toastMessageType');
                        return redirect()->to('admin/settings/general-settings')->withCookies();
                    }
                }
                $updatedData = $this->request->getPost();
                unset($updatedData['files']);
                unset($updatedData[csrf_token()]);
                unset($updatedData['update']);
                $json_string = json_encode($updatedData);
                if ($this->update_setting('api_key_settings', $json_string)) {
                    $_SESSION['toastMessage']  = 'Unable to update PAI key section.';
                    $_SESSION['toastMessageType']  = 'error';
                } else {
                    $_SESSION['toastMessage'] = ' PAI key  section has been successfuly updated.';
                    $_SESSION['toastMessageType']  = 'success';
                }
                $this->session->markAsFlashdata('toastMessage');
                $this->session->markAsFlashdata('toastMessageType');
                return redirect()->to('admin/settings/api_key_settings')->withCookies();
            }
            $this->builder->select('value');
            $this->builder->where('variable', 'api_key_settings');
            $query = $this->builder->get()->getResultArray();
            if (count($query) == 1) {
                $settings = $query[0]['value'];
                $settings = json_decode($settings, true);
                $this->data = array_merge($this->data, $settings);
            }
            setPageInfo($this->data, 'API key Settings | Admin Panel', 'api_key_settings');
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - api_key_settings()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    private function update_setting($variable, $value)
    {
        try {
            $this->builder->where('variable', $variable);
            if (exists(['variable' => $variable], 'settings')) {
                $this->db->transStart();
                $this->builder->update(['value' => $value]);
                $this->db->transComplete();
            } else {
                $this->db->transStart();
                $this->builder->insert(['variable' => $variable, 'value' => $value]);
                $this->db->transComplete();
            }
            return $this->db->transComplete() ? true : false;
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - update_setting()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function themes()
    {
        if (!$this->isLoggedIn || !$this->userIsAdmin) {
            return redirect('admin/login');
        }
        if ($this->request->getPost('update')) {
        }
        $this->data["themes"] = fetch_details('themes', [], [], null, '0', 'id', "ASC");
        setPageInfo($this->data, 'About us Settings | Admin Panel', 'themes');
        return view('backend/admin/template', $this->data);
    }
    public function system_tax_settings()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            if ($this->request->getPost('update')) {
                if ($this->superadmin == "superadmin@gmail.com") {
                    defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1;
                } else {
                    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                        $_SESSION['toastMessage'] = DEMO_MODE_ERROR;
                        $_SESSION['toastMessageType']  = 'error';
                        $this->session->markAsFlashdata('toastMessage');
                        $this->session->markAsFlashdata('toastMessageType');
                        return redirect()->to('admin/settings/general-settings')->withCookies();
                    }
                }
                $updatedData = $this->request->getPost();
                unset($updatedData['files']);
                unset($updatedData[csrf_token()]);
                unset($updatedData['update']);
                $json_string = json_encode($updatedData);
                if ($this->update_setting('system_tax_settings', $json_string)) {
                    $_SESSION['toastMessage']  = 'Unable to update system tax settings.';
                    $_SESSION['toastMessageType']  = 'error';
                } else {
                    $_SESSION['toastMessage'] = ' System Tax settings successfuly updated.';
                    $_SESSION['toastMessageType']  = 'success';
                }
                $this->session->markAsFlashdata('toastMessage');
                $this->session->markAsFlashdata('toastMessageType');
                return redirect()->to('admin/settings/system_tax_settings')->withCookies();
            }
            $this->builder->select('value');
            $this->builder->where('variable', 'system_tax_settings');
            $query = $this->builder->get()->getResultArray();
            if (count($query) == 1) {
                $settings = $query[0]['value'];
                $settings = json_decode($settings, true);
                $this->data = array_merge($this->data, $settings);
            }
            setPageInfo($this->data, 'System Tax Settings | Admin Panel', 'system_tax_settings');
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - system_tax_settings()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function app_settings()
    {
        try {
            if ($this->request->getPost('update')) {
                if ($this->superadmin == "superadmin@gmail.com") {
                    defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1;
                } else {
                    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                        $_SESSION['toastMessage'] = DEMO_MODE_ERROR;
                        $_SESSION['toastMessageType']  = 'error';
                        $this->session->markAsFlashdata('toastMessage');
                        $this->session->markAsFlashdata('toastMessageType');
                        return redirect()->to('admin/settings/general-settings')->withCookies();
                    }
                }
                $updatedData = $this->request->getPost();
                $flag = 0;
                $favicon = false;
                $halfLogo = false;
                $logo = false;
                $partner_favicon = false;
                $partner_halfLogo = false;
                $partner_logo = false;
                $files = array();
                $data = get_settings('general_settings', true);
                if (!empty($_FILES['favicon'])) {
                    if ($_FILES['favicon']['name'] != "") {
                        if (!valid_image('favicon')) {
                            $flag = 1;
                        } else {
                            $favicon = true;
                        }
                    }
                }
                if (!empty($_FILES['halfLogo'])) {
                    if ($_FILES['halfLogo']['name'] != "") {
                        if (!valid_image('halfLogo')) {
                            $flag = 1;
                        } else {
                            $halfLogo = true;
                        }
                    }
                }
                if (!empty($_FILES['logo'])) {
                    if ($_FILES['logo']['name'] != "") {
                        if (!valid_image('logo')) {
                            $flag = 1;
                        } else {
                            $logo = true;
                        }
                    }
                }
                if (!empty($_FILES['logo'])) {
                    if ($_FILES['partner_favicon']['name'] != "") {
                        if (!valid_image('partner_favicon')) {
                            $flag = 1;
                        } else {
                            $partner_favicon = true;
                        }
                    }
                }
                if (!empty($_FILES['partner_halfLogo'])) {
                    if ($_FILES['partner_halfLogo']['name'] != "") {
                        if (!valid_image('partner_halfLogo')) {
                            $flag = 1;
                        } else {
                            $partner_halfLogo = true;
                        }
                    }
                }
                if (!empty($_FILES['partner_logo'])) {
                    if ($_FILES['partner_logo']['name'] != "") {
                        if (!valid_image('partner_logo')) {
                            $flag = 1;
                        } else {
                            $partner_logo = true;
                        }
                    }
                }
                if ($favicon) {
                    $file = $this->request->getFile('favicon');
                    $path = FCPATH . 'public/uploads/site/';
                    $image = $file->getName();
                    $newName = $file->getRandomName();
                    $file->move($path, $newName);
                    $updatedData['favicon'] = $newName;
                } else {
                    $updatedData['favicon'] = isset($data['favicon']) ? $data['favicon'] : "";
                }
                if ($logo) {
                    $file = $this->request->getFile('logo');
                    $path = FCPATH . 'public/uploads/site/';
                    $image = $file->getName();
                    $newName = $file->getRandomName();
                    $file->move($path, $newName);
                    $updatedData['logo'] = $newName;
                } else {
                    $updatedData['logo'] = isset($data['logo']) ? $data['logo'] : "";
                }
                if ($halfLogo) {
                    $file = $this->request->getFile('halfLogo');
                    $path = FCPATH . 'public/uploads/site/';
                    $image = $file->getName();
                    $newName = $file->getRandomName();
                    $file->move($path, $newName);
                    $updatedData['half_logo'] = $newName;
                } else {
                    $updatedData['half_logo'] = isset($data['half_logo']) ? $data['half_logo'] : "";
                }
                if ($partner_favicon) {
                    $file = $this->request->getFile('partner_favicon');
                    $path = FCPATH . 'public/uploads/site/';
                    $image = $file->getName();
                    $newName = $file->getRandomName();
                    $file->move($path, $newName);
                    $updatedData['partner_favicon'] = $newName;
                } else {
                    $updatedData['partner_favicon'] = isset($data['partner_favicon']) ? $data['partner_favicon'] : "";
                }
                if ($partner_logo) {
                    $file = $this->request->getFile('partner_logo');
                    $path = FCPATH . 'public/uploads/site/';
                    $image = $file->getName();
                    $newName = $file->getRandomName();
                    $file->move($path, $newName);
                    $updatedData['partner_logo'] = $newName;
                } else {
                    $updatedData['partner_logo'] = isset($data['partner_logo']) ? $data['partner_logo'] : "";
                }
                if ($partner_halfLogo) {
                    $file = $this->request->getFile('partner_halfLogo');
                    $path = FCPATH . 'public/uploads/site/';
                    $image = $file->getName();
                    $newName = $file->getRandomName();
                    $file->move($path, $newName);
                    $updatedData['partner_half_logo'] = $newName;
                } else {
                    $updatedData['partner_half_logo'] = isset($data['partner_half_logo']) ? $data['partner_half_logo'] : '';
                }
                unset($updatedData['update']);
                unset($updatedData[csrf_token()]);
                $updatedData['currency'] = (!empty($this->request->getPost('currency'))) ? $this->request->getPost('currency') : (isset($data['currency']) ? $data['currency'] : "");
                $updatedData['country_currency_code'] = (!empty($this->request->getPost('country_currency_code'))) ? $this->request->getPost('country_currency_code') : (isset($data['country_currency_code']) ? $data['country_currency_code'] : "");
                if ($this->request->getPost('decimal_point') == 0) {
                    $updatedData['decimal_point'] = "0";
                } elseif (!empty($this->request->getPost('decimal_point'))) {
                    $updatedData['decimal_point'] = $this->request->getPost('decimal_point');
                } else {
                    $updatedData['decimal_point'] = $data['decimal_point'];
                }
                $updatedData['customer_current_version_android_app'] = (!empty($this->request->getPost('customer_current_version_android_app'))) ? $this->request->getPost('customer_current_version_android_app') : (isset($data['customer_current_version_android_app']) ? $data['customer_current_version_android_app'] : "");
                $updatedData['customer_current_version_ios_app'] = (!empty($this->request->getPost('customer_current_version_ios_app'))) ? $this->request->getPost('customer_current_version_ios_app') : (isset($data['customer_current_version_ios_app']) ? $data['customer_current_version_ios_app'] : "");
                $updatedData['provider_current_version_android_app'] = (!empty($this->request->getPost('provider_current_version_android_app'))) ? $this->request->getPost('provider_current_version_android_app') : (isset($data['provider_current_version_android_app']) ? $data['provider_current_version_android_app'] : "");
                $updatedData['provider_current_version_ios_app'] = (!empty($this->request->getPost('provider_current_version_ios_app'))) ? $this->request->getPost('provider_current_version_ios_app') : (isset($data['provider_current_version_ios_app']) ? $data['provider_current_version_ios_app'] : "");
                $updatedData['customer_app_maintenance_schedule_date'] = (!empty($this->request->getPost('customer_app_maintenance_schedule_date'))) ? $this->request->getPost('customer_app_maintenance_schedule_date') : (isset($data['customer_app_maintenance_schedule_date']) ? $data['customer_app_maintenance_schedule_date'] : "");
                $updatedData['message_for_customer_application'] = (!empty($this->request->getPost('message_for_customer_application'))) ? $this->request->getPost('message_for_customer_application') : (isset($data['message_for_customer_application']) ? $data['message_for_customer_application'] : "");
                $updatedData['provider_app_maintenance_schedule_date'] = (!empty($this->request->getPost('provider_app_maintenance_schedule_date'))) ? ($this->request->getPost('provider_app_maintenance_schedule_date')) : (isset($data['provider_app_maintenance_schedule_date']) ? $data['provider_app_maintenance_schedule_date'] : "");
                $updatedData['message_for_provider_application'] = (!empty($this->request->getPost('message_for_provider_application'))) ? $this->request->getPost('message_for_provider_application') : (isset($data['message_for_provider_application']) ? $data['message_for_provider_application'] : "");
                if ($this->request->getPost('customer_compulsary_update_force_update') == 0) {
                    $updatedData['customer_compulsary_update_force_update'] = "0";
                } elseif (!empty($this->request->getPost('customer_compulsary_update_force_update'))) {
                    $updatedData['customer_compulsary_update_force_update'] = $this->request->getPost('customer_compulsary_update_force_update');
                } else {
                    $updatedData['customer_compulsary_update_force_update'] = $data['customer_compulsary_update_force_update'];
                }
                if ($this->request->getPost('provider_compulsary_update_force_update') == 0) {
                    $updatedData['provider_compulsary_update_force_update'] = "0";
                } elseif (!empty($this->request->getPost('provider_compulsary_update_force_update'))) {
                    $updatedData['provider_compulsary_update_force_update'] = $this->request->getPost('provider_compulsary_update_force_update');
                } else {
                    $updatedData['provider_compulsary_update_force_update'] = $data['provider_compulsary_update_force_update'];
                }
                if ($this->request->getPost('provider_location_in_provider_details') == 0) {
                    $updatedData['provider_location_in_provider_details'] = "0";
                } elseif (!empty($this->request->getPost('provider_location_in_provider_details'))) {
                    $updatedData['provider_location_in_provider_details'] = $this->request->getPost('provider_location_in_provider_details');
                } else {
                    $updatedData['provider_location_in_provider_details'] = $data['provider_location_in_provider_details'];
                }
                if ($this->request->getPost('provider_app_maintenance_mode') == 0) {
                    $updatedData['provider_app_maintenance_mode'] = "0";
                } elseif (!empty($this->request->getPost('provider_app_maintenance_mode'))) {
                    $updatedData['provider_app_maintenance_mode'] = $this->request->getPost('provider_app_maintenance_mode');
                } else {
                    $updatedData['provider_app_maintenance_mode'] = $data['provider_app_maintenance_mode'];
                }
                if ($this->request->getPost('customer_app_maintenance_mode') == 0) {
                    $updatedData['customer_app_maintenance_mode'] = "0";
                } elseif (!empty($this->request->getPost('customer_app_maintenance_mode'))) {
                    $updatedData['customer_app_maintenance_mode'] = $this->request->getPost('customer_app_maintenance_mode');
                } else {
                    $updatedData['customer_app_maintenance_mode'] = $data['customer_app_maintenance_mode'];
                }
                if ($this->request->getPost('android_google_ads_status') == 0) {
                    $updatedData['android_google_ads_status'] = "0";
                } elseif (!empty($this->request->getPost('android_google_ads_status'))) {
                    $updatedData['android_google_ads_status'] = $this->request->getPost('android_google_ads_status');
                } else {
                    $updatedData['android_google_ads_status'] = $data['android_google_ads_status'];
                }
                if ($this->request->getPost('ios_google_ads_status') == 0) {
                    $updatedData['ios_google_ads_status'] = "0";
                } elseif (!empty($this->request->getPost('ios_google_ads_status'))) {
                    $updatedData['ios_google_ads_status'] = $this->request->getPost('ios_google_ads_status');
                } else {
                    $updatedData['ios_google_ads_status'] = $data['ios_google_ads_status'];
                }
                $keys = [
                    'customer_current_version_android_app',
                    'customer_current_version_ios_app',
                    'provider_current_version_android_app',
                    'provider_current_version_ios_app',
                    'customer_app_maintenance_schedule_date',
                    'message_for_customer_application',
                    'customer_app_maintenance_mode',
                    'provider_app_maintenance_schedule_date',
                    'message_for_provider_application',
                    'provider_app_maintenance_mode',
                    'company_title',
                    'support_name',
                    'support_email',
                    'phone',
                    'system_timezone_gmt',
                    'system_timezone',
                    'primary_color',
                    'secondary_color',
                    'primary_shadow',
                    'max_serviceable_distance',
                    'distance_unit',
                    'address',
                    'short_description',
                    'copyright_details',
                    'booking_auto_cancle_duration',
                    'customer_playstore_url',
                    'customer_appstore_url',
                    'provider_playstore_url',
                    'provider_appstore_url',
                    'maxFilesOrImagesInOneMessage',
                    'maxFileSizeInMBCanBeSent',
                    'maxCharactersInATextMessage',
                    'android_google_interstitial_id',
                    'android_google_banner_id',
                    'ios_google_interstitial_id',
                    'ios_google_banner_id',
                    'otp_system',
                    'authentication_mode',
                    'company_map_location',
                    'support_hours',
                    'allow_pre_booking_chat',
                    'allow_post_booking_chat'
                ];
                foreach ($keys as $key) {
                    $updatedData[$key] = (!empty($this->request->getPost($key))) ? $this->request->getPost($key) : (isset($data[$key]) ? ($data[$key]) : "");
                }
                if ($this->request->getPost('image_compression_preference') == 0) {
                    $updatedData['image_compression_preference'] = "0";
                    $updatedData['image_compression_quality'] = "0";
                } elseif (!empty($this->request->getPost('image_compression_preference'))) {
                    $updatedData['image_compression_preference'] = $this->request->getPost('image_compression_preference');
                } else {
                    $updatedData['image_compression_preference'] = $data['image_compression_preference'];
                }
                if (!empty($updatedData['system_timezone_gmt'])) {
                    if ($updatedData['system_timezone_gmt'] == " 00:00") {
                        $updatedData['system_timezone_gmt'] = '+' . trim($updatedData['system_timezone_gmt']);
                    }
                }
                unset($updatedData['update']);
                unset($updatedData[csrf_token()]);
                $json_string = json_encode($updatedData);
                if ($this->update_setting('general_settings', $json_string)) {
                    $_SESSION['toastMessage']  = 'Unable to update the App settings.';
                    $_SESSION['toastMessageType']  = 'error';
                } else {
                    $_SESSION['toastMessage'] = 'App settings has been successfuly updated.';
                    $_SESSION['toastMessageType']  = 'success';
                }
                $this->session->markAsFlashdata('toastMessage');
                $this->session->markAsFlashdata('toastMessageType');
                return redirect()->to('admin/settings/app')->withCookies();
            }
            $this->builder->select('value');
            $this->builder->where('variable', 'general_settings');
            $query = $this->builder->get()->getResultArray();
            if (count($query) == 1) {
                $settings = $query[0]['value'];
                $settings = json_decode($settings, true);
                if (!empty($settings)) {
                    $this->data = array_merge($this->data, $settings);
                }
            }
            $this->data['timezones'] = get_timezone_array();
            setPageInfo($this->data, 'App Settings | Admin Panel', 'app');
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - app_settings()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
        if (!$this->isLoggedIn || !$this->userIsAdmin) {
            return redirect('admin/login');
        }
    }
    public function firebase_settings()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            if ($this->request->getPost('update')) {
                if ($this->superadmin == "superadmin@gmail.com") {
                    defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1;
                } else {
                    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                        $_SESSION['toastMessage'] = DEMO_MODE_ERROR;
                        $_SESSION['toastMessageType']  = 'error';
                        $this->session->markAsFlashdata('toastMessage');
                        $this->session->markAsFlashdata('toastMessageType');
                        return redirect()->to('admin/settings/general-settings')->withCookies();
                    }
                }
                $updatedData = $this->request->getPost();
                unset($updatedData[csrf_token()]);
                unset($updatedData['update']);
                $json_file = false;
                $flag = 0;
                if (!empty($_FILES['json_file'])) {
                    if ($_FILES['json_file']['name'] != "") {
                        if (!valid_image('json_file')) {
                            $flag = 1;
                        } else {
                            $json_file = true;
                        }
                    }
                }
                if ($json_file) {
                    $file = $this->request->getFile('json_file');
                    $path = FCPATH . 'public/';
                    $newName = "firebase_config.json";


                    // Check if the file exists and delete it
                    if (file_exists($path . $newName)) {
                        unlink($path . $newName);
                    }

                    $file->move($path, $newName);
                    $updatedData['json_file'] = $newName;
                } else {
                    $updatedData['json_file'] = isset($data['json_file']) ? $data['json_file'] : "";
                }
                $json_string = json_encode($updatedData);
                if ($this->update_setting('firebase_settings', $json_string)) {
                    $_SESSION['toastMessage']  = 'Unable to update Firebase section.';
                    $_SESSION['toastMessageType']  = 'error';
                } else {
                    $_SESSION['toastMessage'] = ' Firebase has been successfuly updated.';
                    $_SESSION['toastMessageType']  = 'success';
                }
                $this->session->markAsFlashdata('toastMessage');
                $this->session->markAsFlashdata('toastMessageType');
                return redirect()->to('admin/settings/firebase_settings')->withCookies();
            }
            $this->builder->select('value');
            $this->builder->where('variable', 'firebase_settings');
            $query = $this->builder->get()->getResultArray();
            if (count($query) == 1) {
                $settings = $query[0]['value'];
                $settings = json_decode($settings, true);
                $this->data = array_merge($this->data, $settings);
            }
            setPageInfo($this->data, 'Firebase Settings | Admin Panel', 'firebase_settings');
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - firebase_settings()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function web_setting_page()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            setPageInfo($this->data, 'Web Settings | Admin Panel', 'web_settings');
            $this->builder->select('value');
            $this->builder->where('variable', 'web_settings');
            $query = $this->builder->get()->getResultArray();
            if (count($query) == 1) {
                $settings = $query[0]['value'];
                $settings = json_decode($settings, true);
                $this->data = array_merge($this->data, $settings);
            }
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - web_setting_page()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function web_setting_update()
    {
        try {
            $social_media = [];
            $updatedData['social_media'] = ($social_media);
            $updatedData['web_title'] = $_POST['web_title'];
            $updatedData['playstore_url'] = $_POST['playstore_url'];
            $updatedData['app_section_status'] = isset($_POST['app_section_status']) ? 1 : 0;
            $updatedData['applestore_url'] = $_POST['applestore_url'];
            if ($this->isLoggedIn && $this->userIsAdmin) {
                if ($this->request->getPost('update')) {
                    $old_settings = get_settings('web_settings', true);
                    $old_data = [
                        'category_section_title',
                        'category_section_description',
                        'rating_section_title',
                        'rating_section_description',
                        'faq_section_title',
                        'faq_section_description',
                        'landing_page_logo',
                        'landing_page_backgroud_image',
                        'rating_section_status',
                        'faq_section_status',
                        'category_section_status',
                        'category_ids',
                        'rating_ids',
                        'landing_page_title',
                        'process_flow_title',
                        'process_flow_description',
                        'footer_description',
                        'step_1_title',
                        'step_2_title',
                        'step_3_title',
                        'step_4_title',
                        'step_1_description',
                        'step_2_description',
                        'step_3_description',
                        'step_4_description',
                        'step_1_image',
                        'step_2_image',
                        'step_3_image',
                        'step_4_image',
                        'process_flow_status'
                    ];
                    foreach ($old_data as $key) {
                        $updatedData[$key] = (!empty($this->request->getPost($key))) ? $this->request->getPost($key) : (isset($old_settings[$key]) ? ($old_settings[$key]) : "");
                    }
                    if ($this->superadmin == "superadmin@gmail.com") {
                        defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1;
                    } else {
                        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                            $_SESSION['toastMessage'] = DEMO_MODE_ERROR;
                            $_SESSION['toastMessageType']  = 'error';
                            $this->session->markAsFlashdata('toastMessage');
                            $this->session->markAsFlashdata('toastMessageType');
                            return redirect()->to('admin/settings/general-settings')->withCookies();
                        }
                    }
                    $data = get_settings('web_settings', true);
                    $files_to_check = array(
                        'web_logo',
                        'web_favicon',
                        'web_half_logo',
                        'footer_logo',
                    );
                    $path = FCPATH . 'public/uploads/web_settings/';
                    foreach ($files_to_check as $row) {
                        if (!empty($_FILES[$row]['name'])) {
                            if (!valid_image($row)) {
                                $flag = 1;
                            } else {
                                $file = $this->request->getFile($row);
                                $newName = $file->getRandomName();
                                $tempPath = $_FILES[$row]['tmp_name'];
                                compressImage($tempPath, 'public/uploads/web_settings/' . $newName, 70);
                                $updatedData[$row] = $newName;
                            }
                        } else {
                            $updatedData[$row] = isset($data[$row]) ? $data[$row] : "";
                        }
                    }
                    $updatedSocialMedia = [];
                    if (!empty($data['social_media'])) {
                        $updatedSocialMedia = [];
                    }
                    $updatedData1 = [];
                    foreach ($_POST['social_media'] as $i => $item) {
                        if (($item['exist_url'] == 'new') && ($item['exist_file'] == 'new')) {
                            $path = FCPATH . 'public/uploads/web_settings/';
                            $newName = $_FILES['social_media']['name'][$i]['file'];
                            $fileFullPath = $path . $newName;
                            if (move_uploaded_file($_FILES['social_media']['tmp_name'][$i]['file'], $fileFullPath)) {
                                $updatedSocialMedia[] = [
                                    'url' => $item['url'],
                                    'file' => $newName
                                ];
                            }
                        } else {
                            if ($item['exist_url'] != $item['url'] || !empty($_FILES['social_media']['name'][$i]['file'])) {
                                $updatedData1['url'] = $item['url'];
                            } else {
                                $updatedData1['url'] = $item['exist_url'];
                            }
                            if (!empty($_FILES['social_media']['name'][$i]['file'])) {
                                $path = FCPATH . 'public/uploads/web_settings/';
                                $newName = $_FILES['social_media']['name'][$i]['file'];
                                $fileFullPath = $path . $newName;
                                if ($_FILES['social_media']['name'][$i]['file'] != $item['exist_file']) {
                                    compressImage($_FILES['social_media']['tmp_name'][$i]['file'], 'public/uploads/web_settings/' . $newName, 70);
                                    $updatedData[$row] = $newName;
                                    $updatedData1['file'] = $newName;
                                } else {
                                    $updatedData1['file'] = $item['exist_file'];
                                }
                            } else {
                                $updatedData1['file'] = $item['exist_file'];
                            }
                            $updatedSocialMedia[] = $updatedData1;
                        }
                    }
                    $updatedData['social_media'] = $updatedSocialMedia;
                    unset($updatedData[csrf_token()]);
                    unset($updatedData['update']);
                    $json_string = json_encode($updatedData);
                    if ($this->update_setting('web_settings', $json_string)) {
                        $_SESSION['toastMessage']  = 'Unable to update Web Settings.';
                        $_SESSION['toastMessageType']  = 'error';
                    } else {
                        $_SESSION['toastMessage'] = ' Web Settings has been successfuly updated.';
                        $_SESSION['toastMessageType']  = 'success';
                    }
                    $this->session->markAsFlashdata('toastMessage');
                    $this->session->markAsFlashdata('toastMessageType');
                    return redirect()->to('admin/settings/web_setting')->withCookies();
                }
                $this->builder->select('value');
                $this->builder->where('variable', 'web_settings');
                $query = $this->builder->get()->getResultArray();
                if (count($query) == 1) {
                    $settings = $query[0]['value'];
                    $settings = json_decode($settings, true);
                    $this->data = array_merge($this->data, $settings);
                }
                setPageInfo($this->data, 'Web Settings | Admin Panel', 'web_settings');
                return view('backend/admin/template', $this->data);
            } else {
                return redirect('admin/login');
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - web_setting_page()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function contry_codes()
    {
        setPageInfo($this->data, 'Country Code Settings  | Admin Panel', 'country_code');
        return view('backend/admin/template', $this->data);
    }
    public function add_contry_code()
    {
        try {
            $result = checkModificationInDemoMode($this->superadmin);
            if ($result !== true) {
                return $this->response->setJSON($result);
            }
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            $this->validation->setRules(
                [
                    'name' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please enter name"
                        ]
                    ],
                    'code' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please enter code"
                        ]
                    ],
                ],
            );
            if (!$this->validation->withRequest($this->request)->run()) {
                $errors  = $this->validation->getErrors();
                $response['error'] = true;
                $response['message'] = $errors;
                $response['csrfName'] = csrf_token();
                $response['csrfHash'] = csrf_hash();
                $response['data'] = [];
                return $this->response->setJSON($response);
            }
            $data['code'] = ($_POST['code']);
            $data['name'] = ($_POST['name']);
            $contry_code = new Country_code_model();
            if ($contry_code->save($data)) {
                $response = [
                    'error' => false,
                    'message' => "Country code added successfully",
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                    'data' => []
                ];
                return json_encode($response);
            } else {
                $response = [
                    'error' => true,
                    'message' => "please try again....",
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                    'data' => []
                ];
                return json_encode($response);
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - add_contry_code()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function fetch_contry_code()
    {
        $limit = (isset($_GET['limit']) && !empty($_GET['limit'])) ? $_GET['limit'] : 10;
        $offset = (isset($_GET['offset']) && !empty($_GET['offset'])) ? $_GET['offset'] : 0;
        $sort = (isset($_GET['sort']) && !empty($_GET['sort'])) ? $_GET['sort'] : 'id';
        $order = (isset($_GET['order']) && !empty($_GET['order'])) ? $_GET['order'] : 'ASC';
        $search = (isset($_GET['search']) && !empty($_GET['search'])) ? $_GET['search'] : '';
        $where = [];
        $from_app = false;
        $contry_code = new Country_code_model();
        $data = $contry_code->list($from_app, $search, $limit, $offset, $sort, $order, $where);
        return $data;
    }
    public function delete_contry_code()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                $response['error'] = true;
                $response['message'] = DEMO_MODE_ERROR;
                $response['csrfName'] = csrf_token();
                $response['csrfHash'] = csrf_hash();
                return $this->response->setJSON($response);
            }
            $db = \Config\Database::connect();
            $id = $this->request->getVar('id');
            $builder = $db->table('country_codes');
            $builder->where('id', $id);
            $data = fetch_details("country_codes", ['id' => $id]);
            $settings = fetch_details('country_codes', ['is_default' => 1]);
            if ($settings[0]['id'] ==  $id) {
                $response = [
                    'error' => true,
                    'message' => 'Default country code cannot be removed.',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
            if ($builder->delete()) {
                $response = [
                    'error' => false,
                    'message' => 'Country code Removed successfully.',
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - delete_contry_code()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function store_default_country_code()
    {
        try {
            $settings = fetch_details('country_codes', ['is_default' => 1]);
            if (!empty($settings)) {
                $country_codes = fetch_details('country_codes', ['is_default' => 1]);
                $Country_code_model = new Country_code_model();
                $data['is_default'] = 0;
                $Country_code_model->update($country_codes[0]['id'], $data);
                $data2['is_default'] = 1;
                $Country_code_model2 = new Country_code_model();
                $Country_code_model2->update($_POST['id'], $data2);
            }
            $response = [
                'error' => false,
                'message' => 'Default setted.',
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash(),
                'data' => []
            ];
            return $this->response->setJSON($response);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - store_default_country_code()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function update_country_codes()
    {
        try {
            $result = checkModificationInDemoMode($this->superadmin);
            if ($result !== true) {
                return $this->response->setJSON($result);
            }
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            $this->validation->setRules(
                [
                    'name' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please enter name"
                        ]
                    ],
                    'code' => [
                        "rules" => 'required',
                        "errors" => [
                            "required" => "Please enter code"
                        ]
                    ],
                ],
            );
            if (!$this->validation->withRequest($this->request)->run()) {
                $errors  = $this->validation->getErrors();
                $response['error'] = true;
                $response['message'] = $errors;
                $response['csrfName'] = csrf_token();
                $response['csrfHash'] = csrf_hash();
                $response['data'] = [];
                return $this->response->setJSON($response);
            }
            $data['code'] = ($_POST['code']);
            $data['name'] = ($_POST['name']);
            $contry_code = new Country_code_model();
            if ($contry_code->update($_POST['id'], $data)) {
                $response = [
                    'error' => false,
                    'message' => "Country code updated successfully",
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                    'data' => []
                ];
                return json_encode($response);
            } else {
                $response = [
                    'error' => true,
                    'message' => "please try again....",
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                    'data' => []
                ];
                return json_encode($response);
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - update_country_codes()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function about_us_page_preview()
    {
        $settings = get_settings('general_settings', true);
        $this->data['title'] = 'About Us | ' . $settings['company_title'];
        $this->data['meta_description'] = 'About Us | ' . $settings['company_title'];
        $this->data['about_us'] = get_settings('about_us', true);
        $this->data['russian_about_us'] = get_settings('russian_about_us', true);
        $this->data['estonian_about_us'] = get_settings('estonian_about_us', true);
        $this->data['settings'] =  $settings;
        return view('backend/admin/pages/about_us_preview', $this->data);
    }
    public function contact_us_page_preview()
    {
        $settings = get_settings('general_settings', true);
        $this->data['title'] = 'Contact Us | ' . $settings['company_title'];
        $this->data['meta_description'] = 'Contact Us | ' . $settings['company_title'];
        $this->data['contact_us'] = get_settings('contact_us', true);
        $this->data['settings'] =  $settings;
        return view('backend/admin/pages/contact_us_preview', $this->data);
    }
    public function email_template_configuration()
    {
        if (!$this->isLoggedIn && !$this->userIsPartner) {
            return redirect('unauthorised');
        }
        setPageInfo($this->data, 'Email Configuration  | Admin Panel', 'email_template_configuration');
        return view('backend/admin/template', $this->data);
    }
    public function email_template_configuration_update()
    {
        try {
            $validationRules = [
                'subject' => [
                    "rules" => 'required',
                    "errors" => [
                        "required" => "Please enter Subject"
                    ]
                ],
                'email_type' => [
                    "rules" => 'required',
                    "errors" => [
                        "required" => "Please select type"
                    ]
                ],
                'template' => [
                    "rules" => 'required',
                    "errors" => [
                        "required" => "Please select Template"
                    ]
                ],
            ];
            if (!$this->validate($validationRules)) {
                $errors = $this->validator->getErrors();
                return ErrorResponse($errors, true, [], [], 200, csrf_token(), csrf_hash());
            }
            $updatedData = $this->request->getPost('template');
            $email_type = $this->request->getPost('email_type');
            $subject = $this->request->getPost('subject');
            $email_to = $this->request->getPost('email_to');
            $bcc = $this->request->getPost('bcc');
            $cc = $this->request->getPost('cc');
            $template = htmlspecialchars($updatedData);
            $parameters = extractVariables($updatedData);
            $data['type'] = $email_type;
            $data['subject'] = $subject;
            $data['to'] = json_encode($email_to);
            $data['template'] = $template;
            $data['bcc'] = $bcc;
            $data['cc'] = $cc;
            $data['parameters'] = json_encode($parameters);
            $insert = insert_details($data, 'email_templates');
            if ($insert) {
                $response = [
                    'error' => false,
                    'message' => "Template Saved successfully !",
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                    'data' => []
                ];
            } else {
                $response = [
                    'error' => true,
                    'message' => "Something went wrong....",
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                    'data' => []
                ];
            }
            return json_encode($response);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - email_template_configuration_update()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function email_template_list()
    {
        if (!$this->isLoggedIn && !$this->userIsPartner) {
            return redirect('unauthorised');
        }
        setPageInfo($this->data, 'Email Templates | Admin Panel', 'email_template_list');
        return view('backend/admin/template', $this->data);
    }
    public function email_template_list_fetch()
    {
        $limit = (isset($_GET['limit']) && !empty($_GET['limit'])) ? $_GET['limit'] : 10;
        $offset = (isset($_GET['offset']) && !empty($_GET['offset'])) ? $_GET['offset'] : 0;
        $sort = (isset($_GET['sort']) && !empty($_GET['sort'])) ? $_GET['sort'] : 'id';
        $order = (isset($_GET['order']) && !empty($_GET['order'])) ? $_GET['order'] : 'ASC';
        $search = (isset($_GET['search']) && !empty($_GET['search'])) ? $_GET['search'] : '';
        $where = [];
        $from_app = false;
        $email_templates = new Email_template_model();
        $data = $email_templates->list($from_app, $search, $limit, $offset, $sort, $order, $where);
        return $data;
    }
    public function edit_email_template()
    {
        if (!$this->isLoggedIn && !$this->userIsPartner) {
            return redirect('unauthorised');
        }
        helper('function');
        $uri = service('uri');
        $template_id = $uri->getSegments()[3];
        $templates = fetch_details('email_templates', ['id' => $template_id])[0];
        $this->data['template'] =  $templates;
        setPageInfo($this->data, 'Email Templates | Admin Panel', 'email_template_edit');
        return view('backend/admin/template', $this->data);
    }
    public function edit_email_template_operation()
    {
        try {
            $validationRules = [
                'subject' => [
                    "rules" => 'required',
                    "errors" => [
                        "required" => "Please enter Subject"
                    ]
                ],
                'email_type' => [
                    "rules" => 'required',
                    "errors" => [
                        "required" => "Please select type"
                    ]
                ],
                'template' => [
                    "rules" => 'required',
                    "errors" => [
                        "required" => "Please select Template"
                    ]
                ],
            ];
            if (!$this->validate($validationRules)) {
                $errors = $this->validator->getErrors();
                return ErrorResponse($errors, true, [], [], 200, csrf_token(), csrf_hash());
            }
            $updatedData = $this->request->getPost('template');
            $email_type = $this->request->getPost('email_type');
            $subject = $this->request->getPost('subject');
            $email_to = $this->request->getPost('email_to');
            $bcc = $this->request->getPost('bcc');
            $cc = $this->request->getPost('cc');
            $template = ($updatedData);
            $parameters = extractVariables($updatedData);
            $data['type'] = $email_type;
            $data['subject'] = $subject;
            $data['to'] = json_encode($email_to);
            $data['template'] = $this->request->getPost('template');;
            if (isset($_POST['bcc'][0]) && !empty($_POST['bcc'][0])) {
                $base_tags = $this->request->getPost('bcc');
                $s_t = $base_tags;
                $val = explode(',', str_replace(']', '', str_replace('[', '', $s_t[0])));
                $bcc = [];
                foreach ($val as $s) {
                    $bcc[] = json_decode($s, true)['value'];
                }
                $data['bcc'] = implode(',', $bcc);
            }
            if (isset($_POST['cc'][0]) && !empty($_POST['cc'][0])) {
                $base_tags = $this->request->getPost('cc');
                $s_t = $base_tags;
                $val = explode(',', str_replace(']', '', str_replace('[', '', $s_t[0])));
                $cc = [];
                foreach ($val as $s) {
                    $cc[] = json_decode($s, true)['value'];
                }
                $data['cc'] = implode(',', $cc);
            }
            $data['parameters'] = json_encode($parameters);
            $update = update_details($data, ['id' => $_POST['template_id']], 'email_templates', false);
            if ($update) {
                $response = [
                    'error' => false,
                    'message' => "Template updated successfully !",
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                    'data' => []
                ];
            } else {
                $response = [
                    'error' => true,
                    'message' => "Something went wrong....",
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                    'data' => []
                ];
            }
            return json_encode($response);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - edit_email_template_operation()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function delete_email_template()
    {
        try {
            if ($this->superadmin == "superadmin@gmail.com") {
                defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1;
            } else {
                if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                    $_SESSION['toastMessage'] = DEMO_MODE_ERROR;
                    $_SESSION['toastMessageType']  = 'error';
                    $this->session->markAsFlashdata('toastMessage');
                    $this->session->markAsFlashdata('toastMessageType');
                    return redirect()->to('admin/settings/general-settings')->withCookies();
                }
            }
            $creator_id = $this->userId;
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            $id = $this->request->getPost('id');
            $db = \Config\Database::connect();
            $builder = $db->table('email_templates');
            if ($builder->delete(['id' => $id])) {
                return successResponse("Email template deleted successfully", false, [], [], 200, csrf_token(), csrf_hash());
            }
            return ErrorResponse("An error occured during deleting this item", true, [], [], 200, csrf_token(), csrf_hash());
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - delete_email_template()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function sms_gateway_setting_index()
    {
        if (!$this->isLoggedIn) {
            return redirect('unauthorised');
        }
        $this->builder->select('value');
        $this->builder->where('variable', 'sms_gateway_setting');
        $query = $this->builder->get()->getResultArray();
        if (count($query) == 1) {
            $settings = $query[0]['value'];
            $settings = json_decode($settings, true);
            if (!empty($settings)) {
                $this->data = array_merge($this->data, $settings);
            }
        }
        setPageInfo($this->data, 'SMS Gateway settings | Admin Panel', 'sms_gateways');
        return view('backend/admin/template', $this->data);
    }
    public function sms_gateway_setting_update()
    {
        if ($this->superadmin == "superadmin@gmail.com") {
            defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1;
        } else {
            if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                $_SESSION['toastMessage'] = DEMO_MODE_ERROR;
                $_SESSION['toastMessageType']  = 'error';
                $this->session->markAsFlashdata('toastMessage');
                $this->session->markAsFlashdata('toastMessageType');
                return redirect()->to('admin/settings/sms-gateways')->withCookies();
            }
        }
        $smsgateway_data = array();
        $smsgateway_data['twilio_endpoint'] = isset($_POST['twilio_endpoint']) ? $_POST['twilio_endpoint'] : '';
        $smsgateway_data['sms_gateway_method'] = isset($_POST['sms_gateway_method']) ? $_POST['sms_gateway_method'] : 'POST';
        $smsgateway_data['country_code_include'] = isset($_POST['country_code_include']) ? $_POST['country_code_include'] : '0';
        $smsgateway_data['header_key'] = isset($_POST['header_key']) && !empty($_POST['header_key']) ? $_POST['header_key'] : '';
        $smsgateway_data['header_value'] = isset($_POST['header_value']) && !empty($_POST['header_value']) ? $_POST['header_value'] : '';
        $smsgateway_data['params_key'] = isset($_POST['params_key']) && !empty($_POST['params_key']) ? $_POST['params_key'] : '';
        $smsgateway_data['params_value'] = isset($_POST['params_value']) && !empty($_POST['params_value']) ? $_POST['params_value'] : '';
        $smsgateway_data['body_key'] = isset($_POST['body_key']) && !empty($_POST['body_key']) ? $_POST['body_key'] : '';
        $smsgateway_data['body_value'] = isset($_POST['body_value']) && !empty($_POST['body_value']) ? $_POST['body_value'] : '';
        $smsgateway_data = json_encode($smsgateway_data);
        $this->update_setting('sms_gateway_setting', $smsgateway_data);
     
        if ($this->update_setting('sms_gateway_setting', $smsgateway_data)) {
            $_SESSION['toastMessage']  = 'Unable to update the SMS Gateway settings.';
            $_SESSION['toastMessageType']  = 'error';
        } else {
            $_SESSION['toastMessage'] = 'SMS Gateway settings has been successfuly updated.';
            $_SESSION['toastMessageType']  = 'success';
        }
        $this->session->markAsFlashdata('toastMessage');
        $this->session->markAsFlashdata('toastMessageType');
        return redirect()->to('admin/settings/sms-gateways')->withCookies();
    }
    public function sms_templates()
    {
        try {
            $validationRules = [
                'title' => [
                    "rules" => 'required',
                    "errors" => [
                        "required" => "Please enter Title"
                    ]
                ],
                'type' => [
                    "rules" => 'required',
                    "errors" => [
                        "required" => "Please select type"
                    ]
                ],
                'template' => [
                    "rules" => 'required',
                    "errors" => [
                        "required" => "Please select Template"
                    ]
                ],
            ];
            if (!$this->validate($validationRules)) {
                $errors = $this->validator->getErrors();
                return ErrorResponse($errors, true, [], [], 200, csrf_token(), csrf_hash());
            }
            $updatedData = $this->request->getPost('template');
            $type = $this->request->getPost('type');
            $title = $this->request->getPost('title');
            $template = htmlspecialchars($updatedData);
            $parameters = extractVariables($updatedData);
            $data['type'] = $type;
            $data['title'] = $title;
            $data['template'] = $template;
            $data['parameters'] = json_encode($parameters);
            $insert = insert_details($data, 'sms_templates');
            if ($insert) {
                $response = [
                    'error' => false,
                    'message' => "Template Saved successfully !",
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                    'data' => []
                ];
            } else {
                $response = [
                    'error' => true,
                    'message' => "Something went wrong....",
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                    'data' => []
                ];
            }
            return json_encode($response);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - sms_templates()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function sms_template_list()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('sms_templates');
        $multipleWhere = [];
        $condition = $bulkData = $rows = $tempRow = [];
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
        $sort = ($_GET['sort'] ?? '') == 'id' ? 'id' : ($_GET['sort'] ?? 'id');
        $order = $_GET['order'] ?? 'DESC';
        $offset = $_GET['offset'] ?? '0';
        if (!empty($search)) {
            $multipleWhere = [
                'id' => $search,
                'type' => $search,
            ];
        }
        if (!empty($where)) {
            $builder->where($where);
        }
        if (!empty($multipleWhere)) {
            $builder->groupStart()->orLike($multipleWhere)->groupEnd();
        }
        $total = $builder->countAllResults(false);
        $template_record = $builder->select('*')
            ->orderBy($sort, $order)
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
        foreach ($template_record as $row) {
            $operations = '';
            $operations = '<div class="dropdown">
                    <a class="" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <button class="btn btn-secondary btn-sm px-3"> <i class="fas fa-ellipsis-v "></i></button>
                    </a>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">';
            $operations .= '<a class="dropdown-item" href="' . base_url('/admin/settings/edit_sms_template/' . $row['id']) . '"><i class="fa fa-pen mr-1 text-primary"></i> Edit SMS Template</a>';
            $operations .= '</div></div>';
            $tempRow['id'] = $row['id'];
            $tempRow['type'] = $row['type'];
            $tempRow['title'] = $row['title'];
            $tempRow['template'] = $row['template'];
            $tempRow['parameters'] =  substr($row['parameters'], 0, 30) . '...';
            $truncatedtemplate = substr($row['template'], 0, 30) . '...';
            $tempRow['truncatedtemplate'] = $truncatedtemplate;
            $tempRow['operations'] = $operations;
            $rows[] = $tempRow;
        }
        $bulkData['total'] = $total;
        $bulkData['rows'] = $rows;
        return json_encode($bulkData);
    }
    public function edit_sms_template()
    {
        if (!$this->isLoggedIn && !$this->userIsPartner) {
            return redirect('unauthorised');
        }
        helper('function');
        $uri = service('uri');
        $template_id = $uri->getSegments()[3];
        $templates = fetch_details('sms_templates', ['id' => $template_id]);
        if (empty($templates)) {
            $templates = fetch_details('sms_templates', [], [], [], 1, 0);
        }
        $this->data['template'] =  $templates[0];
        setPageInfo($this->data, 'SMS Templates | Admin Panel', 'edit_sms_template');
        return view('backend/admin/template', $this->data);
    }
    public function edit_sms_template_update()
    {
        try {
            $validationRules = [
                'title' => [
                    "rules" => 'required',
                    "errors" => [
                        "required" => "Please enter Title"
                    ]
                ],
                'type' => [
                    "rules" => 'required',
                    "errors" => [
                        "required" => "Please select type"
                    ]
                ],
                'template' => [
                    "rules" => 'required',
                    "errors" => [
                        "required" => "Please select Template"
                    ]
                ],
            ];
            if (!$this->validate($validationRules)) {
                $errors = $this->validator->getErrors();
                return ErrorResponse($errors, true, [], [], 200, csrf_token(), csrf_hash());
            }
            $updatedData = $this->request->getPost('template');
            $id = $this->request->getPost('template_id');
            $type = $this->request->getPost('type');
            $title = $this->request->getPost('title');
            $template = htmlspecialchars($updatedData);
            $parameters = extractVariables($updatedData);
            $data['type'] = $type;
            $data['title'] = $title;
            $data['template'] = $template;
            $data['parameters'] = json_encode($parameters);
            $update = update_details($data, ['id' => $id], 'sms_templates', false);
            if ($update) {
                $response = [
                    'error' => false,
                    'message' => "Template updated successfully !",
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                    'data' => []
                ];
            } else {
                $response = [
                    'error' => true,
                    'message' => "Something went wrong....",
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                    'data' => []
                ];
            }
            return json_encode($response);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - sms_templates()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function notification_settings()
    {
        if (!$this->isLoggedIn) {
            return redirect('unauthorised');
        }
        $current_settings = get_settings('notification_settings', true);
        $notification_settings = [
            'provider_approved',
            'provider_disapproved',
            'withdraw_request_approved',
            'withdraw_request_disapproved',
            'payment_settlement',
            'service_approved',
            'service_disapproved',
            'user_account_active',
            'user_account_deactive',
            'provider_update_information',
            'new_provider_registerd',
            'withdraw_request_received',
            'booking_status_updated',
            'new_booking_confirmation_to_customer',
            'new_booking_received_for_provider',
            'withdraw_request_send',
            'new_rating_given_by_customer',
            'rating_request_to_customer'
        ];
        $this->data['notification_settings'] = $notification_settings;
        $this->data['current_settings'] = $current_settings; // Include current settings
        setPageInfo($this->data, 'Notification settings | Admin Panel', 'notification_settings');
        return view('backend/admin/template', $this->data);
    }
    public function notification_setting_update()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            $updatedData = $this->request->getPost();
            unset($updatedData['update']);
            unset($updatedData[csrf_token()]);
            $json_string = json_encode($updatedData);
            if ($this->update_setting('notification_settings', $json_string)) {
                $_SESSION['toastMessage']  = 'Unable to update the Notification settings.';
                $_SESSION['toastMessageType']  = 'error';
            } else {
                $_SESSION['toastMessage'] = 'Notification settings has been successfuly updated.';
                $_SESSION['toastMessageType']  = 'success';
            }
            $this->session->markAsFlashdata('toastMessage');
            $this->session->markAsFlashdata('toastMessageType');
            return redirect()->to('admin/settings/notification-settings')->withCookies();
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - notification_setting_update()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function sms_email_preview()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            $uri = service('uri');
            $type = $uri->getSegments()[3];
            $email_template = fetch_details('email_templates', ['type' => $type]);
            $sms_template = fetch_details('sms_templates', ['type' => $type]);
            $this->data['email_template'] =  $email_template[0];
            $this->data['sms_template'] =  $sms_template[0];
            setPageInfo($this->data, 'Preview Of Templates | Admin Panel', 'sms_email_preview');
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - sms_email_preview()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function web_landing_page_settings()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            $this->builder->select('value');
            $this->builder->where('variable', 'web_settings');
            $query = $this->builder->get()->getResultArray();
            if (count($query) == 1) {
                $settings = $query[0]['value'];
                $settings = json_decode($settings, true);
                $this->data = array_merge($this->data, $settings);
            }
            $db = \Config\Database::connect();
            $builder = $db->table('services_ratings sr');
            $builder->select('sr.*,u.image as profile_image,u.username')
                ->join('users u', "(sr.user_id = u.id)")
                ->orderBy('id', 'DESC');
            $services_ratings = $builder->get()->getResultArray();
            foreach ($services_ratings as $key => $row) {
                $services_ratings[$key]['profile_image'] = base_url('public/backend/assets/profiles/' . $row['profile_image']);
            }
            $this->data['services_ratings'] = $services_ratings;
            // echo "<pre>";
            // print_r($this->data['services_ratings']);
            // die;
            $this->data['categories_name'] = fetch_details('categories', [], ['id', 'name']);
            setPageInfo($this->data, 'Web Landing Page Settings | Admin Panel', 'web_landing_page');
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - web_landing_page_settings()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function web_setting_landing_page_update()
    {
        try {
            $updatedData['category_section_title'] = $_POST['category_section_title'];
            $updatedData['category_section_description'] = $_POST['category_section_description'];
            $updatedData['rating_section_title'] = $_POST['rating_section_title'];
            $updatedData['rating_section_description'] = $_POST['rating_section_description'];
            $updatedData['faq_section_title'] = $_POST['faq_section_title'];
            $updatedData['faq_section_description'] = $_POST['faq_section_description'];
            $updatedData['rating_section_status'] = isset($_POST['rating_section_status']) ? 1 : 0;
            $updatedData['faq_section_status'] = isset($_POST['faq_section_status']) ? 1 : 0;
            $updatedData['category_section_status'] = isset($_POST['category_section_status']) ? 1 : 0;
            $updatedData['process_flow_status'] = isset($_POST['process_flow_status']) ? 1 : 0;
            $updatedData['landing_page_title'] = $_POST['landing_page_title'];
            $updatedData['process_flow_title'] = $_POST['process_flow_title'];
            $updatedData['process_flow_description'] = $_POST['process_flow_description'];
            $updatedData['footer_description'] = $_POST['footer_description'];
            $updatedData['step_1_title'] = $_POST['step_1_title'];
            $updatedData['step_2_title'] = $_POST['step_2_title'];
            $updatedData['step_3_title'] = $_POST['step_3_title'];
            $updatedData['step_4_title'] = $_POST['step_4_title'];
            $updatedData['step_1_description'] = $_POST['step_1_description'];
            $updatedData['step_2_description'] = $_POST['step_2_description'];
            $updatedData['step_3_description'] = $_POST['step_3_description'];
            $updatedData['step_4_description'] = $_POST['step_4_description'];
            $categories = $this->request->getPost('categories');
            if (!empty($categories)) {
                $category_ids =  !empty($categories) ? ($categories) : "";
            }
            $updatedData['category_ids'] = isset($category_ids) ? $category_ids : '';
            $ratings = $this->request->getPost('new_rating_ids');
            if (!empty($ratings)) {
                $rating_ids =  !empty($ratings) ? ($ratings) : "";
            }
            $updatedData['rating_ids'] = isset($rating_ids) ? $rating_ids : '';
            $old_settings = get_settings('web_settings', true);
            $old_data = [
                'social_media',
                'web_title',
                'playstore_url',
                'app_section_status',
                'applestore_url',
                'web_logo',
                'web_favicon',
                'web_half_logo',
                'footer_logo',
            ];
            foreach ($old_data as $key) {
                $updatedData[$key] = (!empty($this->request->getPost($key))) ? $this->request->getPost($key) : (isset($old_settings[$key]) ? ($old_settings[$key]) : "");
            }
            if ($this->isLoggedIn && $this->userIsAdmin) {
                if ($this->request->getPost('update')) {
                    if ($this->superadmin == "superadmin@gmail.com") {
                        defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1;
                    } else {
                        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                            $_SESSION['toastMessage'] = DEMO_MODE_ERROR;
                            $_SESSION['toastMessageType']  = 'error';
                            $this->session->markAsFlashdata('toastMessage');
                            $this->session->markAsFlashdata('toastMessageType');
                            return redirect()->to('admin/settings/general-settings')->withCookies();
                        }
                    }
                    $data = get_settings('web_settings', true);
                    $files_to_check = array(
                        'landing_page_logo',
                        'landing_page_backgroud_image',
                    );
                    $path = FCPATH . 'public/uploads/web_settings/';
                    foreach ($files_to_check as $row) {
                        if (!empty($_FILES[$row]['name'])) {
                            if (!valid_image($row)) {
                                $flag = 1;
                            } else {
                                $file = $this->request->getFile($row);
                                $newName = $file->getRandomName();
                                $tempPath = $_FILES[$row]['tmp_name'];
                                compressImage($tempPath, 'public/uploads/web_settings/' . $newName, 70);
                                $updatedData[$row] = $newName;
                            }
                        } else {
                            $updatedData[$row] = isset($data[$row]) ? $data[$row] : "";
                        }
                    }
                    $data = get_settings('web_settings', true);
                    $files_to_check = array(
                        'web_logo',
                        'web_favicon',
                        'web_half_logo',
                        'footer_logo',
                        'step_1_image',
                        'step_2_image',
                        'step_3_image',
                        'step_4_image',
                    );
                    foreach ($files_to_check as $row) {
                        if (!empty($_FILES[$row]['name'])) {
                            if (!valid_image($row)) {
                                $flag = 1;
                            } else {
                                $file = $this->request->getFile($row);
                                $newName = $file->getRandomName();
                                $tempPath = $_FILES[$row]['tmp_name'];
                                compressImage($tempPath, 'public/uploads/web_settings/' . $newName, 70);
                                $updatedData[$row] = $newName;
                            }
                        } else {
                            $updatedData[$row] = isset($data[$row]) ? $data[$row] : "";
                        }
                    }
                    unset($updatedData[csrf_token()]);
                    unset($updatedData['update']);
                    $json_string = json_encode($updatedData);
                    if ($this->update_setting('web_settings', $json_string)) {
                        $_SESSION['toastMessage']  = 'Unable to update Landing Page.';
                        $_SESSION['toastMessageType']  = 'error';
                    } else {
                        $_SESSION['toastMessage'] = ' Landing Page Settings has been successfuly updated.';
                        $_SESSION['toastMessageType']  = 'success';
                    }
                    $this->session->markAsFlashdata('toastMessage');
                    $this->session->markAsFlashdata('toastMessageType');
                    return redirect()->to('admin/settings/web-landing-page-settings')->withCookies();
                }
            } else {
                return redirect('admin/login');
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/Settings.php - web_setting_page()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function review_list()
    {
        $db      = \Config\Database::connect();
        $builder = $db->table('services_ratings sr');
        $builder->select('COUNT(sr.id) as total')
            ->join('users u', 'u.id = sr.user_id')
            ->join('services s', 's.id = sr.service_id');
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $builder->orLike($multipleWhere);
        }
        $ratings_total_count = $builder->get()->getResultArray();
        $total = $ratings_total_count[0]['total'];
        $builder->select(
            'sr.id, sr.rating, sr.comment, sr.created_at as rated_on, 
         u.image as profile_image, u.username as user_name, 
         s.title as service_name, s.user_id as partner_id'
        )
            ->join('users u', 'u.id = sr.user_id')
            ->join('services s', 's.id = sr.service_id');
        if (isset($_GET['rating_star_filter']) && $_GET['rating_star_filter'] != '') {
            $builder->where('sr.rating', $_GET['rating_star_filter']);
        }
        $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
        $rating_records = $builder->orderBy('sr.id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
        $rows = [];
        foreach ($rating_records as $row) {
            $tempRow = [
                'id' => $row['id'],
                'comment' => $row['comment'],
                'profile_image' => $row['profile_image'],
                'user_name' => $row['user_name'],
                'service_name' => $row['service_name'],
                'rated_on' => $row['rated_on'],
                'stars' => '<i class="fa-solid fa-star text-warning"></i> ' . $row['rating'],
                'partner_name' => fetch_details('users', ['id' => $row['partner_id']], ['username'])[0]['username'],
            ];
            $rows[] = $tempRow;
        }
        $bulkData = [
            'total' => $total,
            'rows' => $rows
        ];
        return $this->response->setJSON($bulkData);
    }
}
