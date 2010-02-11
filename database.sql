DROP TABLE IF EXISTS `twitterfavorites_data`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `twitterfavorites_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `source_id` int(10) unsigned NOT NULL,
  `tweet_id` varchar(255) NOT NULL,
  `title` text NOT NULL,
  `content` text,
  `author` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `published` varchar(45) NOT NULL,
  PRIMARY KEY  USING BTREE (`id`),
  UNIQUE KEY `DUPLICATES` USING BTREE (`source_id`, `tweet_id`),
  FULLTEXT KEY `SEARCH` (`content`,`title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;