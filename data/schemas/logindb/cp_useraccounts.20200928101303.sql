CREATE TABLE IF NOT EXISTS `cp_user_accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `account_id` int(11) unsigned NOT NULL,
  `create_date` datetime DEFAULT NULL,
  `deleted_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE INDEX master_user_id
ON cp_user_accounts (user_id);

CREATE INDEX master_accounts_id
ON cp_user_accounts (account_id);