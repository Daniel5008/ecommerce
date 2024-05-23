<?php 

namespace hcode\Model;

use \hcode\DB\Sql;
use \hcode\Model;
use \hcode\Model\User;
use \hcode\Model\Product;

class Cart extends Model {

    const SESSION = "Cart";

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

}


?>