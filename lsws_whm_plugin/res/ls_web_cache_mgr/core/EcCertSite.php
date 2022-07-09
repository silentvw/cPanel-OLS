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

/**
 *
 * @since 2.1
 */
class EcCertSite
{

    /**
     * @since 2.1
     * @var string
     */
    const FLD_HAS_SSL_VH = 'hasSslVhost';

    /**
     * @since 2.1
     * @var string
     */
    const FLD_DOCROOT = 'docroot';

    /**
     * @since 2.1
     * @var string
     */
    const FLD_SERVERNAME = 'serverName';

    /**
     * @since 2.1
     * @var string
     */
    const FLD_EC_EXISTS = 'ecExists';

    /**
     * @since 2.1
     * @var string
     */
    const FLD_EC_CERT_COVERED = 'ecCertCovered';

    /**
     * @since 2.1
     * @var string
     */
    const FLD_LAST_GEN_MSG = 'lastGenMsg';

    /**
     * @since 2.1
     * @var mixed[]
     */
    private $data;

    /**
     *
     * @since 2.1
     *
     * @param string    $docroot
     * @param string    $domain
     * @param bool      $sslVh
     * @param bool      $ecCert
     * @param string[]  $ecCertCovered,
     * @param string    $lastGenMsg
     */
    public function __construct( $docroot, $domain, $sslVh, $ecCert,
            $ecCertCovered, $lastGenMsg )
    {
        $this->init(
            $docroot,
            $domain,
            $sslVh,
            $ecCert,
            $ecCertCovered,
            $lastGenMsg
        );
    }

    /**
     *
     * @since 2.1
     *
     * @param string    $docroot
     * @param string    $domain
     * @param bool      $sslVh
     * @param bool      $ecCert
     * @param string[]  $ecCertCovered
     * @param string    $lastGenMsg
     */
    private function init($docroot, $domain, $sslVh, $ecCert,
            $ecCertCovered, $lastGenMsg )
    {
        if ( ($realPath = realpath($docroot)) != false ) {
            $docroot = $realPath;
        }
        $this->data = array(
            self::FLD_DOCROOT => $docroot,
            self::FLD_SERVERNAME => $domain,
            self::FLD_HAS_SSL_VH => $sslVh,
            self::FLD_EC_EXISTS => $ecCert,
            self::FLD_EC_CERT_COVERED => $ecCertCovered,
            self::FLD_LAST_GEN_MSG => $lastGenMsg
        );
    }

    /**
     *
     * @since 2.1
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf(
            "%s (docroot=%s  hasSslVh=%d  ecCertExists=%s  "
                . "coveredDomains=%s  lastGenMsg=\"%s\")",
            $this->data[self::FLD_SERVERNAME],
            $this->data[self::FLD_DOCROOT],
            (int)$this->data[self::FLD_HAS_SSL_VH],
            (int)$this->data[self::FLD_EC_EXISTS],
            implode(',', $this->data[self::FLD_EC_CERT_COVERED]),
            $this->data[self::FLD_LAST_GEN_MSG]
        );
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $field
     * @return mixed|null
     */
    public function getData( $field = '' )
    {
        if ( !$field ) {
            return $this->data;
        }

        if ( isset($this->data[$field]) ) {
            return $this->data[$field];
        }

        /**
         * Error out
         */
        return null;
    }

