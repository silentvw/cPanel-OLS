<?php

/** ******************************************
 * LiteSpeed Web Server Plugin for WHM
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2018-2022
 * ******************************************* */

namespace LsPanel;

use Lsc\Wp\LSCMException;
use Lsc\Wp\Panel\ControlPanel;
use Lsc\Wp\Panel\CPanel;

class WhmMod_LiteSpeed_CPanelConf
{

    /**
     * @deprecated 4.1.5  Split into new paper_lantern and jupiter theme
     *     specific constants.
     * @var string
     */
    const CPANEL_PLUGIN_DIR = '/usr/local/cpanel/base/frontend/paper_lantern/ls_web_cache_manager';

    /**
     * @since 4.1.5
     * @var string
     */
    const THEME_JUPITER_PLUGIN_DIR = '/usr/local/cpanel/base/frontend/jupiter/ls_web_cache_manager';

    /**
     * @since 4.1.5
     * @var string
     */
    const THEME_PAPER_LANTERN_PLUGIN_DIR = '/usr/local/cpanel/base/frontend/paper_lantern/ls_web_cache_manager';

    /**
     * @since 4.1.5
     * @var string
     */
    const CPANEL_PLUGIN_3RD_PARTY_DIR = '/usr/local/cpanel/3rdparty/ls_webcache_mgr';

    /**
     * @var string
     */
    const CPANEL_PLUGIN_CONF = self::CPANEL_PLUGIN_3RD_PARTY_DIR . '/lswcm.conf';

    /**
     * @since 4.1.3.1
     * @var string
     */
    const CPANEL_PLUGIN_TEMP_DATA_DIR = __DIR__ . '/data';

    /**
     * @since 4.1
     * @var string
     */
    const CPANEL_PLUGIN_TEMP_CONF = self::CPANEL_PLUGIN_TEMP_DATA_DIR . '/lswcm.conf';

    /**
     * @deprecated 4.1.5  No longer used.
     * @var string
     */
    const CPANEL_PLUGIN_THEME_DIR = '/usr/local/cpanel/base/frontend/paper_lantern/ls_web_cache_manager/landing';

    /**
     * @since 4.1.5
     * @var string
     */
    const CPANEL_PLUGIN_RELATIVE_THEME_DIR = 'landing';

    /**
     * @var string
     */
    const FLD_CPANEL_PLUGIN_INSTALLED = 'cpanel_plugin_installed';

    /**
     * @var string
     */
    const FLD_CPANEL_PLUGIN_AUTOINSTALL = 'cpanel_plugin_autoinstall';

    /**
     * @var string
     */
    const FLD_LSWS_DIR = 'lsws_dir';

    /**
     * @var string
     */
    const FLD_VHOST_CACHE_ROOT = 'vhost_cache_root';

    /**
     * @var string
     */
    const FLD_USE_CUST_TPL = 'use_cust_tpl';

    /**
     * @var string
     */
    const FLD_CUST_TPL_NAME = 'cust_tpl_name';

    /**
     * @since 4.1
     * @var string
     */
    const FLD_GENERATE_EC_CERTS = 'generateEcCerts';

    /**
     * @since 4.1
     * @var string
     */
    const SETTING_OFF = 0;

    /**
     * @since 4.1
     * @var string
     */
    const SETTING_ON = 1;

    /**
     * @since 4.1
     * @var string
     */
    const SETTING_ON_PLUS_AUTO = 2;

    /**
     * @var ControlPanel
     */
    private $panelEnv;

    /**
     * @var array
     */
    private $data = array(
        self::FLD_LSWS_DIR => '/usr/local/lsws/',
        self::FLD_VHOST_CACHE_ROOT => '',
        self::FLD_GENERATE_EC_CERTS => self::SETTING_OFF,
        self::FLD_USE_CUST_TPL => false,
        self::FLD_CUST_TPL_NAME => ''
    );

    /**
     * @var string[]
     */
    private $succ_msgs = array();

    /**
     * @var string[]
     */
    private $err_msgs = array();

    /**
     * @var boolean
     */
    private $save = false;

