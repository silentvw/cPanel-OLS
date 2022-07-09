<?php

/** *********************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2018-2019
 * *******************************************
 */

use \LsUserPanel\Lsc\Context\UserContext;
use \LsUserPanel\Lsc\Context\UserPanelContextOption;

UserContext::initialize(new UserPanelContextOption('cpanel'));
