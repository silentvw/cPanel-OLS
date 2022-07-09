<?php

/** *********************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author: LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright: (c) 2018-2020
 * *******************************************
 */

namespace LsUserPanel\Lsc;

use \LsUserPanel\CPanelWrapper;
use \LsUserPanel\Lsc\Context\UserContext;
use \LsUserPanel\Lsc\Panel\UserControlPanel;

class UserWPInstall
{

    const ST_PLUGIN_ACTIVE = 1;
    const ST_PLUGIN_INACTIVE = 2;
    const ST_LSC_ADVCACHE_DEFINED = 4;
    const ST_FLAGGED = 8;
    const ST_ERR_SITEURL = 16;
    const ST_ERR_DOCROOT = 32;
    const ST_ERR_EXECMD = 64;
    const ST_ERR_TIMEOUT = 128;
    const ST_ERR_EXECMD_DB = 256;
    const ST_ERR_WPCONFIG = 1024;
    const ST_ERR_REMOVE = 2048;
    const FLD_STATUS = 'status';
    const FLD_DOCROOT = 'docroot';
    const FLD_SERVERNAME = 'server_name';
    const FLD_SITEURL = 'site_url';
    const FLAG_FILE = '.litespeed_flag';
    const FLAG_NEW_LSCWP = '.lscm_new_lscwp';

    /**
     * @var string
     */
    private $path;

    /**
     * @var mixed[]
     */
    private $data;

    /**
     * @var null|string
     */
    private $phpBinary = null;

    /**
     * @var bool
     */
    private $changed = false;

    /**
     * @var bool
     */
    private $refreshed = false;

    /**
     * @var null|string
     */
    private $wpConfigFile = '';

    /**
     * @var int
     */
    private $cmdStatus = 0;

    /**
     * @var string
     */
    private $cmdMsg = '';

    /**
     *
     * @param string  $path
     */
    public function __construct( $path )
    {
        $this->init($path);
    }

    /**
     *
     * @since
     *
     * @param string  $path
     */
    private function init( $path )
    {
        if ( ($realPath = realpath($path)) === false ) {
            $this->path = $path;
        }
        else {
            $this->path = $realPath;
        }

        $this->data = array(
            self::FLD_STATUS => 0,
            self::FLD_DOCROOT => null,
            self::FLD_SERVERNAME => null,
            self::FLD_SITEURL => null
        );
    }

    /**
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf(
            "%s (status=%d docroot=%s siteurl=%s)",
            $this->path,
            $this->data[self::FLD_STATUS],
            $this->data[self::FLD_DOCROOT],
            UserUtil::tryIdnToUtf8($this->data[self::FLD_SITEURL])
        );
    }

    /**
     *
     * @param int  $status
     * @return boolean
     */
    public function setStatus( $status )
    {
        return $this->setData(self::FLD_STATUS, $status);
    }

    /**
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->getData(self::FLD_STATUS);
    }

    /**
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
     * @param string  $field
     * @param mixed   $value
     * @return boolean
     */
    private function setData( $field, $value )
    {
        $updated = false;

        if ( $this->data[$field] !== $value ) {
            $this->changed = $updated = true;
            $this->data[$field] = $value;
        }

        return $updated;
    }

    /**
     * Calling from unserialized data.
     *
     * @param mixed[]  $data
     */
    public function initData( $data )
    {
        $this->data = $data;
    }

    /**
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     *
     * @return boolean
     */
    public function shouldRemove()
    {
        return ($this->getStatus() & self::ST_ERR_REMOVE) ? true : false;
    }

    /**
     *
     * @since 2.1
     *
     * @return bool
     */
    public function isLscwpEnabled()
    {
        return ($this->getStatus() & self::ST_PLUGIN_ACTIVE) ? true : false;
    }

    public function hasNewLscwpFlagFile()
    {
        return file_exists("{$this->path}/" . self::FLAG_NEW_LSCWP);
    }

    /**
     *
     * @return boolean
     */
    public function hasValidPath()
    {
        if ( !is_dir($this->path) || !is_dir("{$this->path}/wp-admin") ) {
            $this->setStatusBit(self::ST_ERR_REMOVE);

            $msg = "{$this->getPath()} - Installation could not be found and has been removed "
                . 'from Cache Manager list.';
            $uiMsg = "{$this->getPath()} - "
                . _('Installation could not be found and has been removed from Cache Manager list.');

            UserLogger::addUiMsg($uiMsg, UserLogger::UI_ERR);
            UserLogger::logMsg($msg, UserLogger::L_NOTICE);
        }
        elseif ( $this->getWpConfigFile() == null ) {
            $this->setStatusBit(self::ST_ERR_WPCONFIG);
        }
        else {
            return true;
        }

        return false;
    }

    /**
     * Set the provided status bit.
     *
     * @param int  $bit
     */
    public function setStatusBit( $bit )
    {
        $status = $this->getStatus();
        $status |= $bit;
        $this->setStatus($status);
    }

    /**
     * Unset the provided status bit.
     *
     * @param int  $bit
     */
    public function unsetStatusBit( $bit )
    {
        $status = $this->getStatus();
        $status &= ~$bit;
        $this->setStatus($status);
    }

    /**
     *
     * @deprecated 2.1  Deprecated to avoid confusion with $this->cmdStatus
     *                    and $this->cmdMsg related functions. Use
     *                    $this->setStatus() instead.
     *
     * @param int  $newStatus
     */
    public function updateCommandStatus( $newStatus )
    {
        $this->setData(self::FLD_STATUS, $newStatus);
    }

