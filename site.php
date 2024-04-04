<?php

use \hcode\Page;
use \hcode\Model\Product;

$app->get('/', function() {

	$products = Product::listAll();

	$page = new Page();
	$page->setTpl("index", array(
		"products"=>Product::checkList($products)
	));

});

?>