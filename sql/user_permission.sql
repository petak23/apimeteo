-- Adminer 5.4.0 MariaDB 10.4.32-MariaDB dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `user_permission`;
CREATE TABLE `user_permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '[A]Index',
  `id_user_roles` int(11) NOT NULL DEFAULT 0 COMMENT 'Užívateľská rola',
  `id_user_resource` int(11) NOT NULL COMMENT 'Zdroj',
  `actions` varchar(255) DEFAULT NULL COMMENT 'Povolenie na akciu. (Ak viac oddelené čiarkou, ak null tak všetko)',
  PRIMARY KEY (`id`),
  KEY `id_user_roles` (`id_user_roles`),
  KEY `id_user_resource` (`id_user_resource`),
  CONSTRAINT `user_permission_ibfk_1` FOREIGN KEY (`id_user_roles`) REFERENCES `user_roles` (`id`),
  CONSTRAINT `user_permission_ibfk_2` FOREIGN KEY (`id_user_resource`) REFERENCES `user_resource` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Užívateľské oprávnenia';

INSERT INTO `user_permission` (`id`, `id_user_roles`, `id_user_resource`, `actions`) VALUES
(1,	0,	4,	NULL),
(2,	0,	6,	NULL),
(3,	0,	7,	'default'),
(4,	0,	8,	NULL),
(5,	0,	1,	NULL),
(6,	0,	5,	NULL),
(7,	0,	2,	NULL),
(8,	0,	9,	'default'),
(9,	0,	3,	'activateNewEmail'),
(10,	1,	3,	'default,mailChange,passwordChange'),
(11,	3,	19,	'default,edit,edit2,add,add2,del'),
(12,	3,	13,	'default,edit,edit2,add,add2'),
(13,	3,	10,	NULL),
(14,	3,	15,	NULL),
(15,	4,	9,	NULL),
(16,	4,	19,	'addpol'),
(17,	4,	13,	'addpol'),
(18,	4,	12,	'default'),
(19,	4,	11,	'default'),
(20,	4,	14,	'default,edit'),
(21,	4,	18,	NULL),
(22,	4,	17,	NULL),
(23,	4,	20,	NULL),
(24,	4,	16,	'default,edit'),
(25,	5,	16,	NULL),
(26,	5,	14,	NULL),
(27,	5,	11,	NULL),
(28,	5,	12,	NULL),
(29,	5,	13,	NULL),
(30,	5,	19,	NULL),
(31,	3,	21,	NULL),
(32,	1,	23,	'default,mailChange,passwordChange,activateNewEmail'),
(33,	4,	7,	'default,edit'),
(34,	4,	25,	NULL),
(35,	0,	26,	NULL),
(36,	0,	27,	'getsubmenu,getmenu,getonemenuarticle,getonemenuarticlesp,getonehlavnemenuarticle,getfotocollagesettings,getactualuserinfo'),
(37,	0,	28,	'getactualuserinfo,testuseremail,forgottenpassword,registration,getUserNpk,resetpassword'),
(38,	0,	29,	NULL),
(39,	4,	27,	NULL),
(40,	4,	30,	NULL),
(41,	0,	31,	NULL),
(42,	4,	32,	NULL),
(45,	3,	34,	'view'),
(46,	0,	35,	NULL),
(47,	0,	36,	NULL),
(48,	0,	32,	'getall'),
(49,	0,	37,	NULL),
(50,	0,	38,	NULL),
(51,	3,	39,	NULL),
(52,	0,	30,	'product,nakup'),
(53,	4,	40,	NULL),
(54,	4,	28,	NULL),
(55,	0,	41,	'myappsettings,getnews'),
(56,	4,	41,	NULL),
(57,	5,	34,	NULL);

-- 2026-01-14 13:30:51 UTC
