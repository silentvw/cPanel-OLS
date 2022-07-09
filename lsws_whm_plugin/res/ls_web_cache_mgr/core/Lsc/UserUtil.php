<?php

/** *********************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2018-2020
 * *******************************************
 */

namespace LsUserPanel\Lsc;

use \LsUserPanel\Ls_WebCacheMgr_Util;
use \LsUserPanel\PluginSettings;

class UserUtil
{

    /**
     *
     * @return string
     * @throws UserLSCMException  Thrown indirectly.
     */
    public static function getUserCacheDir()
    {
        $vhCacheRoot =
                PluginSettings::getSetting(PluginSettings::FLD_VHOST_CACHE_ROOT);

        if ( !empty($vhCacheRoot) && $vhCacheRoot[0] != '/' ) {
            $homeDir = Ls_WebCacheMgr_Util::getHomeDir();
            $vhCacheRoot = "{$homeDir}/{$vhCacheRoot}";
        }

        return ($vhCacheRoot == '') ? '' : $vhCacheRoot;
    }

    /**
     * Flushes LiteSpeed Cache for the current user by removing all VH cache
     * files.
     *
     * @param string  $cacheDir  Path to user's VH cache directory.
     * @retun null
     */
    public static function flushVHCacheRoot( $cacheDir )
    {
        $flushed = false;

        $validCacheSubDirs = array(
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
            'a', 'b', 'c', 'd', 'e', 'f'
        );

        if ( !file_exists($cacheDir) ) {
            $msg = sprintf(_('Could Not Find Cache Directory %s'),
                    htmlspecialchars($cacheDir));

            UserLogger::addUiMsg($msg, UserLogger::UI_ERR);
            return;
        }

        foreach ( $validCacheSubDirs as $subDir ) {

            if ( Ls_WebCacheMgr_Util::rrmdir("{$cacheDir}/{$subDir}") ) {
                $flushed = true;
            }
        }

        if ( $flushed ) {
            $msg = _('Cache Files Successfully Flushed');
        }
        else {
            $msg = _('No Cache Files To Flush');
        }

        UserLogger::addUiMsg($msg, UserLogger::UI_SUCC);
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $file
     * @param string  $owner
     * @param string  $group
     */
    private static function changeUserGroup( $file, $owner, $group )
    {
        chown($file, $owner);
        chgrp($file, $group);
    }

    /**
     * Set file permissions of $file2 to match those of $file1.
     *
     * @since 2.1
     *
     * @param string  $file1
     * @param string  $file2
     */
    private static function matchPermissions( $file1, $file2 )
    {
        /**
         * convert dec to oct
         */
        $perms = (fileperms($file1) & 0777);

        chmod($file2, $perms);
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $file
     * @param string  $backup
     * @return boolean
     */
    private static function matchFileSettings( $file, $backup )
    {
        clearstatcache();
        $ownerID = fileowner($file);
        $groupID = filegroup($file);

        if ( $ownerID === false || $groupID === false ) {
            UserLogger::debug("Could not get owner/group of file {$file}");

            unlink($backup);

            UserLogger::debug("Removed file {$backup}");
            return false;
        }

        self::changeUserGroup($backup, $ownerID, $groupID);
        self::matchPermissions($file, $backup);

        return true;
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $filepath
     * @param string  $bak
     * @return string
     */
    private static function getBackupSuffix( $filepath, $bak = '_lscachebak_orig' )
    {
        $i = 1;

        if ( file_exists($filepath . $bak) ) {
            $bak = sprintf("_lscachebak_%02d", $i);

            while ( file_exists($filepath . $bak) ) {
                $i++;
                $bak = sprintf("_lscachebak_%02d", $i);
            }
        }

        return $bak;
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $filepath
     * @return boolean
     */
    public static function createBackup( $filepath )
    {
        $bak = self::getBackupSuffix($filepath);
        $backup = $filepath . $bak;

        if ( !copy($filepath, $backup) ) {
            UserLogger::debug("Could not backup file {$filepath} to location {$backup}");

            return false;
        }

        UserLogger::logMsg("Created file{$backup}", UserLogger::L_VERBOSE);

        if ( !self::matchFileSettings($filepath, $backup) ) {
            UserLogger::debug("Could not backup file {$filepath} to location {$backup}");

            return false;
        }

        UserLogger::debug('Matched owner/group setting for both files');
        UserLogger::info("Successfully backed up file {$filepath} to location {$backup}");

        return true;
    }

    /**
     *
     * @deprecated 2.1  Moved to
     *                  Ls_WebCacheMgr_Controller::getUserLSCMDataDir().
     *
     * @return string
     * @throws UserLSCMException  Thrown indirectly.
     */
    public static function getUserLSCMDataDir()
    {
        return Ls_WebCacheMgr_Util::getUserLSCMDataDir();
    }

    /**
     *
     * @deprecated 2.1  Moved to
     *                  Ls_WebCacheMgr_Controller::createUserLSCMDataDir().
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    public static function createUserLSCMDataDir()
    {
        Ls_WebCacheMgr_Util::createUserLSCMDataDir();
    }

    /**
     *
     * @deprecated 2.1 Moved to
     *                 Ls_WebCacheMgr_Controller::getLscmUserDirReadmeContent().
     *
     * @return string
     */
    public static function getLscmUserDirReadmeContent()
    {
        return Ls_WebCacheMgr_Util::getLscmUserDirReadmeContent();
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $url
     * @param array   $postData
     * @return string
     */
    public static function PostToUrl( $url, $postData = array() )
    {

        $post_string = http_build_query($postData);

        if ( ini_get('allow_url_fopen') ) {

            $options = array(
                'http'  => array(
                    'method' => 'POST',
                    'header'=> "Content-type: application/x-www-form-urlencoded\n"
                            . "Content-Length: " . strlen($post_string) . "\n",
                    'content' => $post_string
                )
            );

            $context = stream_context_create($options);

            /**
             * Silence warning when OpenSSL missing.
             */
            $ret = @file_get_contents($url, false, $context);

            if ( $ret !== false ) {
                return $ret;
            }

        }

        if ( function_exists('curl_version') ) {
            $ch = curl_init();

            curl_setopt_array(
                $ch,
                array(
                    CURLOPT_URL => $url,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS  => $post_string,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
                )
            );

            $ret = curl_exec($ch);
            curl_close($ch);

            return $ret;
        }

        exec("curl --data \"{$post_string}\" {$url}", $output, $ret);

        if ( $ret === 0 ) {
            return implode("\n", $output);
        }

        return '';
    }

    /**
     * Wrapper for idn_to_utf8() function call to avoid "undefined" exceptions
     * when PHP intl module is not installed and enabled.
     *
     * @since 2.1.3.4
     *
     * @param string $domain
     * @param int    $flags
     * @param int|null    $variant
     * @param array|null $idna_info
     *
     * @return false|string
     */
    public static function tryIdnToUtf8(
        $domain,
        $flags = 0,
        $variant = null,
        array &$idna_info = null )
    {
        if ( function_exists('idn_to_utf8') ) {

            if ( $variant == null ) {
                $variant = INTL_IDNA_VARIANT_UTS46;
            }

            return idn_to_utf8($domain, $flags, $variant, $idna_info);
        }

        return $domain;
    }

    /**
     * Wrapper for idn_to_ascii() function call to avoid "undefined" exceptions
     * when PHP intl module is not installed and enabled.
     *
     * @since 2.1.3.4
     *
     * @param string     $domain
     * @param int|null   $flags
     * @param int|null   $variant
     * @param array|null $idna_info
     *
     * @return false|string
     */
    public static function tryIdnToAscii(
        $domain,
        $flags = null,
        $variant = null,
        array &$idna_info = null )
    {
        if ( function_exists('idn_to_ascii') ) {

            if ($flags = null ) {
                $flags = IDNA_DEFAULT;
            }

            if ( $variant == null ) {
                $variant = INTL_IDNA_VARIANT_UTS46;
            }

            return idn_to_ascii($domain, $flags, $variant, $idna_info);
        }

        return $domain;
    }

}
