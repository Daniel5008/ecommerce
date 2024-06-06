<?php

use \hcode\Page;
use \hcode\Model\Product;
use \hcode\Model\Category;
use \hcode\Model\Cart;
use \hcode\Model\Address;
use \hcode\Model\User;

$app->get('/', function() {

	$products = Product::listAll();

	$page = new Page();
	$page->setTpl("index", array(
		"products"=>Product::checkList($products)
	));

});

$app->get("/categories/:idcategory", function ($idcategory){

	$pageNumber = (isset($_GET["page"])) ? intval($_GET["page"]) : 1;

	$category = new Category();

	$category->get(intval($idcategory));

	$pagination = $category->getProductsPage($pageNumber);

	$pages = array();

	for ($i=1; $i <= $pagination["pages"]; $i++) { 
		array_push($pages, array(
			"link"=>"/categories/".$category->getidcategory()."?page=".$i,
			"page"=>$i
		));
	}

	$page = new Page();

	$page->setTpl("category", [
		"category"=>$category->getValues(), 
		"products"=>$pagination["data"],
		"pages"=>$pages
	]);

});


$app->get("/products/:desurl", function($desurl){

	$product = new Product();

	$product->getFromURL($desurl);

	$page = new Page();

	$page->setTpl("product-detail", array(
		"product"=>$product->getValues(),
		"categories"=>$product->getCategories()
	));

});


$app->get("/cart", function() {

	$cart = Cart::getFromSession();

	$page = new Page();

	$page->setTpl("cart", array(
		"cart"=>$cart->getValues(),
		"products"=>$cart->getProducts(),
		"error"=>Cart::getMsgError()
	));

});

$app->get("/cart/:idproduct/add", function ($idproduct){

	$product = new Product();

	$product->get(intval($idproduct));

	$cart = Cart::getFromSession();

	$qtd = (isset($_GET["qtd"])) ? intval($_GET["qtd"]) : 1;

	for ($i = 0; $i < $qtd; $i++) {
		$cart->addProduct($product);
	}

	header("Location: /cart");
	exit;

});

$app->get("/cart/:idproduct/minus", function ($idproduct){

	$product = new Product();

	$product->get(intval($idproduct));

	$cart = Cart::getFromSession();

	$cart->removeProducts($product);

	header("Location: /cart");
	exit;
});

$app->get("/cart/:idproduct/remove", function ($idproduct){

	$product = new Product();

	$product->get(intval($idproduct));

	$cart = Cart::getFromSession();

	$cart->removeProducts($product, true);

	header("Location: /cart");
	exit;

});

$app->post("/cart/freight", function() {

	$cart = Cart::getFromSession();

	//$cart->setFreight($_POST['zipcode']);
	//colocar algo ilustrativo talvez pois o frete ñ sera calculado

	header("Location: /cart");
	exit;

});


$app->get("/checkout", function() {

	User::verifyLogin(false);

	$cart = Cart::getFromSession();

	$address = new Address();

	$page = new Page();

	$page->setTpl("checkout" , array(
		"cart"=>$cart->getValues(),
		"address"=>$address->getValues()
	));

});


$app->get("/login", function() {

	$page = new Page();

	$page->setTpl("login", array(
		"error"=>User::getError(),
		"errorRegister"=>User::getErrorRegister(),
		"registerValues"=>(isset($_SESSION["registerValues"]) ? $_SESSION["registerValues"] : array(
			"name"=>"",
			"email"=>"",
			"phone"=>""))
	));

});


$app->post("/login", function() {

	try {

		User::Login($_POST["login"], $_POST["password"]);

	} catch (Exception $e) {

		User::setError($e->getMessage());

	}


	header("Location: /checkout");
	exit;

});


$app->get("/logout", function() {

	User::logout();

	header("Location: /login");
	exit;

});

$app->post("/register", function() {

	$_SESSION["registerValues"] = $_POST;

	if (!isset($_POST["name"]) || $_POST["name"] == "") {

		User::setErrorRegister("Preencha seu nome!");

		header("Location: /login");
		exit;

	}

	if (!isset($_POST["email"]) || $_POST["email"] == "") {

		User::setErrorRegister("Preencha seu e-mail!");

		header("Location: /login");
		exit;

	}

	if (!isset($_POST["password"]) || $_POST["password"] == "") {

		User::setErrorRegister("Preencha sua senha!");

		header("Location: /login");
		exit;

	}

	if (User::checkLoginExist($_POST["email"])) {

		User::setErrorRegister("Este endereço de e-mail já está em uso!");

		header("Location: /login");
		exit;

	}

	$user = new User();

	$user->setData(
		array(
			"inadmin"=>0,
			"deslogin"=>$_POST["email"],
			"desperson"=>$_POST["name"],
			"desemail"=>$_POST["email"],
			"despassword"=>$_POST["password"],
			"nrphone"=>$_POST["phone"]
		)
	);

	$user->save();

	User::login($_POST["email"], $_POST["password"]);

	header("Location: /checkout");
	exit;

});

$app->get("/forgot", function() {

	$page = new Page();

	$page->setTpl("forgot");

});

$app->post("/forgot", function() {

	$user = User::getForgot($_POST["email"], false);

	header("Location: /forgot/sent");
	exit;

});


$app->get("/forgot/sent", function (){

	$page = new Page();

	$page->setTpl("forgot-sent");

});

$app->get("/forgot/reset", function (){

	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new Page();

	$page->setTpl("forgot-reset" , array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));

});

$app->post("/forgot/reset", function (){

	$forgot = User::validForgotDecrypt($_GET["code"]);

	User::setForgotUsed($forgot["idrecovery"]);

	$user = new User();

	$user->get(intval($forgot["iduser"]));

	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
		"cost"=>12
	]);

	$user->setPassword($password);

	$page = new Page([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("forgot-reset-success");

});


?>