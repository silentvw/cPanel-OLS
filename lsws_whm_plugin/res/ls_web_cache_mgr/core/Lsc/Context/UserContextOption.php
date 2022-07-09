<?php

/** ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2018-2020
 * ******************************************* */

namespace LsUserPanel\Lsc\Context;

use \LsUserPanel\Ls_WebCacheMgr_Util;
use \LsUserPanel\Lsc\UserLSCMException;

class UserContextOption
{

    const FROM_CONTROL_PANEL = 'panel';

    /**
     * @var string
     */
    protected $invokerName;

    /**
     * @var string  If set, must be writable.
     */
    protected $logFile;

    /**
     * @var int  Log to file level.
     */
    protected $logFileLvl;

    /**
     * @var int  Echo to user interface level.
     */
    protected $logEchoLvl;

    /**
     * @var boolean
     */
    protected $bufferedWrite;

    /**
     * @var boolean
     */
    protected $bufferedEcho;

    /**
     * @var int
     */
    protected $scanDepth = 2;

    /**
     *
     * @param string   $invokerName
     * @param int      $logFileLvl
     * @param int      $logEchoLvl
     * @param boolean  $bufferedWrite
     * @param boolean  $bufferedEcho
     * @throws UserLSCMException  Thrown indirectly.
     */
    protected function __construct( $invokerName, $logFileLvl, $logEchoLvl,
            $bufferedWrite, $bufferedEcho )
    {
        $this->invokerName = $invokerName;
        $this->logFile =
            Ls_WebCacheMgr_Util::getUserLSCMDataDir() . '/ls_webcachemgr.log';
        $this->logFileLvl = $logFileLvl;
        $this->logEchoLvl = $logEchoLvl;
        $this->bufferedWrite = $bufferedWrite;
        $this->bufferedEcho = $bufferedEcho;
    }

    /**
     *
     * @return string
     */
    public function getLogFile()
    {
        return $this->logFile;
    }

    /**
     *
     * @return int
     */
    public function getLogFileLvl()
    {
        return $this->logFileLvl;
    }

    /**
     *
     * @return int
     */
    public function getLogEchoLvl()
    {
        return $this->logEchoLvl;
    }

    /**
     *
     * @return boolean
     */
    public function isBufferedWrite()
    {
        return $this->bufferedWrite;
    }

    /**
     *
     * @return boolean
     */
    public function isBufferedEcho()
    {
        return $this->bufferedEcho;
    }

    /**
     *
     * @return int
     */
    public function getScanDepth()
    {
        return $this->scanDepth;
    }

    /**
     *
     * @return string
     */
    public function getInvokerName()
    {
        return $this->invokerName;
    }

}
