<?php

namespace hcode\Model;

use \hcode\DB\Sql;
use \hcode\Model;
use \hcode\Model\Cart;

class Order extends Model
{

    const SUCCESS = "Order-Success";
	const ERROR = "Order-Error";

	public function save()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_orders_save(:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal)", array(
			':idorder' => $this->getidorder(),
			':idcart' => $this->getidcart(),
			':iduser' => $this->getiduser(),
			':idstatus' => $this->getidstatus(),
			':idaddress' => $this->getidaddress(),
			':vltotal' => $this->getvltotal()
        ));

		if (count($results) > 0) {
			$this->setData($results[0]);
		}
	}

	public function get($idorder)
	{

		$sql = new Sql();

		$results = $sql->select("
			SELECT *, tb_orders.dtregister
			FROM tb_orders 
			INNER JOIN tb_ordersstatus USING(idstatus) 
			INNER JOIN tb_carts USING(idcart)
			INNER JOIN tb_users ON tb_users.iduser = tb_orders.iduser
			INNER JOIN tb_addresses USING(idaddress)
			INNER JOIN tb_persons ON tb_persons.idperson = tb_users.idperson
			WHERE tb_orders.idorder = :idorder
		", array(
			':idorder' => $idorder
        ));

		if (count($results) > 0) {
			$this->setData($results[0]);
		}
	}

	public static function listAll()
	{

		$sql = new Sql();

		return $sql->select("
			SELECT * 
			FROM tb_orders 
			INNER JOIN tb_ordersstatus USING(idstatus) 
			INNER JOIN tb_carts USING(idcart)
			INNER JOIN tb_users ON tb_users.iduser = tb_orders.iduser
			INNER JOIN tb_addresses USING(idaddress)
			INNER JOIN tb_persons ON tb_persons.idperson = tb_users.idperson
			ORDER BY tb_orders.dtregister DESC
		");
	}

	public function delete()
	{

		$sql = new Sql();

		$sql->query("DELETE FROM tb_orders WHERE idorder = :idorder", array(
			':idorder' => $this->getidorder()
		));
	}

	public function getCart(): Cart
	{

		$cart = new Cart();

		$cart->get(intval($this->getidcart()));

		return $cart;
	}

	public static function setError($msg)
	{

		$_SESSION[Order::ERROR] = $msg;
	}

	public static function getError()
	{

		$msg = (isset($_SESSION[Order::ERROR]) && $_SESSION[Order::ERROR]) ? $_SESSION[Order::ERROR] : '';

		Order::clearError();

		return $msg;
	}

	public static function clearError()
	{

		$_SESSION[Order::ERROR] = NULL;
	}

	public static function setSuccess($msg)
	{

		$_SESSION[Order::SUCCESS] = $msg;
	}

	public static function getSuccess()
	{

		$msg = (isset($_SESSION[Order::SUCCESS]) && $_SESSION[Order::SUCCESS]) ? $_SESSION[Order::SUCCESS] : '';

		Order::clearSuccess();

		return $msg;
	}

	public static function clearSuccess()
	{

		$_SESSION[Order::SUCCESS] = NULL;
	}

}

?>