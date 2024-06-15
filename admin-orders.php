<?php

use \hcode\PageAdmin;
use \hcode\Model\User;
use \hcode\Model\Order;
use \hcode\Model\OrderStatus;

$app->get("/admin/orders/:idorder/status", function($idorder){

	User::verifyLogin();

	$order = new Order();

	$order->get(intval($idorder));

	$page = new PageAdmin();

	$page->setTpl("order-status", array(
		'order'=>$order->getValues(),
		'status'=>OrderStatus::listAll(),
		'msgSuccess'=>Order::getSuccess(),
		'msgError'=>Order::getError()
	));

});

$app->post("/admin/orders/:idorder/status", function($idorder){

	User::verifyLogin();

	if (!isset($_POST['idstatus']) || !intval($_POST['idstatus']) > 0) {
		Order::setError("Informe o status atual.");
		header("Location: /admin/orders/".$idorder."/status");
		exit;
	}

	$order = new Order();

	$order->get(intval($idorder));

	$order->setidstatus(intval($_POST['idstatus']));

	$order->save();

	Order::setSuccess("Status atualizado.");

	header("Location: /admin/orders/".$idorder."/status");
	exit;

});

$app->get("/admin/orders/:idorder/delete", function($idorder){

	User::verifyLogin();

	$order = new Order();

	$order->get(intval($idorder));

	$order->delete();

	header("Location: /admin/orders");
	exit;

});

$app->get("/admin/orders/:idorder", function($idorder){

	User::verifyLogin();

	$order = new Order();

	$order->get(intval($idorder));

	$cart = $order->getCart();

	$page = new PageAdmin();

	$page->setTpl("order", array(
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts()
	));

});

$app->get("/admin/orders", function(){

	User::verifyLogin();

	// $search = (isset($_GET['search'])) ? $_GET['search'] : "";
	// $page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	// if ($search != '') {

	// 	$pagination = Order::getPageSearch($search, $page);

	// } else {

	// 	$pagination = Order::getPage($page);

	// }

	// $pages = [];

	// for ($x = 0; $x < $pagination['pages']; $x++)
	// {

	// 	array_push($pages, [
	// 		'href'=>'/admin/orders?'.http_build_query([
	// 			'page'=>$x+1,
	// 			'search'=>$search
	// 		]),
	// 		'text'=>$x+1
	// 	]);

	// }

	$page = new PageAdmin();

	$page->setTpl("orders", array(
		"orders"=>Order::listAll()
	));

});

?>