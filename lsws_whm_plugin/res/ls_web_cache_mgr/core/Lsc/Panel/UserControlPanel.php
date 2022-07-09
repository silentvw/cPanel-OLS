<?php

/** ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author: LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright: (c) 2018-2020
 * ******************************************* */

namespace LsUserPanel\Lsc\Panel;

use \LsUserPanel\Lsc\UserLSCMException;
use \LsUserPanel\Lsc\UserWPInstall;

abstract class UserControlPanel
{

    const PHP_TIMEOUT = 30;

    /**
     * @var string
     */
    protected $phpOptions;

    /**
     * @var null|mixed[][]  'docroots' => (index => docroots),
     *                      'names' => (servername => index)
     */
    protected $docRootMap = null;

    /**
     * @var null|UserControlPanel
     */
    protected static $instance;

    protected function __construct()
    {
        /**
         * output_handler value cleared to avoid compressed output through
         * 'ob_gzhandler' etc.
         */
        $this->phpOptions = '-d disable_functions=ini_set -d opcache.enable=0 '
                . '-d max_execution_time=' . self::PHP_TIMEOUT . ' -d memory_limit=512M '
                . '-d register_argc_argv=1 -d zlib.output_compression=0 -d output_handler= '
                . '-d safe_mode=0 -d open_basedir=';
    }

    /**
     *
     * @param string  $name
     * @throws UserLSCMException
     */
    public static function init( $name )
    {
        switch ($name) {
            case 'cpanel':
                self::$instance = new UserCPanel();
                break;
            default:
                throw new UserLSCMException("Control panel '{$name}' is not supported.");
        }
    }

    /**
     *
     * @return UserCPanel
     * @throws UserLSCMException
     */
    public static function getInstance()
    {
        if ( self::$instance == null ) {
            throw new UserLSCMException('Could not get instance, ControlPanel not initialized. ');
        }

        return self::$instance;
    }

    /**
     *
     * @param string  $serverName
     * @return string|null
     */
    public function mapDocRoot( $serverName )
    {
        if ( $this->docRootMap == null ) {
            $this->prepareDocrootMap();
        }

        if ( isset($this->docRootMap['names'][$serverName]) ) {
            $index = $this->docRootMap['names'][$serverName];

            return $this->docRootMap['docroots'][$index];
        }

        // error out
        return null;
    }

    /**
     * return array of docroots, can set index from and batch
     *
     * @param int       $offset
     * @param null|int  $length
     * @return string[]
     */
    public function getDocRoots( $offset = 0, $length = null )
    {
        if ( $this->docRootMap == null ) {
            $this->prepareDocrootMap();
        }

        return array_slice($this->docRootMap['docroots'], $offset, $length);
    }

    abstract protected function prepareDocrootMap();

    abstract public function getPhpBinary( UserWPInstall $wpInstall );

}
