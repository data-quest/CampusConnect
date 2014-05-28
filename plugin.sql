--
-- Tabellenstruktur für Tabelle `campus_connect_config`
--

DROP TABLE IF EXISTS `campus_connect_config`;
CREATE TABLE `campus_connect_config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `campus_connect_course_group`
--

DROP TABLE IF EXISTS `campus_connect_course_group`;
CREATE TABLE `campus_connect_course_group` (
  `cg_id` varchar(32) NOT NULL,
  `Seminar_id` varchar(32) NOT NULL,
  `parallelgroup_id` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`cg_id`,`Seminar_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `campus_connect_entities`
--

DROP TABLE IF EXISTS `campus_connect_entities`;
CREATE TABLE `campus_connect_entities` (
  `item_id` varchar(128) NOT NULL,
  `type` varchar(20) NOT NULL,
  `foreign_id` varchar(64) DEFAULT NULL,
  `participant_id` int(11) NOT NULL,
  `data` text NOT NULL,
  PRIMARY KEY (`item_id`,`type`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `campus_connect_trees`
--

DROP TABLE IF EXISTS `campus_connect_trees`;
CREATE TABLE `campus_connect_trees` (
  `tree_id` varchar(32) NOT NULL,
  `root_id` varchar(64) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `title` varchar(128) NOT NULL,
  `mapping` enum('pending','all','manual') NOT NULL DEFAULT 'pending',
  `sem_tree_id` varchar(32) DEFAULT NULL,
  `data` text NOT NULL,
  `chdate` bigint(20) NOT NULL,
  `mkdate` bigint(20) NOT NULL,
  PRIMARY KEY (`tree_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `campus_connect_tree_items`
--

DROP TABLE IF EXISTS `campus_connect_tree_items`;
CREATE TABLE `campus_connect_tree_items` (
  `item_id` varchar(64) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `title` varchar(128) NOT NULL,
  `parent_id` varchar(64) DEFAULT NULL,
  `root_id` varchar(64) NOT NULL,
  `sem_tree_id` varchar(32) DEFAULT NULL,
  `mapped_sem_tree_id` varchar(32) DEFAULT NULL,
  `data` text NOT NULL,
  `chdate` bigint(20) NOT NULL,
  `mkdate` bigint(20) NOT NULL,
  PRIMARY KEY (`item_id`,`participant_id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `campus_connect_trigger_stack`
--

DROP TABLE IF EXISTS `campus_connect_trigger_stack`;
CREATE TABLE `campus_connect_trigger_stack` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `object_id` varchar(32) NOT NULL,
  `object_type` varchar(32) NOT NULL,
  `mkdate` bigint(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;