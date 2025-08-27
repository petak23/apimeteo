-- Adminer 5.3.0 MySQL 8.0.43-0ubuntu0.22.04.1 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `blobs`;
CREATE TABLE `blobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `device_id` smallint NOT NULL,
  `data_time` datetime NOT NULL,
  `server_time` datetime NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `extension` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `session_id` mediumint DEFAULT NULL,
  `remote_ip` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT '0',
  `filesize` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;


DROP TABLE IF EXISTS `device_classes`;
CREATE TABLE `device_classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `desc` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

INSERT INTO `device_classes` (`id`, `desc`) VALUES
(1,	'CONTINUOUS_MINMAXAVG'),
(2,	'CONTINUOUS'),
(3,	'IMPULSE_SUM');

DROP TABLE IF EXISTS `devices`;
CREATE TABLE `devices` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `passphrase` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `first_login` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_bad_login` datetime DEFAULT NULL,
  `user_id` smallint NOT NULL,
  `json_token` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `blob_token` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `monitoring` tinyint DEFAULT NULL,
  `app_name` varchar(256) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `uptime` int DEFAULT NULL,
  `rssi` smallint DEFAULT NULL,
  `config_ver` smallint DEFAULT NULL,
  `config_data` text CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_main` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='List of devices. Device has one or more Sensors.';

