<!-- Main Content -->
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
            <h1><?= labels('customer_terms_and_conditions', "Customer Terms and Conditions") ?></h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= base_url('/admin/dashboard') ?>"><i class="fas fa-home-alt text-primary"></i> <?= labels('Dashboard', 'Dashboard') ?></a></div>
                <div class="breadcrumb-item "><a href="<?= base_url('/admin/settings/system-settings') ?>"><?= labels('system_settings', "System Settings") ?></a></div>
                <div class="breadcrumb-item"><?= labels('terms_and_conditions', 'Terms & Conditions ') ?></div>
            </div>
        </div>
        <div class="">
            <ul class="nav nav-pills justify-content-center py-2 nav-fill" id="gen-list">
                <li class="nav-item">
                    <a class="nav-link active" href="<?= base_url('admin/settings/customer-terms-and-conditions') ?>" id="pills-customer_terms_and_conditions" aria-selected="false">
                        <?= labels('customer_terms_and_conditions', "Customer Terms and Conditions") ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('admin/settings/terms-and-conditions') ?>" id="pills-partner_terms_and_conditions" aria-selected="false">
                        <?= labels('partner_terms_and_conditions', "Partner Terms and Conditions") ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('admin/settings/customer-privacy-policy') ?>" id="pills-customer_privacy_policy" aria-selected="false">
                        <?= labels('customer_privacy_policy', "Customer Privacy Policy") ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('admin/settings/privacy-policy') ?>" id="pills-partner_privacy_policy" aria-selected="false">
                        <?= labels('partner_privacy_policy', "Partner Privacy Policy") ?></a>
                </li>
            </ul>
        </div>
        <form action="<?= base_url('admin/settings/customer-terms-and-conditions') ?>" method="post">
            <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
            <div class="container-fluid card p-3">
                <div class="row">
                    <div class="col-lg">
                        <label class="form-label mb-0" for="english_customer_terms_and_conditions"><?= labels('english_t&c', 'Terms and Conditions in English') ?></label>
                        <textarea rows=50 class='form-control h-50 summernotes' id="english_customer_terms_and_conditions" name="english_customer_terms_and_conditions"><?= isset($english_customer_terms_and_conditions) ? $english_customer_terms_and_conditions : 'Enter Terms & Conditions in English.' ?></textarea>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-lg">
                        <label class="form-label mb-0" for="russian_customer_terms_and_conditions"><?= labels('russian_t&c', 'Terms and Conditions in Russian') ?></label>
                        <textarea rows=50 class='form-control h-50 summernotes' id="russian_customer_terms_and_conditions" name="russian_customer_terms_and_conditions"><?= isset($russian_customer_terms_and_conditions) ? $russian_customer_terms_and_conditions : 'Enter Terms & Conditions in Russian.' ?></textarea>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-lg">
                        <label class="form-label mb-0" for="estonian_customer_terms_and_conditions"><?= labels('estonian_t&c', 'Terms and Conditions in Estonian') ?></label>
                        <textarea rows=50 class='form-control h-50 summernotes' id="estonian_customer_terms_and_conditions" name="estonian_customer_terms_and_conditions"><?= isset($estonian_customer_terms_and_conditions) ? $estonian_customer_terms_and_conditions : 'Enter Terms & Conditions in Estonian.' ?></textarea>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6 mt-3 mb-4">
                        <a href="<?= base_url('admin/settings/customer_terms_and_condition'); ?>" class="btn btn-primary"><i class="fa fa-eye"></i> <?= labels('preview', 'Preview') ?></a>
                    </div>
                    <?php if ($permissions['update']['settings'] == 1) : ?>

                        <div class="col-md d-flex justify-content-end mt-3">
                            <div class="form-group">
                                <input type="submit" name="update" id="update" value="<?= labels('save_changes', 'Update') ?>" class="btn btn-primary" />
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </form>
    </section>
</div>