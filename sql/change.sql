-- 1️⃣ Vloženie záznamu 'Json' do user_resource, iba ak ešte neexistuje
INSERT INTO user_resource (name)
SELECT 'Json'
WHERE NOT EXISTS (
		SELECT 1 FROM user_resource WHERE name = 'Json'
);

-- 2️⃣ Vloženie oprávnenia pre 'Json' do user_permission, iba ak ešte neexistuje
INSERT INTO user_permission (id_user_roles, id_user_resource, actions)
SELECT 1, ur.id, NULL
FROM user_resource ur
WHERE ur.name = 'Json'
	AND NOT EXISTS (
			SELECT 1
			FROM user_permission up
			WHERE up.id_user_roles = 1
				AND up.id_user_resource = ur.id
	);

-- 2026-01-29

INSERT INTO `user_resource` (`name`)
VALUES ('View');

INSERT INTO `user_permission` (`id_user_roles`, `id_user_resource`, `actions`)
VALUES ('3', '14', NULL);

ALTER TABLE `view_source`
CHANGE `id` `id` int NOT NULL COMMENT 'Index' AUTO_INCREMENT PRIMARY KEY FIRST,
CHANGE `desc` `desc` varchar(255) COLLATE 'utf32_bin' NOT NULL COMMENT 'Popis' AFTER `id`,
CHANGE `short_desc` `short_desc` varchar(255) COLLATE 'utf32_bin' NOT NULL COMMENT 'Krátky popis' AFTER `desc`;

ALTER TABLE `view_detail`
CHANGE `view_source_id` `id_view_source` int(11) NOT NULL COMMENT 'Which kind of data to load (references to VIEW_SOURCE)' AFTER `y_axis`,
ADD FOREIGN KEY (`id_view_source`) REFERENCES `view_source` (`id`);

ALTER TABLE `view_detail`
CHANGE `view_id` `id_view` smallint(6) NOT NULL COMMENT 'Reference to VIEWS' AFTER `id`,
ADD FOREIGN KEY (`id_view`) REFERENCES `views` (`id`);

-- 2026-02-24

INSERT INTO user_resource (name)
SELECT 'Sensors'
WHERE NOT EXISTS (
		SELECT 1 FROM user_resource WHERE name = 'Sensors'
);
INSERT INTO user_permission (id_user_roles, id_user_resource, actions)
SELECT 1, ur.id, NULL
FROM user_resource ur
WHERE ur.name = 'Sensors'
	AND NOT EXISTS (
			SELECT 1
			FROM user_permission up
			WHERE up.id_user_roles = 1
				AND up.id_user_resource = ur.id
	);

UPDATE `user_permission` SET `id_user_resource` = '15' WHERE `id` = '7';

-- 2026-02-25

INSERT INTO user_resource (name)
SELECT 'Chart'
WHERE NOT EXISTS (
		SELECT 1 FROM user_resource WHERE name = 'Chart'
);
INSERT INTO user_permission (id_user_roles, id_user_resource, actions)
SELECT 1, ur.id, NULL
FROM user_resource ur
WHERE ur.name = 'Chart'
	AND NOT EXISTS (
			SELECT 1
			FROM user_permission up
			WHERE up.id_user_roles = 1
				AND up.id_user_resource = ur.id
	);

-- 2026-04-23 11:20:16

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `device_classes`;
CREATE TABLE `device_classes` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Index',
  `desc` varchar(50) NOT NULL COMMENT 'Popis',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf32 COLLATE=utf32_bin COMMENT='Druh merania senzora';

INSERT INTO `device_classes` (`id`, `desc`) VALUES
(1,	'CONTINUOUS_MINMAXAVG'),
(2,	'CONTINUOUS'),
(3,	'IMPULSE_SUM'),
(4,	'RAIN_SUM');

-- 2026-04-29

INSERT INTO user_resource (name)
SELECT 'Crontask'
WHERE NOT EXISTS (
		SELECT 1 FROM user_resource WHERE name = 'Crontask'
);
INSERT INTO user_permission (id_user_roles, id_user_resource, actions)
SELECT 1, ur.id, NULL
FROM user_resource ur
WHERE ur.name = 'Crontask'
	AND NOT EXISTS (
			SELECT 1
			FROM user_permission up
			WHERE up.id_user_roles = 1
				AND up.id_user_resource = ur.id
	);