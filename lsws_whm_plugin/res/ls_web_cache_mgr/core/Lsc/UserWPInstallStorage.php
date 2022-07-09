<?php

/** *********************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2018-2020
 * *******************************************
 */

namespace LsUserPanel\Lsc;

use \LsUserPanel\CPanelWrapper;
use \LsUserPanel\Ls_WebCacheMgr_Util;
use \LsUserPanel\Lsc\Context\UserContext;
use \LsUserPanel\PluginSettings;
use \LsUserPanel\QuicCloudApiUtil;

/**
 * Map to data file.
 */
class UserWPInstallStorage
{

    /**
     * @since 2.1
     * @var string
     */
    const CMD_QUICCLOUD_UPLOAD_SSL_CERT = 'quicCloudUploadSslCert';

    /**
     * @var string
     */
    const DATA_VERSION = '1.0';

    /**
     * @var int
     */
    const ERR_NOT_EXIST = 1;

    /**
     * @var int
     */
    const ERR_CORRUPTED = 2;

    /**
     * @var int
     */
    const ERR_VERSION_HIGH = 3;

    /**
     * @var int
     */
    const ERR_VERSION_LOW = 4;

    /**
     * @var string
     */
    private $dataFile;

    /**
     * @var null|UserWPInstall[]  Key is path
     */
    private $wpInstalls = null;

    /**
     * @var int
     */
    private $error;

    /**
     * @var UserWPInstall[]
     */
    private $workingQueue = array();

    /**
     *
     * @param string  $dataFile
     */
    public function __construct( $dataFile )
    {
        $this->dataFile = $dataFile;
        $this->error = $this->init();
    }

    /**
     *
     * @return int
     */
    private function init()
    {
        if ( !file_exists($this->dataFile) ) {
            return self::ERR_NOT_EXIST;
        }

        $content = file_get_contents($this->dataFile);

        if ( ($data = json_decode($content, true)) === null ) {
            /*
             * Data file may be in old serialized format. Try unserializing.
             */
            $data = unserialize($content);
        }

        if ( $data === false || !is_array($data) || !isset($data['__VER__']) ) {
            return self::ERR_CORRUPTED;
        }

        if ( $err = $this->checkDataFileVer($data['__VER__']) ) {
            return $err;
        }

        unset($data['__VER__']);

        $this->wpInstalls = array();

        foreach ( $data as $path => $idata ) {
            $i = new UserWPInstall($path);
            $i->initData($idata);
            $this->wpInstalls[$path] = $i;
        }

        return 0;
    }

    /**
     *
     * @return int
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     *
     * @param boolean  $nonFatalOnly
     * @return int
     */
    public function getCount( $nonFatalOnly = false )
    {
        if ( !$nonFatalOnly ) {
            return count($this->wpInstalls);
        }

        $count = 0;

        foreach ( $this->wpInstalls as $install ) {

            if ( !$install->hasFatalError() ) {
                $count++;
            }
        }

        return $count;
    }

    /**
     *
     * @return null|UserWPInstall[]
     */
    public function getWPInstalls()
    {
        return $this->wpInstalls;
    }

    /**
     * Get all known WPInstall paths.
     *
     * @return string[]
     */
    public function getPaths()
    {
        if ( $this->wpInstalls == null ) {
            return array();
        }

        return array_keys($this->wpInstalls);
    }

    /**
     *
     * @param string  $path
     * @return UserWPInstall|null
     */
    public function getWPInstall( $path )
    {
        if ( ($realPath = realpath($path)) === false ) {
            $index = $path;
        }
        else {
            $index = $realPath;
        }

        if ( isset($this->wpInstalls[$index]) ) {
            return $this->wpInstalls[$index];
        }

        return null;
    }

    /**
     *
     * @param UserWPInstall  $wpInstall
     */
    public function addWPInstall( UserWPInstall $wpInstall )
    {
        $this->wpInstalls[$wpInstall->getPath()] = $wpInstall;
    }

