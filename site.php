<?php

use \hcode\Page;
use \hcode\Model\Product;
use \hcode\Model\Category;

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
?>