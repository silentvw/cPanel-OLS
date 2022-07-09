<?php

/** *********************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2020
 * @since 2.1
 * *******************************************
 */

namespace LsUserPanel;

use \LsUserPanel\Lsc\UserLogger;
use \LsUserPanel\Lsc\UserLSCMException;

/**
 * Map to EC sites data file.
 *
 * @since 2.1
 */
class EcCertSiteStorage
{

    /**
     * @since 2.1
     * @var string
     */
    const DATA_VERSION = '1.0';

    /**
     * @since 2.1
     * @var int
     */
    const ERR_NOT_EXIST = 1;

    /**
     * @since 2.1
     * @var int
     */
    const ERR_CORRUPTED = 2;

    /**
     * @since 2.1
     * @var int
     */
    const ERR_VERSION_HIGH = 3;

    /**
     * @since 2.1
     * @var int
     */
    const ERR_VERSION_LOW = 4;

    /**
     * @since 2.1
     * @var string
     */
    const EC_LIST_DATA_FILE = 'ecList.data';

    /**
     * @since 2.1
     * @var string
     */
    const CMD_GEN_EC = 'genEc';

    /**
     * @since 2.1
     * @var string
     */
    const CMD_REMOVE_EC = 'removeEc';

    /**
     * @since 2.1
     * @var string
     */
    private $dataFilePath;

    /**
     * @since 2.1
     * @var null|EcCertSite[]  Key is servername.
     */
    private $ecCertSites = null;

    /**
     * @since 2.1
     * @var int
     */
    private $error;

    /**
     *
     * @since 2.1
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    public function __construct()
    {
        $this->error = $this->init();
    }

    /**
     *
     * @since 2.1
     *
     * @return int
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function init()
    {
        $this->dataFilePath =
            Ls_WebCacheMgr_Util::getUserLSCMDataDir() . '/'
            . self::EC_LIST_DATA_FILE;

        if ( !file_exists($this->dataFilePath) ) {
            return self::ERR_NOT_EXIST;
        }

        $content = file_get_contents($this->dataFilePath);

        $data = json_decode($content, true);

        if ( $data === null
            || $data === false
            || !is_array($data)
            || !isset($data['__VER__']) ) {

            return self::ERR_CORRUPTED;
        }

        if ( $err = $this->checkDataFileVer($data['__VER__']) ) {
            return $err;
        }

        unset($data['__VER__']);

        $this->ecCertSites = array();

        foreach ( $data as $serverName => $ecCertSiteData ) {
            $this->ecCertSites[$serverName] = new EcCertSite(
                $ecCertSiteData[EcCertSite::FLD_DOCROOT],
                $serverName,
                $ecCertSiteData[EcCertSite::FLD_HAS_SSL_VH],
                $ecCertSiteData[EcCertSite::FLD_EC_EXISTS],
                $ecCertSiteData[EcCertSite::FLD_EC_CERT_COVERED],
                $ecCertSiteData[EcCertSite::FLD_LAST_GEN_MSG]
            );
        }

        return 0;
    }

    /**
     *
     * @since 2.1
     *
     * @return int
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     *
     * @since 2.1
     *
     * @param bool  $SslVhOnly
     * @return int
     */
    public function getCount($SslVhOnly = false )
    {
        if ( !$SslVhOnly ) {
            return count($this->ecCertSites);
        }

        $count = 0;

        foreach ($this->ecCertSites as $ecCertSite ) {

            if ( $ecCertSite->hasSslVh() ) {
                $count++;
            }
        }

        return $count;
    }

    /**
     *
     * @since 2.1
     *
     * @return null|EcCertSite[]
     */
    public function getEcCertSites()
    {
        return $this->ecCertSites;
    }

