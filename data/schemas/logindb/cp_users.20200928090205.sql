CREATE TABLE IF NOT EXISTS `cp_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `email` varchar(39) UNIQUE NOT NULL,
  `password` varchar(255) NOT NULL,
  `group_id` tinyint(3) NOT NULL default '0',
  `birth_date` date NOT NULL,
  `last_ip` varchar(100) NOT NULL,
  `confirmed_date` datetime DEFAULT NULL,
  `confirm_code` varchar(32) DEFAULT NULL,
  `confirm_expire` datetime DEFAULT NULL,
  `unban_date` datetime DEFAULT NULL,
  `create_date` datetime DEFAULT NULL,
  `update_date` datetime DEFAULT NULL,
  `delete_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
