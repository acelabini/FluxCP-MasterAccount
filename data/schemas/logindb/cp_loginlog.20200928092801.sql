ALTER TABLE `cp_loginlog`
ADD COLUMN `user_id` int(10) DEFAULT NULL AFTER `id`,
MODIFY `password` varchar(255) DEFAULT NULL;