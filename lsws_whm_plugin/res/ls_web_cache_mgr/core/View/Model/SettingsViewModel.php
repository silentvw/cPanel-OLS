<?php

/* * ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 * @author: LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright: (c) 2018
 * ******************************************* */

namespace LsUserPanel\View\Model;

use \LsUserPanel\Lsc\Context\UserContext;
use \LsUserPanel\Lsc\UserLogger;

class SettingsViewModel
{

    const FLD_LOG_FILE = 'logFile';
    const FLD_CURR_LOG_LVL = 'currLogLvl';
    const FLD_LOG_LVLS = 'logLvls';
    const FLD_ERR_MSGS = 'errMsgs';
    const FLD_SUCC_MSGS = 'succMsgs';

    /**
     * @var mixed[]
     */
    private $tplData = array();

    public function __construct()
    {
        $this->init();
    }

    private function init()
    {
        $this->setLogData();
        $this->setMsgData();
    }

    /**
     *
     * @param string  $field
     * @return null|mixed
     */
    public function getTplData( $field )
    {
        if ( !isset($this->tplData[$field]) ) {
            return null;
        }

        return $this->tplData[$field];
    }

    private function setLogData()
    {
        $options = UserContext::getOption();

        $logFile = $options->getLogFile();
        $currLogLvl = $options->getLogFileLvl();

        $logLvls = array(
            'None' => UserLogger::L_NONE,
            'Error' => UserLogger::L_ERROR,
            'Warning' => UserLogger::L_WARN,
            'Notice' => UserLogger::L_NOTICE,
            'Info' => UserLogger::L_INFO,
            'Verbose' => UserLogger::L_VERBOSE,
            'Debug' => UserLogger::L_DEBUG
        );

        $this->tplData[self::FLD_LOG_FILE] = $logFile;
        $this->tplData[self::FLD_CURR_LOG_LVL] = $currLogLvl;
        $this->tplData[self::FLD_LOG_LVLS] = $logLvls;
    }

    private function setMsgData()
    {
        $this->tplData[self::FLD_ERR_MSGS] =
                UserLogger::getUiMsgs(UserLogger::UI_ERR);
        $this->tplData[self::FLD_SUCC_MSGS] =
                UserLogger::getUiMsgs(UserLogger::UI_SUCC);
    }

    /**
     *
     * @return string
     */
    public function getTpl()
    {
        return realpath(__DIR__ . '/../Tpl') . '/Settings.tpl';
    }

}
