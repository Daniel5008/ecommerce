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

	$category = new Category();

	$category->get(intval($idcategory));

	$page = new Page();

	$page->setTpl("category", [
		"category"=>$category->getValues(), 
		"products"=>Product::checkList($category->getProducts())
	]);

});

?>