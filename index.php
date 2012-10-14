<?php
include 'bootstrap.php';

$routes = new \otakusa\Routes();
$routes->out(array(
	'hoge/abc'=>'AAAA',
	'abc/def'=>'BBB'
));
