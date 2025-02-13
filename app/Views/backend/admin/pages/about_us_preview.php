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

    echo $about_us['about_us'];
    echo "<br>";
    echo $about_us['russian_about_us'];
    echo "<br>";
    echo $about_us['estonian_about_us'];
    ?>
</body>

</html>