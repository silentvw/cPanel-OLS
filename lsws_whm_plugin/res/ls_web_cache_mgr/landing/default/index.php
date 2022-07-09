<?php

/** ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 * @Author: LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @Copyright: (c) 2018
 * ******************************************* */

use \LsUserPanel\CPanelWrapper;
use \LsUserPanel\Ls_WebCacheMgr_Controller;
use \LsUserPanel\Lsc\UserLSCMException;
use \LsUserPanel\Lsc\UserLogger;

CPanelWrapper::init();

try {
    $app = new Ls_WebCacheMgr_Controller();
    $app->run();
}
catch ( UserLSCMException $e ) {
    $msg = $e->getMessage();
    UserLogger::logMsg($msg, UserLogger::L_ERROR);

    header("status: 500\n");
    echo "<h1>{$msg}</h1>";
}

CPanelWrapper::getCpanelObj()->end();