INSERT INTO `devices` (`id`, `passphrase`, `name`, `desc`, `first_login`, `last_login`, `last_bad_login`, `user_id`, `json_token`, `blob_token`, `monitoring`, `app_name`, `uptime`, `rssi`, `config_ver`, `config_data`) VALUES
(1,	'46c2e0cd0320dec47bf9a5456eb180df',	'ie:teplomer',	'Teplomer v spálni',	NULL,	NULL,	NULL,	2,	'0mudc9zpecsqbw2k218a3l9rkqr1pq0taxuxk7b3',	'7d59ajxxp488vaut2wfcqbskzq9um33ruhmmt6yh',	1,	NULL,	NULL,	NULL,	NULL,	NULL),
(2,	'1331189df2eca5cf4812d57de54d8a4f',	'AA:pokus',	'Toto je popis',	NULL,	NULL,	NULL,	1,	'wo8ojizg8k69nhl3009qzlh93akyof4cbjyu2t4i',	'02zh5z2cm3zvejp5kamszhht3xi09xdl2shejrui',	1,	NULL,	NULL,	NULL,	NULL,	NULL),
(3,	'e746ab4a3c88f1df07b4763ae23b1ad0',	'AA:meteozahradka',	'Toto je pokusné meteorologické zariadenie, určené na testovanie.',	NULL,	NULL,	NULL,	1,	'7pef3qavn1iq9qju2i04eqfi84f8knf67neiobej',	'z0x5v64fkt1siw5mdesejuumkdxtmn9yr5x8oeaz',	1,	NULL,	NULL,	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `lang`;
CREATE TABLE `lang` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '[A]Index',
  `acronym` varchar(3) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT 'sk' COMMENT 'Skratka jazyka',
  `name` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT 'Slovenčina' COMMENT 'Miestny názov jazyka',
  `name_en` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT 'Slovak' COMMENT 'Anglický názov jazyka',
  `accepted` tinyint NOT NULL DEFAULT '0' COMMENT 'Ak je > 0 jazyk je možné použiť na Frond',
  PRIMARY KEY (`id`),
  UNIQUE KEY `acronym` (`acronym`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Jazyky pre web';

INSERT INTO `lang` (`id`, `acronym`, `name`, `name_en`, `accepted`) VALUES
(1,	'sk',	'Slovenčina',	'Slovak',	1);

DROP TABLE IF EXISTS `main_menu`;
CREATE TABLE `main_menu` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '[A]Index',
  `name` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL COMMENT 'Zobrazený názov položky',
  `link` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL COMMENT 'Odkaz',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Hlavné menu';

INSERT INTO `main_menu` (`id`, `name`, `link`) VALUES
(1,	'Môj účet',	'Admin:Inventory:User'),
(2,	'Zariadenia',	'Admin:Device:List'),
(3,	'Grafy',	'View:Views'),
(4,	'Kódy jednotiek',	'Admin:Units:Default'),
(5,	'Uživatelia',	'Admin:User:Default'),
(6,	'Editácia ACL',	'Admin:UserAcl:');

DROP TABLE IF EXISTS `measures`;
CREATE TABLE `measures` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sensor_id` smallint NOT NULL,
  `data_time` datetime NOT NULL COMMENT 'timestamp of data recording',
  `server_time` datetime NOT NULL COMMENT 'timestamp where data has been received by server',
  `s_value` double NOT NULL COMMENT 'data measured (raw)',
  `session_id` mediumint DEFAULT NULL,
  `remote_ip` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `out_value` double DEFAULT NULL COMMENT 'processed value',
  `status` tinyint NOT NULL DEFAULT '0' COMMENT '0 = received, 1 = processed, 2 = exported',
  PRIMARY KEY (`id`),
  KEY `device_id_sensor_id_data_time_id` (`sensor_id`,`data_time`,`id`),
  KEY `status_id` (`status`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Recorded data - raw. SUMDATA are created from recorded data, and old data are deleted from MEASURES.';


DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `rauser_id` int DEFAULT NULL,
  `device_id` int DEFAULT NULL,
  `sensor_id` int DEFAULT NULL,
  `event_type` tinyint NOT NULL COMMENT '1 sensor max, 2 sensor min, 3 device se nepripojuje, 4 senzor neposila data',
  `event_ts` datetime NOT NULL,
  `status` int NOT NULL DEFAULT '0' COMMENT '0 vygenerováno, 1 odeslán mail',
  `custom_text` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `out_value` double DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;


DROP TABLE IF EXISTS `prelogin`;
CREATE TABLE `prelogin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hash` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `device_id` smallint NOT NULL,
  `started` datetime NOT NULL,
  `remote_ip` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `session_key` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Sem se ukládají session po akci LOGINA - před tím, než je zařízení potvrdí via LOGINB';


DROP TABLE IF EXISTS `sensors`;
CREATE TABLE `sensors` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `device_id` smallint NOT NULL,
  `channel_id` smallint DEFAULT NULL,
  `name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `id_device_classes` int NOT NULL COMMENT 'Typ snímania',
  `id_value_types` int NOT NULL COMMENT 'Jednotky',
  `msg_rate` int NOT NULL COMMENT 'expected delay between messages',
  `desc` varchar(256) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `display_nodata_interval` int NOT NULL DEFAULT '7200' COMMENT 'how long interval will be detected as "no data"',
  `preprocess_data` tinyint NOT NULL DEFAULT '0' COMMENT '0 = no, 1 = yes',
  `preprocess_factor` double DEFAULT NULL COMMENT 'out = factor * sensor_data',
  `last_data_time` datetime DEFAULT NULL,
  `last_out_value` double DEFAULT NULL,
  `data_session` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `imp_count` bigint DEFAULT NULL,
  `warn_max` tinyint NOT NULL DEFAULT '0',
  `warn_max_after` int NOT NULL DEFAULT '0' COMMENT 'za jak dlouho se má poslat',
  `warn_max_val` double DEFAULT NULL,
  `warn_max_val_off` double DEFAULT NULL COMMENT 'vypínací hodnota',
  `warn_max_text` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `warn_max_fired` datetime DEFAULT NULL,
  `warn_max_sent` tinyint NOT NULL DEFAULT '0' COMMENT '0 = ne, 1 = posláno',
  `warn_min` tinyint NOT NULL DEFAULT '0',
  `warn_min_after` int NOT NULL DEFAULT '0' COMMENT 'za jak dlouho se má poslat',
  `warn_min_val` double DEFAULT NULL,
  `warn_min_val_off` double DEFAULT NULL COMMENT 'vypínací hodnota',
  `warn_min_text` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci DEFAULT NULL,
  `warn_min_fired` datetime DEFAULT NULL,
  `warn_min_sent` tinyint DEFAULT '0' COMMENT '0 = ne, 1 = posláno',
  `warn_noaction_fired` datetime DEFAULT NULL,
  `warning_icon` tinyint DEFAULT '0' COMMENT 'Upozornenie na chýbajúca dáta',
  PRIMARY KEY (`id`),
  KEY `device_id_name` (`device_id`,`name`),
  KEY `device_id_channel_id_name` (`device_id`,`channel_id`,`name`),
  KEY `device_class` (`id_device_classes`),
  KEY `value_type` (`id_value_types`),
  CONSTRAINT `sensors_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`),
  CONSTRAINT `sensors_ibfk_3` FOREIGN KEY (`id_value_types`) REFERENCES `value_types` (`id`),
  CONSTRAINT `sensors_ibfk_4` FOREIGN KEY (`id_device_classes`) REFERENCES `device_classes` (`id`),
  CONSTRAINT `sensors_ibfk_5` FOREIGN KEY (`id_value_types`) REFERENCES `value_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='List of sensors. Each sensor is a part of one DEVICE.';

INSERT INTO `sensors` (`id`, `device_id`, `channel_id`, `name`, `id_device_classes`, `id_value_types`, `msg_rate`, `desc`, `display_nodata_interval`, `preprocess_data`, `preprocess_factor`, `last_data_time`, `last_out_value`, `data_session`, `imp_count`, `warn_max`, `warn_max_after`, `warn_max_val`, `warn_max_val_off`, `warn_max_text`, `warn_max_fired`, `warn_max_sent`, `warn_min`, `warn_min_after`, `warn_min_val`, `warn_min_val_off`, `warn_min_text`, `warn_min_fired`, `warn_min_sent`, `warn_noaction_fired`, `warning_icon`) VALUES
(3,	2,	1,	'temp',	1,	1,	1,	NULL,	7200,	0,	NULL,	NULL,	NULL,	NULL,	NULL,	0,	0,	NULL,	NULL,	NULL,	NULL,	0,	0,	0,	NULL,	NULL,	NULL,	NULL,	0,	NULL,	0);

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `hash` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `device_id` smallint NOT NULL,
  `started` datetime NOT NULL,
  `remote_ip` varchar(32) NOT NULL,
  `session_key` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_id` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Sessions on IoT interface.';


DROP TABLE IF EXISTS `sumdata`;
CREATE TABLE `sumdata` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sensor_id` smallint NOT NULL,
  `sum_type` tinyint NOT NULL COMMENT '1 = hour, 2 = day',
  `rec_date` date NOT NULL,
  `rec_hour` tinyint NOT NULL COMMENT '-1 if day value',
  `min_val` double DEFAULT NULL,
  `min_time` time DEFAULT NULL,
  `max_val` double DEFAULT NULL,
  `max_time` time DEFAULT NULL,
  `avg_val` double DEFAULT NULL,
  `sum_val` double DEFAULT NULL,
  `ct_val` tinyint NOT NULL DEFAULT '0' COMMENT 'Počet započtených hodnot (pro denní sumy)',
  `status` tinyint DEFAULT '0' COMMENT '0 = created hourly stat (= daily stat should be recomputed), 1 = used',
  PRIMARY KEY (`id`),
  KEY `sensor_id_rec_date_sum_type` (`sensor_id`,`rec_date`,`sum_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Day and hour summaries. Computed from MEASURES. Data from MEASURES are getting deleted some day; but SUMDATA are here for stay.';


DROP TABLE IF EXISTS `updates`;
CREATE TABLE `updates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `device_id` smallint NOT NULL COMMENT 'ID zařízení',
  `fromVersion` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL COMMENT 'verze, ze které se aktualizuje',
  `fileHash` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL COMMENT 'hash souboru',
  `inserted` datetime NOT NULL COMMENT 'timestamp vložení',
  `downloaded` datetime DEFAULT NULL COMMENT 'timestamp stažení',
  PRIMARY KEY (`id`),
  KEY `device_id_fromVersion` (`device_id`,`fromVersion`),
  CONSTRAINT `updates_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;


DROP TABLE IF EXISTS `user_main`;
CREATE TABLE `user_main` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `phash` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `role` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT 'user',
  `id_user_roles` int NOT NULL DEFAULT '1' COMMENT 'Rola užívateľa',
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `id_lang` int NOT NULL DEFAULT '1' COMMENT 'Jazyk užívateľa',
  `prefix` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL,
  `id_user_state` int NOT NULL DEFAULT '10',
  `bad_pwds_count` smallint NOT NULL DEFAULT '0',
  `locked_out_until` datetime DEFAULT NULL,
  `measures_retention` int NOT NULL DEFAULT '90' COMMENT 'jak dlouho se drží data v measures',
  `sumdata_retention` int NOT NULL DEFAULT '731' COMMENT 'jak dlouho se drží data v sumdata',
  `blob_retention` int NOT NULL DEFAULT '14' COMMENT 'jak dlouho se drží bloby',
  `self_enroll` tinyint NOT NULL DEFAULT '0' COMMENT '1 = self-enrolled',
  `self_enroll_code` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `self_enroll_error_count` tinyint DEFAULT '0',
  `cur_login_time` datetime DEFAULT NULL,
  `cur_login_ip` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `cur_login_browser` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `prev_login_time` datetime DEFAULT NULL,
  `prev_login_ip` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `prev_login_browser` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `last_error_time` datetime DEFAULT NULL,
  `last_error_ip` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `last_error_browser` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `monitoring_token` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `new_password_key` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL COMMENT 'Kľúč nového hesla',
  `new_password_requested` datetime DEFAULT NULL COMMENT 'Čas požiadavky na nové heslo',
  PRIMARY KEY (`id`),
  KEY `id_user_state` (`id_user_state`),
  KEY `id_user_roles` (`id_user_roles`),
  KEY `id_lang` (`id_lang`),
  CONSTRAINT `user_main_ibfk_1` FOREIGN KEY (`id_user_state`) REFERENCES `user_state` (`id`),
  CONSTRAINT `user_main_ibfk_2` FOREIGN KEY (`id_user_roles`) REFERENCES `user_roles` (`id`),
  CONSTRAINT `user_main_ibfk_3` FOREIGN KEY (`id_lang`) REFERENCES `lang` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Hlavné údaje užívateľa';

INSERT INTO `user_main` (`id`, `username`, `phash`, `role`, `id_user_roles`, `email`, `id_lang`, `prefix`, `id_user_state`, `bad_pwds_count`, `locked_out_until`, `measures_retention`, `sumdata_retention`, `blob_retention`, `self_enroll`, `self_enroll_code`, `self_enroll_error_count`, `cur_login_time`, `cur_login_ip`, `cur_login_browser`, `prev_login_time`, `prev_login_ip`, `prev_login_browser`, `last_error_time`, `last_error_ip`, `last_error_browser`, `monitoring_token`, `new_password_key`, `new_password_requested`) VALUES
(1,	'admin',	'$2y$11$kF8e3Y.8PpVviKO9eer4CusvSkj3DsSlFIoaz8z7bzwgMN0vzRTPq',	'admin,user',	4,	'petak23@echo-msz.eu',	1,	'AA',	10,	0,	'2021-11-17 09:03:13',	90,	731,	14,	0,	NULL,	0,	'2023-07-28 16:08:03',	'127.0.0.1',	'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0 / sk,cs;q=0.8,en-US;q=0.5,en;q=0.3',	'2023-07-28 16:04:27',	'127.0.0.1',	'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0 / sk,cs;q=0.8,en-US;q=0.5,en;q=0.3',	'2021-11-17 09:03:11',	'127.0.0.1',	'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:94.0) Gecko/20100101 Firefox/94.0 / sk,cs;q=0.8,en-US;q=0.5,en;q=0.3',	'',	NULL,	NULL),
(2,	'iot@echo-msz.eu',	'$2y$11$Wa6jKjPGm.XEePrMg/QtVOX50iIgWhp7KKvNrMrkbWfkrZoRBpXGq',	'user',	3,	'iot@echo-msz.eu',	1,	'ie',	10,	0,	NULL,	90,	366,	7,	1,	'8555',	0,	'2021-09-06 10:54:52',	'217.12.48.22',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:92.0) Gecko/20100101 Firefox/92.0 / sk,cs;q=0.8,en-US;q=0.5,en;q=0.3',	'2021-09-03 12:28:31',	'188.112.82.102',	'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:91.0) Gecko/20100101 Firefox/91.0 / sk,cs;q=0.8,en-US;q=0.5,en;q=0.3',	NULL,	NULL,	NULL,	'c9r6b08epfosbko3s4zccwy1w7vsenkx103dutzi',	NULL,	NULL);

