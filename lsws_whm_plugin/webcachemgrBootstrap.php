<?php

/** ******************************************
 * LiteSpeed Web Server Plugin for WHM
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2019
 * @since 3.3.4
 * ******************************************* */

use \Lsc\Wp\Context\Context;
use \Lsc\Wp\Context\RootPanelContextOption;
use \LsPanel\WhmPluginLogger;

/** @noinspection PhpIncludeInspection */
require_once LSWS_HOME . '/add-ons/webcachemgr/autoloader.php';

/** @noinspection PhpUnhandledExceptionInspection */
Context::initialize(new RootPanelContextOption('whm'),
        WhmPluginLogger::getInstance());
