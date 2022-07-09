<?php

/** ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2018-2021
 * ******************************************* */

namespace LsUserPanel;

use LsUserPanel\Lsc\UserLSCMException;

class PluginSettings
{

    /**
     * @var string
     */
    const FLD_LSWS_HOME_DIR = 'lswsHomeDir';

    /**
     * @var string
     */
    const FLD_VHOST_CACHE_ROOT = 'vhostCacheRoot';

    /**
     * @since 2.1
     * @var string
     */
    const FLD_GENERATE_EC_CERTS = 'generateEcCerts';

    /**
     * @var string
     */
    const FLD_USE_CUST_THEME = 'useCustTheme';

    /**
     * @var string
     */
    const FLD_CUST_THEME = 'custTheme';

    /**
     * @since 2.1
     * @var string
     */
    const PLUGIN_CONF_FILE = '/usr/local/cpanel/3rdparty/ls_webcache_mgr/lswcm.conf';

    /**
     * @since 2.1
     * @var string
     */
    const SETTING_OFF = 0;

    /**
     * @since 2.1
     * @var string
     */
    const SETTING_ON = 1;

    /**
     * @since 2.1
     * @var string
     */
    const SETTING_ON_PLUS_AUTO = 2;

    /**
     * @var null|PluginSettings
     */
    private static $instance;

    /**
     * @since 2.1
     * @var array
     */
    private $data = array(
        self::FLD_LSWS_HOME_DIR => '',
        self::FLD_VHOST_CACHE_ROOT => '',
        self::FLD_GENERATE_EC_CERTS => self::SETTING_OFF,
        self::FLD_USE_CUST_THEME => false,
        self::FLD_CUST_THEME => ''
    );

    private function __construct()
    {
        $this->init();
    }

    private function init()
    {
        if ( file_exists(self::PLUGIN_CONF_FILE) ) {
            $this->readSettingsFile();
        }
    }

    /**
     *
     * @throws UserLSCMException
     */
    public static function initialize()
    {
        if ( self::$instance != null ) {
            /**
             * Do not allow, already initialized.
             */
            throw new UserLSCMException(
                'PluginSettings cannot be initialized twice.',
                UserLSCMException::E_PROGRAM
            );
        }

        self::$instance = new self();
    }

    /**
     *
     * @return PluginSettings
     * @throws UserLSCMException
     */
    private static function me()
    {
        if ( self::$instance == null ) {
            /**
             * Do not allow, must initialize first.
             */
            throw new UserLSCMException('Uninitialized PluginSettings.');
        }

        return self::$instance;
    }

    /**
     *
     * @param string  $setting
     * @return mixed
     * @throws UserLSCMException  Thrown indirectly.
     */
    public static function getSetting( $setting )
    {
        $m = self::me();

        if ( !isset($m->data[$setting]) ) {
            return null;
        }

        return $m->data[$setting];
    }

    /**
     *
     * @param string  $pattern
     * @param string  $contents
     * @return null|string
     */
    private function readSetting( $pattern, $contents )
    {
        preg_match($pattern, $contents, $matches);

        if ( isset($matches[1]) ) {
            return $matches[1];
        }
        else {
            return null;
        }
    }

    /**
     * Reads the contents of the cPanel plugin's conf file and sets any
     * the values for any expected settings found.
     */
    private function readSettingsFile()
    {
        $contents = file_get_contents(self::PLUGIN_CONF_FILE);

        $pattern = '/LSWS_HOME_DIR = "(.+)"/';
        $lswsHomeDir = $this->readSetting($pattern, $contents);

        if ( $lswsHomeDir !== null ) {
            $this->data[self::FLD_LSWS_HOME_DIR] = $lswsHomeDir;
        }

        $pattern = '/VHOST_CACHE_ROOT = "(.*)"/';
        $vhCacheRoot = $this->readSetting($pattern, $contents);

        if ( $vhCacheRoot !== null ) {

            if ( $vhCacheRoot[0] == '/' ) {
                $user = Ls_WebCacheMgr_Util::getCurrentCpanelUser();
                $vhCacheRoot =
                    str_replace('/$vh_user', "/{$user}", $vhCacheRoot);
            }

            $this->data[self::FLD_VHOST_CACHE_ROOT] = $vhCacheRoot;
        }

        $pattern = '/GENERATE_EC_CERTS = (\d)/';
        $genEcCerts = $this->readSetting($pattern, $contents);

        if ( $genEcCerts !== null ) {
            $this->data[self::FLD_GENERATE_EC_CERTS] = (int)$genEcCerts;
        }

        $pattern = '/USE_CUST_TPL = (\d)/';
        $useCustTheme = $this->readSetting($pattern, $contents);

        if ( $useCustTheme !== null ) {
            $this->data[self::FLD_USE_CUST_THEME] = (bool)$useCustTheme;
        }

        $pattern = '/CUST_TPL = "(.*)"/';
        $custTheme = $this->readSetting($pattern, $contents);

        if ( $custTheme !== null ) {
            $this->data[self::FLD_CUST_THEME] = $custTheme;
        }
    }

}