    /**
     * Get all known EcCertSite servernames.
     *
     * @since 2.1
     *
     * @return string[]
     */
    public function getServerNames()
    {
        if ( $this->ecCertSites == null ) {
            return array();
        }

        return array_keys($this->ecCertSites);
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $serverName
     * @return EcCertSite|null
     */
    public function getEcCertSite($serverName )
    {
        if ( isset($this->ecCertSites[$serverName]) ) {
            return $this->ecCertSites[$serverName];
        }

        return null;
    }

    /**
     *
     * @since 2.1
     */
    public function syncToDisk()
    {
        $data = array( '__VER__' => self::DATA_VERSION );

        if ( !empty($this->ecCertSites) ) {

            foreach ($this->ecCertSites as $serverName => $site ) {

                if ( $site->hasValidPath() ) {
                    $data[$serverName] = $site->getData();
                }
            }

            ksort($data);
        }

        $file_str = json_encode($data);
        file_put_contents($this->dataFilePath, $file_str, LOCK_EX);
        chmod($this->dataFilePath, 0600);

        UserLogger::logMsg(
            "EC Data file saved {$this->dataFilePath}",
            UserLogger::L_DEBUG
        );
    }

    /**
     * Updates data file to the latest format if possible/needed.
     *
     * @since 2.1
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
                UserLogger::L_INFO
            );
            return self::ERR_VERSION_HIGH;
        }

        if ( $res == -1 ) {
            return self::ERR_VERSION_LOW;
        }

        return 0;
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $action
     * @param string  $serverName
     * @return void
     */
    private function doEcCertSiteAction($action, $serverName )
    {
        if ( ($ecCertSite = $this->getEcCertSite($serverName)) == null ) {
            return;
        }

        switch ($action) {
            case self::CMD_GEN_EC:

                if ( $ecCertSite->getData(EcCertSite::FLD_HAS_SSL_VH) == false ) {
                    UserLogger::addUiMsg(
                        "{$serverName} - "
                            . _('Domain has no SSL VHost. Skipped.'),
                        UserLogger::UI_SUCC
                    );

                    return;
                }

                $ecCertSite->generateEcCert();
                break;

            case self::CMD_REMOVE_EC:
                $ecCertSite->removeEcCert();
                break;

            //no default
        }
    }

    /**
     *
     * @since 2.1
     *
     * @param string    $action
     * @param string[]  $list
     */
    public function doEcCertAction($action, $list )
    {
        $count = count($list);

        UserLogger::logMsg(
            "doAction {$action} for {$count} items",
            UserLogger::L_VERBOSE
        );

        foreach ( $list as $serverName ) {
            $this->doEcCertSiteAction($action, $serverName);
        }

        $this->syncToDisk();
    }

    /**
     *
     * @since 2.1
     *
     * @return void
     */
    public function updateList()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $result = CPanelWrapper::getCpanelObj()->uapi(
            'lsws',
            'getUpdatedEcCertList'
        );

        $ecCertListDataJson =
            $result['cpanelresult']['result']['data']['ecCertListDataJson'];

        $ecCertListData = json_decode($ecCertListDataJson, true);

        if ( empty($ecCertListData) ) {
            return;
        }

        $newEcCertSites = array();

        foreach ( $ecCertListData as $serverName => $data ) {
            $docroot = $data['docroot'];
            $hasSslVhost = (bool)$data['sslVh'];
            $ecCertExists = (bool)$data['ecCert'];
            $lastGenMsg = '';

            $ecCertCovered = Ls_WebCacheMgr_Util::getCertificateAltNames(
                $data['ecCertFingerprint']
            );

            $existingEcCertSite = $this->getEcCertSite($serverName);

            if ($existingEcCertSite !== null) {
                $lastGenMsg =
                    $existingEcCertSite->getData(EcCertSite::FLD_LAST_GEN_MSG);
            }

            $newEcCertSites[$serverName] = new EcCertSite(
                $docroot,
                $serverName,
                $hasSslVhost,
                $ecCertExists,
                $ecCertCovered,
                $lastGenMsg
            );
        }

        $this->ecCertSites = $newEcCertSites;
        $this->error = 0;

        $this->syncToDisk();
    }

}
