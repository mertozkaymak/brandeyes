<?php
header("Content-Type: text/html; charset=utf-8");

require_once("classes/db.class.php");
require_once("classes/idea.class.php");
require_once("classes/user.class.php");

$controller = new user;

$status = $controller->checkStatus();

if($status == 1) {
    
    // Servisten veritabanına ürün aktarımı

    $controller->getAccessToken();
    $data = $controller->getServiceInformation();

    $data2 = array();
    foreach($data as $d) {
        $found = 0;
        foreach($data2 as $key => $d2) {
            if($d2["Kod"] == $d["Kod"]) {
                $data2[$key]["FiiliStok"] += (!is_int($d["FiiliStok"]) || $d["FiiliStok"] <= 0 ? 0 : $d["FiiliStok"]);
                if($d2["TerminTarihi"] == "" && $d["TerminTarihi"] != "") {
                    $data2[$key]["TerminTarihi"] = $d["TerminTarihi"]; 
                }
                else if($d2["TerminTarihi"] != "" && $d["TerminTarihi"] != "") {
                    $data2[$key]["TerminTarihi"] = strtotime($d2["TerminTarihi"]) > strtotime($d["TerminTarihi"]) ? $d["TerminTarihi"] : $d2["TerminTarihi"];
                }
                $found = 1;
            }
        }
        if($found == 0) {
            $d["FiiliStok"] = $d["FiiliStok"] == "" || $d["FiiliStok"] <= 0 ? 0 : $d["FiiliStok"];
            array_push($data2, $d);
        }
    }

    $controller->prepareDataUpdate(0);

    foreach($data2 as $product) {
        $controller->updateData($product);
    }

    $controller->prepareDataUpdate(1);

    // Ideasoft tarafında güncelleme

    $products = $controller->getAllIdeaProducts();
    
    foreach($products as $key => $product) {
        $controller->updateProduct($product);
    }
    
}
?>