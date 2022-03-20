<?php
header("Content-Type: text/html; charset=utf-8");

require_once("classes/db.class.php");
require_once("classes/idea.class.php");
require_once("classes/user.class.php");

$controller = new user;

$status = $controller->checkStatus();

if($status == 1) {
    
    $images = $controller->getVariantImages(["productId" => 16248]);

    try {
        echo '<pre>';
        print_r(json_decode($images, true));
        echo '</pre>';
    }
    catch(Exception $e) {
        echo "Hata";
    }

}
?>