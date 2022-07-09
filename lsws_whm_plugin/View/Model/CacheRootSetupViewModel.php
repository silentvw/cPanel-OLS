<?php

/* * ******************************************
 * LiteSpeed Web Server Plugin for WHM
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2018-2019
 * ******************************************* */

namespace LsPanel\View\Model;

use \Lsc\Wp\Panel\ControlPanel;
use \LsPanel\View\Model\BaseViewModel;
use \LsPanel\WhmMod_LiteSpeed_ControlApp;
use \LsPanel\WhmPluginLogger;

class CacheRootSetupViewModel extends BaseViewModel
{

    const FLD_ICON = 'icon';
    const FLD_SVR_CACHE_ROOT = 'svr_cache_root';
    const FLD_VH_CACHE_ROOT = 'vh_cache_root';
    const FLD_MISSING = 'missing';
    const FLD_ERR_MSGS = 'errMsgs';

    /**
     * @var ControlPanel
     */
    private $panelEnv;

    /**
     *
     * @param ControlPanel  $panelEnv
     */
    public function __construct( ControlPanel $panelEnv )
    {
        parent::__construct();

        $this->panelEnv = $panelEnv;

        $this->init();
    }

    private function init()
    {
        $this->setIconPath();
        $this->setCacheRootData();
        $this->setErrMsgsData();
    }

    private function setIconPath()
    {
        $iconPath = WhmMod_LiteSpeed_ControlApp::ICON_DIR . '/cacheRootSetup.svg';

        $this->tplData[self::FLD_ICON] = $iconPath;
    }

    private function setCacheRootData()
    {
        $missing = false;
        $svrCacheRoot = $this->panelEnv->getServerCacheRoot();
        $vhCacheRoot = $this->panelEnv->getVHCacheRoot();

        if ( $svrCacheRoot != ControlPanel::NOT_SET ) {
            $svr = '  ' . htmlspecialchars(rtrim($svrCacheRoot, '/')) . '/  ';
        }
        else {
            $svr = 'not set!';
            $missing = true;
        }

        $this->tplData[self::FLD_SVR_CACHE_ROOT] = $svr;

        if ( $vhCacheRoot != ControlPanel::NOT_SET ) {
            $vh = '  ' . htmlspecialchars(rtrim($vhCacheRoot, '/'));

            if ( $vhCacheRoot[0] != '/' ) {
                $vh .= '<b>*</b> ';
            }
        }
        else {
            $vh = 'not set!';
            $missing = true;
        }

        $this->tplData[self::FLD_VH_CACHE_ROOT] = $vh;

        $this->tplData[self::FLD_MISSING] = $missing;
    }

    private function setErrMsgsData()
    {
        $errMsgs = WhmPluginLogger::getUiMsgs(WhmPluginLogger::UI_ERR);

        $this->tplData[self::FLD_ERR_MSGS] = $errMsgs;
    }

    /**
     *
     * @return string
     */
    public function getTpl()
    {
        return realpath(__DIR__ . '/../Tpl') . '/CacheRootSetup.tpl';
    }

}
