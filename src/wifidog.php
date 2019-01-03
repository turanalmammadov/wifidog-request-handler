<?php
require_once("functions.php");
if (empty($_REQUEST["type"])) {
    $url = "http://google.com";
    header('Location:' . $url);
} else {


    //LOGIN REQUEST
    if ($_REQUEST["type"] == "login") {


        if (empty($_REQUEST["url"])) {
            $url = "http://google.com";
        } else {


            if (strpos($_REQUEST["url"], 'http') !== false) {
                $url = $_REQUEST["url"];
            } else {
                $url = "http://google.com";
            }
        }


        $adres = 'LOGIN_REQUEST_URL' . $_REQUEST['gw_id'] . '&mac=' . $_REQUEST['mac'] . '&cl_ip=' . $_REQUEST["ip"] . '&gw_ip=' . $_REQUEST["gw_address"] . '&gw_port=' . $_REQUEST["gw_port"] . '&url=' . $url;
        header('Location:' . $adres);
    }

    //AUTH REQUEST
    elseif ($_REQUEST["type"] == "auth") {
        wifidog_actions('auth', $_REQUEST);
    }

    //PING REQUEST
    elseif ($_REQUEST["type"] == "ping") {
        wifidog_actions('ping', $_REQUEST);
    }


    //PORTAL REQUEST
    elseif ($_REQUEST["type"] == "portal") {
        wifidog_actions('portal', $_REQUEST);
    }
}
?>