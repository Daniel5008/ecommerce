<?php

use hcode\Model\User;
use hcode\PageAdmin;
use hcode\Model\Product;

$app->get("/admin/products", function(){

	User::verifyLogin();

	$search = (isset($_GET["search"])) ? $_GET["search"] : "";
	$page = (isset($_GET["page"])) ? intval($_GET["page"]) : 1;

	if ($search != "") {

		$pagination = Product::getPageSearch($search, $page);

	} else {

		$pagination = Product::getPage($page);

	}

	$pages = array();

	for ($x = 0; $x < $pagination["pages"]; $x++)
	{

		array_push($pages, [
			"href"=>"/admin/products?".http_build_query([
				"page"=>$x+1,
				"search"=>$search
			]),
			"text"=>$x+1
		]);

	}

	$products = Product::listAll();

	$page = new PageAdmin();

	$page->setTpl("products", [
		"products"=>$pagination['data'],
		"search"=>$search,
		"pages"=>$pages
	]);

});

$app->get("/admin/products/create", function (){

    User::verifyLogin();

    $page = new PageAdmin();

    $page->setTpl("products-create");

});

$app->post("/admin/products/create", function (){

    User::verifyLogin();

    $product = new Product();
    $product->setData($_POST);
    $product->save();

    header("Location: /admin/products");
    exit;

});

$app->get("/admin/products/:idproduct", function($idproduct){

    User::verifyLogin();

    $product = new Product();

    $product->get(intval($idproduct));

    $page = new PageAdmin();

    $page->setTpl("products-update", array(
        "product"=>$product->getValues()
    ));

});

$app->post("/admin/products/:idproduct", function($idproduct){

    User::verifyLogin();

    $product = new Product();

    $product->get(intval($idproduct));

    $product->setData($_POST);

    $product->save();

    $product->setPhoto($_FILES['file']);

    header("Location: /admin/products");
    exit;

});

$app->get("/admin/products/:idproduct/delete", function($idproduct){

    User::verifyLogin();

    $product = new Product();

    $product->get(intval($idproduct));

    $product->delete();

    header("Location: /admin/products");
    exit;

});

?>