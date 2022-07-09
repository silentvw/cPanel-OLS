<?php

/* * ******************************************
 * LiteSpeed Web Server Plugin for WHM
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2019
 * @since 3.3.4
 * ******************************************* */

namespace LsPanel;

/**
 * @since 3.3.4
 */
class WhmPluginException extends \Exception
{

    /**
     * Show trace msg.
     *
     * @since 3.3.4
     * @var int
     */
    const E_PROGRAM = 100;

    /**
     * Exception is considered non-fatal. Used to determine
     * UserCommand->runAsUser() return status.
     *
     * @since 3.3.4
     * @var int
     */
    const E_NON_FATAL = 103;
}
