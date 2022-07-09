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
use \LsPanel\WhmPluginLogger;

class PhpSuExecQuickConfViewModel extends BaseViewModel
{

    const FLD_ICON_DIR = 'iconDir';
    const FLD_SUEXEC_INFO = 'suExecInfo';
    const FLD_SUEXEC_OPTIONS = 'suExecOptions';
    const FLD_MAX_CONN_INFO = 'maxConnInfo';
    const FLD_WARN_MSGS = 'warnMsgs';
    const FLD_ERR_MSGS = 'errMsgs';

    /**
     *
     * @var mixed[]
     */
    private $settingsInfo;

    /**
     *
     * @param mixed[]  $info
     */
    public function __construct( $info )
    {
        parent::__construct();

        $this->settingsInfo = $info;

        $this->init();
    }

    private function init()
    {
        $this->setIconDir();
        $this->setSuExecData();
        $this->setMaxConnData();
        $this->setMsgData();
    }

    private function setIconDir()
    {
        $this->tplData[self::FLD_ICON_DIR] =
                WhmMod_LiteSpeed_ControlApp::ICON_DIR;
    }

    private function getSettingInfo( $id )
    {
        if ( isset($this->settingsInfo['new'][$id]) ) {
            $new = $this->settingsInfo['new'][$id];
        }
        else {
            $new = false;
        }

        if ( isset($this->settingsInfo['error'][$id]) ) {
            $err = $this->settingsInfo['error'][$id];
        }
        else {
            $err = false;
        }

        $info = array (
            'curr' => $this->settingsInfo['cur'][$id],
            'new' => $new,
            'err' => $err
        );

        return $info;
    }

    private function setSuExecData()
    {
        $id = 'phpSuExec';
        $info = $this->getSettingInfo($id);

        $this->tplData[self::FLD_SUEXEC_INFO] = array (
            'id' => $id,
            'curr' =>$info['curr'],
            'new' =>$info['new'],
            'rowErr' => $info['err']
        );

        $this->tplData[self::FLD_SUEXEC_OPTIONS] = array (
            '0' => 'No',
            '1' => 'Yes',
            '2' => 'User Home Directory Only'
        );
    }

    private function setMaxConnData()
    {
        $id = 'phpSuExecMaxConn';

        $info = $this->getSettingInfo($id);

        $this->tplData[self::FLD_MAX_CONN_INFO] = array (
            'id' => $id,
            'curr' =>$info['curr'],
            'new' =>$info['new'],
            'rowErr' => $info['err']
        );

        if ( $info['curr'] > 100 ) {
            $msg = 'PHP suEXEC Max Conn (PHP processes per user) is set to a value greater than'
                    . ' 100! Setting this value too high can significantly slow down your server.';
            WhmPluginLogger::uiWarning($msg);
        }
    }

    private function setMsgData()
    {
        $this->tplData[self::FLD_WARN_MSGS] =
                WhmPluginLogger::getUiMsgs(WhmPluginLogger::UI_WARN);
        $this->tplData[self::FLD_ERR_MSGS] =
                WhmPluginLogger::getUiMsgs(WhmPluginLogger::UI_ERR);

    }

    public function getTpl()
    {
        return realpath(__DIR__ . '/../Tpl') . '/PhpSuExecQuickConf.tpl';
    }

}
