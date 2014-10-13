CREATE TABLE IF NOT EXISTS `trademe_tokens` (
  `trademe_token_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `oauth_token` varchar(255) NOT NULL,
  `oauth_token_secret` varchar(255) NOT NULL,
  PRIMARY KEY (`trademe_token_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

