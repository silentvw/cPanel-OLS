<?php

/** ******************************************
 * LiteSpeed Web Server Plugin for WHM
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2013-2020
 * ******************************************* */

use \Lsc\Wp\LSCMException;
use \LsPanel\WhmMod_LiteSpeed_ControlApp;
use \LsPanel\WhmPluginException;
use \LsPanel\WhmPluginLogger;

/**
 *
 * @return int
 */
function checkacl()
{
    $user = $_ENV['REMOTE_USER'];

    if ( $user == 'root' ) {
        return 1;
    }

    $reseller = file_get_contents('/var/cpanel/resellers');

    foreach ( explode("\n", $reseller) as $line ) {

        if ( preg_match("/^{$user}:/", $line) ) {
            $line = preg_replace("/^{$user}:/", '', $line);

            foreach ( explode(',', $line) as $perm ) {

                if ( $perm == 'all' ) {
                    return 1;
                }
            }
        }
    }

    return 0;
}

/**
 *
 * @since 3.3.4
 *
 * @param string $msg
 */
function displayCustomExceptionMsg( $msg )
{
    WhmPluginLogger::error($msg);

    header("status: 500\n");
    echo "<h1>Exception Caught - {$msg}</h1>";
}

if ( checkacl() == 0 ) {
    header("status: 403\n");
    echo '<h1>Only root privileged users can access this module!</h1>';
}
else {
    require_once __DIR__ . '/autoloader.php';

    try
    {
        WhmPluginLogger::Initialize();
        WhmPluginLogger::setAdditionalTagInfo(
                "[{$_SERVER['REMOTE_ADDR']}-" . getmypid() . ']');

        $app = new WhmMod_LiteSpeed_ControlApp();
        $app->Run();
    }
    catch ( WhmPluginException $e )
    {
        displayCustomExceptionMsg($e->getMessage());
    }
    catch ( LSCMException $e )
    {
        displayCustomExceptionMsg($e->getMessage());
    }
}
