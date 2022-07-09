<?php

/** ******************************************
 * LiteSpeed Web Cache Manager Plugin for cPanel
 *
 * @author: LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright: (c) 2018-2020
 *
 * @noinspection PhpUnhandledExceptionInspection
 * ******************************************* */

use \LsUserPanel\Lsc\UserLSCMException;
use \LsUserPanel\PluginSettings;

require_once __DIR__ . '/autoloader.php';

/**
 *
 * @return string
 * @throws UserLSCMException  Thrown indirectly.
 */
function getCustomLandingTheme()
{
    $custTheme = '';

    $useCustTheme =
            PluginSettings::getSetting(PluginSettings::FLD_USE_CUST_THEME);

    if ( $useCustTheme ) {
        $custTheme = PluginSettings::getSetting(PluginSettings::FLD_CUST_THEME);
    }

    return $custTheme;
}

PluginSettings::initialize();

$customLandingTheme = getCustomLandingTheme();

if ( $customLandingTheme != '' && is_dir("landing/{$customLandingTheme}") ) {
    $activeTheme = $customLandingTheme;
}
else {
    $activeTheme = 'default';
}

include "landing/{$activeTheme}/index.php";
