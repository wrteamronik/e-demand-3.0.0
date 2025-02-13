<!-- Main Content -->
<?php
$db      = \Config\Database::connect();
$builder = $db->table('users u');
$builder->select('u.*,ug.group_id')
    ->join('users_groups ug', 'ug.user_id = u.id')
    ->where('ug.group_id', 1)
    ->where(['phone' => $_SESSION['identity']]);
$user1 = $builder->get()->getResultArray();
$permissions = get_permission($user1[0]['id']);
?>
<div class="main-content">
    <section class="section">
        <div class="section-header mt-2">
            <h1><?= labels('sms_gateways', "SMS Gateways") ?></h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="<?= base_url('/admin/dashboard') ?>">
                        <i class="fas fa-home-alt text-primary"></i> <?= labels('Dashboard', 'Dashboard') ?>
                    </a>
                </div>
                <div class="breadcrumb-item">
                    <a href="<?= base_url('/admin/settings/system-settings') ?>">
                        <?= labels('system_settings', "System Settings") ?>
                    </a>
                </div>
                <div class="breadcrumb-item"><?= labels('sms_gateways', "SMS Gateways") ?></div>
            </div>
        </div>
        <?php
        $settings = get_settings('system_settings', true);
        $sms_gateway_setting = get_settings('sms_gateway_setting');
        $sms_gateway_data = is_string($sms_gateway_setting) ? json_decode($sms_gateway_setting, true) : [];
        ?>
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link active" id="twilio-tab" data-toggle="tab" href="#twilio" role="tab"><?= labels('twilio', "Twilio") ?></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="sms-template-tab" data-toggle="tab" href="#sms_template" role="tab"><?= labels('sms_templates', "SMS Templates") ?></a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="twilio" role="tabpanel">
                <input type="hidden" id="sms_gateway_data" value='<?= json_encode($sms_gateway_data) ?>' />
                <form method="POST" action="<?= base_url('admin/settings/sms-gateway-settings') ?>">
                    <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
                    <div class="row" id="">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 ">
                            <div class="card px-3">
                                <div class="row">
                                    <div class="col mb-3" style="border-bottom: solid 1px #e5e6e9;">
                                        <div class='toggleButttonPostition'><?= labels('twilio', 'Twilio') ?></div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="twilio_endpoint"><?= labels('base_url', 'Base URL ') ?></label>
                                            <div class="input-group">
                                                <input type="text" value="<?= isset($twilio_endpoint) ? (ALLOW_VIEW_KEYS == 0 ? "asc****************adaca" : $twilio_endpoint) : '' ?>" name='twilio_endpoint' id='twilio_endpoint' class="form-control" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class='toggleButttonPostition m-2 p-0'><?= labels('create_authorization_token', 'Create Authorization Token ') ?></div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label for="twilio_account_sid"><?= labels('account_sid', 'Account SID') ?></label>
                                            <input type="text" value="<?= isset($twilio_account_sid) ? (ALLOW_VIEW_KEYS == 0 ? "asc****************adaca" : $twilio_account_sid) : '' ?>" name='twilio_account_sid' id='twilio_account_sid' placeholder='Enter Account SID' class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label for="twilio_auth_token"><?= labels('auth_token', 'Auth Token') ?></label>
                                            <input type="text" value="<?= isset($twilio_auth_token) ? (ALLOW_VIEW_KEYS == 0 ? "asc****************adaca" : $twilio_auth_token) : '' ?>" name='twilio_auth_token' id='twilio_auth_token' placeholder='Enter Auth Token' class="form-control" />
                                        </div>
                                    </div>
                                    <div class="col-md-4 mt-4">
                                        <button type="button" onClick="createHeader()" class="btn btn-primary"><?= labels('create', 'Create') ?></button>
                                    </div>
                                    <div class="col-md-12">
                                        <h4 id="basicToken" class="mb-3 p-0"></h4>
                                    </div>
                                </div>
                                <ul class="nav nav-tabs">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="headers-tab" data-toggle="tab" href="#headers" role="tab"><?= labels('header', 'Header') ?></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="body-tab" data-toggle="tab" href="#body" role="tab"><?= labels('body', 'Body') ?></a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="param-tab" data-toggle="tab" href="#param" role="tab"><?= labels('param', 'Param') ?></a>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="headers" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <button type="button" class="btn btn-outline-primary add-header"><i class="fas fa-plus-circle mr-2"></i><?= labels('add_header', 'Add header') ?></button>
                                            </div>
                                        </div>
                                        <div id="header-container">
                                            <div class="form-group row">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="body" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <button type="button" class="btn btn-outline-primary add-body"><i class="fas fa-plus-circle mr-2"></i><?= labels('add_body_data', 'Add Body data') ?></button>
                                            </div>
                                        </div>
                                        <div id="body-container">
                                            <div class="form-group row">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="param" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <button type="button" class="btn btn-outline-primary add-param"><i class="fas fa-plus-circle mr-2"></i><?= labels('add_param_data', 'Add Param data') ?></button>
                                            </div>
                                        </div>
                                        <div id="param-container">
                                            <div class="form-group row">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <?php if ($permissions['update']['settings'] == 1) : ?>

                                        <div class="col-md d-flex justify-content-lg-end m-1">
                                            <div class="form-group">
                                                <input type='submit' name='update' id='update' value='<?= labels('save_changes', "Save Changes") ?>' class='btn btn-primary' />
                                            </div>
                                        </div>

                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            
            <div class="tab-pane fade show " id="sms_template" role="tabpanel">
                <div class="card">
                    <div class="col mb-3" style="border-bottom: solid 1px #e5e6e9;">
                        <div class="toggleButttonPostition"><?= labels('sms_templates', "SMS Templates") ?></div>
                    </div>
                    <div class="card-body">