DROP TABLE IF EXISTS `user_permission`;
CREATE TABLE `user_permission` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Index',
  `id_user_roles` int NOT NULL DEFAULT '0' COMMENT 'Užívateľská rola',
  `id_user_resource` int NOT NULL COMMENT 'Zdroj oprávnenia',
  `actions` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL COMMENT 'Povolenie na akciu. (Ak viac oddelené čiarkou, ak null tak všetko)',
  PRIMARY KEY (`id`),
  KEY `id_user_roles` (`id_user_roles`),
  KEY `id_user_resource` (`id_user_resource`),
  CONSTRAINT `user_permission_ibfk_1` FOREIGN KEY (`id_user_roles`) REFERENCES `user_roles` (`id`),
  CONSTRAINT `user_permission_ibfk_2` FOREIGN KEY (`id_user_resource`) REFERENCES `user_resource` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Užívateľské oprávnenia';

INSERT INTO `user_permission` (`id`, `id_user_roles`, `id_user_resource`, `actions`) VALUES
(1,	1,	1,	NULL),
(2,	1,	2,	NULL),
(3,	4,	3,	NULL),
(4,	1,	4,	NULL),
(5,	1,	5,	'deleteupdate'),
(6,	3,	5,	NULL),
(7,	1,	6,	NULL),
(8,	1,	7,	NULL),
(9,	1,	8,	NULL),
(10,	1,	9,	NULL),
(11,	1,	10,	NULL),
(12,	3,	11,	NULL),
(13,	1,	12,	NULL),
(14,	1,	13,	NULL),
(15,	1,	14,	NULL),
(16,	3,	15,	NULL),
(17,	3,	16,	NULL),
(18,	3,	17,	NULL),
(19,	4,	18,	NULL),
(20,	4,	19,	NULL);