    /**
     *
     * @return null|string
     */
    public function getWpConfigFile()
    {
        if ( $this->wpConfigFile === '' ) {
            $file = "{$this->path}/wp-config.php";

            if ( !file_exists($file) ) {
                /**
                 *  check parent dir
                 */
                $parentDir = dirname($this->path);
                $file = "{$parentDir}/wp-config.php";

                if ( !file_exists($file)
                        || file_exists("{$parentDir}/wp-settings.php") ) {

                    /**
                     * If wp-config moved up, in same dir should NOT have
                     * wp-settings
                     */
                    $file = null;
                }
            }

            $this->wpConfigFile = $file;
        }

        return $this->wpConfigFile;
    }

    /**
     *
     * @deprecated 2.1  Use populateDataFromUrl() instead.
     *
     * @param string  $siteUrl
     * @return bool
     * @throws UserLSCMException  Thrown indirectly.
     */
    public function setSiteUrl( $siteUrl )
    {
        return $this->populateDataFromUrl($siteUrl);
    }

    /**
     * Takes a WordPress site URL and uses it to populate serverName, siteUrl,
     * and docRoot data. If a matching docRoot cannot be found using the
     * serverName, the install will be flagged and an ST_ERR_DOCROOT status set.
     *
     * @param string  $siteUrl
     * @return boolean
     * @throws UserLSCMException  Thrown indirectly.
     */
    public function populateDataFromUrl( $siteUrl )
    {
        if ( preg_match('#^https?://#', $siteUrl) ) {
            $parseSafeSiteUrl = $siteUrl;
        }
        else {
            $parseSafeSiteUrl = "http://$siteUrl";
        }

        $info = parse_url($parseSafeSiteUrl);

        $serverName = UserUtil::tryIdnToAscii($info['host']);

        $this->setData(self::FLD_SERVERNAME, $serverName);

        $siteUrlTrim = $serverName;

        if ( isset($info['path']) ) {
            $siteUrlTrim .= $info['path'];
        }

        $this->setData(self::FLD_SITEURL, $siteUrlTrim);

        $docRoot = UserControlPanel::getInstance()->mapDocRoot($serverName);
        $this->setData(self::FLD_DOCROOT, $docRoot);

        if ( $docRoot === null ) {
            $this->setStatus(self::ST_ERR_DOCROOT);
            $this->addUserFlagFile();

            $msg = "$this->path - Could not find matching document root for "
                . "WP siteurl/servername $serverName.";

            $uiMsg = "$this->path - "
                . sprintf(
                    _(
                        'Could not find matching document root for WP '
                            . 'siteurl/servername %s.'
                    ),
                    $serverName
                );

            $this->setCmdStatusAndMsg(UserUserCommand::EXIT_ERROR, $uiMsg);
            UserLogger::error($msg);
            return false;
        }

        return true;
    }

    /**
     * Adds the flag file to an installation.
     *
     * @return boolean  True when install has a flag file created/already.
     */
    public function addUserFlagFile()
    {
        $file = "{$this->path}/" . self::FLAG_FILE;

        if ( !file_exists($file) ) {
            $content = UserContext::getFlagFileContent();

            if ( !file_put_contents($file, $content) ) {
                return false;
            }
        }

        $this->setStatusBit(self::ST_FLAGGED);
        return true;
    }

    /**
     *
     * @return boolean
     */
    public function removeFlagFile()
    {
        $file = "{$this->path}/" . self::FLAG_FILE;

        if ( file_exists($file) ) {

            if ( !unlink($file) ) {
                return false;
            }
        }

        $this->unsetStatusBit(self::ST_FLAGGED);
        return true;
    }

    /**
     * Remove "In Progress" flag file to indicate that a WPInstall action has
     * been completed.
     *
     * @return boolean
     */
    public function removeNewLscwpFlagFile()
    {
        $cpanel = CPanelWrapper::getCpanelObj();

        /** @noinspection PhpUndefinedMethodInspection */
        $result = $cpanel->uapi('lsws', 'removeNewLscwpFlagFile',
                array( 'path' => $this->path ));

        return (bool) $result['cpanelresult']['result']['data']['result'];
    }

    /**
     *
     * @param boolean  $forced
     * @return int
     * @throws UserLSCMException  Thrown indirectly.
     */
    public function refreshStatus( $forced = false )
    {
        if ( !$this->refreshed || $forced ) {
            $this->refreshed = true;
            UserUserCommand::issue(UserUserCommand::CMD_STATUS, $this);
        }

        return $this->getData(self::FLD_STATUS);
    }

    /**
     *
     * @param null|int  $status
     * @return boolean
     */
    public function hasFatalError( $status = null )
    {
        if ( $status === null ) {
            $status = $this->getData(self::FLD_STATUS);
        }

        $errMask = (self::ST_ERR_EXECMD
                | self::ST_ERR_EXECMD_DB
                | self::ST_ERR_TIMEOUT
                | self::ST_ERR_SITEURL
                | self::ST_ERR_DOCROOT
                | self::ST_ERR_WPCONFIG);

        return (($status & $errMask) > 0);
    }

    /**
     *
     * @return string
     * @throws UserLSCMException  Thrown indirectly.
     */
    public function getPhpBinary()
    {
        if ( $this->phpBinary == null ) {
            $this->phpBinary =
                    UserControlPanel::getInstance()->getPhpBinary($this);
        }

        return $this->phpBinary;
    }

    /**
     *
     * @return int
     */
    public function getCmdStatus()
    {
        return $this->cmdStatus;
    }

    /**
     *
     * @return string
     */
    public function getCmdMsg()
    {
        return $this->cmdMsg;
    }

    /**
     *
     * @param int     $status
     * @param string  $msg
     */
    public function setCmdStatusAndMsg( $status, $msg )
    {
        $this->cmdStatus = $status;
        $this->cmdMsg = $msg;
    }

}
