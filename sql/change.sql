UPDATE `user_permission` SET `actions` = 'users,user,default,save' WHERE `id` = '29';
INSERT INTO `user_resource` (`name`)
VALUES ('Json');
INSERT INTO `user_permission` (`id_user_roles`, `id_user_resource`, `actions`)
VALUES ('1', '12', NULL);