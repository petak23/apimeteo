-- Adminer 4.8.1 MySQL 10.4.34-MariaDB-log dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `blobs`;
CREATE TABLE `blobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` smallint(6) NOT NULL,
  `data_time` datetime NOT NULL,
  `server_time` datetime NOT NULL,
  `description` varchar(255) NOT NULL,
  `extension` varchar(50) NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `session_id` mediumint(9) DEFAULT NULL,
  `remote_ip` varchar(32) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1 = nahrano, 2 = zpracovano cron taskem (jen obrazky jpg), 3 = exportovano (jen obrazky jpg)',
  `filesize` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin;


DROP TABLE IF EXISTS `devices`;
CREATE TABLE `devices` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT COMMENT '[A] Index',
  `passphrase` varchar(100) NOT NULL COMMENT 'Hash hesla',
  `name` varchar(100) NOT NULL COMMENT 'Meno',
  `desc` varchar(255) DEFAULT NULL COMMENT 'Popis',
  `first_login` datetime DEFAULT NULL COMMENT 'Prvé prihlásenie',
  `last_login` datetime DEFAULT NULL COMMENT 'Posledné prihlásenie',
  `last_bad_login` datetime DEFAULT NULL COMMENT 'Posledné chybné prihlásenie',
  `user_id` smallint(6) NOT NULL COMMENT 'Id užívateľa',
  `json_token` varchar(255) DEFAULT NULL,
  `blob_token` varchar(255) DEFAULT NULL,
  `monitoring` tinyint(4) DEFAULT NULL,
  `app_name` varchar(256) DEFAULT NULL,
  `uptime` int(11) DEFAULT NULL COMMENT 'Dobu prevádzky alebo bezporuchovosti v sekundách',
  `rssi` smallint(6) DEFAULT NULL,
  `config_ver` smallint(6) DEFAULT NULL,
  `config_data` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin COMMENT='List of devices. Device has one or more Sensors.';

INSERT INTO `devices` (`id`, `passphrase`, `name`, `desc`, `first_login`, `last_login`, `last_bad_login`, `user_id`, `json_token`, `blob_token`, `monitoring`, `app_name`, `uptime`, `rssi`, `config_ver`, `config_data`) VALUES
(1,	'3a3a53cdc87d69ce5fa0dc3c838d4d53',	'PV:meteozahradka',	'Meteorologická stanica na záhradke',	'2023-09-12 15:11:37',	'2024-05-24 10:12:12',	NULL,	1,	'a746p2vo4pikwb1a2euvo1ofeaj6ypr20wqpvs64',	'zhaq8xz0amigbxblmflcbpssp8esv9hw7hwub2p5',	1,	'[Zmeteo_50a_02]; /home/petak23/arduino/arduino/v50a-BME280-meteozahradka/v50a-BME280-meteozahradka.ino, Nov 16 2023 06:31:58; RA 5.4.1; LS Y; OTA Y; ESP32-C3',	5262299,	-59,	NULL,	NULL),
(3,	'2bc0775814adefe3af97f10a83e6ecb4',	'PV:meteobalkon',	'Testovacia meteostanička na balkóne.',	'2023-11-02 14:11:02',	'2024-09-13 22:14:40',	'2025-07-25 12:59:35',	1,	'k5iuq1o5gq94sy44qnew64b9atxffuc5d1dgrsh8',	'09yrd07uo0gjwnewyjpfmg0vxgnze1ts5ihla4bu',	1,	'[Bmeteo_50a_02]; /home/petak23/arduino/arduino/v50a-BME280-meteobalkon/v50a-BME280-meteobalkon.ino, Nov 10 2023 10:22:19; RA 5.4.1; LS Y; OTA Y; ESP32',	15049765,	-95,	NULL,	NULL);

