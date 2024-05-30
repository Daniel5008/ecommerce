<?php

use \hcode\Page;
use \hcode\Model\Product;
use \hcode\Model\Category;
use \hcode\Model\Cart;

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


?>