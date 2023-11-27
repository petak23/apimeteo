<?php

declare(strict_types=1);

namespace App\Model;

use App\Exceptions;

use Nette;
use Nette\Database;
use Nette\Http\Url;
use Nette\Security\User;
use Nette\Utils\ArrayHash;
use Nette\Utils\Random;


/**
 * Model, ktory sa stara o tabulku user_main
 * 
 * Posledna zmena 25.11.2023
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2023 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.1.0
 */
class User_main extends Table
{

	use Nette\SmartObject;

	/** @var string */
	protected $tableName = 'rausers'; //'user_main';

	/** 
	 * Opravy v tabulke zaznam s danym id
	 * @param mixed $id primary key
	 * @param iterable (column => value)
	 * @return Database\Table\ActiveRow|null */
	public function save($id, $data): ?Database\Table\ActiveRow
	{
		$this->find($id)->update($data);
		return $this->find($id);
	}

	/**
	 * Nájdenie všetkých užívateľov 
	 */
	public function getUsers(bool $return_as_array = false): Database\Table\Selection|array
	{
		$out = $this->findAll()->order('username ASC');
		if ($return_as_array) {
			$_cols = $this->getTableColsInfo();
			$_tmp = [];
			foreach ($out as $o) {
				$_user = [];
				foreach ($_cols as $k => $v) {
					if ($v['type'] == "datetime") {
						$_user[$v['field']] = $o->{$v['field']}->format('d.m.Y H:i:s');
					} else {
						$_user[$v['field']] = $o->{$v['field']};
					}
				}
				$_tmp[$o->id] = $_user;
			}
			$out = $_tmp;
		}
		return $out;
	}

	/**
	 * Nájdenie info o jednom užívateľovy
	 * @param int $id primary key
	 * @return array */
	public function getUser(int $id, User $user = null, String $baseUrl = ""): array
	{
		$out = $this->find($id);
		if ($out == null) return ['error' => "User not found", 'error_n' => 1, 'user_id' => $id];
		$_cols = $this->getTableColsInfo();
		$_user = $out->toArray();
		foreach ($_cols as $k => $v) {
			if ($v['type'] == "datetime") {
				$_user[$v['field']] = $out->{$v['field']}->format('d.m.Y H:i:s');
			}
		}
		if ($_user['prev_login_ip'] != NULL) {
			$_user['prev_login_name'] = gethostbyaddr($_user['prev_login_ip']);
			if ($_user['prev_login_name'] === $_user['prev_login_ip']) {
				$_user['prev_login_name'] = NULL;
			}
		}
		if ($_user['last_error_ip'] != NULL) {
			$_user['last_error_name'] = gethostbyaddr($_user['last_error_ip']);
			if ($_user['last_error_name'] === $_user['last_error_ip']) {
				$_user['last_error_name'] = NULL;
			}
		}
		if ($_user['monitoring_token'] != null) {
			$_user['monitoringUrl'] = $baseUrl . "monitor/show/" . $_user['monitoring_token'] . "/" . $user->getId() . "/";
		} else {
			$_user['monitoringUrl'] = null;
		}
		unset($_user['phash'], $_user['role'],);

		return $_user;
	}

	/**
	 * Nájdenie info o jednom užívateľovy na základe nejakého pravidla
	 * @param string|string[] $by
	 * @return Database\Table\ActiveRow|null */
	public function getUserBy($by): ?Database\Table\ActiveRow
	{
		return $this->findOneBy($by);
	}

	/** 
	 * Vytvorenie užívateľa
	 * @param iterable $data
	 * @return Database\Table\ActiveRow|int|bool */
	public function createUser($data)
	{
		return $this->add($data);
	}

	/** 
	 * @param int $id Id uzivatela
	 * @param string $phash Hash hesla 
	 * @return Database\Table\ActiveRow|int|bool */
	public function updateUserPassword(int $id, string $phash)
	{
		return $this->save($id, ['phash' => $phash]);
	}

	/** 
	 * Oprava email-u a monitoring_token-u užívateľa
	 * @param iterable $data
	 * @return Database\Table\ActiveRow|int|bool */
	public function updateUser(int $id, $values)
	{
		return $this->save($id, [
			'email' => $values['email'],
			'monitoring_token' => $values['monitoring_token'],
			'id_lang' => $values['id_lang']
		]);
	}

	/**
	 * Založenie užívateľa pri registrácii
	 * @return ActiveRow
	 * @throws Exceptions\UserDuplicateEmailException */
	public function createEnrollUser(ArrayHash $values, string $hash, string $prefix, string $code): Database\Table\ActiveRow
	{
		if ($this->testEmail($values->email)) { // Uzivatel s takym e-mailom uz existuje
			throw new Exceptions\UserDuplicateEmailException("Duplicate e-mail");
		}

		return $this->add([
			'username'            => $values->email,
			'phash'               => $hash,
			'id_user_roles'       => 2, // Registrácia cez web
			'email'               => $values->email,
			'prefix'              => $prefix,
			'id_user_state'       => 1, // čeká na zadání kódu z e-mailu
			'self_enroll'         => 1, // self-enrolled
			'self_enroll_code'    => $code,
			'measures_retention'  => 90,
			'sumdata_retention'   => 366,
			'blob_retention'      => 7,
			'monitoring_token'    => Random::generate(40)
		]);
	}

	/**
	 * Vráti všetkých užívateľov s daným prefixom
	 * @return Database\Table\Selection */
	public function getPrefix(string $prefix): Database\Table\Selection
	{
		return $this->findBy(['prefix' => $prefix]);
	}

	/**
	 * Aktualizuje údaje špecifické pri registrácii */
	public function updateUserEnrollState(string $email, int $status, int $errCount, int $id_user_roles = 0)
	{
		$this->getUserBy(['email' => $email, 'id_user_state' => 1])
			->update([
				'id_user_state' => $status,
				'id_user_roles' => $id_user_roles,
				'self_enroll_error_count' => $errCount
			]);
	}

	/**
	 * Vymaže užívateľa pri registrácii
	 * @return int return number of affected rows */
	public function deleteUserByEmailEnroll(string $email): int
	{
		return strlen($email) ? $this->getUserBy(['email' => $email, 'id_user_state' => 1])->delete() : 0;
	}

	/** Test existencie emailu
	 * @param string $email
	 * @return bool */
	public function testEmail(string $email): bool
	{
		return $this->findBy(['email' => $email])->count() > 0 ? true : false;
	}

	/** 
	 * Zmaže užívateľa
	 * @param int $id Id užívateľa
	 * $return int Vráti počet zmazaných užívateľov */
	public function deleteUser(int $id): int
	{
		// Administrátorský účet sa nedá zmazať
		return ($id > 1) ? $this->find($id)->delete() : 0;
	}
}
