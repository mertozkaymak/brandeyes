<?php
header("Content-Type: text/html; charset=utf-8");

require_once("classes/db.class.php");
require_once("classes/idea.class.php");
require_once("classes/user.class.php");

$controller = new user;

$status = $controller->checkStatus();

if($status == 1) {
    
    $response = $controller->getIdeaProductByParentId(16176);
    echo $response;
    
}
?>