DROP TABLE IF EXISTS `device_classes`;
CREATE TABLE `device_classes` (
  `id` int(11) NOT NULL,
  `desc` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin;

INSERT INTO `device_classes` (`id`, `desc`) VALUES
(1,	'CONTINUOUS_MINMAXAVG'),
(2,	'CONTINUOUS'),
(3,	'IMPULSE_SUM');

DROP TABLE IF EXISTS `lang`;
CREATE TABLE `lang` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '[A]Index',
  `acronym` varchar(3) NOT NULL DEFAULT 'sk' COMMENT 'Skratka jazyka',
  `name` varchar(15) NOT NULL DEFAULT 'Slovenčina' COMMENT 'Miestny názov jazyka',
  `name_en` varchar(15) NOT NULL DEFAULT 'Slovak' COMMENT 'Anglický názov jazyka',
  `accepted` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Ak je > 0 jazyk je možné použiť na Frond',
  PRIMARY KEY (`id`),
  UNIQUE KEY `acronym` (`acronym`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin COMMENT='Jazyky pre web';

INSERT INTO `lang` (`id`, `acronym`, `name`, `name_en`, `accepted`) VALUES
(1,	'sk',	'Slovenčina',	'Slovak',	1);

DROP TABLE IF EXISTS `main_menu`;
CREATE TABLE `main_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '[A]Index',
  `name` varchar(30) NOT NULL COMMENT 'Zobrazený názov položky',
  `link` varchar(30) NOT NULL COMMENT 'Odkaz',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin COMMENT='Hlavné menu';

INSERT INTO `main_menu` (`id`, `name`, `link`) VALUES
(1,	'Môj účet',	'Admin:Inventory:User'),
(2,	'Zariadenia',	'Admin:Device:List'),
(3,	'Grafy',	'View:Views'),
(4,	'Kódy jednotiek',	'Admin:Units:Default'),
(5,	'Uživatelia',	'Admin:User:Default'),
(6,	'Editácia ACL',	'Admin:UserAcl:');

DROP TABLE IF EXISTS `measures`;
CREATE TABLE `measures` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sensor_id` smallint(6) NOT NULL,
  `data_time` datetime NOT NULL COMMENT 'timestamp of data recording',
  `server_time` datetime NOT NULL COMMENT 'timestamp where data has been received by server',
  `s_value` double NOT NULL COMMENT 'data measured (raw)',
  `session_id` mediumint(9) DEFAULT NULL,
  `remote_ip` varchar(32) DEFAULT NULL,
  `out_value` double DEFAULT NULL COMMENT 'processed value',
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 = received, 1 = processed, 2 = exported',
  PRIMARY KEY (`id`),
  KEY `device_id_sensor_id_data_time_id` (`sensor_id`,`data_time`,`id`),
  KEY `status_id` (`status`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin COMMENT='Recorded data - raw. SUMDATA are created from recorded data, and old data are deleted from MEASURES.';


DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rauser_id` int(11) DEFAULT NULL,
  `device_id` int(11) DEFAULT NULL,
  `sensor_id` int(11) DEFAULT NULL,
  `event_type` tinyint(4) NOT NULL COMMENT '1 sensor max, 2 sensor min, 3 device se nepripojuje, 4 senzor neposila data',
  `event_ts` datetime NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0 COMMENT '0 vygenerováno, 1 odeslán mail',
  `custom_text` varchar(255) DEFAULT NULL,
  `out_value` double DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin;

INSERT INTO `notifications` (`id`, `rauser_id`, `device_id`, `sensor_id`, `event_type`, `event_ts`, `status`, `custom_text`, `out_value`) VALUES
(1,	NULL,	2,	1,	4,	'2023-09-12 16:01:45',	1,	'2023-08-17 14:52:27',	0),
(2,	NULL,	2,	2,	4,	'2023-09-12 16:01:45',	1,	'2023-08-17 14:52:27',	0),
(3,	NULL,	2,	3,	4,	'2023-09-12 16:01:45',	1,	'2023-08-17 14:52:27',	0),
(4,	NULL,	1,	4,	4,	'2023-09-20 20:01:49',	1,	'2023-09-20 16:48:15',	0),
(5,	NULL,	1,	5,	4,	'2023-09-20 20:01:49',	1,	'2023-09-20 16:48:15',	0),
(6,	NULL,	1,	6,	4,	'2023-09-20 20:01:49',	1,	'2023-09-20 16:48:15',	0),
(7,	NULL,	1,	4,	-4,	'2023-09-25 20:01:33',	1,	'2023-09-25 20:01:03',	0),
(8,	NULL,	1,	5,	-4,	'2023-09-25 20:01:33',	1,	'2023-09-25 20:01:03',	0),
(9,	NULL,	1,	6,	-4,	'2023-09-25 20:01:33',	1,	'2023-09-25 20:01:03',	0),
(10,	NULL,	1,	4,	4,	'2023-10-04 20:01:47',	1,	'2023-10-04 18:20:55',	0),
(11,	NULL,	1,	5,	4,	'2023-10-04 20:01:47',	1,	'2023-10-04 18:20:55',	0),
(12,	NULL,	1,	6,	4,	'2023-10-04 20:01:47',	1,	'2023-10-04 18:20:55',	0),
(13,	NULL,	1,	4,	-4,	'2023-10-05 16:01:56',	1,	'2023-10-05 16:01:19',	0),
(14,	NULL,	1,	5,	-4,	'2023-10-05 16:01:56',	1,	'2023-10-05 16:01:19',	0),
(15,	NULL,	1,	6,	-4,	'2023-10-05 16:01:56',	1,	'2023-10-05 16:01:19',	0),
(16,	NULL,	1,	4,	4,	'2023-10-10 20:01:44',	1,	'2023-10-10 15:12:15',	0),
(17,	NULL,	1,	5,	4,	'2023-10-10 20:01:44',	1,	'2023-10-10 15:12:15',	0),
(18,	NULL,	1,	6,	4,	'2023-10-10 20:01:44',	1,	'2023-10-10 15:12:15',	0),
(19,	NULL,	1,	4,	-4,	'2023-10-11 20:01:34',	1,	'2023-10-11 20:00:54',	0),
(20,	NULL,	1,	5,	-4,	'2023-10-11 20:01:34',	1,	'2023-10-11 20:00:54',	0),
(21,	NULL,	1,	6,	-4,	'2023-10-11 20:01:34',	1,	'2023-10-11 20:00:54',	0),
(22,	NULL,	1,	4,	4,	'2023-10-18 18:01:39',	1,	'2023-10-18 16:51:48',	0),
(23,	NULL,	1,	5,	4,	'2023-10-18 18:01:39',	1,	'2023-10-18 16:51:48',	0),
(24,	NULL,	1,	6,	4,	'2023-10-18 18:01:39',	1,	'2023-10-18 16:51:48',	0),
(25,	NULL,	1,	4,	-4,	'2023-10-19 14:01:34',	1,	'2023-10-19 14:00:48',	0),
(26,	NULL,	1,	5,	-4,	'2023-10-19 14:01:34',	1,	'2023-10-19 14:00:48',	0),
(27,	NULL,	1,	6,	-4,	'2023-10-19 14:01:34',	1,	'2023-10-19 14:00:48',	0),
(28,	NULL,	1,	6,	4,	'2023-10-22 12:02:12',	1,	'2023-10-22 09:40:45',	0),
(29,	NULL,	1,	6,	-4,	'2023-10-22 20:01:26',	1,	'2023-10-22 20:00:45',	0),
(30,	NULL,	1,	4,	4,	'2023-10-28 20:01:40',	1,	'2023-10-28 18:18:40',	0),
(31,	NULL,	1,	5,	4,	'2023-10-28 20:01:40',	1,	'2023-10-28 18:18:40',	0),
(32,	NULL,	1,	6,	4,	'2023-10-28 20:01:40',	1,	'2023-10-28 18:18:40',	0),
(33,	NULL,	1,	4,	-4,	'2023-10-30 16:01:44',	1,	'2023-10-30 16:00:49',	0),
(34,	NULL,	1,	5,	-4,	'2023-10-30 16:01:44',	1,	'2023-10-30 16:00:49',	0),
(35,	NULL,	1,	6,	-4,	'2023-10-30 16:01:44',	1,	'2023-10-30 16:00:49',	0),
(36,	NULL,	3,	7,	4,	'2023-11-26 10:01:43',	1,	'2023-11-26 06:26:00',	0),
(37,	NULL,	3,	8,	4,	'2023-11-26 10:01:43',	1,	'2023-11-26 06:26:00',	0),
(38,	NULL,	3,	9,	4,	'2023-11-26 10:01:43',	1,	'2023-11-26 06:26:00',	0),
(39,	NULL,	3,	10,	4,	'2023-11-26 10:01:43',	1,	'2023-11-26 06:26:00',	0),
(40,	NULL,	3,	7,	-4,	'2023-11-26 14:01:29',	1,	'2023-11-26 14:01:04',	0),
(41,	NULL,	3,	8,	-4,	'2023-11-26 14:01:29',	1,	'2023-11-26 14:01:04',	0),
(42,	NULL,	3,	9,	-4,	'2023-11-26 14:01:29',	1,	'2023-11-26 14:01:04',	0),
(43,	NULL,	3,	10,	-4,	'2023-11-26 14:01:29',	1,	'2023-11-26 14:01:04',	0),
(44,	NULL,	3,	7,	4,	'2023-11-27 06:01:47',	1,	'2023-11-27 04:41:34',	0),
(45,	NULL,	3,	8,	4,	'2023-11-27 06:01:47',	1,	'2023-11-27 04:41:34',	0),
(46,	NULL,	3,	9,	4,	'2023-11-27 06:01:47',	1,	'2023-11-27 04:41:34',	0),
(47,	NULL,	3,	10,	4,	'2023-11-27 06:01:47',	1,	'2023-11-27 04:41:34',	0),
(48,	NULL,	3,	7,	-4,	'2023-11-27 10:01:19',	1,	'2023-11-27 09:59:51',	0),
(49,	NULL,	3,	8,	-4,	'2023-11-27 10:01:19',	1,	'2023-11-27 09:59:51',	0),
(50,	NULL,	3,	9,	-4,	'2023-11-27 10:01:19',	1,	'2023-11-27 09:59:51',	0),
(51,	NULL,	3,	10,	-4,	'2023-11-27 10:01:19',	1,	'2023-11-27 09:59:51',	0),
(52,	NULL,	3,	7,	4,	'2023-11-29 00:02:23',	1,	'2023-11-28 19:41:48',	0),
(53,	NULL,	3,	8,	4,	'2023-11-29 00:02:23',	1,	'2023-11-28 19:41:48',	0),
(54,	NULL,	3,	9,	4,	'2023-11-29 00:02:23',	1,	'2023-11-28 19:41:48',	0),
(55,	NULL,	3,	10,	4,	'2023-11-29 00:02:23',	1,	'2023-11-28 19:41:48',	0),
(56,	NULL,	3,	7,	-4,	'2023-12-04 12:02:08',	1,	'2023-12-04 11:59:10',	0),
(57,	NULL,	3,	8,	-4,	'2023-12-04 12:02:08',	1,	'2023-12-04 11:59:10',	0),
(58,	NULL,	3,	9,	-4,	'2023-12-04 12:02:08',	1,	'2023-12-04 11:59:10',	0),
(59,	NULL,	3,	10,	-4,	'2023-12-04 12:02:08',	1,	'2023-12-04 11:59:10',	0),
(60,	NULL,	3,	7,	4,	'2023-12-15 08:02:10',	1,	'2023-12-15 05:53:13',	0),
(61,	NULL,	3,	8,	4,	'2023-12-15 08:02:10',	1,	'2023-12-15 05:53:13',	0),
(62,	NULL,	3,	9,	4,	'2023-12-15 08:02:10',	1,	'2023-12-15 05:53:13',	0),
(63,	NULL,	3,	10,	4,	'2023-12-15 08:02:10',	1,	'2023-12-15 05:53:13',	0),
(64,	NULL,	3,	7,	-4,	'2023-12-16 00:02:38',	1,	'2023-12-16 00:00:36',	0),
(65,	NULL,	3,	8,	-4,	'2023-12-16 00:02:38',	1,	'2023-12-16 00:00:36',	0),
(66,	NULL,	3,	9,	-4,	'2023-12-16 00:02:38',	1,	'2023-12-16 00:00:36',	0),
(67,	NULL,	3,	10,	-4,	'2023-12-16 00:02:38',	1,	'2023-12-16 00:00:36',	0),
(68,	NULL,	3,	7,	4,	'2024-03-12 08:01:30',	1,	'2024-03-12 05:04:33',	0),
(69,	NULL,	3,	8,	4,	'2024-03-12 08:01:30',	1,	'2024-03-12 05:04:33',	0),
(70,	NULL,	3,	9,	4,	'2024-03-12 08:01:30',	1,	'2024-03-12 05:04:33',	0),
(71,	NULL,	3,	10,	4,	'2024-03-12 08:01:30',	1,	'2024-03-12 05:04:33',	0),
(72,	NULL,	3,	7,	-4,	'2024-03-12 12:01:51',	1,	'2024-03-12 11:59:47',	0),
(73,	NULL,	3,	8,	-4,	'2024-03-12 12:01:51',	1,	'2024-03-12 11:59:47',	0),
(74,	NULL,	3,	9,	-4,	'2024-03-12 12:01:51',	1,	'2024-03-12 11:59:47',	0),
(75,	NULL,	3,	10,	-4,	'2024-03-12 12:01:51',	1,	'2024-03-12 11:59:47',	0),
(76,	NULL,	1,	4,	4,	'2024-04-08 06:01:14',	1,	'2024-04-08 03:54:01',	0),
(77,	NULL,	1,	5,	4,	'2024-04-08 06:01:14',	1,	'2024-04-08 03:54:01',	0),
(78,	NULL,	1,	6,	4,	'2024-04-08 06:01:14',	1,	'2024-04-08 03:54:01',	0),
(79,	NULL,	1,	4,	-4,	'2024-04-09 18:01:04',	1,	'2024-04-09 17:56:07',	0),
(80,	NULL,	1,	5,	-4,	'2024-04-09 18:01:04',	1,	'2024-04-09 17:56:07',	0),
(81,	NULL,	1,	6,	-4,	'2024-04-09 18:01:04',	1,	'2024-04-09 17:56:07',	0),
(82,	NULL,	1,	6,	4,	'2024-05-23 16:01:18',	1,	'2024-05-23 14:54:00',	0),
(83,	NULL,	1,	4,	4,	'2024-05-23 20:01:29',	1,	'2024-05-23 18:54:02',	0),
(84,	NULL,	1,	5,	4,	'2024-05-23 20:01:29',	1,	'2024-05-23 18:54:02',	0),
(85,	NULL,	3,	7,	4,	'2024-07-16 04:01:21',	1,	'2024-07-16 00:56:28',	0),
(86,	NULL,	3,	8,	4,	'2024-07-16 04:01:21',	1,	'2024-07-16 00:56:28',	0),
(87,	NULL,	3,	9,	4,	'2024-07-16 04:01:21',	1,	'2024-07-16 00:56:29',	0),
(88,	NULL,	3,	10,	4,	'2024-07-16 04:01:21',	1,	'2024-07-16 00:56:29',	0),
(89,	NULL,	3,	7,	-4,	'2024-07-17 19:01:14',	1,	'2024-07-17 18:57:35',	0),
(90,	NULL,	3,	8,	-4,	'2024-07-17 19:01:14',	1,	'2024-07-17 18:57:35',	0),
(91,	NULL,	3,	9,	-4,	'2024-07-17 19:01:14',	1,	'2024-07-17 18:57:35',	0),
(92,	NULL,	3,	10,	-4,	'2024-07-17 19:01:14',	1,	'2024-07-17 18:57:35',	0),
(93,	NULL,	3,	8,	4,	'2024-08-19 20:01:14',	1,	'2024-08-19 15:56:00',	0),
(94,	NULL,	3,	8,	-4,	'2024-09-11 22:01:50',	1,	'2024-09-11 21:50:45',	0),
(95,	NULL,	3,	7,	4,	'2024-09-14 20:01:44',	1,	'2024-09-14 16:56:37',	0),
(96,	NULL,	3,	8,	4,	'2024-09-14 20:01:44',	1,	'2024-09-14 16:56:37',	0),
(97,	NULL,	3,	9,	4,	'2024-09-14 20:01:44',	1,	'2024-09-14 16:56:37',	0),
(98,	NULL,	3,	10,	4,	'2024-09-14 20:01:44',	1,	'2024-09-14 16:56:37',	0);

DROP TABLE IF EXISTS `prelogin`;
CREATE TABLE `prelogin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hash` varchar(20) NOT NULL,
  `device_id` smallint(6) NOT NULL,
  `started` datetime NOT NULL,
  `remote_ip` varchar(32) NOT NULL,
  `session_key` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin COMMENT='Sem se ukládají session po akci LOGINA - před tím, než je zařízení potvrdí via LOGINB';


DROP TABLE IF EXISTS `rausers`;
CREATE TABLE `rausers` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `phash` varchar(255) NOT NULL,
  `role` varchar(100) NOT NULL,
  `id_user_roles` int(11) NOT NULL DEFAULT 1 COMMENT 'Rola užívateľa',
  `email` varchar(255) NOT NULL,
  `prefix` varchar(20) NOT NULL,
  `state_id` tinyint(4) NOT NULL DEFAULT 10,
  `bad_pwds_count` smallint(6) NOT NULL DEFAULT 0,
  `locked_out_until` datetime DEFAULT NULL,
  `measures_retention` int(11) NOT NULL DEFAULT 90 COMMENT 'jak dlouho se drží data v measures',
  `sumdata_retention` int(11) NOT NULL DEFAULT 731 COMMENT 'jak dlouho se drží data v sumdata',
  `blob_retention` int(11) NOT NULL DEFAULT 14 COMMENT 'jak dlouho se drží bloby',
  `self_enroll` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1 = self-enrolled',
  `self_enroll_code` varchar(255) DEFAULT NULL,
  `self_enroll_error_count` tinyint(4) DEFAULT 0,
  `cur_login_time` datetime DEFAULT NULL,
  `cur_login_ip` varchar(32) DEFAULT NULL,
  `cur_login_browser` varchar(255) DEFAULT NULL,
  `prev_login_time` datetime DEFAULT NULL,
  `prev_login_ip` varchar(32) DEFAULT NULL,
  `prev_login_browser` varchar(255) DEFAULT NULL,
  `last_error_time` datetime DEFAULT NULL,
  `last_error_ip` varchar(32) DEFAULT NULL,
  `last_error_browser` varchar(255) DEFAULT NULL,
  `monitoring_token` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_user_roles` (`id_user_roles`),
  CONSTRAINT `rausers_ibfk_1` FOREIGN KEY (`id_user_roles`) REFERENCES `user_roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

INSERT INTO `rausers` (`id`, `username`, `phash`, `role`, `id_user_roles`, `email`, `prefix`, `state_id`, `bad_pwds_count`, `locked_out_until`, `measures_retention`, `sumdata_retention`, `blob_retention`, `self_enroll`, `self_enroll_code`, `self_enroll_error_count`, `cur_login_time`, `cur_login_ip`, `cur_login_browser`, `prev_login_time`, `prev_login_ip`, `prev_login_browser`, `last_error_time`, `last_error_ip`, `last_error_browser`, `monitoring_token`) VALUES
(1,	'admin',	'$2y$11$DReWNmWgn.WiqOYDlvkiVul3u/ADHsUNywIOjO3TxSJ5Y3iPsYjHC',	'admin,user',	4,	'petak23@echo-msz.eu',	'PV',	10,	0,	'2023-11-27 14:45:01',	90,	731,	14,	0,	NULL,	0,	'2025-07-31 11:23:41',	'217.12.60.62',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0 / sk,cs;q=0.8,en-US;q=0.5,en;q=0.3',	'2025-07-25 06:37:05',	'217.12.60.62',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0 / en-US,en;q=0.5',	'2023-11-27 14:44:59',	'188.112.89.190',	'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0 / sk,cs;q=0.8,en-US;q=0.5,en;q=0.3',	NULL);

DROP TABLE IF EXISTS `rauser_state`;
CREATE TABLE `rauser_state` (
  `id` tinyint(4) NOT NULL,
  `desc` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin;

INSERT INTO `rauser_state` (`id`, `desc`) VALUES
(1,	'čeká na zadání kódu z e-mailu'),
(10,	'aktivní'),
(90,	'zakázán administrátorem'),
(91,	'dočasně uzamčen');

DROP TABLE IF EXISTS `sensors`;
CREATE TABLE `sensors` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `device_id` smallint(6) NOT NULL,
  `channel_id` smallint(6) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `device_class` tinyint(4) NOT NULL,
  `id_value_types` int(11) NOT NULL COMMENT 'Typ jednotky',
  `msg_rate` int(11) NOT NULL COMMENT 'expected delay between messages',
  `desc` varchar(256) DEFAULT NULL,
  `display_nodata_interval` int(11) NOT NULL DEFAULT 7200 COMMENT 'how long interval will be detected as "no data"',
  `preprocess_data` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 = no, 1 = yes',
  `preprocess_factor` double DEFAULT NULL COMMENT 'out = factor * sensor_data',
  `last_data_time` datetime DEFAULT NULL,
  `last_out_value` double DEFAULT NULL,
  `data_session` varchar(20) DEFAULT NULL,
  `imp_count` bigint(20) DEFAULT NULL,
  `warn_max` tinyint(4) NOT NULL DEFAULT 0,
  `warn_max_after` int(11) NOT NULL DEFAULT 0 COMMENT 'za jak dlouho se má poslat',
  `warn_max_val` double DEFAULT NULL,
  `warn_max_val_off` double DEFAULT NULL COMMENT 'vypínací hodnota',
  `warn_max_text` varchar(255) DEFAULT NULL,
  `warn_max_fired` datetime DEFAULT NULL,
  `warn_max_sent` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0 = ne, 1 = posláno',
  `warn_min` tinyint(4) NOT NULL DEFAULT 0,
  `warn_min_after` int(11) NOT NULL DEFAULT 0 COMMENT 'za jak dlouho se má poslat',
  `warn_min_val` double DEFAULT NULL,
  `warn_min_val_off` double DEFAULT NULL COMMENT 'vypínací hodnota',
  `warn_min_text` varchar(255) DEFAULT NULL,
  `warn_min_fired` datetime DEFAULT NULL,
  `warn_min_sent` tinyint(4) DEFAULT 0 COMMENT '0 = ne, 1 = posláno',
  `warn_noaction_fired` datetime DEFAULT NULL,
  `warning_icon` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Upozornenie na chýbajúce dáta',
  PRIMARY KEY (`id`),
  KEY `device_id_name` (`device_id`,`name`),
  KEY `device_id_channel_id_name` (`device_id`,`channel_id`,`name`),
  KEY `id_value_types` (`id_value_types`),
  CONSTRAINT `sensors_ibfk_1` FOREIGN KEY (`id_value_types`) REFERENCES `value_types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin COMMENT='List of sensors. Each sensor is a part of one DEVICE.';

INSERT INTO `sensors` (`id`, `device_id`, `channel_id`, `name`, `device_class`, `id_value_types`, `msg_rate`, `desc`, `display_nodata_interval`, `preprocess_data`, `preprocess_factor`, `last_data_time`, `last_out_value`, `data_session`, `imp_count`, `warn_max`, `warn_max_after`, `warn_max_val`, `warn_max_val_off`, `warn_max_text`, `warn_max_fired`, `warn_max_sent`, `warn_min`, `warn_min_after`, `warn_min_val`, `warn_min_val_off`, `warn_min_text`, `warn_min_fired`, `warn_min_sent`, `warn_noaction_fired`, `warning_icon`) VALUES
(4,	1,	1,	'ztemp',	1,	1,	3600,	'ztemp',	7200,	0,	NULL,	'2024-05-23 18:54:02',	181.64,	NULL,	NULL,	0,	0,	NULL,	NULL,	NULL,	NULL,	0,	0,	0,	NULL,	NULL,	NULL,	NULL,	0,	'2024-05-23 20:01:29',	1),
(5,	1,	2,	'zhumi',	1,	2,	3600,	'zhumi',	7200,	0,	NULL,	'2024-05-23 18:54:02',	100,	NULL,	NULL,	0,	0,	NULL,	NULL,	NULL,	NULL,	0,	0,	0,	NULL,	NULL,	NULL,	NULL,	0,	'2024-05-23 20:01:29',	1),
(6,	1,	3,	'zpres',	1,	3,	3600,	'zpres',	7200,	0,	NULL,	'2024-05-23 14:54:00',	1023.02,	NULL,	NULL,	0,	0,	NULL,	NULL,	NULL,	NULL,	0,	0,	0,	NULL,	NULL,	NULL,	NULL,	0,	'2024-05-23 16:01:18',	1),
(7,	3,	1,	'btemp',	1,	1,	3600,	'btemp',	7200,	0,	NULL,	'2024-09-14 16:56:37',	17.31,	NULL,	NULL,	0,	0,	NULL,	NULL,	NULL,	NULL,	0,	0,	0,	NULL,	NULL,	NULL,	NULL,	0,	'2024-09-14 20:01:44',	1),
(8,	3,	2,	'bhumi',	1,	2,	3600,	'bhumi',	7200,	0,	NULL,	'2024-09-14 16:56:37',	76.45117,	NULL,	NULL,	0,	0,	NULL,	NULL,	NULL,	NULL,	0,	0,	0,	NULL,	NULL,	NULL,	NULL,	0,	'2024-09-14 20:01:44',	1),
(9,	3,	3,	'bpres',	1,	3,	3600,	'bpres',	7200,	0,	NULL,	'2024-09-14 16:56:37',	1007.886,	NULL,	NULL,	0,	0,	NULL,	NULL,	NULL,	NULL,	0,	0,	0,	NULL,	NULL,	NULL,	NULL,	0,	'2024-09-14 20:01:44',	1),
(10,	3,	4,	'blux',	1,	16,	3600,	'blux',	7200,	0,	NULL,	'2024-09-14 16:56:37',	959.9999,	NULL,	NULL,	0,	0,	NULL,	NULL,	NULL,	NULL,	0,	0,	0,	NULL,	NULL,	NULL,	NULL,	0,	'2024-09-14 20:01:44',	1);

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `hash` varchar(20) NOT NULL,
  `device_id` smallint(6) NOT NULL,
  `started` datetime NOT NULL,
  `remote_ip` varchar(32) NOT NULL,
  `session_key` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_id` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin COMMENT='Sessions on IoT interface.';

INSERT INTO `sessions` (`id`, `hash`, `device_id`, `started`, `remote_ip`, `session_key`) VALUES
(688,	'z8hEqa7z',	1,	'2024-05-24 10:12:12',	'46.34.240.71',	'fc912a383406fadea14e323a3e3000d51c0ba8bc7b81cacacda91b8298c1c2a9'),
(802,	'VUiNaH1A',	3,	'2024-09-13 22:14:40',	'178.253.187.223',	'd96207956f645eeb78992e427a25554a7d2617fb682ded9c21067ee0c373a2c9');

DROP TABLE IF EXISTS `sumdata`;
CREATE TABLE `sumdata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sensor_id` smallint(6) NOT NULL,
  `sum_type` tinyint(4) NOT NULL COMMENT '1 = hour, 2 = day',
  `rec_date` date NOT NULL,
  `rec_hour` tinyint(4) NOT NULL COMMENT '-1 if day value',
  `min_val` double DEFAULT NULL,
  `min_time` time DEFAULT NULL,
  `max_val` double DEFAULT NULL,
  `max_time` time DEFAULT NULL,
  `avg_val` double DEFAULT NULL,
  `sum_val` double DEFAULT NULL,
  `ct_val` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'Počet započtených hodnot (pro denní sumy)',
  `status` tinyint(4) DEFAULT 0 COMMENT '0 = created hourly stat (= daily stat should be recomputed), 1 = used',
  PRIMARY KEY (`id`),
  KEY `sensor_id_rec_date_sum_type` (`sensor_id`,`rec_date`,`sum_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin COMMENT='Day and hour summaries. Computed from MEASURES. Data from MEASURES are getting deleted some day; but SUMDATA are here for stay.';


DROP TABLE IF EXISTS `updates`;
CREATE TABLE `updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` smallint(6) NOT NULL COMMENT 'ID zařízení',
  `fromVersion` varchar(200) NOT NULL COMMENT 'verze, ze které se aktualizuje',
  `fileHash` varchar(100) NOT NULL COMMENT 'hash souboru',
  `inserted` datetime NOT NULL COMMENT 'timestamp vložení',
  `downloaded` datetime DEFAULT NULL COMMENT 'timestamp stažení',
  PRIMARY KEY (`id`),
  KEY `device_id_fromVersion` (`device_id`,`fromVersion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin;

INSERT INTO `updates` (`id`, `device_id`, `fromVersion`, `fileHash`, `inserted`, `downloaded`) VALUES
(6,	1,	'Zmeteo_50a_02',	'af45d9797b8aff715a87dd9f8bd08b5a2f56f5c20abb2db7c38f1838f359a12c',	'2023-11-16 06:40:58',	'2024-05-24 10:11:25');

DROP TABLE IF EXISTS `user_main`;
CREATE TABLE `user_main` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `phash` varchar(255) NOT NULL,
  `role` varchar(100) NOT NULL DEFAULT 'user',
  `id_user_roles` int(11) NOT NULL DEFAULT 1 COMMENT 'Rola užívateľa',
  `email` varchar(255) NOT NULL,
  `id_lang` int(11) NOT NULL DEFAULT 1 COMMENT 'Jazyk užívateľa',
  `prefix` varchar(20) NOT NULL,
  `id_user_state` int(11) NOT NULL DEFAULT 10,
  `bad_pwds_count` smallint(6) NOT NULL DEFAULT 0,
  `locked_out_until` datetime DEFAULT NULL,
  `measures_retention` int(11) NOT NULL DEFAULT 90 COMMENT 'jak dlouho se drží data v measures',
  `sumdata_retention` int(11) NOT NULL DEFAULT 731 COMMENT 'jak dlouho se drží data v sumdata',
  `blob_retention` int(11) NOT NULL DEFAULT 14 COMMENT 'jak dlouho se drží bloby',
  `self_enroll` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1 = self-enrolled',
  `self_enroll_code` varchar(255) DEFAULT NULL,
  `self_enroll_error_count` tinyint(4) DEFAULT 0,
  `cur_login_time` datetime DEFAULT NULL,
  `cur_login_ip` varchar(32) DEFAULT NULL,
  `cur_login_browser` varchar(255) DEFAULT NULL,
  `prev_login_time` datetime DEFAULT NULL,
  `prev_login_ip` varchar(32) DEFAULT NULL,
  `prev_login_browser` varchar(255) DEFAULT NULL,
  `last_error_time` datetime DEFAULT NULL,
  `last_error_ip` varchar(32) DEFAULT NULL,
  `last_error_browser` varchar(255) DEFAULT NULL,
  `monitoring_token` varchar(100) DEFAULT NULL,
  `new_password_key` varchar(100) DEFAULT NULL COMMENT 'Kľúč nového hesla',
  `new_password_requested` datetime DEFAULT NULL COMMENT 'Čas požiadavky na nové heslo',
  PRIMARY KEY (`id`),
  KEY `id_user_state` (`id_user_state`),
  KEY `id_user_roles` (`id_user_roles`),
  KEY `id_lang` (`id_lang`),
  CONSTRAINT `user_main_ibfk_1` FOREIGN KEY (`id_user_state`) REFERENCES `user_state` (`id`),
  CONSTRAINT `user_main_ibfk_2` FOREIGN KEY (`id_user_roles`) REFERENCES `user_roles` (`id`),
  CONSTRAINT `user_main_ibfk_3` FOREIGN KEY (`id_lang`) REFERENCES `lang` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin COMMENT='Hlavné údaje užívateľa';

INSERT INTO `user_main` (`id`, `username`, `phash`, `role`, `id_user_roles`, `email`, `id_lang`, `prefix`, `id_user_state`, `bad_pwds_count`, `locked_out_until`, `measures_retention`, `sumdata_retention`, `blob_retention`, `self_enroll`, `self_enroll_code`, `self_enroll_error_count`, `cur_login_time`, `cur_login_ip`, `cur_login_browser`, `prev_login_time`, `prev_login_ip`, `prev_login_browser`, `last_error_time`, `last_error_ip`, `last_error_browser`, `monitoring_token`, `new_password_key`, `new_password_requested`) VALUES
(1,	'admin',	'$2y$11$HiD7pethtowf5aOPT8T1nOfexBUSxEPu/UTBqIChVnMBZ/2Y1BQje',	'admin,user',	4,	'petak23@echo-msz.eu',	1,	'AA',	10,	0,	'2021-11-19 17:07:35',	90,	731,	14,	0,	NULL,	0,	'2023-07-28 12:13:32',	'217.12.60.61',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0 / sk,cs;q=0.8,en-US;q=0.5,en;q=0.3',	'2023-07-28 12:12:00',	'217.12.60.61',	'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0 / sk,cs;q=0.8,en-US;q=0.5,en;q=0.3',	'2021-11-19 17:07:03',	'188.112.68.18',	'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:94.0) Gecko/20100101 Firefox/94.0 / sk,cs;q=0.8,en-US;q=0.5,en;q=0.3',	NULL,	NULL,	NULL);

DROP TABLE IF EXISTS `user_permission`;
CREATE TABLE `user_permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Index',
  `id_user_roles` int(11) NOT NULL DEFAULT 0 COMMENT 'Užívateľská rola',
  `id_user_resource` int(11) NOT NULL COMMENT 'Zdroj oprávnenia',
  `actions` varchar(100) DEFAULT NULL COMMENT 'Povolenie na akciu. (Ak viac oddelené čiarkou, ak null tak všetko)',
  PRIMARY KEY (`id`),
  KEY `id_user_roles` (`id_user_roles`),
  KEY `id_user_resource` (`id_user_resource`),
  CONSTRAINT `user_permission_ibfk_1` FOREIGN KEY (`id_user_roles`) REFERENCES `user_roles` (`id`),
  CONSTRAINT `user_permission_ibfk_2` FOREIGN KEY (`id_user_resource`) REFERENCES `user_resource` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin COMMENT='Užívateľské oprávnenia';

INSERT INTO `user_permission` (`id`, `id_user_roles`, `id_user_resource`, `actions`) VALUES
(1,	3,	1,	NULL),
(2,	1,	2,	NULL),
(3,	4,	3,	NULL),
(4,	3,	4,	NULL),
(5,	1,	5,	NULL),
(6,	1,	6,	NULL),
(7,	3,	7,	NULL),
(8,	1,	8,	NULL),
(9,	1,	9,	'logIn,logOut,user'),
(10,	3,	9,	'users,user,default'),
(11,	4,	9,	NULL),
(12,	1,	10,	NULL),
(13,	1,	11,	NULL);

DROP TABLE IF EXISTS `user_resource`;
CREATE TABLE `user_resource` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Index',
  `name` varchar(30) NOT NULL COMMENT 'Názov zdroja',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin COMMENT='Zdroje oprávnení';

INSERT INTO `user_resource` (`id`, `name`) VALUES
(1,	'Api:Devices'),
(2,	'Api:Units'),
(3,	'Api:Users'),
(4,	'Api:Homepage'),
(5,	'Front:Homepage'),
(6,	'Home'),
(7,	'Devices'),
(8,	'Units'),
(9,	'Users'),
(10,	'Homepage'),
(11,	'Comm');

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL COMMENT 'Index',
  `role` varchar(30) NOT NULL DEFAULT 'guest' COMMENT 'Rola pre ACL',
  `inherited` varchar(30) DEFAULT NULL COMMENT 'Dedí od roli',
  `name` varchar(80) NOT NULL DEFAULT 'Registracia cez web' COMMENT 'Názov úrovne registrácie',
  `color` varchar(15) NOT NULL DEFAULT 'fff' COMMENT 'Farba pozadia',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin COMMENT='Úrovne registrácie a ich názvy';

INSERT INTO `user_roles` (`id`, `role`, `inherited`, `name`, `color`) VALUES
(1,	'guest',	NULL,	'Bez registrácie',	'fff'),
(2,	'register',	'guest',	'Registrovaný ale neaktivovaný užívateľ',	'fffc29'),
(3,	'active',	'register',	'Aktivovaný užívateľ',	'7ce300'),
(4,	'admin',	'active',	'Administrátor',	'ff6a6a');

DROP TABLE IF EXISTS `user_state`;
CREATE TABLE `user_state` (
  `id` int(11) NOT NULL,
  `desc` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin;

INSERT INTO `user_state` (`id`, `desc`) VALUES
(1,	'čeká na zadání kódu z e-mailu'),
(10,	'aktivní'),
(90,	'zakázán administrátorem'),
(91,	'dočasně uzamčen');

DROP TABLE IF EXISTS `value_types`;
CREATE TABLE `value_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '[A]Index',
  `unit` varchar(20) NOT NULL COMMENT 'Značka jednotky',
  `description` varchar(100) DEFAULT NULL COMMENT 'Popis jednotky',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin COMMENT='Units for any kind of recorder values.';

INSERT INTO `value_types` (`id`, `unit`, `description`) VALUES
(1,	'°C',	'Teplota'),
(2,	'%',	'Percento | Vlhkosť'),
(3,	'hPa',	'Tlak'),
(4,	'dB',	'Decibely'),
(5,	'ppm',	NULL),
(6,	'kWh',	NULL),
(7,	'#',	NULL),
(8,	'V',	'Napätie'),
(9,	'sec',	NULL),
(10,	'A',	'Prúd'),
(11,	'Ah',	NULL),
(12,	'W',	'Výkon'),
(13,	'Wh',	NULL),
(14,	'mA',	'miliamér'),
(15,	'mAh',	NULL),
(16,	'lx',	'Intenzita svetla'),
(17,	'°',	NULL),
(18,	'm/s',	'Rýchlosť'),
(19,	'mm',	'Milimetre');

DROP TABLE IF EXISTS `views`;
CREATE TABLE `views` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Chart name - title in view window, name in left menu.',
  `vdesc` varchar(1024) NOT NULL COMMENT 'Description',
  `token` varchar(100) NOT NULL COMMENT 'Security token. All charts (views) with the same token will be displayed in together (with left menu for switching between)',
  `vorder` smallint(6) NOT NULL COMMENT 'Order - highest on top.',
  `render` varchar(10) NOT NULL DEFAULT 'view' COMMENT 'Which renderer to use ("view" is only available now)',
  `allow_compare` tinyint(4) NOT NULL DEFAULT 1 COMMENT 'Allow to select another year for compare?',
  `user_id` smallint(6) NOT NULL,
  `app_name` varchar(100) NOT NULL DEFAULT 'RatatoskrIoT' COMMENT 'Application name in top menu',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin COMMENT='Chart views. Every VIEW (chart) has 0-N series defined in VIEW_DETAILS.';

INSERT INTO `views` (`id`, `name`, `vdesc`, `token`, `vorder`, `render`, `allow_compare`, `user_id`, `app_name`) VALUES
(1,	'Teplota',	'Porovnanie teploty namerané na balkóne a záhradke.',	'hh5tjwxhs1t0n490pfxvurnd5oaksaxxl6rpadae',	1,	'chart',	0,	1,	'Teplota'),
(2,	'Záhradka',	'Dáta zo záhradky',	'7mcp6l7i4syag7w8hnzkrieldft9i1s1sq6i44km',	2,	'chart',	1,	1,	'meteozahradka'),
(3,	'Záhradka teplota',	'Teplota meraná na záhradke',	'6wwagf3h9sa5fmdmsyic94j603g5ngql958unmlm',	2,	'chart',	0,	1,	'ZT');

DROP TABLE IF EXISTS `view_detail`;
CREATE TABLE `view_detail` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `view_id` smallint(6) NOT NULL COMMENT 'Reference to VIEWS',
  `vorder` smallint(6) NOT NULL COMMENT 'Order in chart',
  `sensor_ids` varchar(30) NOT NULL COMMENT 'List of SENSORS, comma delimited',
  `y_axis` tinyint(4) NOT NULL COMMENT 'Which Y-axis to use? 1 or 2',
  `view_source_id` tinyint(4) NOT NULL COMMENT 'Which kind of data to load (references to VIEW_SOURCE)',
  `color_1` varchar(20) NOT NULL DEFAULT '255,0,0' COMMENT 'Color (R,G,B) for primary data',
  `color_2` varchar(20) NOT NULL DEFAULT '0,0,255' COMMENT 'Color (R,G,B) for comparison year',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin COMMENT='One serie for chart (VIEW).';

INSERT INTO `view_detail` (`id`, `view_id`, `vorder`, `sensor_ids`, `y_axis`, `view_source_id`, `color_1`, `color_2`) VALUES
(3,	2,	1,	'4',	1,	1,	'30,48,80',	'53,132,228'),
(4,	2,	2,	'6',	2,	1,	'230,97,0',	'246,211,45'),
(6,	3,	2,	'4',	1,	1,	'0,0,160',	'138,138,255'),
(7,	1,	10,	'7',	1,	1,	'255,0,0',	'91,32,103'),
(8,	1,	10,	'4',	1,	1,	'33,161,222',	'16,36,87');

DROP TABLE IF EXISTS `view_source`;
CREATE TABLE `view_source` (
  `id` tinyint(4) NOT NULL,
  `desc` varchar(255) NOT NULL,
  `short_desc` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin COMMENT='Types of views. Referenced from VIEW_DETAIL.';

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
(11,	'Týdenní součet',	'Týdenní součet (pro srážky)');

-- 2025-08-27 10:15:31
