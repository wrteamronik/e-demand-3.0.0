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

    echo html_entity_decode($customer_terms_conditions['english_customer_terms_and_conditions']);
    echo "<br>";

    echo html_entity_decode($customer_terms_conditions['russian_customer_terms_and_conditions']);
    echo "<br>";

    echo html_entity_decode($customer_terms_conditions['estonian_customer_terms_and_conditions']);

    ?>
</body>

</html>