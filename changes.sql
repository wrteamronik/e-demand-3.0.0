ALTER TABLE `categories` 
    ADD `russian_name` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL AFTER `name`;

ALTER TABLE `categories`
    ADD `estonian_name` VARCHAR(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL AFTER `russian_name`;

ALTER TABLE `faqs`
    CHANGE `question` `english_question` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;

ALTER TABLE `faqs` 
    ADD `russian_question` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL AFTER `english_question`;

ALTER TABLE `faqs` 
    ADD `estonian_question` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL AFTER `russian_question`;

ALTER TABLE `faqs` 
    CHANGE `answer` `english_answer` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL;

ALTER TABLE `faqs` 
    ADD `russian_answer` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL AFTER `english_answer`;

ALTER TABLE `faqs` 
    ADD `estonian_answer` MEDIUMTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL AFTER `russian_answer`;