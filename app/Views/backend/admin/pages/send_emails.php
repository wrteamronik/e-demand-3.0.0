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
                <h1><?= labels('email', "Email") ?></h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="<?= base_url('/admin/dashboard') ?>"><i class="fas fa-home-alt text-primary"></i> <?= labels('Dashboard', 'Dashboard') ?></a></div>
                    <div class="breadcrumb-item"><?= labels('email', 'Email') ?></div>
                </div>
            </div>
            <div class="row">
                <?php
                if ($permissions['create']['send_notification'] == 1) { ?>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="row pl-3  m-0 border_bottom_for_cards">
                                <div class="col ">
                                    <div class="toggleButttonPostition"><?= labels('send_email', 'Send Email') ?></div>
                                </div>
                            </div>
                            <?= helper('form'); ?>
                            <div class="row">
                                <div class="col-md">
                                    <div class="card-body">
                                        <?= form_open('/admin/send_email', [
                                            'method' => "post", 'class' => 'form-submit-event',
                                            'id' => 'send_email', 'enctype' => "multipart/form-data"
                                        ]); ?>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="type" class="required"><?= labels('send', "Send") ?> <?= labels('to', "To") ?> </label>
                                                    <select id="email_user_type" class="form-control select2" name="email_user_type">
                                                        <option value="all_users" selected><?= labels('all_users', "All Users") ?> </option>
                                                        <option value="provider"><?= labels('provider', "Provider") ?></option>
                                                        <option value="customer"><?= labels('customer', "Customer") ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row" id="email_provider_select">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <div class="per">
                                                        <label for="Category_item" class="required"><?= labels('provider', "Provider") ?></label>
                                                        <select id="providers" class="form-control select2 select2-hidden-accessible" name="provider_id[]" multiple>
                                                            <?php foreach ($partners as $partner) : ?>
                                                                <option value="<?= $partner['partner_id'] ?>"><?= $partner['company_name'] ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row" id="email_customer_select">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <div class="per">
                                                        <label for="Category_item" class="required"><?= labels('customer', "Customer") ?></label>
                                                        <select id="customers" class="form-control select2 select2-hidden-accessible" name="customer_id[]" multiple>
                                                            <?php foreach ($customers as $cus) : ?>
                                                                <option value="<?= $cus['id'] ?>"><?= $cus['username'] ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12  parameters">
                                                <div class="form-group">

                                                    <label><?= labels('parameters', "Parameters") ?></label>
                                                    <div class="form-group">
                                                        <button type="button" class="btn btn-primary btn-icon icon-left mb-2" data-variable="user_id"><?= labels('user_id', "User ID") ?></button>
                                                        <button type="button" class="btn btn-primary btn-icon icon-left mb-2" data-variable="user_name"><?= labels('user_name', "User Name") ?></button>
                                                        <button type="button" class="btn btn-primary btn-icon icon-left mb-2" data-variable="company_name"><?= labels('company_name', "Company name") ?></button>
                                                        <button type="button" class="btn btn-primary btn-icon icon-left mb-2" data-variable="site_url"><?= labels('site_url', "Site URL") ?></button>
                                                        <button type="button" class="btn btn-primary btn-icon icon-left mb-2" data-variable="company_contact_info"><?= labels('company_contact_info', "Company Contact Info") ?></button>
                                                        <button type="button" class="btn btn-primary btn-icon icon-left mb-2" data-variable="company_logo"><?= labels('company_logo', "Company Logo") ?></button>
                                                        <button type="button" class="btn btn-primary btn-icon icon-left mb-2" data-variable="unsubscribe_link"><?= labels('unsubscribe_link', "Unsubscribe Link") ?></button>

                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label><?= labels('subject', "Subject") ?></label>
                                                    <input class="form-control" type="text" name="subject">
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label><?= labels('bcc', "BCC") ?></label>
                                                    <input id="bcc" style="border-radius: 0.25rem!important" class="w-100" type="text" name="bcc[]" placeholder="press enter to add bcc">

                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label><?= labels('cc', "CC") ?></label>
                                                    <input id="cc" style="border-radius: 0.25rem" class="w-100" type="text" name="cc[]" placeholder="press enter to add cc">

                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="template" class="required"><?= labels('template', 'Template') ?></label>
                                                    <textarea rows="10" class="form-control h-50 summernotes custome_reset" name="template"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md d-flex justify-content-end">
                                                <button type="submit" class="btn bg-new-primary submit_btn" id="add_slider"><?= labels('send_email', "Send Email") ?></button>
                                            </div>
                                            <?= form_close() ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <div class="col-md-8">
                    <div class="container-fluid card">
                        <div class="row ">
                            <div class="col mb-3 border_bottom_for_cards">
                                <div class="toggleButttonPostition "><?= labels('email_list', 'Email List') ?></div>
                            </div>
                        </div>
                        <div class="row mt-4 mb-3 ml-1">
                            <div class="col-md-4 col-sm-2 mb-2">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="customSearch" placeholder="Search here!" aria-label="Search" aria-describedby="customSearchBtn">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="button">
                                            <i class="fa fa-search d-inline"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="dropdown d-inline ml-2">
                                <button class="btn export_download dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <?= labels('download', 'Download') ?>
                                </button>
                                <div class="dropdown-menu" x-placement="bottom-start" style="position: absolute; transform: translate3d(0px, 28px, 0px); top: 0px; left: 0px; will-change: transform;">
                                    <a class="dropdown-item" onclick="custome_export('pdf','Email list','email_list');"><?= labels('pdf', 'PDF') ?></a>
                                    <a class="dropdown-item" onclick="custome_export('excel','Email list','email_list');"><?= labels('excel', 'Excel') ?></a>
                                    <a class="dropdown-item" onclick="custome_export('csv','Email list','email_list')"><?= labels('csv', 'CSV') ?></a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg">
                            <table class="table" data-fixed-columns="true"  id="email_list" data-pagination-successively-size="2" data-detail-formatter="user_formater" data-auto-refresh="true" data-toggle="table" data-url="<?= base_url("admin/email_list") ?>" data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 25, 50, 100, 200, All]" data-search="false" data-show-columns="false" data-show-columns-search="true" data-show-refresh="false" data-sort-name="id" data-sort-order="desc" data-query-params="email_query_param">
                                <thead>
                                    <tr>
                                        <th data-field="id" class="text-center" data-visible="true" data-sortable="true"><?= labels('id', 'ID') ?></th>
                                        <th data-field="subject" class="text-center"><?= labels('subject', 'Subject') ?></th>
                                        <th data-field="type" class="text-center" data-visible="true" data-sortable="true"><?= labels('type', 'Type') ?></th>
                                        <th data-field="operations" class="text-center" data-events="email_events"><?= labels('operations', 'Operations') ?></th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    <div id="filterBackdrop"></div>
    <div class="drawer" id="filterDrawer">
        <section class="section">
            <div class="row">
                <div class="col-md-12">
                    <div class="bg-new-primary" style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center;">
                            <div class="bg-white m-3 text-new-primary" style="box-shadow: 0px 8px 26px #00b9f02e; display: inline-block; padding: 10px; height: 45px; width: 45px; border-radius: 15px;">
                                <span class="material-symbols-outlined">
                                    filter_alt
                                </span>
                            </div>
                            <h3 class="mb-0" style="display: inline-block; font-size: 16px; margin-left: 10px;"><?= labels('filters', 'Filters') ?></h3>
                        </div>
                        <div id="cancelButton" style="cursor: pointer;">
                            <span class="material-symbols-outlined mr-2">
                                cancel
                            </span>
                        </div>
                    </div>
                    <div class="row mt-4 mx-2">
                        <div class="col-md-12">
                            <div class="form-group ">
                                <label for="table_filters"><?= labels('table_filters', 'Table filters') ?></label>
                                <div id="columnToggleContainer">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    </section>
    </div>
    <script>
        $("#customSearch").on('keydown', function() {
            $('#email_list').bootstrapTable('refresh');
        });
        $(document).ready(function() {
            for_drawer("#filterButton", "#filterDrawer", "#filterBackdrop", "#cancelButton");
            var dynamicColumns = fetchColumns('user_list');
            setupColumnToggle('email_list', dynamicColumns, 'columnToggleContainer');
        });
        $(document).ready(function() {
            $('#email_provider_select,#email_customer_select').hide();
            $("#email_user_type").change(function(e) {
                if ($("#email_user_type").val() == "all_users") {
                    $("#email_customer_select,#email_provider_select").hide();
                } else if ($("#email_user_type").val() == "provider") {
                    $("#email_customer_select").hide();
                    $("#email_provider_select").show();
                } else if ($("#email_user_type").val() == "customer") {
                    $("#specificUsers").hide();
                    $("#email_provider_select").hide();
                    $("#email_customer_select").show();
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


        $('.parameters .btn').click(function() {
            let variableName = $(this).data('variable');
            let formattedText = `[[${variableName}]]`;
            tinymce.activeEditor.execCommand('mceInsertContent', false, formattedText);
        });
    </script>