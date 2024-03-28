<?php

use \hcode\Page;

$app->get('/', function() {
    
	$page = new Page();
	$page->setTpl("index");

});

?>