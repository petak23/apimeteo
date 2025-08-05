INSERT INTO `user_resource` (`name`)
VALUES ('Units');

INSERT INTO `user_permission` (`id_user_roles`, `id_user_resource`, `actions`)
SELECT '1', '24', NULL
FROM `user_permission`
WHERE `id_user_resource` = '21' AND ((`id` = '23'));

INSERT INTO `user_resource` (`name`)
VALUES ('Users:');

UPDATE `user_resource` SET `name` = 'Users' WHERE `id` = '25';

INSERT INTO `user_permission` (`id_user_roles`, `id_user_resource`, `actions`)
VALUES ('0', '25', 'default');

INSERT INTO `user_permission` (`id_user_roles`, `id_user_resource`, `actions`)
VALUES ('1', '25', 'default');

INSERT INTO `user_permission` (`id_user_roles`, `id_user_resource`, `actions`)
VALUES ('3', '25', 'users,user');

UPDATE `user_permission` SET `actions` = 'login,logout' WHERE `id` = '28';
UPDATE `user_permission` SET `actions` = 'users,user,default' WHERE `id` = '29';
UPDATE `user_permission` SET `actions` = 'logIn,logOut' WHERE `id` = '28';
UPDATE `user_permission` SET `actions` = 'users,user' WHERE `id` = '29';
UPDATE `user_permission` SET `actions` = 'logIn,logOut,default' WHERE `id` = '28';
UPDATE `user_permission` SET `actions` = 'logIn,logOut,user' WHERE `id` = '28';
UPDATE `user_permission` SET `actions` = 'users,user,default' WHERE `id` = '29';

INSERT INTO `user_resource` (`name`)
SELECT 'Devices'
FROM `user_resource`
WHERE ((`id` = '23'));

INSERT INTO `user_permission` (`id_user_roles`, `id_user_resource`, `actions`)
SELECT '3', '26', NULL
FROM `user_permission`
WHERE ((`id` = '25'));

INSERT INTO `user_resource` (`name`)
VALUES ('Comm');

INSERT INTO `user_permission` (`id_user_roles`, `id_user_resource`, `actions`)
VALUES ('1', '27', NULL);