    /**
     *
     * @throws LSCMException  Thrown indirectly.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     *
     * @return void
     * @throws LSCMException  Thrown indirectly.
     */
    private function init()
    {
        $this->panelEnv =
            ControlPanel::getClassInstance('\Lsc\Wp\Panel\CPanel');

        $this->data[self::FLD_CPANEL_PLUGIN_AUTOINSTALL] =
            (int)CPanel::isCpanelPluginAutoInstallOn();

        $this->data[self::FLD_CPANEL_PLUGIN_INSTALLED] = (
            file_exists(self::THEME_JUPITER_PLUGIN_DIR)
                || file_exists(self::THEME_PAPER_LANTERN_PLUGIN_DIR)
        );

        $contents = $this->getConfFileContent();

        if ( $contents == '' ) {
            $this->writeDefaultConf();
            return;
        }

        $this->loadSettingVhCacheRoot($contents);
        $this->loadSettingGenerateEcCerts($contents);
        $this->loadSettingUseCustTpl($contents);
        $this->loadSettingCustTpl($contents);

        /**
         * Save any detected changes while loading.
         */
        $this->trySaveConf();
    }

    /**
     *
     * @since 4.1
     *
     * @return string
     */
    private function getConfFileContent()
    {
        if ( file_exists(self::CPANEL_PLUGIN_CONF)
                && ($content = file_get_contents(self::CPANEL_PLUGIN_CONF)) ) {

            return $content;
        }

        if ( file_exists(self::CPANEL_PLUGIN_TEMP_CONF)
            && ($content = file_get_contents(self::CPANEL_PLUGIN_TEMP_CONF)) ) {

            return $content;
        }

        return '';
    }

    private function writeDefaultConf()
    {
        if ( defined('LSWS_HOME') ) {
            $this->data[self::FLD_LSWS_DIR] = LSWS_HOME;
        }
        $this->data[self::FLD_LSWS_DIR] = "/usr/local/lsws/";

        $vhCacheRoot = $this->panelEnv->getVHCacheRoot();

        if ( $vhCacheRoot != ControlPanel::NOT_SET ) {
            $this->data[self::FLD_VHOST_CACHE_ROOT] = $vhCacheRoot;
        }

        $this->save = true;
        $this->trySaveConf();
    }

    /**
     *
     * @param string  $pattern
     * @param string  $contents
     * @return string
     */
    private function readSetting( $pattern, $contents )
    {
        preg_match($pattern, $contents, $matches);

        if ( isset($matches[1]) ) {
            return $matches[1];
        }
        else {
            return '';
        }
    }

    /**
     * Attempts to create a temporary WhmMod_LiteSpeed_CPanelConf object which
     * should create a cPanel config file if one does not exist or, if it does
     * exist, read the config file setting values and update the file if
     * changes are detected.
     */
    public static function verifyCpanelPluginConfFile()
    {
        new self();
    }

    /**
     *
     * @param string  $contents  Contents of lswcm.conf file.
     */
    private function loadSettingLswsHomeDir( $contents )
    {
        $pattern = '/LSWS_HOME_DIR = "(.*)"/';
        $setting = $this->readSetting($pattern, $contents);
        $this->data[self::FLD_LSWS_DIR] = $setting;

        if ( defined('LSWS_HOME')
                && $this->data[self::FLD_LSWS_DIR] != LSWS_HOME) {

            $this->setLswsDir(LSWS_HOME);
        }
    }

    /**
     *
     * @param string  $contents  Contents of lswcm.conf file.
     */
    private function loadSettingVhCacheRoot( $contents )
    {
        $pattern = '/VHOST_CACHE_ROOT = "(.*)"/';
        $setting = $this->readSetting($pattern, $contents);
        $this->data[self::FLD_VHOST_CACHE_ROOT] = $setting;

        $vhCacheRoot = $this->panelEnv->getVHCacheRoot();

        if ( $vhCacheRoot == ControlPanel::NOT_SET ) {
            $vhCacheRoot = '';
        }

        if ( $this->data[self::FLD_VHOST_CACHE_ROOT] != $vhCacheRoot ) {
            $this->setVhCacheRoot($vhCacheRoot);
        }
    }

    /**
     *
     * @param string  $contents  Contents of lswcm.conf file.
     */
    private function loadSettingUseCustTpl( $contents )
    {
        $pattern = '/USE_CUST_TPL = (\d)/';
        $setting = $this->readSetting($pattern, $contents);
        $this->data[self::FLD_USE_CUST_TPL] = (bool)$setting;
    }