    /**
     *
     * @since 2.1
     *
     * @return bool
     */
    public function hasValidPath()
    {
        if ( !is_dir($this->data[self::FLD_DOCROOT]) ) {
            $msg = "{$this->data[self::FLD_SERVERNAME]} - DocRoot could not be "
                . 'found and has been removed from EC Certificate Manager '
                . 'list.';
            $uiMsg = "{$this->data[self::FLD_SERVERNAME]} - "
                . _(
                    'DocRoot could not be found and has been removed from EC '
                        . 'Certificate Manger list.'
                );

            UserLogger::addUiMsg($uiMsg, UserLogger::UI_ERR);
            UserLogger::logMsg($msg, UserLogger::L_NOTICE);
        }
        else {
            return true;
        }

        return false;
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $msg
     */
    private function setLastGenerationMessage($msg)
    {
        $this->data[self::FLD_LAST_GEN_MSG] =
            '[' . date('Y-m-d H:i:s', time()) . "] {$msg}";
    }

    /**
     *
     * @since 2.1
     */
    public function generateEcCert()
    {
        $serverName = $this->getData(self::FLD_SERVERNAME);

        /** @noinspection PhpUndefinedMethodInspection */
        $result = CPanelWrapper::getCpanelObj()->uapi(
            'lsws',
            'generateEcCert',
            array('domain' => $serverName)
        );

        $data = $result['cpanelresult']['result']['data'];
        $retVar = $data['retVar'];
        $output = $data['output'];
        $sslVh = (bool)$data['sslVh'];
        $ecCert = (bool)$data['ecCert'];
        $ecCertFingerprint = $data['ecCertFingerprint'];

        UserLogger::debug("EcCert command generate={$retVar} for $serverName");

        if ( !empty($output) ) {
            UserLogger::debug('output = ' . var_export($output, true));
            $this->setLastGenerationMessage($output);
        }

        if ( $retVar == 0 ) {
            UserLogger::addUiMsg(
                "{$serverName} - "
                    . _('Successfully generated a new EC certificate.'),
                UserLogger::UI_SUCC
            );
        }
        elseif ( $ecCert ) {
            UserLogger::addUiMsg(
                "${serverName} - "
                    . _('EC certificate already exists, no need to generate.'),
                UserLogger::UI_SUCC
            );
        }
        else {
            UserLogger::addUiMsg(
                "{$serverName} - "
                    . _('Failed to generate a new EC certificate.'),
                UserLogger::UI_ERR
            );
        }

        $this->data[self::FLD_HAS_SSL_VH] = $sslVh;
        $this->data[self::FLD_EC_EXISTS] = $ecCert;
        $this->data[self::FLD_EC_CERT_COVERED] =
            Ls_WebCacheMgr_Util::getCertificateAltNames($ecCertFingerprint);
    }

    /**
     *
     * @since 2.1
     */
    public function removeEcCert()
    {
        $serverName = $this->getData(self::FLD_SERVERNAME);

        /** @noinspection PhpUndefinedMethodInspection */
        $result = CPanelWrapper::getCpanelObj()->uapi(
            'lsws',
            'removeEcCert',
            array('domain' => $serverName)
        );

        $data = $result['cpanelresult']['result']['data'];
        $retVar = $data['retVar'];
        $output = $data['output'];
        $sslVh = (bool)$data['sslVh'];
        $ecCert = (bool)$data['ecCert'];
        $ecCertFingerprint = $data['ecCertFingerprint'];

        UserLogger::debug("EcCert command remove={$retVar} for $serverName");

        if ( !empty($output) ) {
            UserLogger::debug('output = ' . var_export($output, true));
        }

        if ( $retVar == 0 ) {
            UserLogger::addUiMsg(
                "{$serverName} - "
                    . _('Successfully removed EC certificate.'),
                UserLogger::UI_SUCC
            );
        }
        elseif( $retVar == 100 ) {
            UserLogger::addUiMsg(
                "{$serverName} - " . _('Domain not found.'),
                UserLogger::UI_ERR
            );
        }
        elseif( $retVar == 101 ) {
            UserLogger::addUiMsg(
                "{$serverName} - " . _('No EC certificate found.'),
                UserLogger::UI_SUCC
            );
        }
        else {
            UserLogger::addUiMsg(
                "{$serverName} - " . _('Failed to remove EC certificate.'),
                UserLogger::UI_ERR
            );
        }

        $this->data[self::FLD_HAS_SSL_VH] = $sslVh;
        $this->data[self::FLD_EC_EXISTS] = $ecCert;
        $this->data[self::FLD_EC_CERT_COVERED] =
            Ls_WebCacheMgr_Util::getCertificateAltNames($ecCertFingerprint);
    }

}
