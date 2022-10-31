<?php

echo "\n CHECKING LSHTTPD SERVICE\n";
$service = shell_exec("systemctl status lshttpd -l");
if (strpos($service, 'active (running)') !== false) {
 echo "\nSERVICE IS OKAY!!\n"; 
} else {
 echo "\n Attempting to Fix LSHTTPD Service \n ";
  shell_exec("systemctl stop lshttpd");
  shell_exec("systemctl start lshttpd");
}

echo "\n GENERATING LSWS CONFIG\n";
function changesDetector() {
  $get_domains = shell_exec("whmapi1 --output=json get_domain_info");
  $get_domains = json_decode($get_domains,1);
  $get_domains = $get_domains["data"]["domains"];
  $ssl_info = shell_exec("whmapi1 --output=json   fetch_vhost_ssl_components");
  $ssl_info = json_decode($ssl_info,1);
  $ssl_info = $ssl_info["data"]["components"];  
  $c = "";
  foreach ($get_domains as $domain) {
  $c .= $domain["domain"];
  $c .= $domain["ipv4"];
  $c .= $domain["ipv4_ssl"];
  $c .= $domain["port"];
  $c .= $domain["port_ssl"];
  $c .= $domain["user"];
  $c .= $domain["php_version"];
  }
  foreach ($ssl_info as $v) {
    $c .= $v["certificate"];
    $c .= $v["key"];
  }
  $c = md5($c);
  return $c;
}
if (file_exists("/usr/local/lsws/.changesDetect") && file_get_contents("/usr/local/lsws/.changesDetect") == changesDetector()) die("No changes detected!");
file_put_contents("/usr/local/lsws/.changesDetect",changesDetector());
function convertPHP($php_id) {
if (strpos($php_id, 'ea') !== false) {
return "/usr/local/cpanel/3rdparty/bin/php"; // Return Default PHP Version
}
if (strpos($php_id, 'alt') !== false) {
return '/usr/local/bin/lsphp';
}

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
$premade = "";
$premade_pre = explode("## DO NOT MODIFY BELOW", $premade_pre);
$premade_pre[0] = rtrim($premade_pre[0]);
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

$f = $premade_pre[0] . "\n## DO NOT MODIFY BELOW\n[REPLACE]";
$f = str_replace("[REPLACE]",$premade,$f);
unlink("/usr/local/lsws/conf/httpd_config.conf");
file_put_contents("/usr/local/lsws/conf/httpd_config.conf",$f);
echo "\n PROCESS COMPLETE \n";

echo "\n RESTARTING LSHTTPD \n";
shell_exec("systemctl restart lshttpd");
