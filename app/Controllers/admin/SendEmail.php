<?php

namespace App\Controllers\admin;

use App\Models\Email_model;

class SendEmail extends Admin
{
    public   $validation, $faqs, $creator_id;
    public function __construct()
    {
        parent::__construct();
        helper(['form', 'url']);
        $this->email = new Email_model();
        $this->validation = \Config\Services::validation();
        $this->creator_id = $this->userId;
        $this->superadmin = $this->session->get('email');
        helper('ResponceServices');
    }
    public function index()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsAdmin) {
                return redirect('admin/login');
            }
            $this->data['users'] = fetch_details('users', [], ['id', 'username']);
            $this->data['partners'] = fetch_details('partner_details', []);
            $db      = \Config\Database::connect();
            $builder = $db->table('users u');
            $builder->select('u.*,ug.group_id')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->where('ug.group_id', "2");
            if (isset($_GET['customer_filter']) && $_GET['customer_filter'] != '') {
                $builder->where('u.active',  $_GET['customer_filter']);
            }
            $customers = $builder->get()->getResultArray();
            $this->data['customers'] =   $customers;
            setPageInfo($this->data, 'Send Email | Admin Panel', 'send_emails');
            return view('backend/admin/template', $this->data);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/SendEmail.php - index()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    // public function send_email()
    // {
    //     try {
    //         $template = $this->request->getPost('template');
    //         $type = $this->request->getPost('email_user_type');
    //         $subject = $this->request->getPost('subject');
    //         $bcc = $this->request->getPost('bcc');
    //         $cc = $this->request->getPost('cc');
    //         $data['content'] = $template;
    //         $data['type'] = $type;
    //         $data['bcc'] = json_encode($bcc);
    //         $data['cc'] = json_encode($cc);
    //         $data['subject'] = $subject;
    //         $user_ids = [];
    //         $email_settings = \get_settings('email_settings', true);
    //         $company_settings = \get_settings('general_settings', true);
    //         $smtpUsername = $email_settings['smtpUsername'];
    //         $company_name = $company_settings['company_title'];
    //         $from_email = $smtpUsername;
    //         $from_name = $company_name;
    //         $rules = [
    //             'subject' => [
    //                 "rules" => 'required|trim',
    //                 "errors" => [
    //                     "required" => "Please enter subject"
    //                 ]
    //             ],
    //             'template' => [
    //                 "rules" => 'required|trim',
    //                 "errors" => [
    //                     "required" => "Please enter template content"
    //                 ]
    //             ],
    //         ];
    //         if ($type == "provider") {
    //             $user_ids = $this->request->getPost('provider_id');
    //             if (!is_array($user_ids) || empty($user_ids)) {
    //                 $this->validation->setError('provider_id', 'Please select provider');
    //             }
    //         } elseif ($type == "customer") {
    //             $user_ids = $this->request->getPost('customer_id');
    //             if (!is_array($user_ids) || empty($user_ids)) {
    //                 $this->validation->setError('customer_id', 'Please select customer');
    //             }
    //         }
    //         $this->validation->setRules($rules);
    //         if (!$this->validation->withRequest($this->request)->run()) {
    //             $errors  = $this->validation->getErrors();
    //             return ErrorResponse($errors, true, [], [], 200, csrf_token(), csrf_hash());
    //         }
    //         $users = fetch_details("users", [], ['email', 'id', 'username'], "", 0, 'id', "DESC", 'id', $user_ids);
    //         $email = \Config\Services::email();
    //         foreach ($users as $user) {
    //             $email->setTo($user['email']);
    //             $email->setFrom($from_email, $from_name);
    //             $email->setSubject($subject);
    //             $email->setMailType('html');
    //             if (isset($_POST['bcc'][0]) && !empty($_POST['bcc'][0])) {
    //                 $base_tags = $this->request->getPost('bcc');
    //                 $s_t = $base_tags;
    //                 $val = explode(',', str_replace(']', '', str_replace('[', '', $s_t[0])));
    //                 $bcc = [];
    //                 foreach ($val as $s) {
    //                     $bcc[] = json_decode($s, true)['value'];
    //                 }
    //                 $email->setBCC($bcc);
    //             }
    //             if (isset($_POST['cc'][0]) && !empty($_POST['cc'][0])) {
    //                 $base_tags = $this->request->getPost('cc');
    //                 $s_t = $base_tags;
    //                 $val = explode(',', str_replace(']', '', str_replace('[', '', $s_t[0])));
    //                 $cc = [];
    //                 foreach ($val as $s) {
    //                     $cc[] = json_decode($s, true)['value'];
    //                 }
    //                 $email->setCC($cc);
    //             }
    //             if (strpos($template, '[[unsubscribe_link]]') !== false) {
    //                 $encrypted = unsubscribe_link_user_encrypt($user['id'], $user['email']);
    //                 $template = str_replace("[[unsubscribe_link]]", base_url('unsubscribe_link/' . $encrypted), $template);
    //             }
    //             if (strpos($template, '[[user_id]]') !== false) {
    //                 $template = str_replace("[[user_id]]", $user['id'], $template);
    //             }
    //             if (strpos($template, '[[user_name]]') !== false) {
    //                 $template = str_replace("[[user_name]]", $user['username'], $template);
    //             }
    //             $settings = get_settings('general_settings', true);
    //             if (strpos($template, '[[company_name]]') !== false) {
    //                 $template = str_replace("[[company_name]]", $settings['company_title'], $template);
    //             }
    //             if (strpos($template, '[[site_url]]') !== false) {
    //                 $template = str_replace("[[site_url]]", base_url(), $template);
    //             }
    //             if (strpos($template, '[[company_contact_info]]') !== false) {
    //                 $contact_us = get_settings('contact_us', true);
    //                 $template = str_replace("[[company_contact_info]]", $contact_us['contact_us'], $template);
    //             }
    //             if (strpos($template, '[[company_logo]]') !== false) {
    //                 $logo_url = base_url("public/uploads/site/" . $settings['logo']);
    //                 $logoPath = "public/uploads/site/" . $settings['logo'];
    //                 if (file_exists($logoPath)) {
    //                     $email->attach($logoPath);
    //                     $cid = $email->setAttachmentCID(($logoPath));
    //                     $logo_img_tag = '<img src="cid:' . $cid . '" alt="Company Logo">';
    //                     $template = str_replace("[[company_logo]]", $logo_img_tag, $template);
    //                 } else {
    //                     $template = str_replace("[[company_logo]]", '', $template);
    //                 }
    //             }
    //             preg_match_all('/<img[^>]+src=["\'](.*?)["\'][^>]*>/i', $template, $matches);
    //             $imagePaths = $matches[1];
    //             foreach ($imagePaths as $imagePath) {
    //                 if (file_exists($imagePath)) {
    //                     $email->attach($imagePath);
    //                     $cid = $email->setAttachmentCID(basename($imagePath));
    //                     $template = str_replace($imagePath, "cid:$cid", $template);
    //                 }
    //             }
    //             $email->setMessage($template);
    //             if (!$email->send()) {
    //                 return  $email->printDebugger(['headers']);
    //             }
    //         }
    //         $data['user_id'] = json_encode($user_ids);
    //         insert_details($data,'emails');
    //         return successResponse("Email Sended successfully", false, [], [], 200, csrf_token(), csrf_hash());
    //     } catch (\Throwable $th) {
    //         log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/SendEmail.php - send_email()');
    //         return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
    //     }
    // }
    public function send_email()
    {
        try {
            // Load email configuration
            $email_settings = get_settings('email_settings', true);
            $company_settings = get_settings('general_settings', true);

            // Initialize email service with proper configuration
            $config = [
                'protocol' => 'smtp',
                'SMTPHost' => $email_settings['smtpHost'] ?? '',
                'SMTPPort' => $email_settings['smtpPort'] ?? 587,
                'SMTPUser' => $email_settings['smtpUsername'] ?? '',
                'SMTPPass' => $email_settings['smtpPassword'] ?? '',
                'SMTPCrypto' => 'tls',
                'mailType' => 'html',
                'charset' => 'utf-8',
                'newline' => "\r\n"
            ];

            $email = \Config\Services::email($config);

            // Set email parameters
            $from_email = $email_settings['smtpUsername'];
            $from_name = $company_settings['company_title'];

            // Validate required fields
            $rules = [
                'subject' => [
                    'rules' => 'required|trim',
                    'errors' => ['required' => 'Please enter subject']
                ],
                'template' => [
                    'rules' => 'required|trim',
                    'errors' => ['required' => 'Please enter template content']
                ]
            ];

            $this->validation->setRules($rules);
            if (!$this->validation->withRequest($this->request)->run()) {
                $errors = $this->validation->getErrors();
                return ErrorResponse($errors, true, [], [], 200, csrf_token(), csrf_hash());
            }

            // Get post data
            $template = $this->request->getPost('template');
            $subject = $this->request->getPost('subject');
            $type = $this->request->getPost('email_user_type');
            $bcc = $this->request->getPost('bcc');
            $cc = $this->request->getPost('cc');

            // Get user IDs based on type
            $user_ids = [];
            if ($type == "provider") {
                $user_ids = $this->request->getPost('provider_id');
                if (!is_array($user_ids) || empty($user_ids)) {
                    return ErrorResponse(['provider_id' => 'Please select provider'], true, [], [], 200, csrf_token(), csrf_hash());
                }
            } elseif ($type == "customer") {
                $user_ids = $this->request->getPost('customer_id');
                if (!is_array($user_ids) || empty($user_ids)) {
                    return ErrorResponse(['customer_id' => 'Please select customer'], true, [], [], 200, csrf_token(), csrf_hash());
                }
            }

            // Fetch user details
            $users = fetch_details("users", [], ['email', 'id', 'username'], "", 0, 'id', "DESC", 'id', $user_ids);

            // Send email to each user
            foreach ($users as $user) {
                // Reset email instance for each iteration
                $email->clear();

                $email->setFrom($from_email, $from_name)
                    ->setTo("wrteam.dimple@gmail.com")
                    ->setSubject($subject)
                    ->setMailType('html');

                // Process BCC
                if (isset($_POST['bcc'][0]) && !empty($_POST['bcc'][0])) {
                    $bcc_emails = $this->processBccEmails($_POST['bcc']);
                    if (!empty($bcc_emails)) {
                        $email->setBCC($bcc_emails);
                    }
                }

                // Process CC
                if (isset($_POST['cc'][0]) && !empty($_POST['cc'][0])) {
                    $cc_emails = $this->processCcEmails($_POST['cc']);
                    if (!empty($cc_emails)) {
                        $email->setCC($cc_emails);
                    }
                }

                // Process template with replacements
                $processed_template = $this->processEmailTemplate($template, $user, $company_settings);

                // Handle attachments and inline images
                $processed_template = $this->processInlineImages($email, $processed_template);

                $email->setMessage($processed_template);

                // Send email with error logging
                if (!$email->send()) {
                    log_message('error', 'Email sending failed for user ID: ' . $user['id'] . ' - ' . $email->printDebugger(['headers']));
                    continue;
                }
            }

            // Save email details
            $email_data = [
                'content' => $template,
                'type' => $type,
                'bcc' => json_encode($bcc),
                'cc' => json_encode($cc),
                'subject' => $subject,
                'user_id' => json_encode($user_ids)
            ];
            insert_details($email_data, 'emails');

            return successResponse("Emails sent successfully", false, [], [], 200, csrf_token(), csrf_hash());
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage() . "\n" . $th->getTraceAsString());
            return ErrorResponse("Something went wrong while sending emails", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }

    // Helper method to process BCC emails
    private function processBccEmails($bcc_data)
    {
        $bcc = [];
        if (!empty($bcc_data[0])) {
            $val = explode(',', str_replace([']', '['], '', $bcc_data[0]));
            foreach ($val as $s) {
                $email = json_decode($s, true);
                if (isset($email['value']) && filter_var($email['value'], FILTER_VALIDATE_EMAIL)) {
                    $bcc[] = $email['value'];
                }
            }
        }
        return $bcc;
    }

    // Helper method to process CC emails
    private function processCcEmails($cc_data)
    {
        $cc = [];
        if (!empty($cc_data[0])) {
            $val = explode(',', str_replace([']', '['], '', $cc_data[0]));
            foreach ($val as $s) {
                $email = json_decode($s, true);
                if (isset($email['value']) && filter_var($email['value'], FILTER_VALIDATE_EMAIL)) {
                    $cc[] = $email['value'];
                }
            }
        }
        return $cc;
    }

    // Helper method to process email template
    private function processEmailTemplate($template, $user, $settings)
    {
        $replacements = [
            '[[unsubscribe_link]]' => base_url('unsubscribe_link/' . unsubscribe_link_user_encrypt($user['id'], $user['email'])),
            '[[user_id]]' => $user['id'],
            '[[user_name]]' => $user['username'],
            '[[company_name]]' => $settings['company_title'],
            '[[site_url]]' => base_url(),
            '[[company_contact_info]]' => get_settings('contact_us', true)['contact_us'] ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    // Helper method to process inline images
    private function processInlineImages($email, $template)
    {
        preg_match_all('/<img[^>]+src=["\'](.*?)["\'][^>]*>/i', $template, $matches);
        $imagePaths = $matches[1];

        foreach ($imagePaths as $imagePath) {
            if (file_exists($imagePath)) {
                $email->attach($imagePath);
                $cid = $email->setAttachmentCID(basename($imagePath));
                $template = str_replace($imagePath, "cid:$cid", $template);
            }
        }

        return $template;
    }
    public function list()
    {
        try {
            $limit = (isset($_GET['limit']) && !empty($_GET['limit'])) ? $_GET['limit'] : 10;
            $offset = (isset($_GET['offset']) && !empty($_GET['offset'])) ? $_GET['offset'] : 0;
            $sort = (isset($_GET['sort']) && !empty($_GET['sort'])) ? $_GET['sort'] : 'id';
            $order = (isset($_GET['order']) && !empty($_GET['order'])) ? $_GET['order'] : 'ASC';
            $search = (isset($_GET['search']) && !empty($_GET['search'])) ? $_GET['search'] : '';
            $data = $this->email->list(false, $search, $limit, $offset, $sort, $order);
            return $data;
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/SendEmail.php - list()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function delete_email()
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
            $builder = $db->table('emails');
            if ($builder->delete(['id' => $id])) {
                return successResponse("Email deleted successfully", false, [], [], 200, csrf_token(), csrf_hash());
            } else {
                return ErrorResponse("An error occured during deleting this item", true, [], [], 200, csrf_token(), csrf_hash());
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/SendEmail.php - delete_email()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function unsubscribe_link_view()
    {
        $uri = service('uri');
        $data = $uri->getSegments()[1];
        setPageInfo($this->data, 'Unsubscribe Email | Admin Panel', 'unsubscribe_email');
        return view('/backend/admin/pages/unsubscribe_email.php', $this->data);
    }
    public function unsubscription_email_operation()
    {
        try {
            $decrypted = unsubscribe_link_user_decrypt($_POST['data']);
            $user_id = $decrypted[0];
            $email = $decrypted[1];
            $user = fetch_details('users', ['id' => $user_id, 'email' => $email], ['id']);
            if (!empty($user)) {
                $update = update_details(['unsubscribe_email' => 1], ['id' => $user_id, 'email' => $email], 'users');
                if ($update) {
                    $successMessage = "You have successfully unsubscribed.";
                    session()->setFlashdata('success', $successMessage);
                } else {
                    $errorMessage = "Failed to unsubscribe. Please try again.";
                    session()->setFlashdata('error', $errorMessage);
                }
            } else {
                $errorMessage = "Invalid user or email.";
                session()->setFlashdata('error', $errorMessage);
            }
            return redirect()->back();
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/admin/SendEmail.php - unsubscription_email_operation()');
            return ErrorResponse("Something Went Wrong", true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
}
