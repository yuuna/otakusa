<?php
include 'bootstrap.php';

$routes = new \otakusa\Routes();
$routes->out(array(
	'uvw/xyz'=>array('action'=>'sample.Action::list1'),
	'abc/def'=>array('action'=>'sample.Action::list2')
));