    public function syncToDisk()
    {
        $data = array( '__VER__' => self::DATA_VERSION );

        if ( !empty($this->wpInstalls) ) {

            foreach ( $this->wpInstalls as $path => $install ) {

                if ( !$install->shouldRemove() ) {
                    $data[$path] = $install->getData();
                }
            }

            ksort($data);
        }

        $file_str = json_encode($data);
        file_put_contents($this->dataFile, $file_str, LOCK_EX);
        chmod($this->dataFile, 0600);

        $this->log("Data file saved {$this->dataFile}", UserLogger::L_DEBUG);
    }

    /**
     * Updates data file to the latest format if possible/needed.
     *
     * @param string  $dataFileVer
     * @return int
     */
    private function checkDataFileVer( $dataFileVer )
    {
        $res = version_compare($dataFileVer, self::DATA_VERSION);

        if ( $res == 1 ) {
            UserLogger::logMsg(
                    'Data file version is higher than expected and cannot be used.',
                    UserLogger::L_INFO);
            return self::ERR_VERSION_HIGH;
        }

        if ( $res == -1 && !$this->upgradeDataFile($dataFileVer) ) {
            return self::ERR_VERSION_LOW;
        }

        return 0;
    }

    /**
     *
     * @param string  $dataFileVersion
     * @return boolean
     */
    private function upgradeDataFile( $dataFileVersion )
    {
        UserLogger::logMsg(
                'Old data file version detected. Attempting to update...',
                UserLogger::L_INFO);

        /**
         * Currently no versions are upgradeable to 1.5
         */
        $updatableVersions = array();

        if ( !in_array($dataFileVersion, $updatableVersions)
                || !UserUtil::createBackup($this->dataFile) ) {

            UserLogger::logMsg('Data file could not be updated to version '
                    . self::DATA_VERSION, UserLogger::L_ERROR);

            return false;
        }

        /**
         * Upgrade funcs will be called here.
         */

        return true;
    }

    /**
     *
     * @param string    $action
     * @param string    $path
     * @param string[]  $extraArgs
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function doWPInstallAction( $action, $path, $extraArgs )
    {
        if ( ($wpInstall = $this->getWPInstall($path)) == null ) {
            $wpInstall = new UserWPInstall($path);
            $this->addWPInstall($wpInstall);
        }

        switch ($action) {
            case 'flag':

                if ( !$wpInstall->hasValidPath() ) {
                    return;
                }

                if ( $wpInstall->addUserFlagFile() ) {
                    $msg = _('Flag file set');
                    $wpInstall->setCmdStatusAndMsg(UserUserCommand::EXIT_SUCC,
                            $msg);
                }
                else {
                    $msg = _('Could not create flag file');
                    $wpInstall->setCmdStatusAndMsg(UserUserCommand::EXIT_FAIL,
                            $msg);
                }

                $this->workingQueue[] = $wpInstall;

                return;
            case 'unflag':

                if ( !$wpInstall->hasValidPath() ) {
                    return;
                }

                $wpInstall->removeFlagFile();

                $msg = _('Flag file unset');
                $wpInstall->setCmdStatusAndMsg(UserUserCommand::EXIT_SUCC, $msg);

                $this->workingQueue[] = $wpInstall;

                return;
            case UserUserCommand::CMD_DIRECT_ENABLE:
            case UserUserCommand::CMD_DISABLE:

                if ( $wpInstall->hasFatalError() ) {
                    $msg = _('Install skipped due to Error status. Please '
                            . 'Refresh Status before trying again.');
                    $wpInstall->setCmdStatusAndMsg(UserUserCommand::EXIT_FAIL,
                            $msg);

                    $this->workingQueue[] = $wpInstall;

                    return;
                }

                break;

            case UserWPInstallStorage::CMD_QUICCLOUD_UPLOAD_SSL_CERT:
                    $this->quicCloudUploadSslCert($wpInstall);
                    return;

            //no default
        }

        if ( UserUserCommand::issue($action, $wpInstall, $extraArgs) ) {
            $this->workingQueue[] = $wpInstall;
        }
    }

    /**
     *
     * @param string         $action
     * @param string[]       $list
     * @param string[]       $extraArgs
     * @throws UserLSCMException  Thrown indirectly.
     */
    public function doAction( $action, $list, $extraArgs = array() )
    {
        $count = count($list);
        $this->log(
                "doAction {$action} for {$count} items", UserLogger::L_VERBOSE);
        $newWpInstalls = array();

        foreach ( $list as $path ) {

            if ( $action == 'scan' ) {
                $this->scan($path, $newWpInstalls, true);
            }
            else {
                $this->doWPInstallAction($action, $path, $extraArgs);
            }
        }

        if ( $action == 'scan' ) {

            /*
             * Add an error message for any remaining $this->wpInstalls[]
             * installations not re-discovered during scan.
             */
            foreach ( $this->wpInstalls as $key => $wpInstall ) {
                $msg = "{$key} - Installation could not be found during Scan "
                    . 'and has been removed from the Cache Manager list.';
                $uiMsg = "{$key} - "
                    . _('Installation could not be found during Scan and has '
                        . 'been removed from the Cache Manager list.');

                UserLogger::addUiMsg($uiMsg, UserLogger::UI_ERR);
                UserLogger::logMsg($msg, UserLogger::L_NOTICE);
            }

            $this->wpInstalls = $newWpInstalls;

            /**
             * Explicitly clear any data file errors after scanning as initial
             * data file load and scan operation happen in the same
             * process/page load.
             */
            $this->error = 0;
        }

        $this->syncToDisk();
    }

