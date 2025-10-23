<?php

declare(strict_types=1);

namespace App\Model;

use App\Exceptions;

use Nette;
use Nette\Database;
//use Nette\Http\Url;
use Nette\Security;
use Nette\Utils\ArrayHash;
use Nette\Utils\Random;


/**
 * Model, ktory sa stara o tabulku user_main
 * 
 * Posledna zmena 23.10.2025
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2025 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.1.4
 */
class User_main extends Table
{

	use Nette\SmartObject;

	/** @var string */
	protected $tableName = 'user_main';//'rausers'; 

	private $passwords;
	private $baseUrl;

	/**
	 * @param Database\Context $db
	 * @throws Nette\InvalidStateException */
	public function __construct(string $baseUrl, Database\Explorer $db, Security\Passwords $passwords)
	{
		$this->connection = $db;
		if ($this->tableName === NULL) {
			$class = get_class($this);
			throw new Nette\InvalidStateException("Názov tabuľky musí byť definovaný v $class::\$tableName.");
		}
		$this->passwords = $passwords;
		$this->baseUrl = $baseUrl;
	}

	/** 
	 * Opravy v tabulke zaznam s danym id
	 * @param mixed $id primary key
	 * @param iterable (column => value)
	 * @return Database\Table\ActiveRow|null */
	public function save($id, $data, bool $return_as_array = false): ?Database\Table\ActiveRow
	{
		$this->find($id)->update($data);
		return $return_as_array ? $this->getUser($id, $id, $this->baseUrl, true) : $this->find($id);
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
				unset($_user['phash']);
				$_tmp[$o->id] = $_user;
			}
			$out = $_tmp;
		}
		return $out;
	}

	/**
	 * Nájdenie info o jednom užívateľovy
	 * @param int $id primary key
	 * @return Database\Table\ActiveRow|array|null */
	public function getUser(int $id, int $user_id = 0, String $baseUrl = "", bool $return_as_array = false): Database\Table\ActiveRow|array|null
	{
		$out = $this->find($id);
		if ($return_as_array) {
			if ($out == null) return ['status'=> 404, 'message' => "Užívateľ s id: ".$id." sa nenašiel!", 'user_id' => $id];
			$_cols = $this->getTableColsInfo();
			$_user = [];
			foreach ($_cols as $k => $v) {
				$_user[$v['field']] = ($v['type'] == "datetime") && $out->{$v['field']} !== null
					? $out->{$v['field']}->format('d.m.Y H:i:s') : $out->{$v['field']};
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
			$_user['monitoringUrl'] = ($user_id != 0 && $_user['monitoring_token'] != null)
				? $baseUrl . "/monitor/show/" . $_user['monitoring_token'] . "/" . $user_id . "/" : null;
			unset($_user['phash']);
			$out = ['status' => 200, 'data' => $_user];
		}
		return $out;
	}

	/**
	 * Nájdenie info o jednom užívateľovy na základe nejakého pravidla
	 * @param string|string[] $by
	 * @param bool $return_as_array
	 * @return Database\Table\ActiveRow|array|null */
	public function getUserBy($by, bool $return_as_array = false): Database\Table\ActiveRow|array|null
	{
		$out = $this->findOneBy($by);
		return $return_as_array ? $this->getUser($out->id, $out->id, $this->baseUrl, true) : $out;
	}

	/** 
	 * Vytvorenie užívateľa
	 * @param iterable $data
	 * @param bool $return_as_array
	 * @return Database\Table\ActiveRow|array|null */
	public function createUser($data, bool $return_as_array = false): Database\Table\ActiveRow|array|null
	{
		$out = $this->add($data);
		return $return_as_array ? $this->getUser($out->id, $out->id, $this->baseUrl, true) : $out;
	}

	/**
	 * Zmena hesla užívateľa
	 * @param int $id Id užívateľa
	 * @param string $old_password Staré heslo
	 * @param string $new_password Nové heslo */
	public function changePassword(int $id, string $old_password, string $new_password): void
	{
		$user = $this->getUser($id);
		if ($user === null) {
			throw new Nette\InvalidArgumentException('Užívateľ nebol nájdený.');
		}

		if (!$this->passwords->verify($old_password, $user->phash)) {
			throw new Nette\InvalidArgumentException('Staré heslo je nesprávne.');
		}

		$this->save($id, ['phash' => $this->passwords->hash($new_password)]);
	}

	/** 
	 * Oprava email-u a monitoring_token-u užívateľa
	 * @param iterable $data
	 * @return Database\Table\ActiveRow|array|null */
	public function updateUser(int $id, $values, bool $return_as_array = false): Database\Table\ActiveRow|array|null
	{
		return $this->save($id, [
			'email' => $values['email'],
			'monitoring_token' => $values['monitoring_token'],
			'id_lang' => $values['id_lang']
		], $return_as_array);
	}

	/**
	 * Založenie užívateľa pri registrácii
	 * @return Database\Table\ActiveRow
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
		return ($id > 1) ? $this->getUser($id)->delete() : 0;
	}
}