DROP TABLE IF EXISTS `user_resource`;
CREATE TABLE `user_resource` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Index',
  `name` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL COMMENT 'Názov zdroja',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Zdroje oprávnení';

INSERT INTO `user_resource` (`id`, `name`) VALUES
(1,	'Sign'),
(2,	'Homepage'),
(3,	'Admin:User'),
(4,	'Crontask'),
(5,	'Admin:Device'),
(6,	'Enroll'),
(7,	'Error4xx'),
(8,	'Error'),
(9,	'Gallery'),
(10,	'Chart'),
(11,	'Admin:Inventory'),
(12,	'Json'),
(13,	'Monitor'),
(14,	'Ra'),
(15,	'Admin:Sensor'),
(16,	'View'),
(17,	'Vitem'),
(18,	'Admin:UserAcl'),
(19,	'Admin:Units');

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE `user_roles` (
  `id` int NOT NULL COMMENT 'Index',
  `role` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT 'guest' COMMENT 'Rola pre ACL',
  `inherited` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL COMMENT 'Dedí od roli',
  `name` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT 'Registracia cez web' COMMENT 'Názov úrovne registrácie',
  `color` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NOT NULL DEFAULT 'fff' COMMENT 'Farba pozadia',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin COMMENT='Úrovne registrácie a ich názvy';

INSERT INTO `user_roles` (`id`, `role`, `inherited`, `name`, `color`) VALUES
(1,	'guest',	NULL,	'Bez registrácie',	'fff'),
(2,	'register',	'guest',	'Registrovaný ale neaktivovaný užívateľ',	'fffc29'),
(3,	'active',	'register',	'Aktivovaný užívateľ',	'7ce300'),
(4,	'admin',	'active',	'Administrátor',	'ff6a6a');

DROP TABLE IF EXISTS `user_state`;
CREATE TABLE `user_state` (
  `id` int NOT NULL,
  `desc` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci;

INSERT INTO `user_state` (`id`, `desc`) VALUES
(1,	'čeká na zadání kódu z e-mailu'),
(10,	'aktivní'),
(90,	'zakázán administrátorem'),
(91,	'dočasně uzamčen');

DROP TABLE IF EXISTS `value_types`;
CREATE TABLE `value_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `unit` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Units for any kind of recorder values.';

INSERT INTO `value_types` (`id`, `unit`) VALUES
(1,	'°C'),
(2,	'%'),
(3,	'hPa'),
(4,	'dB'),
(5,	'ppm'),
(6,	'kWh'),
(7,	'#'),
(8,	'V'),
(9,	'sec'),
(10,	'A'),
(11,	'Ah'),
(12,	'W'),
(13,	'Wh'),
(14,	'mA'),
(15,	'mAh'),
(16,	'lx'),
(17,	'°'),
(18,	'm/s'),
(19,	'mm');

DROP TABLE IF EXISTS `view_detail`;
CREATE TABLE `view_detail` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `view_id` smallint NOT NULL COMMENT 'Reference to VIEWS',
  `vorder` smallint NOT NULL COMMENT 'Order in chart',
  `sensor_ids` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL COMMENT 'List of SENSORS, comma delimited',
  `y_axis` tinyint NOT NULL COMMENT 'Which Y-axis to use? 1 or 2',
  `view_source_id` tinyint NOT NULL COMMENT 'Which kind of data to load (references to VIEW_SOURCE)',
  `color_1` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL DEFAULT '255,0,0' COMMENT 'Color (R,G,B) for primary data',
  `color_2` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL DEFAULT '0,0,255' COMMENT 'Color (R,G,B) for comparison year',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='One serie for chart (VIEW).';


DROP TABLE IF EXISTS `view_source`;
CREATE TABLE `view_source` (
  `id` tinyint NOT NULL,
  `desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL,
  `short_desc` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Types of views. Referenced from VIEW_DETAIL.';

INSERT INTO `view_source` (`id`, `desc`, `short_desc`) VALUES
(1,	'Automatická data',	'Automatická data'),
(2,	'Denní maximum',	'Denní maximum'),
(3,	'Denní minimum',	'Denní minimum'),
(4,	'Denní průměr',	'Denní průměr'),
(5,	'Vždy detailní data - na delších pohledech pomalé!',	'Detailní data'),
(6,	'Denní součet',	'Denní suma'),
(7,	'Hodinový součet',	'Hodinová suma'),
(8,	'Hodinové maximum',	'Hodinové maximum'),
(9,	'Hodinové/denní maximum',	'Do 90denních pohledů hodinové maximum, pro delší denní maximum'),
(10,	'Hodinový/denní součet',	'Pro krátké pohledy hodinový součet, pro dlouhé denní součet (typicky pro srážky)'),
(11,	'Týdenní součet',	'Týdenní součet (pro srážky)'),
(10,	'Hodinový/denní součet',	'Pro krátké pohledy hodinový součet, pro dlouhé denní součet (typicky pro srážky)'),
(11,	'Týdenní součet',	'Týdenní součet (pro srážky)');

DROP TABLE IF EXISTS `views`;
CREATE TABLE `views` (
  `id` smallint NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL COMMENT 'Chart name - title in view window, name in left menu.',
  `vdesc` varchar(1024) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL COMMENT 'Description',
  `token` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL COMMENT 'Security token. All charts (views) with the same token will be displayed in together (with left menu for switching between)',
  `vorder` smallint NOT NULL COMMENT 'Order - highest on top.',
  `render` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL DEFAULT 'view' COMMENT 'Which renderer to use ("view" is only available now)',
  `allow_compare` tinyint NOT NULL DEFAULT '1' COMMENT 'Allow to select another year for compare?',
  `user_id` smallint NOT NULL,
  `app_name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_czech_ci NOT NULL DEFAULT 'RatatoskrIoT' COMMENT 'Application name in top menu',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_czech_ci COMMENT='Chart views. Every VIEW (chart) has 0-N series defined in VIEW_DETAILS.';


-- 2025-08-27 09:06:19 UTC
