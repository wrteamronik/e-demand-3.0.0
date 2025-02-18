<!DOCTYPE html>
<html lang="en">
<?php
    $data = get_settings('general_settings', true);
    isset($data['primary_color']) && $data['primary_color'] != "" ?  $primary_color = $data['primary_color'] : $primary_color =  '#05a6e8';
    isset($data['secondary_color']) && $data['secondary_color'] != "" ?  $secondary_color = $data['secondary_color'] : $secondary_color =  '#003e64';
    isset($data['primary_shadow']) && $data['primary_shadow'] != "" ?  $primary_shadow = $data['primary_shadow'] : $primary_shadow =  '#05A6E8';
    ?>
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title><?= $title ?> &mdash; <?= ( isset($data['company_title']) && $data['company_title'] != "" )? $data['company_title'] : "eDemand";?></title>

    <?= view('backend/partner/include-css') ?>
   
    <style>
        body {
            --primary-color: <?= $primary_color ?>;
            --secondary-color: <?= $secondary_color ?>;

        }
    </style>
    <script>
        var baseUrl = "<?= base_url() ?>";
        var siteUrl = "<?= site_url() ?>";
        var csrfName = "<?= csrf_token(); ?>";
        var csrfHash = "<?= csrf_hash();  ?>";
    </script>
</head>

<body>
    <div id="app">
        <div class="main-wrapper">
            <?= view('backend/partner/top_and_sidebar') ?>
            <?= view('backend/partner/pages/' . $main_page) ?>
            <?= view('backend/partner/footer') ?>
            <?= view('backend/partner/include-scripts') ?>
            <?php if (isset($_SESSION['toastMessage'])) { ?>
                <script>
                    $(document).ready(function() {
                        showToastMessage("<?= $_SESSION['toastMessage'] ?>", "<?= $_SESSION['toastMessageType'] ?>")
                    });
                </script>";
            <?php } ?>
        </div>
    </div>

</body>

</html>