    /**
     *
     * @param string  $contents  Contents of lswcm.conf file.
     */
    private function loadSettingCustTpl( $contents )
    {
        $pattern = '/CUST_TPL = "(.*)"/';
        $setting = $this->readSetting($pattern, $contents);
        $this->data[self::FLD_CUST_TPL_NAME] = $setting;
    }

    /**
     *
     * @since 4.1
     *
     * @param string  $contents  Contents of lswcm.conf file.
     */
    private function loadSettingGenerateEcCerts( $contents )
    {
        $pattern = '/GENERATE_EC_CERTS = (\d)/';
        $setting = $this->readSetting($pattern, $contents);
        $this->data[self::FLD_GENERATE_EC_CERTS] = (int)$setting;
    }

    /**
     *
     * @param string  $field
     * @return mixed
     */
    public function getData( $field = '' )
    {
        if ( !isset($this->data[$field]) ) {
            return null;
        }

        return $this->data[$field];
    }

    /**
     *
     * @param boolean  $clear
     * @return string[]
     */
    public function getSuccMsgs( $clear = false )
    {
        $succMsgs = $this->succ_msgs;

        if ( $clear ) {
            $this->succ_msgs = array();
        }

        return $succMsgs;
    }

    /**
     *
     * @param boolean  $clear
     * @return string[]
     */
    public function getErrMsgs( $clear = false )
    {
        $errMsgs = $this->err_msgs;

        if ( $clear ) {
            $this->err_msgs = array();
        }

        return $errMsgs;
    }

    public function setAutoInstallUse( $autoInstall )
    {
        if ( $autoInstall != $this->data[self::FLD_CPANEL_PLUGIN_AUTOINSTALL] ) {
            $this->data[self::FLD_CPANEL_PLUGIN_AUTOINSTALL] = $autoInstall;
            $this->save = true;
        }

        if ( $autoInstall === 1 ) {
            CPanel::turnOnCpanelPluginAutoInstall();
        }
        else {
            CPanel::turnOffCpanelPluginAutoInstall();
        }

        return true;
    }

    /**
     *
     * @param boolean  $useCustTpl
     * @return boolean
     */
    public function setTplUse( $useCustTpl )
    {
        if ( $useCustTpl != $this->data[self::FLD_USE_CUST_TPL] ) {
            $this->data[self::FLD_USE_CUST_TPL] = $useCustTpl;
            $this->save = true;
        }

        return true;
    }

    public function clearTplName()
    {
        $this->data[self::FLD_CUST_TPL_NAME] = '';
        $this->save = true;
    }

    /**
     *
     * @param string  $newCustTpl
     * @return boolean
     */
    public function setTplName( $newCustTpl )
    {
        if ( $newCustTpl == '' ) {
            return false;
        }
        else if ( $newCustTpl == $this->data[self::FLD_CUST_TPL_NAME] ) {
            return true;
        }

        $themeDirs = array();

        if ( file_exists(self::THEME_JUPITER_PLUGIN_DIR) ) {
            $themeDirs[] = self::THEME_JUPITER_PLUGIN_DIR . '/'
                . self::CPANEL_PLUGIN_RELATIVE_THEME_DIR;
        }

        if ( file_exists(self::THEME_PAPER_LANTERN_PLUGIN_DIR) ) {
            $themeDirs[] = self::THEME_PAPER_LANTERN_PLUGIN_DIR . '/'
                . self::CPANEL_PLUGIN_RELATIVE_THEME_DIR;
        }

        if ( empty($themeDirs) ) {
            $this->err_msgs[] = 'Could not find installed LS Web Cache Manager '
                   . 'user-end cPanel plugin.';

            return false;
        }

        $newCustTplSafe = htmlspecialchars($newCustTpl);

        foreach ( $themeDirs as $themeDir ) {

            if ( !file_exists("$themeDir/$newCustTpl/index.php") ) {
                $this->err_msgs[] = 'Could not find index.php file for custom '
                    . "template '$newCustTplSafe' under $themeDir. Custom "
                    . 'template must be added to the LS Web Cache Manager '
                    . 'user-end cPanel plugin\s custom theme directory under '
                    . 'all cPanel themes where it is installed.';

                return false;
            }
        }

        $this->data[self::FLD_CUST_TPL_NAME] = $newCustTpl;
        $this->save = true;

        $this->succ_msgs[] = "Custom template $newCustTplSafe set";

        return true;
    }

