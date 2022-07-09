<?php

/** ******************************************
 * LiteSpeed Web Server Plugin for WHM
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2018-2020
 * ******************************************* */

namespace LsPanel\View\Model;

use \LsPanel\WhmMod_LiteSpeed_ControlApp;
use \LsPanel\WhmMod_LiteSpeed_Util;

class LswsConfigViewModel extends BaseViewModel
{

    const FLD_ICON_DIR = 'iconDir';
    const FLD_ADMIN_CONSOLE_URL = 'adminConsoleUrl';
    const FLD_SUEXEC_STATE = 'suExecState';
    const FLD_HAS_CACHE = 'hasCache';

    /**
     * @var WhmMod_LiteSpeed_Util
     */
    private $util;

    /**
     *
     * @param WhmMod_LiteSpeed_Util  $util
     */
    public function __construct( WhmMod_LiteSpeed_Util $util )
    {
        parent::__construct();

        $this->util = $util;

        $this->init();
    }

    private function init()
    {
        $this->setIconDir();
        $this->setAdminConsoleUrl();
        $this->setSuExecState();
        $this->setHasCache();
    }

    private function setIconDir()
    {
        $this->tplData[self::FLD_ICON_DIR] =
                WhmMod_LiteSpeed_ControlApp::ICON_DIR;
    }

    private function setAdminConsoleUrl()
    {
        $lsPid = $this->util->getLSPID();

        if ( $lsPid > 0 ) {
            $adminConsoleUrl = $this->util->GetAdminUrl();
        }
        else {
            $adminConsoleUrl = '';
        }

        $this->tplData[self::FLD_ADMIN_CONSOLE_URL] = $adminConsoleUrl;
    }

    private function setSuExecState()
    {
        $c = $this->util->GetLSConfig('phpSuExec');
        $phpSuExacVal = $c['phpSuExec'];

        switch ($phpSuExacVal) {
            case '1':
                $state = 'enabled';

                break;
            case '2':
                $state = 'enabled in user home directory only';

                break;
            default:
                $state = 'disabled';
        }

        $this->tplData[self::FLD_SUEXEC_STATE] = $state;
    }

    private function setHasCache()
    {
        $res = $this->util->GetLicenseType();

        if ( $res['has_cache'] ==
                WhmMod_LiteSpeed_Util::LSCACHE_STATUS_NOT_SUPPORTED ) {

            $hasCache = false;
        }
        else {
            $hasCache = true;
        }

        $this->tplData[self::FLD_HAS_CACHE] = $hasCache;
    }

    public function getTpl()
    {
        return realpath(__DIR__ . '/../Tpl') . '/LswsConfig.tpl';
    }

}
