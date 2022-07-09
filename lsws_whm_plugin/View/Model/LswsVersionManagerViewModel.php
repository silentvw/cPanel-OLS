<?php

/* * ******************************************
 * LiteSpeed Web Server Plugin for WHM
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2018-2019
 * ******************************************* */

namespace LsPanel\View\Model;

use \LsPanel\View\Model\BaseViewModel;
use \LsPanel\WhmMod_LiteSpeed_ControlApp;
use \LsPanel\WhmMod_LiteSpeed_Util;
use \LsPanel\WhmPluginLogger;

class LswsVersionManagerViewModel extends BaseViewModel
{

    const FLD_ICON_DIR = 'iconDir';
    const FLD_LSWS_VER = 'lswsVer';
    const FLD_LSWS_NEW_VER = 'lswsNewVer';
    const FLD_LSWS_INSTALLED_VERS = 'lswsInstalledVers';
    const FLD_LSWS_CURR_BUILD = 'lswsCurrBuild';
    const FLD_LSWS_NEW_BUILD = 'lswsNewBuild';
    const FLD_ERR_MSGS = 'errMsgs';
    const FLD_SUCC_MSGS = 'succMsgs';

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
        $this->setVersionAndBuildData();
        $this->setMsgData();
    }

    private function setIconDir()
    {
        $this->tplData[self::FLD_ICON_DIR] =
                WhmMod_LiteSpeed_ControlApp::ICON_DIR;
    }

    private function setVersionAndBuildData()
    {
        $currBuild = $newBuild = '';

        $verInfo = array();
        $this->util->populateVersionInfo($verInfo);

        if ( isset($verInfo['lsws_build']) ) {
            $currBuild = $verInfo['lsws_build'];
            $newBuild = $verInfo['new_build'];
        }

        $installed = $this->util->GetInstalledVersions();
        natsort($installed);

        $this->tplData[self::FLD_LSWS_VER] = $verInfo['lsws_version'];
        $this->tplData[self::FLD_LSWS_NEW_VER] = $verInfo['new_version'];
        $this->tplData[self::FLD_LSWS_CURR_BUILD] = $currBuild;
        $this->tplData[self::FLD_LSWS_NEW_BUILD] = $newBuild;
        $this->tplData[self::FLD_LSWS_INSTALLED_VERS] =
                array_reverse($installed);
    }

    private function setMsgData()
    {
        $this->tplData[self::FLD_ERR_MSGS] =
                WhmPluginLogger::getUiMsgs(WhmPluginLogger::UI_ERR);
        $this->tplData[self::FLD_SUCC_MSGS] =
                WhmPluginLogger::getUiMsgs(WhmPluginLogger::UI_SUCC);
    }

    public function getTpl()
    {
        return realpath(__DIR__ . '/../Tpl') . '/LswsVersionManager.tpl';
    }

}
