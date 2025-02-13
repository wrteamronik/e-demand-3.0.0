<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="icon" href="<?= base_url() . 'public/uploads/site/' . $settings['favicon'] ?>" type="image/gif" sizes="16x16">
</head>
<body>
    <?php
    echo $privacy_policy['english_customer_privacy_policy'];
    echo "<br>";
    echo "<br>";
    echo $privacy_policy['russian_customer_privacy_policy'];
    echo "<br>";
    echo "<br>";
    echo $privacy_policy['estonian_customer_privacy_policy'];
    echo "<br>";
    echo "<br>";
    ?>
</body>
</html>