ALTER TABLE `cp_pwchange`
ADD COLUMN `user_id` int(10) DEFAULT NULL AFTER `id`,
MODIFY `account_id` int(10) DEFAULT NULL,
MODIFY `old_password` varchar(255) DEFAULT NULL,
MODIFY `new_password` varchar(255) DEFAULT NULL;