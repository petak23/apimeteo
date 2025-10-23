UPDATE `user_permission` SET `actions` = 'users,user,default,save' WHERE `id` = '29';

ALTER TABLE `user_main`
ADD `comm_id` varchar(255) COLLATE 'utf32_bin' NULL;

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


-- 1️⃣ Vloženie záznamu 'Monitor' do user_resource, iba ak ešte neexistuje
INSERT INTO user_resource (name)
SELECT 'Monitor'
WHERE NOT EXISTS (
		SELECT 1 FROM user_resource WHERE name = 'Monitor'
);

-- 2️⃣ Vloženie oprávnenia pre 'Monitor' do user_permission, iba ak ešte neexistuje
INSERT INTO user_permission (id_user_roles, id_user_resource, actions)
SELECT 1, ur.id, NULL
FROM user_resource ur
WHERE ur.name = 'Monitor'
	AND NOT EXISTS (
			SELECT 1
			FROM user_permission up
			WHERE up.id_user_roles = 1
				AND up.id_user_resource = ur.id
	);