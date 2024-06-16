<?php

use \hcode\PageAdmin;
use \hcode\Model\User;

$app->get("/admin/users" , function () {

	User::verifyLogin();

	$search = (isset($_GET["search"])) ? $_GET["search"] : "";
	$page = (isset($_GET["page"])) ? intval($_GET["page"]) : 1;

	if ($search != '') {

		$pagination = User::getPageSearch($search, $page);

	} else {

		$pagination = User::getPage($page);

	}

	$pages = array();

	for ($x = 0; $x < $pagination['pages']; $x++)
	{

		array_push($pages, array(
			"href"=>"/admin/users?".http_build_query(array(
				"page"=>$x+1,
				"search"=>$search
			)),
			"text"=>$x+1
		));

	}

	$page = new PageAdmin();
	$page->setTpl("users", array(
		"users"=>$pagination["data"], //varivel passada pra template, todas devem ser passadas por array apos o primeiro param(template)
		"search"=>$search,
		"pages"=>$pages
	));

});

$app->get("/admin/users/create", function () {

	User::verifyLogin();

	$page = new PageAdmin();
	$page->setTpl("users-create");

});

$app->get("/admin/users/:iduser/delete", function ($iduser) {

	User::verifyLogin();

	$user = new User();

	$user->get(intval($iduser));
	$user->delete();

	header("Location: /admin/users");
	exit;

});

$app->get("/admin/users/:iduser", function ($iduser) {

	User::verifyLogin();

	$user = new User();

	$user->get(intval($iduser));

	$page = new PageAdmin();
	$page->setTpl("users-update", array(
		"user"=>$user->getValues()
	));

});

$app->post("/admin/users/create", function () {

	User::verifyLogin();

	$user = new User();

	$_POST["inadmin"] = isset($_POST["inadmin"]) ? 1 : 0;

	$user->setData($_POST);
	$user->save();

	header("Location: /admin/users");
	exit;

});

$app->post("/admin/users/:iduser", function ($iduser) {

	User::verifyLogin();

	$user = new User();

	$_POST["inadmin"] = isset($_POST["inadmin"]) ? 1 : 0;

	$user->get(intval($iduser));
	$user->setData($_POST);
	$user->update();

	header("Location: /admin/users");
	exit;

});

?>