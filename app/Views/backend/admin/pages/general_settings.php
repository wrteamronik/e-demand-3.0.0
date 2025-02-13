<!-- Main Content new-->
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
    <section class="section" id="pill-general_settings" role="tabpanel">
        <div class="section-header mt-2">
            <h1><?= labels('general_settings', 'General Settings') ?></h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= base_url('/admin/dashboard') ?>"><i class="fas fa-home-alt text-primary"></i> <?= labels('Dashboard', 'Dashboard') ?></a></div>
                <div class="breadcrumb-item "><a href="<?= base_url('/admin/settings/system-settings') ?>"><?= labels('system_settings', "System Settings") ?></a></div>
                <div class="breadcrumb-item "><a href="<?= base_url('admin/settings/general-settings') ?>"><?= labels('general_settings', "General Settings") ?></a></div>
            </div>
        </div>
        <ul class="justify-content-start nav nav-fill nav-pills pl-3 py-2 setting" id="gen-list">
            <div class="row">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="<?= base_url('admin/settings/general-settings') ?>" id="pills-general_settings-tab" aria-selected="true">
                        <?= labels('general_settings', "General Settings") ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('admin/settings/about-us') ?>" id="pills-about_us" aria-selected="false">
                        <?= labels('about_us', "About Us") ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('admin/settings/contact-us') ?>" id="pills-about_us" aria-selected="false">
                        <?= labels('support_details', "Support Details") ?></a>
                </li>
            </div>
        </ul>
        <?= form_open_multipart(base_url('admin/settings/general-settings')) ?>
        <div class="row mb-3 mb-sm-3 mb-md-3 mb-xxs-12">
            <div class="col-lg-4 col-md-12 col-sm-12 col-xl-4 mb-md-3 mb-sm-3  mb-3">
                <div class="card h-100 ">
                    <div class="row m-0 border_bottom_for_cards">
                        <div class="col  ">
                            <div class="toggleButttonPostition"><?= labels('business_settings', 'Business settings') ?></div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <input type="hidden" id="set" value="<?= isset($system_timezone) ? $system_timezone : 'Asia/Kolkata' ?>">
                                    <input type="hidden" name="system_timezone_gmt" value="<?= isset($system_timezone_gmt) ? $system_timezone_gmt : '' ?>" id="system_timezone_gmt" value="<?= isset($system_timezone_gmt) ? $system_timezone_gmt : '+05:30' ?>" />
                                    <label for='timezone'><?= labels('select_time_zone', "Select Time Zone") ?></label>
                                    <select class='form-control selectric ' name='system_timezone' id='timezone' value="">
                                        <option value="">-- <?= labels('select_time_zone', "Select Time Zone") ?> --</option>
                                        <?php foreach ($timezones as $row) { ?>
                                            <option value="<?= $row[2] ?>" data-gmt="<?= $row[1] ?>"><?= $row[1] ?> - <?= $row[0] ?> - <?= $row[2] ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="max_serviceable_distance"><?= labels('max_Serviceable_distance_in_kms', "Max Serviceable Distance (in Kms)") ?></label>
                                    <i data-content=" <?= labels('data_content_for_max_serviceable_distance', 'The system will use the distance values (KM) you provide to find providers in Xkms within the location chosen by the customer. For instance, if you set it to 100 KM, customers will see providers within 100 KM of their chosen location. If there are no providers within 100 KM, it\'ll say, We are not available here.') ?>" class="fa fa-question-circle" data-original-title="" title=""></i>
                                    <div class="input-group">
                                        <input type="number" class="form-control custome_reset" name="max_serviceable_distance" id="max_serviceable_distance" value="<?= isset($max_serviceable_distance) ? $max_serviceable_distance : '' ?>" />
                                        <div class="input-group-append">
                                            <select class="form-control" name="distance_unit" id="distance_unit">
                                                <option value="km" <?= isset($distance_unit) && $distance_unit == 'km' ? 'selected' : '' ?>>Kms</option>
                                                <option value="miles" <?= isset($distance_unit) && $distance_unit == 'miles' ? 'selected' : '' ?>>Miles</option>
                                            </select>
                                        </div>
                                    </div>
                                    <label for="max_serviceable_distance" class="text-danger"><?= labels('note_this_distance_is_used_while_search_nearby_partner_for_customer', " This distance is used while search nearby partner for customer") ?></label>
                                </div>
                            </div>
                            <div class="col-md-6 ">
                                <div class="form-group">
                                    <div class="control-label"><?= labels('otp_system', "OTP System") ?> <span class="breadcrumb-item p-3 pt-2 text-primary">
                                            <i data-content="If enabled, both the provider and admin need to obtain an OTP from the customer in order to mark the booking as completed. Otherwise, if no OTP verification is required, the booking can be directly marked as completed." class="fa fa-question-circle" data-original-title="" title=""></i></span></div>
                                    <select name="otp_system" class="form-control">
                                        <option value="0" <?php echo  isset($otp_system) && $otp_system == '0' ? 'selected' : '' ?>>Disable</option>
                                        <option value="1" <?php echo  isset($otp_system) && $otp_system == '1' ? 'selected' : '' ?>>Enable</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 ">
                                <div class="form-group">
                                    <div class="control-label"><?= labels('authentication_mode', "Authentication Mode") ?> </div>
                                    <select name="authentication_mode" class="form-control">
                                        <option value="firebase" <?php echo  isset($authentication_mode) && $authentication_mode == 'firebase' ? 'selected' : '' ?>>Firebase</option>
                                        <option value="sms_gateway" <?php echo  isset($authentication_mode) && $authentication_mode == 'sms_gateway' ? 'selected' : '' ?>>SMS Gateway</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12 ">
                                <div class="form-group">
                                    <label for='logo'><?= labels('login_image', "Login Image") ?></label>
                                    <i data-content="<?= labels('data_content_for_login_image', "This picture will appear as the background on the login pages for the admin and provider panels.") ?>" class="fa fa-question-circle" data-original-title="" title=""></i></span>
                                </div>
                                <input type="file" name="login_image" class="filepond logo" id="login_image" accept="image/*">
                                <img class="settings_logo" style="border-radius: 8px" src="<?= isset($login_image) && $login_image != "" ? base_url("public/frontend/retro/" . $login_image) : base_url('public/backend/assets/img/news/img01.jpg') ?>">
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="primary_color"><?= labels('primary_color', "Primary Color") ?></label>
                                    <input type="text" onkeyup="change_color('change_color',this)" oninput="change_color('change_color',this)" class=" form-control" name="primary_color" id="primary_color" value="<?= isset($primary_color) ? $primary_color : '' ?>" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="secondary_color"><?= labels('secondary_color', "Secondary Color") ?></label>
                                    <input type="text" class=" form-control" name="secondary_color" id="secondary_color" value="<?= isset($secondary_color) ? $secondary_color : '' ?>" />
                                </div>
                            </div>
                            <div class="col-md-6 ">
                                <div class="form-group">
                                    <div class="control-label"><?= labels('booking_auto_cancle', "Booking auto cancle Duration") ?> <span class="breadcrumb-item p-3 pt-2 text-primary">
                                            <i data-content=" If the booking is not accepted by the provider before the added cancelable duration from the actual booking time, the booking will be automatically canceled. If the booking is pre-paid, the amount will be credited to the customer’s bank account.For example, if a customer books a service at 4:00 PM, and the cancelable duration is 30 minutes, if the provider does not accept the booking by 3:30 PM, the booking will be canceled." class="fa fa-question-circle" data-original-title="" title=""></i></span></div>
                                    <input type="number" class="form-control" name="booking_auto_cancle_duration" id="booking_auto_cancle_duration" value="<?= isset($booking_auto_cancle_duration) ? $booking_auto_cancle_duration : '30' ?>" />
                                </div>
                            </div>
                            <div class="col-md-6 ">
                                <div class="form-group">
                                    <div class="control-label"><?= labels('image_compression_preference', "Image Compression Preference") ?> <span class="breadcrumb-item p-3 pt-2 text-primary">
                                            <i data-content="If enabled, This high-quality image has been compressed to a lower quality, as per the quality provided in Image Compression Quality." class="fa fa-question-circle" data-original-title="" title=""></i></span></div>
                                    <select name="image_compression_preference" class="form-control" id="image_compression_preference">
                                        <option value="0" <?php echo  isset($image_compression_preference) && $image_compression_preference == '0' ? 'selected' : '' ?>>Disable</option>
                                        <option value="1" <?php echo  isset($image_compression_preference) && $image_compression_preference == '1' ? 'selected' : '' ?>>Enable</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12 mt-2" id="image_compression_quality_input">
                                <div class="form-group">
                                    <div class="control-label"><?= labels('image_compression_quality', "Image Compression Quality") ?> <span class="breadcrumb-item p-3 pt-2 text-primary">
                                            <i data-content="This high-quality image has been compressed to a lower quality, as per the quality provided here " class="fa fa-question-circle" data-original-title="" title=""></i></span></div>
                                    <input type="number" max=100 min=0 class="form-control" name="image_compression_quality" id="image_compression_quality" value="<?= isset($image_compression_quality) ? $image_compression_quality : '70' ?>" />
                                </div>
                            </div>
                            <!-- <div class="col-md-6 mt-2">
                                <div class="form-group">
                                    <div class="control-label"><?= labels('prepaid_booking_cancellation_time', "Prepaid Booking auto cancle Duration") ?>
                                        <span class="breadcrumb-item p-3 pt-2 text-primary"><i data-content=" If you don't complete the payment for a prepaid booking before the cancellation deadline, the system will cancel the booking automatically. For instance, if you book a service at 4:00 PM with a 30-minute cancellation window, and the payment is still pending by 3:30 PM, the booking will be canceled automatically.." class="fa fa-question-circle" data-original-title="" title=""></i></span>
                                    </div>
                                    <input type="number" class="form-control" name="prepaid_booking_cancellation_time" id="prepaid_booking_cancellation_time" value="<?= isset($prepaid_booking_cancellation_time) ? $prepaid_booking_cancellation_time : '30' ?>" />
                                </div>
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>
            <!-- admin logos  -->
            <div class="col-lg-4 col-md-12 col-sm-12 col-xl-4 mb-md-3 mb-sm-3 mb-3">
                <div class="card h-100">
                    <div class="row border_bottom_for_cards m-0">
                        <div class="col">
                            <div class="toggleButttonPostition"><?= labels('admin_logos', "Admin Logos") ?></div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 ">
                                <div class="form-group">
                                    <label for='logo'><?= labels('logo', "Logo") ?></label>
                                    <input type="file" name="logo" class="filepond logo" id="file" accept="image/*">
                                    <img class="settings_logo" src="<?= isset($logo) && $logo != "" ? base_url("public/uploads/site/" . $logo) : base_url('public/backend/assets/img/news/img01.jpg') ?>">
                                </div>
                            </div>
                            <div class="col-md-12 ">
                                <div class="form-group">
                                    <label for='favicon'><?= labels('favicon', "Favicon") ?></label>
                                    <input type="file" name="favicon" class="filepond logo" id="favicon" accept="image/*">
                                    <img class="settings_logo" src="<?= isset($favicon) && $favicon != "" ? base_url("public/uploads/site/" . $favicon) : base_url('public/backend/assets/img/news/img01.jpg') ?>">
                                </div>
                            </div>
                            <div class="col-md-12 ">
                                <div class="form-group">
                                    <label for='halfLogo'><?= labels('half_logo', "Half Logo") ?></label>
                                    <input type="file" name="halfLogo" class="filepond logo" id="halfLogo" accept="image/*">
                                    <img class="settings_logo" src="<?= isset($half_logo) && $half_logo != "" ? base_url("public/uploads/site/" . $half_logo) : base_url('public/backend/assets/img/news/img01.jpg') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- provider logos  -->
            <div class="col-lg-4 col-md-12 col-sm-12 col-xl-4 mb-md-3 mb-sm-3 mb-3">
                <div class="card h-100">
                    <div class="row border_bottom_for_cards m-0">
                        <div class="col ">
                            <div class="toggleButttonPostition"><?= labels('provider_logos', "Provider Logos") ?></div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 ">
                                <div class="form-group">
                                    <label for='logo'><?= labels('logo', "Logo") ?></label>
                                    <input type="file" name="partner_logo" class="filepond logo" id="partner_logo" accept="image/*">
                                    <img class="settings_logo" src="<?= isset($partner_logo) && $partner_logo != "" ? base_url("public/uploads/site/" . $partner_logo) : base_url('public/backend/assets/img/news/img01.jpg') ?>">
                                </div>
                            </div>
                            <div class="col-md-12 ">
                                <label for='favicon'><?= labels('favicon', "Favicon") ?></label>
                                <input type="file" name="partner_favicon" class="filepond logo" id="partner_favicon" accept="image/*">
                                <img class="settings_logo" src="<?= isset($partner_favicon) && $partner_favicon != "" ? base_url("public/uploads/site/" . $partner_favicon) : base_url('public/backend/assets/img/news/img01.jpg') ?>">
                            </div>
                        </div>
                        <div class="col-md-12 ">
                            <div class="form-group">
                                <label for='halfLogo'><?= labels('half_logo', "Half Logo") ?></label>
                                <input type="file" name="partner_halfLogo" class="filepond logo" id="partner_halfLogo" accept="image/*">
                                <img class="settings_logo" src="<?= isset($partner_half_logo) && $partner_half_logo != "" ? base_url("public/uploads/site/" . $partner_half_logo) : base_url('public/backend/assets/img/news/img01.jpg') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xl-12 mb-md-3 mb-sm-3 mb-3">
                <div class="card h-100">
                    <div class="row border_bottom_for_cards m-0">
                        <div class="col ">
                            <div class="toggleButttonPostition"><?= labels('company_setting', "Company Settings") ?></div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-3">
                                <div class="form-group">
                                    <label for='company_title'><?= labels('company_title', "Company Title") ?></label>
                                    <input type='text' class="form-control custome_reset" name='company_title' id='company_title' value="<?= isset($company_title) ? $company_title : '' ?>" />
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="form-group">
                                    <label for='support_email'><?= labels('support_email', "support Email") ?></label>
                                    <input type='email' class="form-control custome_reset" name='support_email' id='support_email' value="<?= isset($support_email) ? $support_email : '' ?>" />
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="form-group">
                                    <label for="phone"><?= labels('mobile', "Phone") ?></label>
                                    <input type="number" min="0" class="form-control custome_reset" name="phone" id="phone" value="<?= isset($phone) ? $phone : '' ?>" />
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="support_hours"><?= labels('support_hours', "Support Hours") ?></label>
                                <input type="text" class="form-control custome_reset" name="support_hours" id="support_hours" value="<?= isset($support_hours) ? $support_hours : '09:00 to 18:00' ?>" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <label for="copyright_details"><?= labels('copyright_details', "Copyright Details") ?></label>
                                <input type="text" class="form-control " name="copyright_details" id="copyright_details" value="<?= isset($copyright_details) ? $copyright_details : 'Enter Copyright details' ?>" />
                            </div>
                            <div class="col-md-3">
                                <label for="copyright_details"><?= labels('company_map_location', "Company Map Location") ?></label>
                                <input type="text" class="form-control" name="company_map_location" id="company_map_location" value="<?= htmlentities(isset($company_map_location) ? $company_map_location : '') ?>" />
                            </div>
                            <div class="col-md-3">
                                <label for="address"><?= labels('address', "Address") ?></label>
                                <textarea rows=1 class='form-control  custome_reset' name="address"><?= isset($address) ? $address : 'Enter Address' ?></textarea>
                            </div>
                            <div class="col-md-3">
                                <label for="short_description"><?= labels('short_description', "Short Description") ?></label>
                                <textarea rows=1 class='form-control  custome_reset' name="short_description"><?= isset($short_description) ? $short_description : 'Enter Short Description' ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xl-12 mb-md-3 mb-sm-3 mb-3">
                <div class="card h-100">
                    <div class="row border_bottom_for_cards m-0">
                        <div class="col ">
                            <div class="toggleButttonPostition"><?= labels('chat_settings', "Chat Settings") ?></div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label for='maxFilesOrImagesInOneMessage'><?= labels('maxFilesOrImagesInOneMessage', "Max File Or Images In One message") ?></label>
                                    <br>
                                    <small class="text-grey">Note: Maximum File or image allowed in one message</small>
                                    <input type='text' class="form-control custome_reset" name='maxFilesOrImagesInOneMessage' id='maxFilesOrImagesInOneMessage' value="<?= isset($maxFilesOrImagesInOneMessage) ? $maxFilesOrImagesInOneMessage : '' ?>" />
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for='maxFileSizeInMBCanBeSent'><?= labels('maxFileSizeInMBCanBeSent', "Max File Size In MB Can be sent") ?></label>
                                    <br>
                                    <small class="text-grey">Note: The maximum size (
                                        <?php
                                        $maxFileSizeStr = ini_get("upload_max_filesize");
                                        $maxFileSizeBytes = return_bytes($maxFileSizeStr);
                                        $maxFileSizeMB = $maxFileSizeBytes / (1024 * 1024); // Convert bytes to megabytes
                                        echo round($maxFileSizeMB, 2) . ' MB'; // Round to 2 decimal places for MB
                                        function return_bytes($size_str)
                                        {
                                            switch (substr($size_str, -1)) {
                                                case 'M':
                                                case 'm':
                                                    return (int)$size_str * 1048576;
                                                case 'K':
                                                case 'k':
                                                    return (int)$size_str * 1024;
                                                case 'G':
                                                case 'g':
                                                    return (int)$size_str * 1073741824;
                                                default:
                                                    return $size_str;
                                            }
                                        }
                                        ?>
                                        ) allowed for sending files</small>
                                    <input type='number' class="form-control custome_reset" max="<?= round($maxFileSizeMB, 2) ?>" name='maxFileSizeInMBCanBeSent' id='maxFileSizeInMBCanBeSent' value="<?= isset($maxFileSizeInMBCanBeSent) ? $maxFileSizeInMBCanBeSent : '' ?>" />
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label for="phone"><?= labels('maxCharactersInATextMessage', "Max Characters in a text message") ?></label>
                                    <br>
                                    <small class="text-grey">Note: The maximum number of characters allowed in a text message.</small>
                                    <input type="number" min="0" class="form-control custome_reset" name="maxCharactersInATextMessage" id="maxCharactersInATextMessage" value="<?= isset($maxCharactersInATextMessage) ? $maxCharactersInATextMessage : '' ?>" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="control-label"><?= labels('allow_pre_booking_chat', 'Allow Pre Booking Chat') ?></div>
                                    <select name="allow_pre_booking_chat" class="form-control">
                                        <option value="0" <?php echo  isset($allow_pre_booking_chat) && $allow_pre_booking_chat == '0' ? 'selected' : '' ?>>Disable</option>
                                        <option value="1" <?php echo  isset($allow_pre_booking_chat) && $allow_pre_booking_chat == '1' ? 'selected' : '' ?>>Enable</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="control-label"><?= labels('allow_post_booking_chat', 'Allow Post Booking Chat') ?></label> </div>
                                    <select name="allow_post_booking_chat" class="form-control">
                                        <option value="0" <?php echo  isset($allow_post_booking_chat) && $allow_post_booking_chat == '0' ? 'selected' : '' ?>>Disable</option>
                                        <option value="1" <?php echo  isset($allow_post_booking_chat) && $allow_post_booking_chat == '1' ? 'selected' : '' ?>>Enable</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <?php if ($permissions['update']['settings'] == 1) : ?>
        <div class="row mb-3">
            <div class="col-md d-flex justify-content-end">
                <input type='submit' name='update' id='update' value='<?= labels('save_changes', "Save") ?>' class='btn btn-lg bg-new-primary' />
            </div>
        </div>
        
        <?php endif; ?> 
        <?= form_close() ?>
    </section>
</div>
<script>
    function test() {
        $('.custome_reset').attr('value', '');
    }
    $('#customer_compulsary_update_force_update').on('change', function() {
        this.value = this.checked ? 1 : 0;
    }).change();
    $('#provider_compulsary_update_force_update').on('change', function() {
        this.value = this.checked ? 1 : 0;
    }).change();
    $('#customer_maintenance_mode').on('change', function() {
        this.value = this.checked ? 1 : 0;
    }).change();
    $('#provider_maintenance_mode').on('change', function() {
        this.value = this.checked ? 1 : 0;
    }).change();
    $('#otp_system').on('change', function() {
        this.value = this.checked ? 1 : 0;
    }).change();
</script>
<script>
    $(function() {
        $('.fa').popover({
            trigger: "hover"
        });
    })
    if (<?= isset($image_compression_preference) && $image_compression_preference == 1 ? 'true' : 'false' ?>) {
        $("#image_compression_quality_input").show();
    } else {
        $("#image_compression_quality_input").hide();
    }
    $("#image_compression_preference").change(function() {
        if (this.value == 1) {
            $("#image_compression_quality_input").show();
        } else {
            $("#image_compression_quality_input").hide();
        }
    });
</script>