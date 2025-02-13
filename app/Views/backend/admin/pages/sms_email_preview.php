<div class="main-content">
    <section class="section">
        <div class="section-header mt-2">
            <h1><?= labels('preview_of_templates', "Preview Of templates") ?></h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= base_url('/admin/dashboard') ?>"><i class="fas fa-home-alt text-primary"></i> <?= labels('Dashboard', 'Dashboard') ?></a></div>
                <div class="breadcrumb-item "><a href="<?= base_url('/admin/settings/system-settings') ?>"><?= labels('system_settings', "System Settings") ?></a></div>
                <div class="breadcrumb-item "><a href="<?= base_url('admin/settings/notification-settings') ?>"><?= labels('notification_settings', "Notification Settings") ?></a></div>
                <div class="breadcrumb-item "><?= labels('preview_of_templates', "Preview Of templates") ?></div>
            </div>
        </div>
        <?php $data = get_settings('general_settings', true); ?>
        <div class="row">
            <div class="col-md-6">
                <div class="card email-preview-card">
                    <div class="row  m-0 border_bottom_for_cards">
                        <div class="col-auto">
                            <div class="toggleButttonPostition"><?= labels('email_template', "Email Template") ?></div>
                        </div>
                        <div class="col d-flex justify-content-end  mt-4 ">
                            <div class="text-center">
                                <a href="javascript:void(0);" id="editEmailTemplateBtn" class="btn btn-primary">
                                    <?= labels('edit', 'Edit') ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="email-header">
                            <div class="email-subject">
                                <strong><?= $email_template['subject']; ?></strong>
                            </div>
                            <div class="email-from">
                                <span><strong> <?= (isset($data['company_title']) && $data['company_title'] != "") ? $data['company_title'] : "eDemand"; ?></strong> &lt;<?= $data['support_email'] ?>&gt;</span>
                                <span>to xxxxx</span>
                            </div>
                        </div>
                        <div class="email-content">
                            <?= strip_tags(htmlspecialchars_decode(stripslashes($email_template['template'])), '<p><br>')
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card sms-preview-card ">
                    <div class="row  m-0 border_bottom_for_cards">
                        <div class="col-auto">
                            <div class="toggleButttonPostition"><?= labels('sms_template', "SMS Template") ?></div>
                        </div>
                        <div class="col d-flex justify-content-end  mt-4 ">
                            <div class="text-center">
                                <a href="javascript:void(0);" id="editSMSTemplateBtn" class="btn btn-primary">
                                    <?= labels('edit', 'Edit') ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <pre class="sms-template-preview p-3 sms-content"><?= htmlspecialchars($sms_template['template']) ?></pre>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<div class="modal fade" id="edit_email_modal" tabindex="-1" aria-labelledby="edit_email_modal_thing" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <?= form_open_multipart(base_url('admin/settings/edit_email_template_operation'), array('class' => 'form-submit-event')) ?>
            <div class="modal-header m-0 p-0" style="border-bottom: solid 1px #e5e6e9;">
                <div class="row pl-3">
                    <div class="col">
                        <div class="toggleButttonPostition"><?= labels('edit_email_template', 'Edit Email Template') ?></div>
                    </div>
                </div>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="row">
                        <input type="hidden" name="template_id" value="<?= $email_template['id'] ?>" />
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= labels('type', "Type") ?></label>
                                <select class="form-control select2" name="email_type" id="email_type">
                                    <option value="provider_approved" <?= ($email_template['type'] == "provider_approved") ? 'selected' : '' ?>><?= labels('provider_approved', "Provider Approved") ?></option>
                                    <option value="provider_disapproved" <?= ($email_template['type'] == "provider_disapproved") ? 'selected' : '' ?>><?= labels('provider_disapproved', "Provider Disapproved") ?></option>
                                    <option value="withdraw_request_approved" <?= ($email_template['type'] == "withdraw_request_approved") ? 'selected' : '' ?>><?= labels('approved_withdraw_request', "Approved Withdrawal Request") ?></option>
                                    <option value="withdraw_request_disapproved" <?= ($email_template['type'] == "withdraw_request_disapproved") ? 'selected' : '' ?>><?= labels('disapproved_withdraw_request', "Disapproved Withdrawal Request") ?> </option>
                                    <option value="payment_settlement" <?= ($email_template['type'] == "payment_settlement") ? 'selected' : '' ?>><?= labels('payment_settled', "Payment Settled") ?> </option>
                                    <option value="service_approved" <?= ($email_template['type'] == "service_approved") ? 'selected' : '' ?>> <?= labels('service_approved', "Service Approved") ?></option>
                                    <option value="service_disapproved" <?= ($email_template['type'] == "service_disapproved") ? 'selected' : '' ?>><?= labels('service_disapproved', "Service Disapproved") ?> </option>
                                    <option value="user_account_active" <?= ($email_template['type'] == "user_account_active") ? 'selected' : '' ?>><?= labels('user_account_activated', "User Account Activated") ?> </option>
                                    <option value="user_account_deactive" <?= ($email_template['type'] == "user_account_deactive") ? 'selected' : '' ?>> <?= labels('user_account_deactivated', "User Account Deactivated") ?> </option>
                                    <option value="provider_update_information" <?= ($email_template['type'] == "provider_update_information") ? 'selected' : '' ?>> <?= labels('provider_information_updated', "Provider Information Updated") ?> </option>
                                    <option value="new_provider_registerd" <?= ($email_template['type'] == "new_provider_registerd") ? 'selected' : '' ?>> <?= labels('new_provider_registered', "New Provider Registered") ?> </option>
                                    <option value="withdraw_request_received" <?= ($email_template['type'] == "withdraw_request_received") ? 'selected' : '' ?>> <?= labels('withdrawal_request_received', "Withdrawal Request Received") ?> </option>
                                    <option value="booking_status_updated" <?= ($email_template['type'] == "booking_status_updated") ? 'selected' : '' ?>> <?= labels('booking_status_updated', "Booking Status Updated") ?> </option>
                                    <option value="new_booking_confirmation_to_customer" <?= ($email_template['type'] == "new_booking_confirmation_to_customer") ? 'selected' : '' ?>> <?= labels('new_booking_confirmation_to_customer', "Booking Confirmation for customer") ?> </option>
                                    <option value="new_booking_received_for_provider" <?= ($email_template['type'] == "new_booking_received_for_provider") ? 'selected' : '' ?>> <?= labels('new_booking_received_for_provider', "New Booking Received For provider") ?> </option>
                                    <option value="withdraw_request_send" <?= ($email_template['type'] == "withdraw_request_send") ? 'selected' : '' ?>><?= labels('withdraw_request_by_provider', "Withdraw Request By Provider") ?> </option>
                                    <option value="new_rating_given_by_customer" <?= ($email_template['type'] == "new_rating_given_by_customer") ? 'selected' : '' ?>><?= labels('new_rating_given_by_customer', "New Rating Given By Customer") ?> </option>
                                    <option value="rating_request_to_customer" <?= ($email_template['type'] == "rating_request_to_customer") ? 'selected' : '' ?>><?= labels('rating_request_to_customer', "Rating Request to Customer") ?> </option>

                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= labels('subject', "Subject") ?></label>
                                <input class="form-control" type="text" name="subject" value="<?= $email_template['subject'] ?>">
                            </div>
                        </div>
                        <div class="col-md-12 provider_registration_request parameters">
                            <label><?= labels('parameters', "Parameters") ?></label>
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_name"><?= labels('provider_name', "Provider name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_id"><?= labels('provider_id', "Provider ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_name"><?= labels('company_name', "Company name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="site_url"><?= labels('site_url', "Site URL") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_contact_info"><?= labels('company_contact_info', "Company Contact Info") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_logo"><?= labels('company_logo', "Company Logo") ?></button>
                            </div>
                        </div>
                        <div class="col-md-12 withdraw_request parameters">
                            <label><?= labels('parameters', "Parameters") ?></label>
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_name"><?= labels('provider_name', "Provider name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_id"><?= labels('provider_id', "Provider ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_name"><?= labels('company_name', "Company name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="site_url"><?= labels('site_url', "Site URL") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_contact_info"><?= labels('company_contact_info', "Company Contact Info") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="amount"><?= labels('amount', "Amount") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="currency"><?= labels('currency', "Currency") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_logo"><?= labels('company_logo', "Company Logo") ?></button>
                            </div>
                        </div>
                        <div class="col-md-12 payment_settlement parameters">
                            <label><?= labels('parameters', "Parameters") ?></label>
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_name"><?= labels('provider_name', "Provider name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_id"><?= labels('provider_id', "Provider ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_name"><?= labels('company_name', "Company name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="site_url"><?= labels('site_url', "Site URL") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_contact_info"><?= labels('company_contact_info', "Company Contact Info") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="amount"><?= labels('amount', "Amount") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="currency"><?= labels('currency', "Currency") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_logo"><?= labels('company_logo', "Company Logo") ?></button>
                            </div>
                        </div>
                        <div class="col-md-12 service_request parameters">
                            <label><?= labels('parameters', "Parameters") ?></label>
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_name"><?= labels('provider_name', "Provider name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_id"><?= labels('provider_id', "Provider ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_name"><?= labels('company_name', "Company name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="site_url"><?= labels('site_url', "Site URL") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_contact_info"><?= labels('company_contact_info', "Company Contact Info") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="amount"><?= labels('amount', "Amount") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="currency"><?= labels('currency', "Currency") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_logo"><?= labels('company_logo', "Company Logo") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="service_id"><?= labels('service_id', "Service ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="service_name"><?= labels('service_name', "Service Name") ?></button>
                            </div>
                        </div>
                        <div class="col-md-12 user_account parameters">
                            <label><?= labels('parameters', "Parameters") ?></label>
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="user_id"><?= labels('user_id', "User ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="user_name"><?= labels('user_name', "User name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_name"><?= labels('company_name', "Company name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="site_url"><?= labels('site_url', "Site URL") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_contact_info"><?= labels('company_contact_info', "Company Contact Info") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_logo"><?= labels('company_logo', "Company Logo") ?></button>
                            </div>
                        </div>
                        <div class="col-md-12 booking_status parameters">
                            <label><?= labels('parameters', "Parameters") ?></label>
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="user_id"><?= labels('user_id', "User ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="user_name"><?= labels('user_name', "User Name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_name"><?= labels('company_name', "Company name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="site_url"><?= labels('site_url', "Site URL") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_contact_info"><?= labels('company_contact_info', "Company Contact Info") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_logo"><?= labels('company_logo', "Company Logo") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_id"><?= labels('booking_id', "Booking ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_date"><?= labels('booking_date', "Booking Date") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_time"><?= labels('booking_time', "Booking Time") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_service_names"><?= labels('booking_service_names', "Booking Service names") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_address"><?= labels('booking_address', "Booking Address") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="amount"><?= labels('amount', "Amount") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="currency"><?= labels('currency', "Currency") ?></button>
                            </div>
                        </div>
                        <div class="col-md-12 new_booking parameters">
                            <label><?= labels('parameters', "Parameters") ?></label>
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="user_id"><?= labels('user_id', "User ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="user_name"><?= labels('user_name', "User Name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_name"><?= labels('company_name', "Company name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="site_url"><?= labels('site_url', "Site URL") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_contact_info"><?= labels('company_contact_info', "Company Contact Info") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_logo"><?= labels('company_logo', "Company Logo") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_id"><?= labels('booking_id', "Booking ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_date"><?= labels('booking_date', "Booking Date") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_time"><?= labels('booking_time', "Booking Time") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_service_names"><?= labels('booking_service_names', "Booking Service names") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_address"><?= labels('booking_address', "Booking Address") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="amount"><?= labels('amount', "Amount") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="currency"><?= labels('currency', "Currency") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_name"><?= labels('provider_name', "Provider name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_id"><?= labels('provider_id', "Provider ID") ?></button>
                            </div>
                        </div>


                        <div class="col-md-12 rating_module parameters">
                            <label><?= labels('parameters', "Parameters") ?></label>
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="user_id"><?= labels('user_id', "User ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="user_name"><?= labels('user_name', "User Name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_name"><?= labels('company_name', "Company name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="site_url"><?= labels('site_url', "Site URL") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_contact_info"><?= labels('company_contact_info', "Company Contact Info") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_logo"><?= labels('company_logo', "Company Logo") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_name"><?= labels('provider_name', "Provider name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_id"><?= labels('provider_id', "Provider ID") ?></button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?= labels('bcc', "BCC") ?></label>
                                <input id="bcc" style="border-radius: 0.25rem!important" class="w-100" type="text" value="<?= ($email_template['bcc']) ?>" name="bcc[]" placeholder="press enter to add bcc">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><?= labels('cc', "CC") ?></label>
                                <input id="cc" style="border-radius: 0.25rem" class="w-100" type="text" name="cc[]" value="<?= $email_template['cc'] ?>" placeholder="press enter to add cc">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="template" class="required"><?= labels('template', 'Template') ?></label>
                                <textarea rows="10" class="form-control h-50 summernotes custome_reset" name="template"> <?= $email_template['template'] ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn  bg-new-primary submit_btn"><?= labels('save_changes', 'Save') ?></button>
                <?= form_close() ?>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= labels('close', 'Close') ?></button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="edit_sms_modal" tabindex="-1" aria-labelledby="edit_sms_modal_thing" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" class="form-submit-event" action="<?= base_url('admin/settings/edit-sms-templates') ?>">
                <div class="modal-header m-0 p-0" style="border-bottom: solid 1px #e5e6e9;">
                    <div class="row pl-3">
                        <div class="col">
                            <div class="toggleButttonPostition"><?= labels('edit_sms_template', 'Edit SMS Template') ?></div>
                        </div>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= labels('type', "Type") ?></label>
                                <select class="form-control select2" name="type" id="type">
                                    <option value="provider_approved" <?= ($sms_template['type'] == "provider_approved") ? 'selected' : '' ?>><?= labels('provider_approved', "Provider Approved") ?></option>
                                    <option value="provider_disapproved" <?= ($sms_template['type'] == "provider_disapproved") ? 'selected' : '' ?>><?= labels('provider_disapproved', "Provider Disapproved") ?></option>
                                    <option value="withdraw_request_approved" <?= ($sms_template['type'] == "withdraw_request_approved") ? 'selected' : '' ?>><?= labels('approved_withdraw_request', "Approved Withdrawal Request") ?></option>
                                    <option value="withdraw_request_disapproved" <?= ($sms_template['type'] == "withdraw_request_disapproved") ? 'selected' : '' ?>><?= labels('disapproved_withdraw_request', "Disapproved Withdrawal Request") ?> </option>
                                    <option value="payment_settlement" <?= ($sms_template['type'] == "payment_settlement") ? 'selected' : '' ?>><?= labels('payment_settled', "Payment Settled") ?> </option>
                                    <option value="service_approved" <?= ($sms_template['type'] == "service_approved") ? 'selected' : '' ?>> <?= labels('service_approved', "Service Approved") ?></option>
                                    <option value="service_disapproved" <?= ($sms_template['type'] == "service_disapproved") ? 'selected' : '' ?>><?= labels('service_disapproved', "Service Disapproved") ?> </option>
                                    <option value="user_account_active" <?= ($sms_template['type'] == "user_account_active") ? 'selected' : '' ?>><?= labels('user_account_activated', "User Account Activated") ?> </option>
                                    <option value="user_account_deactive" <?= ($sms_template['type'] == "user_account_deactive") ? 'selected' : '' ?>> <?= labels('user_account_deactivated', "User Account Deactivated") ?> </option>
                                    <option value="provider_update_information" <?= ($sms_template['type'] == "provider_update_information") ? 'selected' : '' ?>> <?= labels('provider_information_updated', "Provider Information Updated") ?> </option>
                                    <option value="new_provider_registerd" <?= ($sms_template['type'] == "new_provider_registerd") ? 'selected' : '' ?>> <?= labels('new_provider_registered', "New Provider Registered") ?> </option>
                                    <option value="withdraw_request_received" <?= ($sms_template['type'] == "withdraw_request_received") ? 'selected' : '' ?>> <?= labels('withdrawal_request_received', "Withdrawal Request Received") ?> </option>
                                    <option value="booking_status_updated" <?= ($sms_template['type'] == "booking_status_updated") ? 'selected' : '' ?>> <?= labels('booking_status_updated', "Booking Status Updated") ?> </option>
                                    <option value="new_booking_confirmation_to_customer" <?= ($sms_template['type'] == "new_booking_confirmation_to_customer") ? 'selected' : '' ?>> <?= labels('new_booking_confirmation_to_customer', "Booking Confirmation for customer") ?> </option>
                                    <option value="new_booking_received_for_provider" <?= ($sms_template['type'] == "new_booking_received_for_provider") ? 'selected' : '' ?>> <?= labels('new_booking_received_for_provider', "New Booking Received For provider") ?> </option>
                                    <option value="withdraw_request_send" <?= ($sms_template['type'] == "withdraw_request_send") ? 'selected' : '' ?>><?= labels('withdraw_request_by_provider', "Withdraw Request By Provider") ?> </option>
                                    <option value="new_rating_given_by_customer" <?= ($sms_template['type'] == "new_rating_given_by_customer") ? 'selected' : '' ?>><?= labels('new_rating_given_by_customer', "New Rating Given By Customer") ?> </option>
                                    <option value="rating_request_to_customer" <?= ($sms_template['type'] == "rating_request_to_customer") ? 'selected' : '' ?>><?= labels('rating_request_to_customer', "Rating Request to Customer") ?> </option>

                                </select>
                            </div>
                        </div>
                        <input type="hidden" name="template_id" value="<?= $sms_template['id'] ?>" />
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= labels('title', "Title") ?></label>
                                <input class="form-control" placeholder="Enter title here" type="text" value="<?= $sms_template['title'] ?>" name="title">
                            </div>
                        </div>
                        <div class="col-md-12 provider_registration_request sms_parameters">
                            <label><?= labels('parameters', "Parameters") ?></label>
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_name"><?= labels('provider_name', "Provider name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_id"><?= labels('provider_id', "Provider ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_name"><?= labels('company_name', "Company name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="site_url"><?= labels('site_url', "Site URL") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_contact_info"><?= labels('company_contact_info', "Company Contact Info") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_logo"><?= labels('company_logo', "Company Logo") ?></button>
                            </div>
                        </div>
                        <div class="col-md-12 withdraw_request sms_parameters">
                            <label><?= labels('parameters', "Parameters") ?></label>
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_name"><?= labels('provider_name', "Provider name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_id"><?= labels('provider_id', "Provider ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_name"><?= labels('company_name', "Company name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="site_url"><?= labels('site_url', "Site URL") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_contact_info"><?= labels('company_contact_info', "Company Contact Info") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="amount"><?= labels('amount', "Amount") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="currency"><?= labels('currency', "Currency") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_logo"><?= labels('company_logo', "Company Logo") ?></button>
                            </div>
                        </div>
                        <div class="col-md-12 payment_settlement sms_parameters">
                            <label><?= labels('parameters', "Parameters") ?></label>
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_name"><?= labels('provider_name', "Provider name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_id"><?= labels('provider_id', "Provider ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_name"><?= labels('company_name', "Company name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="site_url"><?= labels('site_url', "Site URL") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_contact_info"><?= labels('company_contact_info', "Company Contact Info") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="amount"><?= labels('amount', "Amount") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="currency"><?= labels('currency', "Currency") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_logo"><?= labels('company_logo', "Company Logo") ?></button>
                            </div>
                        </div>
                        <div class="col-md-12 service_request sms_parameters">
                            <label><?= labels('parameters', "Parameters") ?></label>
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_name"><?= labels('provider_name', "Provider name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_id"><?= labels('provider_id', "Provider ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_name"><?= labels('company_name', "Company name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="site_url"><?= labels('site_url', "Site URL") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_contact_info"><?= labels('company_contact_info', "Company Contact Info") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="amount"><?= labels('amount', "Amount") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="currency"><?= labels('currency', "Currency") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_logo"><?= labels('company_logo', "Company Logo") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="service_id"><?= labels('service_id', "Service ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="service_name"><?= labels('service_name', "Service Name") ?></button>
                            </div>
                        </div>
                        <div class="col-md-12 user_account sms_parameters">
                            <label><?= labels('parameters', "Parameters") ?></label>
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="user_id"><?= labels('user_id', "User ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="user_name"><?= labels('user_name', "User name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_name"><?= labels('company_name', "Company name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="site_url"><?= labels('site_url', "Site URL") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_contact_info"><?= labels('company_contact_info', "Company Contact Info") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_logo"><?= labels('company_logo', "Company Logo") ?></button>
                            </div>
                        </div>
                        <div class="col-md-12 booking_status sms_parameters">
                            <label><?= labels('parameters', "Parameters") ?></label>
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="user_id"><?= labels('user_id', "User ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="user_name"><?= labels('user_name', "User Name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_name"><?= labels('company_name', "Company name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="site_url"><?= labels('site_url', "Site URL") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_contact_info"><?= labels('company_contact_info', "Company Contact Info") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_logo"><?= labels('company_logo', "Company Logo") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_id"><?= labels('booking_id', "Booking ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_date"><?= labels('booking_date', "Booking Date") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_time"><?= labels('booking_time', "Booking Time") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_service_names"><?= labels('booking_service_names', "Booking Service names") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_address"><?= labels('booking_address', "Booking Address") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="amount"><?= labels('amount', "Amount") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="currency"><?= labels('currency', "Currency") ?></button>
                            </div>
                        </div>
                        <div class="col-md-12 new_booking sms_parameters">
                            <label><?= labels('parameters', "Parameters") ?></label>
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="user_id"><?= labels('user_id', "User ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="user_name"><?= labels('user_name', "User Name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_name"><?= labels('company_name', "Company name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="site_url"><?= labels('site_url', "Site URL") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_contact_info"><?= labels('company_contact_info', "Company Contact Info") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_logo"><?= labels('company_logo', "Company Logo") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_id"><?= labels('booking_id', "Booking ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_date"><?= labels('booking_date', "Booking Date") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_time"><?= labels('booking_time', "Booking Time") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_service_names"><?= labels('booking_service_names', "Booking Service names") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="booking_address"><?= labels('booking_address', "Booking Address") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="amount"><?= labels('amount', "Amount") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="currency"><?= labels('currency', "Currency") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_name"><?= labels('provider_name', "Provider name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_id"><?= labels('provider_id', "Provider ID") ?></button>
                            </div>
                        </div>

                        <div class="col-md-12 rating_module sms_parameters">
                            <label><?= labels('parameters', "Parameters") ?></label>
                            <div class="form-group">
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="user_id"><?= labels('user_id', "User ID") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="user_name"><?= labels('user_name', "User Name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_name"><?= labels('company_name', "Company name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="site_url"><?= labels('site_url', "Site URL") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_contact_info"><?= labels('company_contact_info', "Company Contact Info") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="company_logo"><?= labels('company_logo', "Company Logo") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_name"><?= labels('provider_name', "Provider name") ?></button>
                                <button type="button" class="btn btn-primary btn-icon icon-left" data-variable="provider_id"><?= labels('provider_id', "Provider ID") ?></button>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="template" class="required"><?= labels('template', 'Template') ?></label>
                                <textarea id="template" rows="50" placeholder="Enter Message here" class="form-control " name="template"><?= $sms_template['template'] ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn  bg-new-primary submit_btn"><?= labels('save_changes', 'Save') ?></button>
                    <?= form_close() ?>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= labels('close', 'Close') ?></button>
                </div>
        </div>
    </div>
</div>
<script>
    document.getElementById('editEmailTemplateBtn').addEventListener('click', function() {
        $('#edit_email_modal').modal('show');
    });
    document.getElementById('editSMSTemplateBtn').addEventListener('click', function() {
        $('#edit_sms_modal').modal('show');
    });
</script>
<script>
    $(document).ready(function() {
        $('.provider_registration_request,.withdraw_request,.payment_settlement,.service_request,.user_account,.booking_status,.new_booking').hide();
        let type = "<?= $sms_template['type'] ?>";
        if (type == "provider_approved" || type == "provider_disapproved" || type == "provider_update_information" || type == "new_provider_registerd") {
            $('.provider_registration_request').show();
        } else {
            $('.provider_registration_request').hide();
        }
        if (type == "withdraw_request_approved" || type == "withdraw_request_disapproved" || type == "withdraw_request_received" || type == "withdraw_request_send") {
            $('.withdraw_request').show();
        } else {
            $('.withdraw_request').hide();
        }
        if (type == "payment_settlement") {
            $('.payment_settlement').show();
        } else {
            $('.payment_settlement').hide();
        }
        if (type == "service_approved" || type == "service_disapproved") {
            $('.service_request').show();
        } else {
            $('.service_request').hide();
        }
        if (type == "user_account_active" || type == "user_account_deactive") {
            $('.user_account').show();
        } else {
            $('.user_account').hide();
        }
        if (type == "booking_status_updated") {
            $('.booking_status').show();
        } else {
            $('.booking_status').hide();
        }
        if (type == "new_booking_confirmation_to_customer" || type == "new_booking_received_for_provider") {
            $('.new_booking').show();
        } else {
            $('.new_booking').hide();
        }
        if (type == "new_rating_given_by_customer" || type == "rating_request_to_customer") {
            $('.rating_module').show();
        } else {
            $('.rating_module').hide();
        }
        setTimeout(() => {
            $('#email_to').select2();
        });
        $('#email_type').change(function() {
            let email_type = this.value;
            if (email_type == "provider_approved" || email_type == "provider_disapproved" || email_type == "provider_update_information" || email_type == "new_provider_registerd") {
                $('.provider_registration_request').show();
            } else {
                $('.provider_registration_request').hide();
            }
            if (email_type == "withdraw_request_approved" || email_type == "withdraw_request_disapproved" || email_type == "withdraw_request_received" || email_type == "withdraw_request_send") {
                $('.withdraw_request').show();
            } else {
                $('.withdraw_request').hide();
            }
            if (email_type == "payment_settlement") {
                $('.payment_settlement').show();
            } else {
                $('.payment_settlement').hide();
            }
            if (email_type == "service_approved" || email_type == "service_disapproved") {
                $('.service_request').show();
            } else {
                $('.service_request').hide();
            }
            if (email_type == "user_account_active" || email_type == "user_account_deactive") {
                $('.user_account').show();
            } else {
                $('.user_account').hide();
            }
            if (email_type == "booking_status_updated") {
                $('.booking_status').show();
            } else {
                $('.booking_status').hide();
            }
            if (email_type == "new_booking_confirmation_to_customer" || email_type == "new_booking_received_for_provider") {
                $('.new_booking').show();
            } else {
                $('.new_booking').hide();
            }

            if (email_type == "new_rating_given_by_customer" || email_type == "rating_request_to_customer") {
                $('.rating_module').show();
            } else {
                $('.rating_module').hide();
            }
        });
        $('#type').change(function() {
            let sms_type = this.value;
            if (sms_type == "provider_approved" || sms_type == "provider_disapproved" || sms_type == "provider_update_information" || sms_type == "new_provider_registerd") {
                $('.provider_registration_request').show();
            } else {
                $('.provider_registration_request').hide();
            }
            if (sms_type == "withdraw_request_approved" || sms_type == "withdraw_request_disapproved" || sms_type == "withdraw_request_received" || sms_type == "withdraw_request_send") {
                $('.withdraw_request').show();
            } else {
                $('.withdraw_request').hide();
            }
            if (sms_type == "payment_settlement") {
                $('.payment_settlement').show();
            } else {
                $('.payment_settlement').hide();
            }
            if (sms_type == "service_approved" || sms_type == "service_disapproved") {
                $('.service_request').show();
            } else {
                $('.service_request').hide();
            }
            if (sms_type == "user_account_active" || sms_type == "user_account_deactive") {
                $('.user_account').show();
            } else {
                $('.user_account').hide();
            }
            if (sms_type == "booking_status_updated") {
                $('.booking_status').show();
            } else {
                $('.booking_status').hide();
            }
            if (sms_type == "new_booking_confirmation_to_customer" || sms_type == "new_booking_received_for_provider") {
                $('.new_booking').show();
            } else {
                $('.new_booking').hide();
            }
            if (sms_type == "new_rating_given_by_customer" || sms_type == "rating_request_to_customer") {
                $('.rating_module').show();
            } else {
                $('.rating_module').hide();
            }
        });
    });
    if (document.getElementById("bcc") != null) {
        $(document).ready(function() {
            var input = document.querySelector('input[id=bcc]');
            new Tagify(input)
        });
    }
    if (document.getElementById("cc") != null) {
        $(document).ready(function() {
            var input = document.querySelector('input[id=cc]');
            new Tagify(input)
        });
    }
    $('.sms_parameters .btn').click(function() {
        let variableName = $(this).data('variable');
        let formattedText = `[[${variableName}]]`;
        let textarea = document.getElementById('template');
        if (textarea.selectionStart || textarea.selectionStart === 0) {
            let startPos = textarea.selectionStart;
            let endPos = textarea.selectionEnd;
            let scrollTop = textarea.scrollTop;
            textarea.value = textarea.value.substring(0, startPos) + formattedText + textarea.value.substring(endPos, textarea.value.length);
            textarea.focus();
            textarea.selectionStart = startPos + formattedText.length;
            textarea.selectionEnd = startPos + formattedText.length;
            textarea.scrollTop = scrollTop;
        } else {
            textarea.value += formattedText;
            textarea.focus();
        }
    });
    $('.parameters .btn').click(function() {
        let variableName = $(this).data('variable');
        let formattedText = `[[${variableName}]]`;
        tinymce.activeEditor.execCommand('mceInsertContent', false, formattedText);
    });
</script>