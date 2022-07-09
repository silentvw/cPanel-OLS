<?php

/** ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2018-2020
 * ******************************************* */

namespace LsUserPanel;

use \LsUserPanel\Lsc\UserLSCMException;
use \LsUserPanel\Lsc\UserLogger;

class Ls_WebCacheMgr_Util
{

    /**
     * @var null|string
     */
    private static $homeDir;

    private function __construct() {}

    /**
     *
     * @return string
     * @throws UserLSCMException
     */
    public static function getHomeDir()
    {
        if ( !self::$homeDir ) {

            if ( isset($_SERVER['HOME']) ) {
                self::$homeDir = $_SERVER['HOME'];
            }
            elseif ( isset($_SERVER['DOCUMENT_ROOT']) ) {
                self::$homeDir = $_SERVER['DOCUMENT_ROOT'];
            }
            else {
                throw new UserLSCMException('Could not get home directory');
            }
        }

        return self::$homeDir;
    }

    /**
     * Returns the length of chars that make up the users the home dir.
     *
     * @return int
     * @throws UserLSCMException  Thrown indirectly.
     */
    public static function getHomeDirLen()
    {
        return strlen(self::getHomeDir());
    }

    /**
     * Gets currently executing user through cPanel set $_ENV variable.
     *
     * @return string
     */
    public static function getCurrentCpanelUser()
    {
        return posix_getpwuid(posix_geteuid())['name'];
    }

    /**
     *
     * @since 2.1
     *
     * @return string
     * @throws UserLSCMException  Thrown indirectly.
     */
    public static function getUserLSCMDataDir()
    {
        return self::getHomeDir() . '/lscmData';
    }

    /**
     *
     * @since 2.1
     *
     * @throws UserLSCMException  Thrown directly and indirectly.
     */
    public static function createUserLSCMDataDir()
    {
        $dataDir = self::getUserLSCMDataDir();

        if ( !mkdir($dataDir, 0700) ) {
            throw new UserLSCMException(
                'Could not create LSCM user data directory.'
            );
        }

        $readmeContent = self::getLscmUserDirReadmeContent();
        file_put_contents("{$dataDir}/readme", $readmeContent);
    }

    /**
     *
     * @since 2.1
     *
     * @return string
     */
    public static function getLscmUserDirReadmeContent()
    {
        return <<<CONTENT
This directory is used to store scan data and other files created by the
LiteSpeed Web Cache Manger plugin for cPanel.

If you are not longer using the LiteSpeed Web Cache Manger plugin for cPanel,
this directory and it's files can be safely removed.
CONTENT;
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $domain
     * @param bool    $allowEcCertGen  Allow EC cert generation if no SSL cert
     *                                 is found.
     * @return string[]
     */
    public static function getDomainSslData( $domain, $allowEcCertGen = false )
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $result = CPanelWrapper::getCpanelObj()->uapi(
            'lsws',
            'getDomainSslData',
            array( 'domain' => $domain, 'allowEcCertGen' => $allowEcCertGen)
        );

        return array (
            'retCert' => $result['cpanelresult']['result']['data']['retCert'],
            'retKey' => $result['cpanelresult']['result']['data']['retKey']
        );
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $domain
     * @return int
     */
    public static function generateSignedEcCert( $domain )
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $result = CPanelWrapper::getCpanelObj()->uapi(
            'lsws',
            'generateEcCert',
            array( 'domain' => $domain )
        );

        $retVar = $result['cpanelresult']['result']['data']['retVar'];
        $output = $result['cpanelresult']['result']['data']['output'];

        if ( $output != '' ) {

            if ($retVar == 0) {
                $type = UserLogger::UI_SUCC;
            }
            else {
                $type = UserLogger::UI_ERR;
            }

            UserLogger::addUiMsg($output, $type);
        }

        return $retVar;
    }

    /**
     *
     * @param string  $tag
     * @return null|string
     */
    public static function get_request_var( $tag )
    {
        if ( !isset($_REQUEST[$tag]) ) {
            return NULL;
        }

        return trim($_REQUEST[$tag]);
    }

    /**
     *
     * @param string  $tag
     * @return null|string[]
     */
    public static function get_request_list( $tag )
    {
        if ( !isset($_REQUEST[$tag]) ) {
            return NULL;
        }

        $result = $_REQUEST[$tag];

        return (is_array($result)) ? $result : NULL;
    }

    /**
     * Touches a flag file to inform LiteSpeed Web Server to restart user owned
     * running detached PHP processes the next time the server uses that PHP
     * handler.
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    public static function restartDetachedPHP()
    {
        $homeDir = self::getHomeDir();

        $restartPHPFlagFile = $homeDir . '/.lsphp_restart.txt';

        if ( touch($restartPHPFlagFile) ) {
            UserLogger::addUiMsg(
                    _('LiteSpeed Web Server notified to restart detached PHP '
                        . 'processes.'),
                    UserLogger::UI_SUCC);
        }
        else {
            UserLogger::addUiMsg(
                    _('Could not notify LiteSpeed Web Server to restart '
                        . 'detached PHP processes.'),
                    UserLogger::UI_ERR);
        }
    }

    /**
     * Recursively deletes a directory and its contents.
     *
     * @param string   $dir         Directory path.
     * @param boolean  $keepParent
     * @return boolean
     */
    public static function rrmdir( $dir, $keepParent = false )
    {
        if ( $dir != '' && is_dir($dir) ) {

            foreach ( glob($dir . '/*') as $file ) {

                if ( is_dir($file) ) {
                    self::rrmdir($file);
                }
                else {
                    unlink($file);
                }
            }

            if ( !$keepParent )
                rmdir($dir);

            return true;
        }

        return false;
    }

    /**
     *
     * @param mixed[]  $ajaxInfo
     */
    public static function ajaxReturn( $ajaxInfo )
    {
        echo json_encode($ajaxInfo);
        exit;
    }

    public static function setTextDomain()
    {
        $activeLocaleDir = '';

        /** @noinspection PhpUndefinedMethodInspection */
        $attributes = CPanelWrapper::getCpanelObj()->uapi(
            'Locale',
            'get_attributes'
        );
        $locale = $attributes['cpanelresult']['result']['data']['locale'];

        $langDir = realpath(__DIR__ . '/../lang');
        $localeDir = "{$langDir}/{$locale}";
        $custLocaleDir = "{$langDir}/cust/{$locale}";

        if ( file_exists($custLocaleDir) ) {
            $activeLocaleDir = $custLocaleDir;
        }
        elseif ( file_exists($localeDir) ) {
            $activeLocaleDir = $localeDir;
        }

        if ( $activeLocaleDir != '' ) {
            setlocale(LC_MESSAGES, 'en_US.utf8');
            bindtextdomain('messages', $activeLocaleDir);
            bind_textdomain_codeset('messages', 'UTF8');
            textdomain('messages');
        }
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $certFingerprint
     * @return string[]
     */
    public static function getCertificateAltNames( $certFingerprint )
    {
        $certAltNames = array();

        if ( $certInfo = openssl_x509_parse($certFingerprint) ) {
            $rawCertAltNames = array_map(
                'trim',
                explode(',', $certInfo['extensions']['subjectAltName'])
            );

            $certAltNames = preg_replace(
                '/DNS:(.+)/',
                '$1',
                $rawCertAltNames
            );
        }

        return $certAltNames;
    }

}
