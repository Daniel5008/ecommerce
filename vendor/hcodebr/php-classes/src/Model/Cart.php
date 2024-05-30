<?php 

namespace hcode\Model;

use \hcode\DB\Sql;
use \hcode\Model;
use \hcode\Model\User;
use \hcode\Model\Product;

class Cart extends Model {

    const SESSION = "Cart";
    const SESSION_ERROR = "CartError";

    public static function getFromSession()
    {

        $cart = new Cart();

        if (isset($_SESSION[Cart::SESSION]) && intval($_SESSION[Cart::SESSION]["idcart"]) > 0) {

            $cart->get(intval($_SESSION[Cart::SESSION]["idcart"]));

        } else {

            $cart->getFromSessionID();

            if (!intval($cart->getidcart()) > 0) {

                $data = array(
                    "dessessionid"=>session_id()
                );

                if (User::checkLogin(false)){
                    
                    $user = User::getFromSession();
                    
                    $data["iduser"] = $user->getiduser();

                }

                $cart->setData($data);

                $cart->save();

                $cart->setToSession();

            }

        }

        return $cart;

    }

    public function setToSession() 
    {

        $_SESSION[Cart::SESSION] = $this->getValues();

    }


    public function getFromSessionID()
    {

        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid;",
            array(
                ":dessessionid"=>session_id()
            )
        );

        if(count($results) > 0) {
            $this->setData($results[0]);
        }

    }


    public function get($idcart)
    {

        $sql = new Sql();

        $results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart;",
            array(
                ":idcart"=>$idcart
            )
        );

        if(count($results) > 0) {
            $this->setData($results[0]);
        }

    }


    public function save() 
    {

        $sql = new Sql();

        $results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)",
            array(
                ":idcart"=>$this->getidcart(),
                ":dessessionid"=>$this->getdessessionid(),
                ":iduser"=>$this->getiduser(),
                ":deszipcode"=>$this->getdeszipcode(),
                ":vlfreight"=>$this->getvlfreight(),
                ":nrdays"=>$this->getnrdays(),
            )
        );

        $this->setData($results[0]);
    
    }

    public function addProduct(Product $product)
    {

        $sql = new Sql();

        $sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES (:idcart, :idproduct)", array(
            ":idcart"=>$this->getidcart(),
            ":idproduct"=>$product->getidproduct()
        ));

        $this->getCalculateTotal();

    }

    public function removeProducts(Product $products, $all = false)
    {

        $sql = new Sql();

        if ($all) {

            $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL", array(
                ":idcart"=>$this->getidcart(),
                ":idproduct"=>$products->getidproduct()
            ));

        } else {

            $sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL LIMIT 1", array(
                ":idcart"=>$this->getidcart(),
                ":idproduct"=>$products->getidproduct()
            ));

        }

        $this->getCalculateTotal();

    }

    public function getProducts() 
    {

        $sql = new Sql();

        $result = $sql->select("SELECT tb_products.idproduct, tb_products.desproduct, tb_products.vlprice, tb_products.vlwidth, tb_products.vlheight, tb_products.vllength, tb_products.vlweight, tb_products.desurl, COUNT(*) AS quantidade, SUM(tb_products.vlprice) AS total 
            FROM tb_cartsproducts 
            INNER JOIN tb_products ON tb_cartsproducts.idproduct = tb_products.idproduct
            WHERE tb_cartsproducts.idcart = :idcart 
            AND tb_cartsproducts.dtremoved IS NULL
            GROUP BY tb_products.idproduct, tb_products.desproduct, tb_products.vlprice, tb_products.vlwidth, tb_products.vlheight, tb_products.vllength, tb_products.vlweight, tb_products.desurl
            ORDER BY tb_products.desproduct", array(
                ":idcart"=>$this->getidcart()
            ));

        return Product::checkList($result);
    }


    public function getProductsTotals()
    {

        $sql = new Sql();

        $result = $sql->select("SELECT SUM(vlprice) AS vlprice, SUM(vlwidth) AS vlwidth, SUM(vlheight) AS vlheight, SUM(vllength) AS vllength, SUM(vlweight) AS vlweight, COUNT(*) AS nrqtd
        FROM tb_products
        INNER JOIN tb_cartsproducts ON tb_products.idproduct = tb_cartsproducts.idproduct
        WHERE tb_cartsproducts.idcart = :idcart
        AND dtremoved IS NULL", array(
            ":idcart"=>$this->getidcart()
        ));

        if (count($result) > 0) {
            return $result[0];
        } else {
            return array();
        }

    }

    public function setFreight($nrzipcode)
	{

		$nrzipcode = str_replace('-', '', $nrzipcode);

		$totals = $this->getProductsTotals();

		if ($totals['nrqtd'] > 0) {

			if ($totals['vlheight'] < 2) $totals['vlheight'] = 2;
			if ($totals['vllength'] < 16) $totals['vllength'] = 16;

			$qs = http_build_query([
				'nCdEmpresa' => '',
				'sDsSenha' => '',
				'nCdServico' => '40010',
				'sCepOrigem' => '09853120',
				'sCepDestino' => $nrzipcode,
				'nVlPeso' => $totals['vlweight'],
				'nCdFormato' => '1',
				'nVlComprimento' => $totals['vllength'],
				'nVlAltura' => $totals['vlheight'],
				'nVlLargura' => $totals['vlwidth'],
				'nVlDiametro' => '0',
				'sCdMaoPropria' => 'S',
				'nVlValorDeclarado' => $totals['vlprice'],
				'sCdAvisoRecebimento' => 'S'
			]);

			$xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?" . $qs);

			$result = $xml->Servicos->cServico;

			if ($result->MsgErro != '') {

				Cart::setMsgError($result->MsgErro);
			} else {

				Cart::clearMsgError();
			}

			$this->setnrdays($result->PrazoEntrega);
			$this->setvlfreight(Cart::formatValueToDecimal($result->Valor));
			$this->setdeszipcode($nrzipcode);

			$this->save();

			return $result;
		} else {
		}
	}

    public static function formatValueToDecimal($value): float
	{

		$value = str_replace('.', '', $value);
		return str_replace(',', '.', $value);
	}

	public static function setMsgError($msg)
	{

		$_SESSION[Cart::SESSION_ERROR] = $msg;
	}

	public static function getMsgError()
	{

		$msg = (isset($_SESSION[Cart::SESSION_ERROR])) ? $_SESSION[Cart::SESSION_ERROR] : "";

		Cart::clearMsgError();

		return $msg;
	}

	public static function clearMsgError()
	{

		$_SESSION[Cart::SESSION_ERROR] = NULL;
	}

	public function updateFreight()
	{

		if ($this->getdeszipcode() != '') {

			$this->setFreight($this->getdeszipcode());
		}
	}

	public function getValues()
	{

		$this->getCalculateTotal();

		return parent::getValues();
	}

	public function getCalculateTotal()
	{

		$this->updateFreight();

		$totals = $this->getProductsTotals();

		$this->setvlsubtotal($totals['vlprice']);
		$this->setvltotal($totals['vlprice'] + (float)$this->getvlfreight());
	}

}

?>