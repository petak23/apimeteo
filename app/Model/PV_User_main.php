<?php

declare(strict_types=1);

namespace App\Model;

use App\Exceptions;

use Nette;
use Nette\Database\Table;
use Nette\Utils\ArrayHash;
use Nette\Utils\Random;


/**
 * Model, ktory sa stara o tabulku user_main
 * 
 * Posledna zmena 09.06.2023
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2023 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.7
 */
class PV_User_main
{

	use Nette\SmartObject;

	const
		TABLE = 'user_main';

	private Nette\Database\Explorer $database;

	public function __construct(Nette\Database\Explorer $database)
	{
		$this->database = $database;
	}

	private function dbtable()
	{
		return $this->database->table(self::TABLE);
	}

	/** 
	 * Opravy v tabulke zaznam s danym id
	 * @param mixed $id primary key
	 * @param iterable (column => value)
	 * @return Table\ActiveRow|null */
	public function save($id, $data): ?Table\ActiveRow
	{
		$this->dbtable()->get($id)->update($data);
		return $this->dbtable()->get($id);
	}

	/**
	 * Nájdenie všetkých užívateľov
	 * @return Table\Selection */
	public function getUsers(): Table\Selection
	{
		return $this->dbtable()->order('username ASC');
	}

	/**
	 * Nájdenie info o jednom užívateľovy
	 * @param mixed $id primary key
	 * @return Table\ActiveRow|null */
	public function getUser($id): ?Table\ActiveRow
	{
		return $this->dbtable()->get($id);
	}

	/**
	 * Nájdenie info o jednom užívateľovy na základe nejakého pravidla
	 * @param string|string[] $by
	 * @return Table\ActiveRow|null */
	public function getUserBy($by): ?Table\ActiveRow
	{
		return $this->dbtable()->where($by)->limit(1)->fetch();
	}

	/** 
	 * Vytvorenie užívateľa
	 * @param iterable $data
	 * @return Table\ActiveRow|int|bool */
	public function createUser($data)
	{
		return $this->dbtable()->insert($data);
	}

	/** 
	 * @param int $id Id uzivatela
	 * @param string $phash Hash hesla 
	 * @return Table\ActiveRow|int|bool */
	public function updateUserPassword(int $id, string $phash)
	{
		return $this->save($id, ['phash' => $phash]);
	}

	/** 
	 * Oprava email-u a monitoring_token-u užívateľa
	 * @param iterable $data
	 * @return Table\ActiveRow|int|bool */
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
	public function createEnrollUser(ArrayHash $values, string $hash, string $prefix, string $code): Table\ActiveRow
	{
		if (count($this->dbtable()->where('email', $values->email)) > 0) { // Uzivatel s takym e-mailom uz existuje
			throw new Exceptions\UserDuplicateEmailException("Duplicate e-mail");
		}

		return $this->dbtable()->insert([
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
	 * @return Table\Selection */
	public function getPrefix(string $prefix): Table\Selection
	{
		return $this->dbtable()->where(['prefix' => $prefix]);
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
		return $this->dbtable()->where('email', $email)->count() > 0 ? true : false;
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
