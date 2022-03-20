<?php

    header("Content-Type: text/html; charset=utf-8");

    require_once("classes/db.class.php");
    require_once("classes/idea.class.php");
    require_once("classes/user.class.php");

    $user = new user;
    $status = $user->checkStatus();

    if($status == 1) {

        function createPostvars($fields) {
            $postvars = '';
            foreach($fields as $key=>$value) {
                $postvars .= $key . "=" . $value . "&";
            }
            $postvars = rtrim($postvars, '&');
            return $postvars;
        }
        
        function connectToService() {
            $cURL = curl_init("***/api/login");
            curl_setopt($cURL, CURLOPT_POST, 1);
            curl_setopt($cURL, CURLOPT_POSTFIELDS, createPostvars(array(
                'grant_type'    => 'password',
                'username'      => '***',
                'password'      => rawurlencode('***'),
            )));
            curl_setopt($cURL, CURLOPT_HEADER, 0);
            curl_setopt($cURL, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
            $response = json_decode(curl_exec($cURL))->access_token;
            curl_close($cURL);
            return $response;
        }

        function getServiceInformation() {
            $access_token = connectToService();
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "***/api/v1/Products/List");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Bearer ' . $access_token
            ));
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = json_decode(curl_exec($ch));
            curl_close($ch);
            if($response->Success == 1){
                return $response->Data;
            }else{
                return 0;
            }
        }

        function prepareProductUpdate($user, $value) {
            if($value == 0) {
                $stmt = $user->db->prepare("UPDATE service SET updated = 0");
                $stmt->execute();
            }
            else {
                $stmt = $user->db->prepare("DELETE FROM service WHERE updated = 0");
                $stmt->execute();
            }
        }

        function writeToDatabase($user, $id, $code, $comment, $stock, $status, $stock2, $date, $passive) {
            $stmt = $user->db->prepare("SELECT * FROM service WHERE service_id = ? AND Kod = ? AND Aciklama = ? AND FiiliStok = ? AND UrtDurum = ? AND SatinalmaSipMiktar = ? AND TerminTarihi = ? AND Sil = ?");
            $stmt->bind_param("issisisi", $id, $code, $comment, $stock, $status, $stock2, $date, $passive);
            $stmt->execute();
            $result = $stmt->get_result();

            if($result->num_rows == 0) {
                $stmt = $user->db->prepare("INSERT INTO service (service_id, Kod, Aciklama, FiiliStok, UrtDurum, SatinalmaSipMiktar, TerminTarihi, Sil) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->bind_param("issisisi", $id, $code, $comment, $stock, $status, $stock2, $date, $passive);
                $stmt->execute();
            }
            else {
                $stmt = $user->db->prepare("UPDATE service SET updated = 1 WHERE service_id = ? AND Kod = ? AND Aciklama = ? AND FiiliStok = ? AND UrtDurum = ? AND SatinalmaSipMiktar = ? AND TerminTarihi = ? AND Sil = ?");
                $stmt->bind_param("issisisi", $id, $code, $comment, $stock, $status, $stock2, $date, $passive);
                $stmt->execute();
            }
        }

        function readToDatabase($user, $sku, $product) {

            $data = array(
              "FiiliStok" => 0,
              "UrtDurum" => "",
              "SatinalmaSipMiktar" => 0,
              "TerminTarihi" => "",
              "Sil" => ""  
            );

            $stmt = $user->db->prepare("SELECT * FROM service WHERE Kod LIKE ? AND updated = 1");
            $stmt->bind_param("s", $sku);
            $stmt->execute();
            $result = $stmt->get_result();

            if($result->num_rows > 0) {
                $counter = 0;
                while($row = $result->fetch_assoc()) {
                    $data["FiiliStok"] += $row["FiiliStok"];
                    $data["UrtDurum"] .= ($counter === 0) ? $row["UrtDurum"] : "-" . $row["UrtDurum"];
                    $data["SatinalmaSipMiktar"] += $row["SatinalmaSipMiktar"];
                    $data["TerminTarihi"] .= ($counter === 0) ? $row["TerminTarihi"] : "-" . $row["TerminTarihi"];
                    $data["Sil"] .= ($counter === 0) ? $row["Sil"] : "-" . $row["Sil"];
                    $counter++;
                }
                if($data["FiiliStok"] < 1){
                    if($data["SatinalmaSipMiktar"] < 1){
                        if($data["UrtDurum"] === "XX" && $data["Sil"] === "1"){
                            //echo '<tr><th style="border-right: solid 1px;text-align: right;padding-right: 1rem;">' . $sku . '</th><td style="padding-left: 1rem;">Stok Yok <strong>Ve</strong> Uretilmiyor</td></tr>';
                            $product["status"] = 0;
                            $user->updateIdeaProduct($product);
                        }else{
                            if($data["TerminTarihi"] === ""){
                                //echo '<tr><th style="border-right: solid 1px;text-align: right;padding-right: 1rem;">' . $sku . '</th><td style="padding-left: 1rem;">Stok Yok <strong>Ve</strong> Uretimi Var <strong>Ve</strong> Tarih Yok</td></tr>';
                                $product["stockAmount"] = 9999;
                                $product["marketPriceDetail"] = date('d.m.Y', strtotime('+120 day', time()));
                                $product["status"] = 1;
                                $user->updateIdeaProduct($product);
                            }else{
                                if(strpos($data["TerminTarihi"], "-")){
                                    //echo '<tr><th style="border-right: solid 1px;text-align: right;padding-right: 1rem;">' . $sku . '</th><td style="padding-left: 1rem;">Stok Yok <strong>Ve</strong> Uretimi Var <strong>Ve</strong> Birden Fazla Termin Tarihi Var</td></tr>';
                                    /**Test Edilemedi */
                                }else{
                                    //echo '<tr><th style="border-right: solid 1px;text-align: right;padding-right: 1rem;">' . $sku . '</th><td style="padding-left: 1rem;">Stok Yok <strong>Ve</strong> Uretimi Var <strong>Ve</strong> Tek Bir Termin Tarihi Var</td></tr>';
                                    $product["stockAmount"] = 9999;
                                    $product["marketPriceDetail"] = $data["TerminTarihi"];
                                    $product["status"] = 1;
                                    $user->updateIdeaProduct($product);
                                }
                            }
                        }
                    }else{
                        if($data["TerminTarihi"] === ""){
                            //echo '<tr><th style="border-right: solid 1px;text-align: right;padding-right: 1rem;">' . $sku . '</th><td style="padding-left: 1rem;">Stok Var <strong>Ve</strong> Termin Tarihi Yok</td></tr>';
                            $product["stockAmount"] = $data["SatinalmaSipMiktar"];
                            $product["marketPriceDetail"] = date('d.m.Y', strtotime('+120 day', time()));
                            $product["status"] = 1;
                            $user->updateIdeaProduct($product);
                        }else{
                            if(strpos($data["TerminTarihi"], "-")){
                                //echo '<tr><th style="border-right: solid 1px;text-align: right;padding-right: 1rem;">' . $sku . '</th><td style="padding-left: 1rem;">Stok Var <strong>Ve</strong> Birden Fazla Termin Tarihi Var</td></tr>';
                                /**Test Edilemedi */
                            }else{
                                //echo '<tr><th style="border-right: solid 1px;text-align: right;padding-right: 1rem;">' . $sku . '</th><td style="padding-left: 1rem;">Stok Var <strong>Ve</strong> Tek Bir Termin Tarihi Var</td></tr>';
                                $product["stockAmount"] = $data["SatinalmaSipMiktar"];
                                $product["marketPriceDetail"] = $data["TerminTarihi"];
                                $product["status"] = 1;
                                $user->updateIdeaProduct($product);
                            }
                        }
                    }
                }else{
                    //echo '<tr><th style="border-right: solid 1px;text-align: right;padding-right: 1rem;">' . $sku . '</th><td style="padding-left: 1rem;">Stok Var</td></tr>';
                    $product["stockAmount"] = $data["FiiliStok"];
                    $product["status"] = 1;
                    $user->updateIdeaProduct($product);
                }
            }else{
                //echo '<tr><th style="border-right: solid 1px;text-align: right;padding-right: 1rem;">' . $sku . '</th><td style="padding-left: 1rem;">Urun Yok</td></tr>';
            }
        }
                
        $serviceData = getServiceInformation();
        if($serviceData === 0){
            echo "Servis İle Bağlantı Kurulamadı.";
            exit();
        }

        prepareProductUpdate($user, 0);

        foreach ($serviceData as $data) {
            writeToDatabase($user, $data->ID, $data->Kod, $data->Aciklama, $data->FiiliStok, $data->UrtDurum, $data->SatinalmaSipMiktar, $data->TerminTarihi, $data->Sil);
        }

        prepareProductUpdate($user, 1);

        //echo '<table>';
        $stmt = $user->db->prepare("SELECT * FROM service");
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $ideaProduct = $user->getIdeaProductBySku($row["Kod"]);
            if($ideaProduct){
                if($ideaProduct["stockAmount"] < 1){
                    readToDatabase($user, $ideaProduct["sku"], $ideaProduct);
                }else{
                    //echo '<tr><th style="border-right: solid 1px;text-align: right;padding-right: 1rem;">' . $row["Kod"] . '</th><td style="padding-left: 1rem;">Urunde Stok Var</td></tr>';
                    if($ideaProduct["marketPriceDetail"] !== "" && strtotime($ideaProduct["marketPriceDetail"]) < strtotime(date("d.m.Y"))){
                        //echo '<tr><th style="border-right: solid 1px;text-align: right;padding-right: 1rem;">' . $row["Kod"] . '</th><td style="padding-left: 1rem;">Urun Termin Tarihine Ulaştı</td></tr>';
                        $ideaProduct["marketPriceDetail"] = "";
                        $user->updateIdeaProduct($ideaProduct);
                    }  
                }
            }
            /*else{
                echo '<tr><th style="border-right: solid 1px;text-align: right;padding-right: 1rem;">' . $row["Aciklama"] . '</th><td style="padding-left: 1rem;">Urun Yok</td></tr>';
            }
            $user->doFlush();*/
        }
        //echo '</table>';

    }
    
?>