    /**
     *
     * @param string           $docroot
     * @param UserWPInstall[]  $newWpInstalls
     * @param boolean          $forceRefresh
     * @return void
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function scan( $docroot, &$newWpInstalls, $forceRefresh = false )
    {
        $depth = UserContext::getScanDepth();

        $cpanel = CPanelWrapper::getCpanelObj();

        /** @noinspection PhpUndefinedMethodInspection */
        $result = $cpanel->uapi(
            'lsws',
            'getScanDirs',
            array( 'docroot' => $docroot, 'depth' => $depth )
        );

        $directories = $result['cpanelresult']['result']['data']['scanData'];

        /**
         * Example:
         * /home/user/public_html/wordpress/wp-admin
         * /home/user/public_html/blog/wp-admin
         * /home/user/public_html/wp/wp-admin
         */
        if ( !preg_match_all("|{$docroot}(.*)(?=/wp-admin)|", $directories,
                        $matches) ) {

            /**
             *  Nothing found.
             */
            return;
        }

        foreach ( $matches[1] as $path ) {
            $wp_path = $docroot . $path;
            $refresh = $forceRefresh;

            if ( !isset($this->wpInstalls[$wp_path]) ) {
                $newWpInstalls[$wp_path] = new UserWPInstall($wp_path);
                $refresh = true;
                $this->log("New Installation Found: {$wp_path}",
                        UserLogger::L_INFO);
            }
            else {
                $newWpInstalls[$wp_path] = $this->wpInstalls[$wp_path];
                unset($this->wpInstalls[$wp_path]);
                $this->log("Installation already found: {$wp_path}",
                        UserLogger::L_DEBUG);
            }

            if ( $refresh ) {
                $newWpInstalls[$wp_path]->refreshStatus();
                $this->workingQueue[] = $newWpInstalls[$wp_path];
            }
        }
    }

    /**
     *
     * @since 2.1
     *
     * @param UserWPInstall $wpInstall
     * @throws UserLSCMException
     */
    private function quicCloudUploadSslCert( UserWPInstall $wpInstall )
    {
        if ( ! $wpInstall->isLscwpEnabled() ) {
            $wpInstall->refreshStatus();

            if ( ! $wpInstall->isLscwpEnabled() ) {
                UserLogger::addUiMsg(
                    "{$wpInstall->getPath()} - "
                        . sprintf(
                            _(
                                'LiteSpeed Cache Plugin for WordPress must be '
                                    . 'installed and enabled before attempting '
                                    . 'to upload SSL certificate to %s.'
                            ),
                            'QUIC.cloud'
                        ),
                    UserLogger::UI_ERR
                );
                return;
            }
        }

        $domain = $wpInstall->getData(UserWPInstall::FLD_SERVERNAME);

        $genEcCertsSetting = PluginSettings::getSetting(
            PluginSettings::FLD_GENERATE_EC_CERTS
        );

        if ( $genEcCertsSetting == PluginSettings::SETTING_ON_PLUS_AUTO ) {
            $allowEcCertGen = true;
        }
        else {
            $allowEcCertGen = false;
        }

        $sslData = Ls_WebCacheMgr_Util::getDomainSslData(
            $domain,
            $allowEcCertGen
        );

        $certFingerprint = $sslData['retCert'];
        $key = $sslData['retKey'];

        if ( $certFingerprint == '' || $key == '' ) {
            UserLogger::addUiMsg(
                "{$wpInstall->getPath()} - "
                    . _(
                        'Unable to get certificate/private key information for '
                            . 'this domain.'
                    ),
                UserLogger::UI_ERR
            );
            return;
        }

        $apiKey = UserUserCommand::getValueFromWordPress(
            UserUserCommand::CMD_GET_QUICCLOUD_API_KEY,
            $wpInstall
        );

        if ( $apiKey == '' ) {
            UserLogger::addUiMsg(
                "{$wpInstall->getPath()} - "
                    . sprintf(
                        _(
                        'Unable to retrieve %s API key. Please visit %s in the '
                            . 'WordPress Dashboard and confirm that the API '
                            . 'key has already been generated.'
                        ),
                        'QUIC.cloud',
                        '"LiteSPeed Cache -> General"'
                    ),
                UserLogger::UI_ERR
            );
            return;
        }

        $siteUrl = $wpInstall->getData(UserWPInstall::FLD_SITEURL);

        if ( ! preg_match('#^https?://#', $siteUrl) ) {
            $siteUrl = "http://{$siteUrl}";
        }

        try {
            $qcDomainList = QuicCloudApiUtil::callInfo($apiKey, $siteUrl);
        }
        catch ( UserLSCMException $e ) {
            UserLogger::addUiMsg(
                "{$wpInstall->getPath()} - " . $e->getMessage(),
                UserLogger::UI_ERR
            );
            return;
        }

        $certAltNames =
            Ls_WebCacheMgr_Util::getCertificateAltNames($certFingerprint);

        if ( ! empty(array_diff($qcDomainList, $certAltNames)) ) {
            UserLogger::addUiMsg(
                sprintf(
                    _(
                        'Detected SSL certificate does not contain all %s '
                            . 'configured domain and alias entries for this '
                            . 'site.'
                    ),
                    'QUIC.cloud'
                ),
                UserLogger::UI_ERR
            );
            return;
        }

        try {
            $succMsg = QuicCloudApiUtil::uploadCert(
                $apiKey,
                $domain,
                $siteUrl,
                $certFingerprint,
                $key
            );
        }
        catch ( UserLSCMException $e ) {
            UserLogger::addUiMsg(
                "{$wpInstall->getPath()} - " . $e->getMessage(),
                UserLogger::UI_ERR
            );
            return;
        }


        UserLogger::addUiMsg(
            "{$wpInstall->getPath()} - {$succMsg}",
            UserLogger::UI_SUCC
        );
    }

    /**
     * Get all WPInstall command messages as a key=>value array.
     *
     * @return string[][]
     */
    public function getAllCmdMsgs()
    {
        $succ = $fail = $err = array();

        foreach ( $this->workingQueue as $WPInstall ) {
            $cmdStatus = $WPInstall->getCmdStatus();

            if ( $cmdStatus & UserUserCommand::EXIT_SUCC ) {
                $msgType = &$succ;
            }
            elseif ( $cmdStatus & UserUserCommand::EXIT_FAIL ) {
                $msgType = &$fail;
            }
            elseif ( $cmdStatus & UserUserCommand::EXIT_ERROR ) {
                $msgType = &$err;
            }
            else {
                continue;
            }

            if ( $msg = $WPInstall->getCmdMsg() ) {
                $msgType[] = "{$WPInstall->getPath()} - {$msg}";
            }
        }

        return array( 'succ' => $succ, 'fail' => $fail, 'err' => $err );
    }

    /**
     *
     * @param string  $msg
     * @param int     $level
     */
    protected function log( $msg, $level )
    {
        UserLogger::logMsg("WPInstallStorage - {$msg}", $level);
    }

}
