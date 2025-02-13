ALTER TABLE `categories` 
    ADD `russian_name` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL AFTER `name`;

ALTER TABLE `categories`
    ADD `estonian_name` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL AFTER `russian_name`;