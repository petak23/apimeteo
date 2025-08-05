<?php

declare(strict_types=1);

namespace App\Model;

use Nette;

/**
 * Model, ktory sa stara o tabulku user_state
 * 
 * Posledna zmena 09.06.2023
 * 
 * @author     Ing. Peter VOJTECH ml. <petak23@gmail.com>
 * @copyright  Copyright (c) 2012 - 2023 Ing. Peter VOJTECH ml.
 * @license
 * @link       http://petak23.echo-msz.eu
 * @version    1.0.0
 */
class PV_Views extends Table
{

	/** @var string */
	protected $tableName = 'views';

	public $view_detail;

	/**
	 * @param Nette\Database\Explorer $db
	 * @param Nette\Security\User $user */
	public function __construct(Nette\Database\Explorer $db)
	{
		parent::__construct($db);
		$this->view_detail = $db->table("view_detail");
	}


	public function getAllForForm(): array
	{
		return $this->findAll()->fetchPairs('id', 'desc');
	}

	public function deleteViewsForUser(int $id)
	{
		$views = $this->findBy(["user_id" => $id]);

		$this->view_detail->where("view_id", $views->select("id"));
		/*$this->database->query('
						delete from view_detail 
						where view_id in ( select id from views where user_id = ? )
				', $id);*/

		$views->delete();
		/*$this->database->query('
						delete from views where user_id = ? 
				', $id);*/
	}
}
