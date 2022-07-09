<?php

/** ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2018-2020
 * ******************************************* */

namespace LsUserPanel\Lsc\Context;

use \LsUserPanel\Lsc\UserLogger;
use \LsUserPanel\Lsc\UserLSCMException;
use \LsUserPanel\UserSettings;

class UserPanelContextOption extends UserContextOption
{

    /**
     * @var string
     */
    protected $iconDir;

    /**
     * @var string
     */
    protected $sharedTplDir = __DIR__ . '/../View/Tpl';

    /**
     *
     * @param string  $panelName
     * @throws UserLSCMException  Thrown indirectly.
     */
    public function __construct( $panelName )
    {
        $invokerName = $panelName;

        UserSettings::initialize();

        $logFileLvl = UserSettings::getSetting(UserSettings::FLD_LOG_FILE_LVL);
        $logEchoLvl = UserLogger::L_NONE;
        $bufferedWrite = true;
        $bufferedEcho = true;

        parent::__construct($invokerName, $logFileLvl, $logEchoLvl,
                $bufferedWrite, $bufferedEcho);

        $this->init();
    }

    private function init()
    {
        $this->scanDepth = 2;
    }

    /**
     *
     * @param string  $iconDir
     */
    public function setIconDir( $iconDir )
    {
        $this->iconDir = $iconDir;
    }

    /**
     *
     * @return string
     */
    public function getIconDir()
    {
        return $this->iconDir;
    }

}
