<?php 

namespace hcode\Model;

use \hcode\DB\Sql;
use \hcode\Model;

class User extends Model {

    const SESSION = "User";

    public static function login($login, $password) {

        $sql = new Sql();

        $result = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
            ":LOGIN"=>$login
        ));

        if (count($result) === 0) {
            throw new \Exception("Usuário inexistente ou senha inválida!");
        }

        $data = $result[0];

        if (password_verify($password, $data["despassword"]) === true) {

            $user = new User();

            $user->setData($data);

            $_SESSION[User::SESSION] = $user->getValues();
            
            return $user;

        } else {
            throw new \Exception("Usuário inexistente ou senha inválida!");
        }

    }

    public static function verifyLogin($inadmin = true)
    {
        if (!isset($_SESSION[User::SESSION]) || !$_SESSION[User::SESSION] || !intval($_SESSION[User::SESSION]["iduser"]) > 0 || boolval($_SESSION[User::SESSION]["inadmin"]) !== $inadmin) {
            header("Location: /admin/login");
            exit;
        }
    }

    public static function logout() 
    {
        $_SESSION[User::SESSION] = null;

    }

    public static function listAll() 
    {
        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_users INNER JOIN tb_persons USING(idperson) ORDER BY tb_persons.desperson");
    }

    public function save()
    {
        $sql = new Sql();

        $result = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>$this->getdespassword(),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin(),
        ));

        $this->setData($result[0]);
    }

    public function get ($iduser) 
    {

        $sql = new Sql();

        $result = $sql->select("SELECT * FROM tb_users INNER JOIN tb_persons USING(idperson) WHERE tb_users.iduser = :iduser", array(
            ":iduser"=>$iduser
        ));

        $this->setData($result[0]);

    }

    public function update() 
    {
        $sql = new Sql();

        $result = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
            ":iduser"=>$this->getiduser(),
            ":desperson"=>$this->getdesperson(),
            ":deslogin"=>$this->getdeslogin(),
            ":despassword"=>$this->getdespassword(),
            ":desemail"=>$this->getdesemail(),
            ":nrphone"=>$this->getnrphone(),
            ":inadmin"=>$this->getinadmin(),
        ));

        $this->setData($result[0]);

    }

    public function delete() 
    {

        $sql = new Sql();

        $sql->query("CALL sp_users_delete(:iduser)", array(
            ":iduser"=>$this->getiduser()
        ));

    }
}

?>