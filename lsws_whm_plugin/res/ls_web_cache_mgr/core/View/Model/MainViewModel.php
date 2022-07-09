<?php

/** ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 * @author: LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright: (c) 2018
 * ******************************************* */

namespace LsUserPanel\View\Model;

use \LsUserPanel\Ls_WebCacheMgr_Controller;
use \LsUserPanel\Lsc\Context\UserContext;
use \LsUserPanel\Lsc\UserLogger;
use \LsUserPanel\Lsc\UserLSCMException;
use \LsUserPanel\Lsc\UserUtil;
use \LsUserPanel\PluginSettings;

class MainViewModel
{

    /**
     * @var string
     */
    const FLD_PLUGIN_VER = 'pluginVer';

    /**
     * @var string
     */
    const FLD_VH_CACHE_DIR = 'vhCacheDir';

    /**
     * @var string
     */
    const FLD_VH_CACHE_DIR_EXISTS = 'vhCacheDirExists';

    /**
     * @var string
     */
    const FLD_ICON_DIR = 'iconDir';

    /**
     * @since 2.1
     * @var string
     */
    const FLD_EC_ALLOWED = 'ecAllowed';

    /**
     * @var string
     */
    const FLD_ERR_MSGS = 'errMsgs';

    /**
     * @var string
     */
    const FLD_SUCC_MSGS = 'succMsgs';

    /**
     * @var mixed[]
     */
    private $tplData = array();

    /**
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function init()
    {
        $this->setPluginVerData();
        $this->setVhCacheDirData();
        $this->setIconDirData();
        $this->setEcCertAllowed();
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

    private function setPluginVerData()
    {
        $this->tplData[self::FLD_PLUGIN_VER] =
                Ls_WebCacheMgr_Controller::MODULE_VERSION;
    }

    /**
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function setVhCacheDirData()
    {
        $cacheDir = UserUtil::getUserCacheDir();
        $exists = ($cacheDir == '') ? false : file_exists($cacheDir);

        $this->tplData[self::FLD_VH_CACHE_DIR] = $cacheDir;
        $this->tplData[self::FLD_VH_CACHE_DIR_EXISTS] = $exists;
    }

    private function setIconDirData()
    {
        $iconDir = '';

        try {
            $iconDir = UserContext::getOption()->getIconDir();
        }
        catch ( UserLSCMException $e ) {
            $msg = $e->getMessage() . ' Could not get icon directory.';
            UserLogger::logMsg($msg, UserLogger::L_DEBUG);
        }

        $this->tplData[self::FLD_ICON_DIR] = $iconDir;
    }

    /**
     *
     * @since 2.1
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function setEcCertAllowed()
    {
        $ecCertSetting =
            PluginSettings::getSetting(PluginSettings::FLD_GENERATE_EC_CERTS);

        if ( $ecCertSetting == PluginSettings::SETTING_OFF ) {
            $this->tplData[self::FLD_EC_ALLOWED] = false;
        }
        else {
            $this->tplData[self::FLD_EC_ALLOWED] = true;
        }
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
        return realpath(__DIR__ . '/../Tpl') . '/Main.tpl';
    }

}
