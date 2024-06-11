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
			SELECT * 
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

}

?>