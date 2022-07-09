<?php
echo "\n GENERATING LSWS CONFIG\n";
function convertPHP($php_id) {
$php_id = str_replace("ea-php", "lsphp", $php_id); //Convert EasyApache PHP ID
$php_id = str_replace("alt-php", "lsphp", $php_id); //Convert Cloudlinux PHP ID
$php_id = str_replace(".", "", $php_id); //replace dots
if (strpos($php_id, 'lsphp5') !== false) {
    return "lsphp72";
}
if ($php_id == "lsphp74") return $php_id; // 7.4
if ($php_id == "lsphp73") return $php_id; // 7.3
if ($php_id == "lsphp72") return $php_id; // 7.2
return "lsphp74"; // Return Default PHP Version
}
function randomPassword() {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < 8; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}
$get_domains = shell_exec("whmapi1 --output=json get_domain_info");
$get_domains = json_decode($get_domains,1);
$get_domains = $get_domains["data"]["domains"];
shell_exec("rm -rf /usr/local/lsws/conf/vhosts && mkdir /usr/local/lsws/conf/vhosts");
$premade_pre = file_get_contents("/usr/local/lsws/conf/httpd_config.conf");
$premade = "## DO NOT MODIFY BELOW";
$premade_pre = explode("## DO NOT MODIFY BELOW", $premade_pre);
$ssl_listeners = array();
$listeners = array();
shell_exec("rm -rf /usr/local/lsws/conf/sslcerts && mkdir /usr/local/lsws/conf/sslcerts");
foreach ($get_domains as $domain) {
$ssl_info = shell_exec("whmapi1 --output=json   fetch_vhost_ssl_components");
$ssl_info = json_decode($ssl_info,1);
$ssl_info = $ssl_info["data"]["components"];
$sslcrt = "";
$sslprv = "";
foreach ($ssl_info as $v) {
if ($v["servername"] == $domain["domain"]) {
file_put_contents("/usr/local/lsws/conf/sslcerts/" . $domain["domain"] . ".crt",$v["certificate"]);
file_put_contents("/usr/local/lsws/conf/sslcerts/" . $domain["domain"] . ".key",$v["key"]);
}
}
$w = file_get_contents("vhost.conf");
$w = str_replace("[RANDOMSTRING]",randomPassword(),$w);
$w = str_replace("[DOCROOT]",$domain["docroot"],$w);
$w = str_replace("[USER]",$domain["user"],$w);
$w = str_replace("[GROUP]",$domain["user"],$w);
$w = str_replace("[PHPVERSION]",convertPHP($domain["php_version"]),$w);
$map = "keyFile /usr/local/lsws/conf/sslcerts/" . $domain["domain"] . ".key
 certFile /usr/local/lsws/conf/sslcerts/" . $domain["domain"] . ".crt";
$w = str_replace("[SSL]",$map,$w);
file_put_contents("/usr/local/lsws/conf/vhosts/" . $domain["domain"] . ".conf",$w);
$x = file_get_contents("vhost_pre.conf");
$vhostid = randomPassword();
$x = str_replace("[RANDOMSTRING]",$vhostid,$x);
$x = str_replace("[DOCROOT]",$domain["docroot"],$x);
$x = str_replace("[DOMAIN]",$domain["domain"],$x);
$premade = $premade . "\n" . $x;
if (isset($listeners[$domain["ipv4"] . ":" . $domain["port"]])) {
$listeners[$domain["ipv4"] . ":" . $domain["port"]][$vhostid] = $domain["domain"];
} else {
$listeners[$domain["ipv4"] . ":" . $domain["port"]] = array();
$listeners[$domain["ipv4"] . ":" . $domain["port"]][$vhostid] = $domain["domain"];
}
if (isset($listeners_ssl[$domain["ipv4_ssl"] . ":" . $domain["port_ssl"]])) {
$listeners_ssl[$domain["ipv4_ssl"] . ":" . $domain["port_ssl"]][$vhostid] = $domain["domain"];
} else {
$listeners_ssl[$domain["ipv4_ssl"] . ":" . $domain["port_ssl"]] = array();
$listeners_ssl[$domain["ipv4_ssl"] . ":" . $domain["port_ssl"]][$vhostid] = $domain["domain"];
}
}


foreach ($listeners as $c => $l) {
$px = file_get_contents("vhost_listeners.conf");
$px = str_replace("[IPADD]",$c,$px);
$px = str_replace("[SECURE]","0",$px);
$px = str_replace("[RANDOMSTRING]",randomPassword(),$px);
$map = "";
foreach ($l as $n => $t) {
$map = $map . "\n    " . "map " . $n . " " . $t;
}
$px = str_replace("[MAPS]",$map,$px);
$premade = $premade . "\n" . $px;
}
foreach ($listeners_ssl as $c => $l) {
$px = file_get_contents("vhost_listeners.conf");
$px = str_replace("[IPADD]",$c,$px);
$px = str_replace("[SECURE]","1",$px);
$px = str_replace("[RANDOMSTRING]",randomPassword(),$px);
$map = "keyFile /usr/local/lsws/admin/conf/webadmin.key
    certFile /usr/local/lsws/admin/conf/webadmin.crt";
foreach ($l as $n => $t) {
$map = $map . "\n    " . "map " . $n . " " . $t;
}
$px = str_replace("[MAPS]",$map,$px);
$premade = $premade . "\n" . $px;
}


file_put_contents("/usr/local/lsws/conf/httpd_config.conf",$premade_pre[0] . "\n" . $premade);
echo "\n PROCESS COMPLETE \n";

echo "\n RELOADING LSHTTPD \n";
shell_exec("systemctl reload lshttpd");
