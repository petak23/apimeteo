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