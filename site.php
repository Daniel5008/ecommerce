<?php

use \hcode\Page;
use \hcode\Model\Product;
use \hcode\Model\Category;
use \hcode\Model\Cart;
use \hcode\Model\Address;
use \hcode\Model\User;
use \hcode\Model\Order;
use \hcode\Model\OrderStatus;

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

	$address = new Address();
	$cart = Cart::getFromSession();

	if (!isset($_GET['zipcode'])) {

		$_GET['zipcode'] = $cart->getdeszipcode();
	}

	if (isset($_GET["zipcode"])) {
		
		$address->loadFromCEP($_GET["zipcode"]);

		$cart->setdeszipcode($_GET["zipcode"]);

		$cart->save();

		$cart->getCalculateTotal();	
	}

	if (!$address->getdesaddress()) $address->setdesaddress('');
	if (!$address->getdesnumber()) $address->setdesnumber('');
	if (!$address->getdescomplement()) $address->setdescomplement('');
	if (!$address->getdesdistrict()) $address->setdesdistrict('');
	if (!$address->getdescity()) $address->setdescity('');
	if (!$address->getdesstate()) $address->setdesstate('');
	if (!$address->getdescountry()) $address->setdescountry('');
	if (!$address->getdeszipcode()) $address->setdeszipcode('');

	$page = new Page();

	$page->setTpl("checkout" , array(
		"cart"=>$cart->getValues(),
		"address"=>$address->getValues(),
		"products"=>$cart->getProducts(),
		"error" => Address::getMsgError()
	));

});

$app->post("/checkout", function () {

	User::verifyLogin(false);

	if (!isset($_POST['zipcode']) || $_POST['zipcode'] === '') {
		Address::setMsgError("Informe o CEP.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['desaddress']) || $_POST['desaddress'] === '') {
		Address::setMsgError("Informe o endereço.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] === '') {
		Address::setMsgError("Informe o bairro.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['descity']) || $_POST['descity'] === '') {
		Address::setMsgError("Informe a cidade.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['desstate']) || $_POST['desstate'] === '') {
		Address::setMsgError("Informe o estado.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['descountry']) || $_POST['descountry'] === '') {
		Address::setMsgError("Informe o país.");
		header('Location: /checkout');
		exit;
	}

	$user = User::getFromSession();

	$address = new Address();

	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getidperson();

	$address->setData($_POST);

	$address->save();

	$cart = Cart::getFromSession();

	$cart->getCalculateTotal();

	$order = new Order();

	$order->setData(array(
		'idcart' => $cart->getidcart(),
		'idaddress' => $address->getidaddress(),
		'iduser' => $user->getiduser(),
		'idstatus' => OrderStatus::EM_ABERTO,
		'vltotal' => $cart->getvltotal()
	));

	$order->save();

	/*switch (intval($_POST['payment-method'])) {

		case 1:
			header("Location: /order/" . $order->getidorder() . "/pagseguro");
			break;

		case 2:
			header("Location: /order/" . $order->getidorder() . "/paypal");
			break;
	}*/


	header("Location: /order/" . $order->getidorder());
	exit;
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

$app->get("/profile", function() {

	User::verifyLogin(false);

	$user = User::getFromSession();
	$user->get($user->getiduser());
	
	$page = new Page();

	//var_dump($_SESSION);

	$page->setTpl("profile", array(
		"user"=>$user->getValues(),
		"profileMsg"=>User::getSuccess(),
		"profileError"=>User::getError()
	));

});


$app->post("/profile", function() {

	User::verifyLogin(false);

	if (!isset($_POST["desperson"]) || $_POST["desperson"] === "") {
		User::setError("Preencha o seu nome!");
		header("Location: /profile");
		exit;
	}

	if (!isset($_POST["desemail"]) || $_POST["desemail"] === "") {
		User::setError("Preencha o seu e-mail!");
		header("Location: /profile");
		exit;
	}

	$user = User::getFromSession();

	if ($_POST["desemail"] !== $user->getdesemail()) {
		
		if(User::checkLoginExist($_POST["desemail"]) === true) {

			User::setError("Este endereço de email já está em uso!");
			header("Location: /profile");
			exit;
		}
	}

	$_POST["inadmin"] = $user->getinadmin();
	$_POST["despassword"] = $user->getdespassword();
	$_POST["deslogin"] = $_POST["desemail"];

	$user ->setData($_POST);

	$user->update();

	$user->setSuccess("Dados alterados com sucesso!");

	header("Location: /profile");
	exit;

});

$app->get("/order/:idorder", function ($idorder) {

	User::verifyLogin(false);

	$order = new Order();

	$order->get(intval($idorder));

	$page = new Page();

	$page->setTpl("payment", [
		'order' => $order->getValues()
	]);
});

$app->get("/boleto/:idorder", function ($idorder) {

	User::verifyLogin(false);

	$order = new Order();

	$order->get(intval($idorder));

	// DADOS DO BOLETO PARA O SEU CLIENTE
	$dias_de_prazo_para_pagamento = 10;
	$taxa_boleto = 5.00;
	$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/06/2024"; 

	$valor_cobrado = formatPrice($order->getvltotal()); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
	$valor_cobrado = str_replace(".", "", $valor_cobrado);
	$valor_cobrado = str_replace(",", ".", $valor_cobrado);
	$valor_boleto = number_format($valor_cobrado + $taxa_boleto, 2, ',', '');

	$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
	$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
	$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
	$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
	$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
	$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

	// DADOS DO SEU CLIENTE
	$dadosboleto["sacado"] = $order->getdesperson();
	$dadosboleto["endereco1"] = $order->getdesaddress() . " " . $order->getdesdistrict();
	$dadosboleto["endereco2"] = $order->getdescity() . " - " . $order->getdesstate() . " - " . $order->getdescountry() . " -  CEP: " . $order->getdeszipcode();

	// INFORMACOES PARA O CLIENTE
	$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Daniel Store E-commerce";
	$dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
	$dadosboleto["demonstrativo3"] = "";
	$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
	$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
	$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: danielhunsche@gmail.com.br";
	$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Daniel Store E-commerce - www.danielstore.com.br";

	// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
	$dadosboleto["quantidade"] = "";
	$dadosboleto["valor_unitario"] = "";
	$dadosboleto["aceite"] = "";
	$dadosboleto["especie"] = "R$";
	$dadosboleto["especie_doc"] = "";


	// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


	// DADOS DA SUA CONTA - ITAÚ
	$dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
	$dadosboleto["conta"] = "48781";	// Num da conta, sem digito
	$dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta

	// DADOS PERSONALIZADOS - ITAÚ
	$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

	// SEUS DADOS
	$dadosboleto["identificacao"] = "Daniel Store";
	$dadosboleto["cpf_cnpj"] = "12345678901";
	$dadosboleto["endereco"] = "Rua Ademar da Silva, 999 - Rua dos Programadores, 11111-000";
	$dadosboleto["cidade_uf"] = "Sua cidade";
	$dadosboleto["cedente"] = "Daniel Store LTDA";

	// NÃO ALTERAR!
	$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "boletophp" . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR;

	require_once($path . "funcoes_itau.php");
	require_once($path . "layout_itau.php");
});


?>