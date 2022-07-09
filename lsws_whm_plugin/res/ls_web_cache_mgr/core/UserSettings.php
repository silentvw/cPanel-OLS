<?php

/** ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2018-2020
 * ******************************************* */

namespace LsUserPanel;

use \LsUserPanel\Lsc\UserLogger;
use \LsUserPanel\Lsc\UserLSCMException;
use \LsUserPanel\Lsc\UserUtil;

class UserSettings
{

    const FLD_LOG_FILE_LVL = 'logFileLvl';

    /**
     * @var null|UserSettings
     */
    private static $instance;

    /**
     * @var string
     */
    private $settingsFile;

    /**
     * @var mixed[]
     */
    private $settings = array();

    /**
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function __construct()
    {
        $this->init();
    }

    /**
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function init()
    {
        $this->setDefaultSettings();

        $dataDir = Ls_WebCacheMgr_Util::getUserLSCMDataDir();

        if ( !file_exists($dataDir) ) {
            Ls_WebCacheMgr_Util::createUserLSCMDataDir();
        }

        $this->settingsFile = "{$dataDir}/settings";

        if ( !file_exists($this->settingsFile) ) {
            $this->writeSettingsFile();
        }
        else {
            $this->readSettingsFile();
        }
    }

    /**
     *
     * @throws UserLSCMException  Thrown directly and indirectly.
     */
    public static function initialize()
    {
        if ( self::$instance != null ) {
            /**
             * Do not allow, already initialized.
             */
            throw new UserLSCMException(
                'UserSettings cannot be initialized twice.',
                UserLSCMException::E_PROGRAM
            );
        }

        self::$instance = new self();
    }

    /**
     *
     * @return UserSettings
     * @throws UserLSCMException
     */
    private static function me()
    {
        if ( self::$instance == null ) {
            /**
             * Do not allow, must initialize first.
             */
            throw new UserLSCMException('Uninitialized UserSettings.');
        }

        return self::$instance;
    }

    /**
     *
     * @param string  $setting
     * @return mixed
     */
    public static function getSetting( $setting = '' )
    {
        $m = self::me();

        if ( !isset($m->settings[$setting]) ) {
            return null;
        }

        return $m->settings[$setting];
    }

    private function setDefaultSettings()
    {
        $this->settings = array(
            self::FLD_LOG_FILE_LVL => UserLogger::L_INFO
        );
    }

    /**
     *
     * @param mixed[]  $settings
     */
    public static function setSettings( $settings )
    {
        $save = true;

        $m = self::me();

        if ( !$m->setLogFileLvl($settings[self::FLD_LOG_FILE_LVL]) ) {
            $save = false;
        }

        if ( $save && $m->writeSettingsFile() ) {
            UserLogger::addUiMsg(_('Successfully saved user settings.'),
                    UserLogger::UI_SUCC);
        }
    }

    /**
     *
     * @param int  $lvl
     * @return boolean
     */
    private function setLogFileLvl( $lvl )
    {
        if ( $this->isValidLogFileLvl($lvl) ) {

            if ( $lvl > UserLogger::L_DEBUG ) {
                $lvl = UserLogger::L_DEBUG;
            }

            if ( $lvl != $this->settings[self::FLD_LOG_FILE_LVL] ) {
                $this->settings[self::FLD_LOG_FILE_LVL] = $lvl;
                return true;
            }
        }
        else {
            UserLogger::addUiMsg(_('Could not set Log File Level'), UserLogger::UI_ERR);
        }

        return false;
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

    private function readSettingsFile()
    {
        $contents = file_get_contents($this->settingsFile);

        $pattern = '/LOG_FILE_LVL = (\d+)/';
        $logFileLvl = $this->readSetting($pattern, $contents);

        if ( $logFileLvl !== '' ) {

            $logFileLvl = (int)$logFileLvl;

            if ( $this->isValidLogFileLvl($logFileLvl) ) {

                if ( $logFileLvl > UserLogger::L_DEBUG ) {
                    $logFileLvl = UserLogger::L_DEBUG;
                }

                $this->settings[self::FLD_LOG_FILE_LVL] = $logFileLvl;
            }
        }
    }

    /**
     *
     * @return boolean
     */
    private function writeSettingsFile()
    {
        $content = <<<EOF
LOG_FILE_LVL = {$this->settings[self::FLD_LOG_FILE_LVL]}
EOF;

        if ( file_put_contents($this->settingsFile, $content) !== false ) {
            return true;
        }

        return false;
    }

    /**
     *
     * @param int  $lvl
     * @return boolean
     */
    private function isValidLogFileLvl( $lvl )
    {
        if ( is_int($lvl) && $lvl >= 0 ) {
            return true;
        }

        return false;
    }

}
