<?php 

namespace hcode\Model;

use \hcode\DB\Sql;
use \hcode\Model;

class Category extends Model {

    public static function listAll() 
    {
        $sql = new Sql();

        return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");
    }

    public function save() 
    {

        $sql = new Sql();

        $result = $sql->select("CALL sp_categories_save(:idcategory, :descategory)", array(
            ":idcategory"=>$this->getidcategory(),
            ":descategory"=>$this->getdescategory()
        ));

        $this->setData($result);
        
        Category::updatefile();

    }

    public function get($idcategory)
    {

        $sql = new Sql();

        $result = $sql->select("SELECT * FROM tb_categories WHERE idcategory = :idcategory", array(
            ":idcategory"=>$idcategory
        ));

        $this->setData($result[0]);

    }

    public function delete()
    {

        $sql = new Sql();

        $sql->query("DELETE FROM tb_categories WHERE idcategory = :idcategory", array(
          ":idcategory"=>$this->getidcategory()  
        ));

        Category::updatefile();

    }

    public static function updatefile()
    {

        $categories = Category::listAll();

        $html = array();

        foreach ($categories as $row) {
            array_push($html, '<li><a href="/categories/' . $row["idcategory"] . '">' . $row['descategory'] . '</a></li>');
        }

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "categories-menu.html", implode("", $html));

    }
}

?>