    /**
     *
     * @param string  $newLswsDir
     * @return boolean
     */
    private function setLswsDir( $newLswsDir )
    {
        if ( !is_dir($newLswsDir) ) {
            return false;
        }

        $this->data[self::FLD_LSWS_DIR] = $newLswsDir;
        $this->save = true;

        $this->succ_msgs[] = 'LSWS_HOME_DIR set in cPanel Plugin conf file.';

        return true;
    }

    /**
     *
     * @param string  $newVhCacheRoot
     * @return boolean
     */
    public function setVhCacheRoot( $newVhCacheRoot )
    {
        $this->data[self::FLD_VHOST_CACHE_ROOT] = $newVhCacheRoot;
        $this->save = true;

        $this->succ_msgs[] = 'VHOST_CACHE_ROOT set in cPanel Plugin conf file.';

        return true;
    }

    /**
     *
     * @since 4.1
     *
     * @param int  $generateEcCerts
     * @return boolean
     */
    public function setGenerateEcCerts( $generateEcCerts )
    {
        if ( $generateEcCerts < 0 || $generateEcCerts > 2 ) {
            return false;
        }

        if ( $generateEcCerts != $this->data[self::FLD_GENERATE_EC_CERTS] ) {
            $this->data[self::FLD_GENERATE_EC_CERTS] = $generateEcCerts;
            $this->save = true;
        }

        return true;
    }

    /**
     *
     * @since 4.1
     *
     * @return bool
     */
    private function tryRunCertSupportScripts()
    {
        if ( file_exists(self::THEME_JUPITER_PLUGIN_DIR) ) {
            $pluginDir = self::THEME_JUPITER_PLUGIN_DIR;
        }
        elseif ( file_exists(self::THEME_PAPER_LANTERN_PLUGIN_DIR) ) {
            $pluginDir = self::THEME_PAPER_LANTERN_PLUGIN_DIR;
        }
        else {
            return false;
        }

        if ( $this->data[self::FLD_GENERATE_EC_CERTS] == self::SETTING_OFF ) {
            exec($pluginDir . '/scripts/cert_support_remove.sh');
        }
        else {
            exec($pluginDir . '/scripts/cert_support_add.sh');
        }

        return true;
    }

    /**
     *
     * @return void
     */
    public function trySaveConf()
    {
        if ( !$this->save ) {
            return;
        }

        $useCustTpl = (int)$this->data[self::FLD_USE_CUST_TPL];

        $content = <<<EOF
LSWS_HOME_DIR = "{$this->data[self::FLD_LSWS_DIR]}"
VHOST_CACHE_ROOT = "{$this->data[self::FLD_VHOST_CACHE_ROOT]}"
USE_CUST_TPL = $useCustTpl
CUST_TPL = "{$this->data[self::FLD_CUST_TPL_NAME]}"
GENERATE_EC_CERTS = {$this->data[self::FLD_GENERATE_EC_CERTS]}

EOF;

        if ( !file_exists(self::CPANEL_PLUGIN_TEMP_DATA_DIR) ) {
            mkdir(self::CPANEL_PLUGIN_TEMP_DATA_DIR, 0700);
        }

        file_put_contents(self::CPANEL_PLUGIN_TEMP_CONF, $content);

        if ( $this->data[self::FLD_CPANEL_PLUGIN_INSTALLED] ) {

            if ( !file_exists(self::CPANEL_PLUGIN_3RD_PARTY_DIR) ) {
                mkdir(self::CPANEL_PLUGIN_3RD_PARTY_DIR, 0755);
            }
            else {
                chmod(self::CPANEL_PLUGIN_3RD_PARTY_DIR, 0755);
            }

            file_put_contents(self::CPANEL_PLUGIN_CONF, $content);
            chmod(self::CPANEL_PLUGIN_CONF, 0644);
        }

        $this->save = false;

        $this->tryRunCertSupportScripts();
    }

}
