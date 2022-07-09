<?php

/** ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author: LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright: (c) 2018-2020
 * ******************************************* */

namespace LsUserPanel\Lsc;

use \LsUserPanel\Lsc\Context\UserContextOption;

/**
 * UserLogger is a singleton
 */
class UserLogger
{

    const L_NONE = 0;
    const L_ERROR = 1;
    const L_WARN = 2;
    const L_NOTICE = 3;
    const L_INFO = 4;
    const L_VERBOSE = 5;
    const L_DEBUG = 9;
    const UI_INFO = 0;
    const UI_SUCC = 1;
    const UI_ERR = 2;
    const UI_WARN = 3;

    /**
     * @var null|UserLogger
     */
    private static $instance;

    /**
     * @var int  Highest log message level allowed to be logged. Set to the
     *            higher value between $this->logFileLvl and $this->logEchoLvl.
     */
    private $logLvl;

    /**
     * @var string  File that log messages will be written to (if writable).
     */
    private $logFile;

    /**
     * @var int  Highest log message level allowed to be written to the log
     *           file.
     */
    private $logFileLvl;

    /**
     * @var boolean  When set to true, log messages will not be written to the
     *               log file until this UserLogger object is destroyed.
     */
    private $bufferedWrite;

    /**
     * @var UserLogEntry[]  Stores created LogEntry objects.
     */
    private $msgQueue = array();

    /**
     * @var int  Highest log message level allowed to echoed.
     */
    private $logEchoLvl;

    /**
     * @var boolean  When set to true, echoing of log messages is suppressed.
     */
    private $bufferedEcho;

    /**
     * @var string[][]  Leveraged by Control Panel GUI to store and retrieve
     *                  display messages.
     */
    private $uiMsgs = array(
        self::UI_INFO => array(),
        self::UI_SUCC => array(),
        self::UI_ERR => array(),
        self::UI_WARN => array()
    );

    /**
     *
     * @param UserContextOption  $ctxOption
     */
    private function __construct( UserContextOption $ctxOption )
    {
        $this->logFile = $ctxOption->getLogFile();
        $this->logFileLvl = $ctxOption->getLogFileLvl();
        $this->bufferedWrite = $ctxOption->isBufferedWrite();
        $this->logEchoLvl = $ctxOption->getLogEchoLvl();
        $this->bufferedEcho = $ctxOption->isBufferedEcho();

        if ( $this->logEchoLvl >= $this->logFileLvl ) {
            $logLvl = $this->logEchoLvl;
        }
        else {
            $logLvl = $this->logFileLvl;
        }

        $this->logLvl = $logLvl;
    }

    public function __destruct()
    {
        if ( $this->bufferedWrite ) {
            $this->writeToFile($this->msgQueue);
        }
    }

    /**
     *
     * @param UserContextOption  $contextOption
     * @throws UserLSCMException
     */
    public static function Initialize( UserContextOption $contextOption )
    {
        if ( self::$instance != null ) {
            throw new UserLSCMException('UserLogger cannot be initialized twice.',
                    UserLSCMException::E_PROGRAM);
        }

        self::$instance = new self($contextOption);
    }

    /**
     *
     * @param int  $type
     * @return string[]
     */
    public static function getUiMsgs( $type )
    {
        $ret = array();

        switch ($type) {
            case self::UI_INFO:
            case self::UI_SUCC:
            case self::UI_ERR:
            case self::UI_WARN:
                $ret = self::me()->uiMsgs[$type];

            //no default
        }

        return $ret;
    }

    /**
     *
     * @param string  $msg
     * @param int     $type
     */
    public static function addUiMsg( $msg, $type )
    {
        switch ($type) {
            case self::UI_INFO:
            case self::UI_ERR:
            case self::UI_SUCC:
            case self::UI_WARN:
                self::me()->uiMsgs[$type][] = $msg;
                break;

            //no default
        }
    }

    /**
     *
     * @param string  $msg
     * @param int     $lvl
     */
    public static function logMsg( $msg, $lvl )
    {
        self::me()->log($msg, $lvl);
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $msg
     */
    public static function error( $msg )
    {
        static::logMsg($msg, static::L_ERROR);
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $msg
     */
    public static function warn( $msg )
    {
        static::logMsg($msg, static::L_WARN);
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $msg
     */
    public static function notice( $msg )
    {
        static::logMsg($msg, static::L_NOTICE);
    }

    /**
     *
     * @param string  $msg
     */
    public static function info( $msg )
    {
        self::logMsg($msg, static::L_INFO);
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $msg
     */
    public static function verbose( $msg )
    {
        static::logMsg($msg, static::L_VERBOSE);
    }

    /**
     *
     * @param string  $msg
     */
    public static function debug( $msg )
    {
        self::logMsg($msg, static::L_DEBUG);
    }


    /**
     *
     * @return UserLogger
     * @throws UserLSCMException
     */
    private static function me()
    {
        if ( self::$instance == null ) {
            throw new UserLSCMException('UserLogger Uninitialized.',
                    UserLSCMException::E_PROGRAM);
        }

        return self::$instance;
    }

    /**
     *
     * @param string  $msg
     * @param int     $lvl
     */
    protected function log( $msg, $lvl )
    {
        $entry = new UserLogEntry($msg, $lvl);

        $this->msgQueue[] = $entry;

        if ( !$this->bufferedWrite ) {
            $this->writeToFile(array( $entry ));
        }

        if ( !$this->bufferedEcho ) {
            $this->echoEntries(array( $entry ));
        }
    }

    /**
     *
     * @param UserLogEntry[]  $entries
     */
    protected function writeToFile( $entries )
    {
        $content = '';

        foreach ( $entries as $e ) {
            $content .= $e->getOutput($this->logFileLvl);
        }

        if ( $content != '' ) {

            if ( $this->logFile ) {
                file_put_contents($this->logFile, $content,
                        FILE_APPEND | LOCK_EX);
            }
            else {
                error_log($content);
            }
        }
    }

    /**
     *
     * @param UserLogEntry[]  $entries
     */
    protected function echoEntries( $entries )
    {
        foreach ( $entries as $entry ) {

            if ( ($msg = $entry->getOutput($this->logEchoLvl)) !== '' ) {
                echo $msg;
            }
        }
    }

    /**
     *
     * @param int  $lvl
     * @return string
     */
    public static function getLvlDescr( $lvl )
    {
        switch ($lvl) {
            case self::L_ERROR:
                return 'ERROR';
            case self::L_WARN:
                return 'WARN';
            case self::L_NOTICE:
                return 'NOTICE';
            case self::L_INFO:
                return 'INFO';
            case self::L_VERBOSE:
                return 'DETAIL';
            case self::L_DEBUG:
                return 'DEBUG';
            default:
                /**
                 * Do silently.
                 */
                return '';
        }
    }

}
