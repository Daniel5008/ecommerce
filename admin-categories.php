<?php 

use \hcode\Page;
use \hcode\PageAdmin;
use \hcode\Model\User;
use \hcode\Model\Category;
use hcode\Model\Product;

$app->get("/admin/categories", function(){

	User::verifyLogin();

	$search = (isset($_GET['search'])) ? $_GET['search'] : "";
	$page = (isset($_GET['page'])) ? intval($_GET['page']) : 1;

	if ($search != '') {

		$pagination = Category::getPageSearch($search, $page);

	} else {

		$pagination = Category::getPage($page);

	}

	$pages = array();

	for ($x = 0; $x < $pagination["pages"]; $x++)
	{

		array_push($pages, [
			"href"=>"/admin/categories?".http_build_query([
				"page"=>$x+1,
				"search"=>$search
			]),
			"text"=>$x+1
		]);

	}

	$page = new PageAdmin();

	$page->setTpl("categories", [
		"categories"=>$pagination["data"],
		"search"=>$search,
		"pages"=>$pages
	]);	


});

$app->get("/admin/categories/create", function (){

	User::verifyLogin();

	$page = new PageAdmin();
	$page->setTpl("categories-create");

});

$app->post("/admin/categories/create", function (){

	User::verifyLogin();

	$category = new Category();

	$category->setData($_POST);

	$category->save();

	header("Location: /admin/categories");
	exit;

});

$app->get("/admin/categories/:idcategory/delete", function($idcategory) {

	User::verifyLogin();

	$category = new Category();
	$category->get(intval($idcategory));
	$category->delete();

	header("Location: /admin/categories");
	exit;

});

$app->get("/admin/categories/:idcategory", function ($idcategory) {

	User::verifyLogin();

	$category = new Category();
	$category->get(intval($idcategory));

	$page = new PageAdmin();
	$page->setTpl("categories-update", array(
		"category"=>$category->getValues()
	));	

});

$app->post("/admin/categories/:idcategory", function ($idcategory) {

	User::verifyLogin();

	$category = new Category();
	$category->get(intval($idcategory));
	$category->setData($_POST);
	$category->save();

	header("Location: /admin/categories");
	exit;

});

$app->get("/admin/categories/:idcategory/products", function($idcategory){

	User::verifyLogin();

	$category = new Category();

	$category->get(intval($idcategory));

	$page = new PageAdmin();

	$page->setTpl("categories-products", [
		"category"=>$category->getValues(), 
		"productsRelated"=>$category->getProducts(),
		"productsNotRelated"=>$category->getProducts(false)
	]);

});

$app->get("/admin/categories/:idcategory/products/:idproduct/add", function($idcategory, $idproduct){

	User::verifyLogin();

	$category = new Category();

	$category->get(intval($idcategory));

	$category->addProduct($idproduct);

	header("Location: /admin/categories/$idcategory/products");
	exit;

});

$app->get("/admin/categories/:idcategory/products/:idproduct/remove", function($idcategory, $idproduct){

	User::verifyLogin();

	$category = new Category();

	$category->get(intval($idcategory));

	$category->removeProduct($idproduct);

	header("Location: /admin/categories/$idcategory/products");
	exit;

});

?>