<!--                     
                        <form method="POST" class="form-submit-event" action="<?= base_url('admin/settings/sms-templates') ?>">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><?= labels('type', "Type") ?></label>
                                        <select class="form-control select2" name="type" id="type">
                                            <option value="provider_approved"><?= labels('provider_approved', "Provider Approved") ?> </option>
                                            <option value="provider_disapproved"> <?= labels('provider_disapproved', "Provider Disapproved") ?></option>
                                            <option value="withdraw_request_approved"><?= labels('approved_withdraw_request', "Approved Withdrawal Request") ?></option>
                                            <option value="withdraw_request_disapproved"><?= labels('disapproved_withdraw_request', "Disapproved Withdrawal Request") ?> </option>
                                            <option value="payment_settlement"><?= labels('payment_settled', "Payment Settled") ?> </option>
                                            <option value="service_approved"> <?= labels('service_approved', "Service Approved") ?></option>
                                            <option value="service_disapproved"><?= labels('service_disapproved', "Service Disapproved") ?> </option>
                                            <option value="user_account_active"><?= labels('user_account_activated', "User Account Activated") ?> </option>
                                            <option value="user_account_deactive"> <?= labels('user_account_deactivated', "User Account Deactivated") ?> </option>
                                            <option value="provider_update_information"> <?= labels('provider_information_updated', "Provider Information Updated") ?> </option>
                                            <option value="new_provider_registerd"> <?= labels('new_provider_registered', "New Provider Registered") ?> </option>
                                            <option value="withdraw_request_received"> <?= labels('withdrawal_request_received', "Withdrawal Request Received") ?> </option>
                                            <option value="booking_status_updated"> <?= labels('booking_status_updated', "Booking Status Updated") ?> </option>
                                            <option value="new_booking_confirmation_to_customer"> <?= labels('new_booking_confirmation_to_customer', "Booking Confirmation for customer") ?> </option>
                                            <option value="new_booking_received_for_provider"> <?= labels('new_booking_received_for_provider', "New Booking Received For provider") ?> </option>
                                            <option value="withdraw_request_send"><?= labels('withdraw_request_by_provider', "Withdraw Request By Provider") ?> </option>
                                            <option value="new_rating_given_by_customer"><?= labels('new_rating_given_by_customer', "New Rating Given By Customer") ?> </option>
                                            <option value="rating_request_to_customer"><?= labels('rating_request_to_customer', "Rating Request to Customer") ?> </option>

                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><?= labels('title', "Title") ?></label>
                                        <input class="form-control" placeholder="Enter title here" type="text" name="title">
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


                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="template" class="required"><?= labels('template', 'Template') ?></label>
                                        <textarea id="template" rows="50" placeholder="Enter Message here" class="form-control" name="template"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md d-flex justify-content-lg-end m-1">
                                    <div class="form-group">
                                        <input type='submit' name='update' id='update' value='<?= labels('save_changes', "Save Changes") ?>' class='btn btn-primary' />
                                    </div>
                                </div>
                            </div>



                        </form> -->

                        <?php if ($permissions['read']['settings'] == 1) : ?>

                        <div class="col-md-12">
                            <table class="table " data-fixed-columns="true" id="user_list" data-pagination-successively-size="2" data-detail-formatter="user_formater" data-auto-refresh="true" data-toggle="table" data-url="<?= base_url("admin/settings/sms-templates-list") ?>" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 25, 50, 100, 200, All]" data-search="false" data-show-columns="false" data-show-columns-search="true" data-show-refresh="false" data-sort-name="id" data-sort-order="desc" data-query-params="sms_query_params">
                                <thead>
                                    <tr>
                                        <th data-field="id" class="text-center" data-visible="true" data-sortable="true"><?= labels('id', 'ID') ?></th>
                                        <th data-field="title" class="text-center"><?= labels('title', 'Title') ?></th>
                                        <th data-field="type" class="text-center"><?= labels('type', 'Type') ?></th>

                                        <th data-field="truncatedtemplate" class="text-center" data-visible="true"><?= labels('template', 'template') ?></th>
                                        <th data-field="parameters" class="text-center" data-visible="true"><?= labels('parameters', 'Parameters') ?></th>
                                        <th data-field="operations" class="text-center" data-events="sms_gateway_events"><?= labels('operations', 'Operations') ?></th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        <?php endif; ?>


                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<script>
    $('.parameters .btn').click(function() {
        let variableName = $(this).data('variable');
        let formattedText = `[[${variableName}]]`;

        let textarea = document.getElementById('template');
        if (textarea.selectionStart || textarea.selectionStart === 0) {
            // For modern browsers
            let startPos = textarea.selectionStart;
            let endPos = textarea.selectionEnd;
            let scrollTop = textarea.scrollTop;

            textarea.value = textarea.value.substring(0, startPos) + formattedText + textarea.value.substring(endPos, textarea.value.length);
            textarea.focus();
            textarea.selectionStart = startPos + formattedText.length;
            textarea.selectionEnd = startPos + formattedText.length;
            textarea.scrollTop = scrollTop;
        } else {
            // For IE < 9
            textarea.value += formattedText;
            textarea.focus();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const smsData = JSON.parse(document.getElementById('sms_gateway_data').value || '{}');

        function createInputRow(containerId, keyName, valueName, keyPlaceholder, valuePlaceholder, removeClass, key = '', value = '') {
            const container = document.getElementById(containerId);
            const row = document.createElement('div');
            row.classList.add('form-group', 'row');
            row.innerHTML = `
            <div class="col-md-5">
                <input type="text" name="${keyName}" class="form-control" placeholder="${keyPlaceholder}" value="${key}">
            </div>
            <div class="col-md-5">
                <input type="text" name="${valueName}" class="form-control" placeholder="${valuePlaceholder}" value="${value}">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger ${removeClass}"><i class="fas fa-minus-circle"></i></button>
            </div>
        `;
            container.appendChild(row);
        }
        document.querySelector('.add-header').addEventListener('click', function() {
            createInputRow('header-container', 'header_key[]', 'header_value[]', 'Enter Key', 'Enter Value', 'remove-header');
        });
        document.querySelector('.add-body').addEventListener('click', function() {
            createInputRow('body-container', 'body_key[]', 'body_value[]', 'Enter Key', 'Enter Value', 'remove-body');
        });
        document.querySelector('.add-param').addEventListener('click', function() {
            createInputRow('param-container', 'params_key[]', 'params_value[]', 'Enter Key', 'Enter Value', 'remove-param');
        });

        function loadSmsHeaderSection() {
            if (smsData.header_key && smsData.header_value) {
                for (let i = 0; i < smsData.header_key.length; i++) {
                    createInputRow('header-container', 'header_key[]', 'header_value[]', 'Enter Key', 'Enter Value', 'remove-header', smsData.header_key[i], smsData.header_value[i]);
                }
            }
            if (smsData.body_key && smsData.body_value) {
                for (let i = 0; i < smsData.body_key.length; i++) {
                    createInputRow('body-container', 'body_key[]', 'body_value[]', 'Enter Key', 'Enter Value', 'remove-body', smsData.body_key[i], smsData.body_value[i]);
                }
            }
            if (smsData.params_key && smsData.params_value) {
                for (let i = 0; i < smsData.params_key.length; i++) {
                    createInputRow('param-container', 'params_key[]', 'params_value[]', 'Enter Key', 'Enter Value', 'remove-param', smsData.params_key[i], smsData.params_value[i]);
                }
            }
        }
        document.addEventListener('click', function(event) {
            if (event.target.closest('.remove-header')) {
                event.target.closest('.row').remove();
            }
            if (event.target.closest('.remove-body')) {
                event.target.closest('.row').remove();
            }
            if (event.target.closest('.remove-param')) {
                event.target.closest('.row').remove();
            }
        });
        window.createHeader = function() {
            const accountSID = document.getElementById('twilio_account_sid').value;
            const authToken = document.getElementById('twilio_auth_token').value;
            const base64Encoded = btoa(`${accountSID}:${authToken}`);
            document.getElementById('basicToken').innerText = `Authorization: Basic ${base64Encoded}`;
        };
        loadSmsHeaderSection();
    });
    $('.provider_registration_request,.withdraw_request,.payment_settlement,.service_request,.user_account,.booking_status,.new_booking,.rating_module').hide();
    $('#type').change(function() {
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
</script>