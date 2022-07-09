<?php

/** *********************************************
 * LiteSpeed Web Server Plugin for Whm
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2019-2020
 * @since 3.3.4
 * *******************************************
 */

namespace LsPanel;

/**
 * WhmPluginLogger is a singleton.
 *
 * Functions starting with 'p_' are implemented for compatibility with shared
 * code Logger class and are not intended for use directly.
 *
 * @since 3.3.4
 */
class WhmPluginLogger
{

    /**
     * @since 3.3.4
     * @var int
     */
    const L_NONE = 0;

    /**
     * @since 3.3.4
     * @var int
     */
    const L_ERROR = 1;

    /**
     * @since 3.3.4
     * @var int
     */
    const L_WARN = 2;

    /**
     * @since 3.3.4
     * @var int
     */
    const L_NOTICE = 3;

    /**
     * @since 3.3.4
     * @var int
     */
    const L_INFO = 4;

    /**
     * @since 3.3.4
     * @var int
     */
    const L_VERBOSE = 5;

    /**
     * @since 3.3.4
     * @var int
     */
    const L_DEBUG = 9;

    /**
     * @since 3.3.4
     * @var int
     */
    const UI_INFO = 0;

    /**
     * @since 3.3.4
     * @var int
     */
    const UI_SUCC = 1;

    /**
     * @since 3.3.4
     * @var int
     */
    const UI_ERR = 2;

    /**
     * @since 3.3.4
     * @var int
     */
    const UI_WARN = 3;

    /**
     * @since 3.3.4
     * @var null|WhmPluginLogger
     */
    protected static $instance;

    /**
     * @since 3.3.4
     * @var int  Highest log message level allowed to be logged. Set to the
     *           higher value between $this->logFileLvl and $this->logEchoLvl.
     */
    protected $logLvl;

    /**
     * @since 3.3.4
     * @var string  File that log messages will be written to (if writable).
     */
    protected $logFile;

    /**
     * @since 3.3.4
     * @var int  Highest log message level allowed to be written to the log
     *           file.
     */
    protected $logFileLvl;

    /**
     * @since 3.3.4
     * @var string  Additional tag to be added at the start of any log
     *              messages.
     */
    protected $addTagInfo = '';

    /**
     * @since 3.3.4
     * @var boolean  When set to true, log messages will not be written to the
     *               log file until this WhmPluginLogger object is destroyed.
     */
    protected $bufferedWrite;

    /**
     * @since 3.3.4
     * @var WhmPluginLogEntry[]  Stores created WhmLogEntry objects.
     */
    protected $msgQueue = array();

    /**
     * @since 3.3.4
     * @var int  Highest log message level allowed to echoed.
     */
    protected $logEchoLvl;

    /**
     * @since 3.3.4
     * @var boolean  When set to true, echoing of log messages is suppressed.
     */
    protected $bufferedEcho;

    /**
     * @since 3.3.4
     * @var string[][]  Leveraged by control panel GUI to store and retrieve
     *                  display messages.
     */
    protected $uiMsgs = array(
        self::UI_INFO => array(),
        self::UI_SUCC => array(),
        self::UI_ERR => array(),
        self::UI_WARN => array()
    );

    /**
     *
     * @since 3.3.4
     */
    protected function __construct()
    {
        $this->init();
    }

    /**
     *
     * @since 3.3.4
     */
    protected function init()
    {
        date_default_timezone_set('UTC');

        /**
         * Temp log file location. This will be updated once LSWS_HOME is
         * defined.
         */
        $this->logFile = '/tmp/ls-whm.log';

        $this->logFileLvl = static::L_INFO;
        $this->logEchoLvl = static::L_NONE;
        $this->bufferedEcho = true;
        $this->bufferedWrite = true;

        if ( $this->logEchoLvl >= $this->logFileLvl ) {
            $logLvl = $this->logEchoLvl;
        }
        else {
            $logLvl = $this->logFileLvl;
        }

        $this->logLvl = $logLvl;
    }

    /**
     *
     * @since 3.3.4
     */
    public function __destruct()
    {
        if ( $this->bufferedWrite ) {
            $this->writeToFile($this->msgQueue);
        }
    }

    /**
     *
     * @since 3.3.4
     *
     * @throws WhmPluginException
     */
    public static function Initialize()
    {
        if ( static::$instance != null ) {
            throw new WhmPluginException('Plugin logger class cannot be initialized twice.',
                    WhmPluginException::E_PROGRAM);
        }

        static::$instance = new static();
    }

    /**
     *
     * @since 3.3.4
     *
     * @return null|WhmPluginLogger
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * Create log file if it does not already exist. Useful for creating temp
     * log file with correct permissions before writing to it in escalated
     * functions.
     *
     * @since 3.3.4
     */
    public static function preCreateLogFile()
    {
        if ( !file_exists(static::me()->logFile) ) {
            touch(static::me()->logFile);
        }
    }

    /**
     *
     * @since 3.3.4
     *
     * @param string  $logFile
     */
    public static function changeLogFileUsed( $logFile )
    {
        static::me()->logFile = $logFile;
    }

    /**
     *
     * @since 3.3.4
     *
     * @param string  $addInfo
     */
    public static function setAdditionalTagInfo( $addInfo )
    {
        static::me()->addTagInfo = $addInfo;
    }

    /**
     *
     * @since 3.3.4
     *
     * @param string  $msg
     * @param int     $type
     */
    public function p_addUiMsg( $msg, $type )
    {
        switch ($type) {
            case static::UI_INFO:
            case static::UI_SUCC:
            case static::UI_ERR:
            case static::UI_WARN:
                $this->uiMsgs[$type][] = $msg;
                break;

            //no default
        }
    }

    /**
     *
     * @since 3.3.4
     *
     * @param WhmPluginLogEntry[]  $entries
     */
    public function p_echoEntries( $entries )
    {
        $this->echoEntries($entries);
    }

    /**
     *
     * @since 3.3.4
     *
     * @return string
     */
    public function p_getAddTagInfo()
    {
        return $this->addTagInfo;
    }

    /**
     *
     * @since 3.3.4
     *
     * @return boolean
     */
    public function p_getBufferedEcho()
    {
        return $this->bufferedEcho;
    }

    /**
     *
     * @since 3.3.4
     *
     * @return boolean
     */
    public function p_getBufferedWrite()
    {
        return $this->bufferedWrite;
    }

    /**
     *
     * @since 3.3.4
     *
     * @return string
     */
    public function p_getLogFile()
    {
        return $this->logFile;
    }

    /**
     *
     * @since 3.3.4
     *
     * @return WhmPluginLogEntry[]
     */
    public function p_getMsgQueue()
    {
        return $this->msgQueue;
    }

    /**
     *
     * @since 3.3.4
     *
     * @param int  $type
     * @return string[]
     */
    public function p_getUiMsgs( $type )
    {
        $ret = array();

        switch ($type) {
            case static::UI_INFO:
            case static::UI_SUCC:
            case static::UI_ERR:
            case static::UI_WARN:
                $ret = $this->uiMsgs[$type];
                break;

            //no default
        }

        return $ret;
    }

    /**
     *
     * @since 3.3.4
     *
     * @param string  $msg
     * @param int     $lvl
     */
    public function p_log( $msg, $lvl )
    {
        $this->log($msg, $lvl);
    }

    /**
     *
     * @since 3.3.4
     *
     * @param string  $addInfo
     */
    public function p_setAddTagInfo( $addInfo )
    {
        $this->addTagInfo = $addInfo;
    }

    /**
     *
     * @since 3.3.4
     *
     * @param string  $logFile
     */
    public function p_setLogFile( $logFile )
    {
        $this->logFile = $logFile;
    }

    /**
     *
     * @since 3.3.4
     *
     * @param int  $logFileLvl
     */
    public function p_setLogFileLvl( $logFileLvl )
    {
        $this->logFileLvl = $logFileLvl;
    }

    /**
     *
     * @since 3.3.4
     *
     * @param WhmPluginLogEntry[]  $msgQueue
     */
    public function p_setMsgQueue( $msgQueue )
    {
        $this->msgQueue = $msgQueue;
    }

    /**
     *
     * @since 3.3.4
     *
     *
     * @param WhmPluginLogEntry[]  $entries  Array of objects that implement
     *                                       all LogEntry class public
     *                                       functions.
     */
    public function p_writeToFile( $entries )
    {
        $this->writeToFile($entries);
    }

    /**
     *
     * @since 3.3.4
     *
     * @return string
     */
    public static function getAdditionalTagInfo()
    {
        return static::me()->addTagInfo;
    }

    /**
     *
     * @since 3.3.4
     *
     * @return string
     */
    public static function getLogFilePath()
    {
        return static::me()->logFile;
    }

    /**
     *
     * @since 3.3.4
     *
     * @return WhmPluginLogEntry[]  Array of objects that implement all
     *                              LogEntry class public functions.
     */
    public static function getLogMsgQueue()
    {
        return static::me()->p_getMsgQueue();
    }

    /**
     *
     * @since 3.3.4
     *
     * @param int  $type
     * @return string[]
     */
    public static function getUiMsgs( $type )
    {
        return static::me()->p_getUiMsgs($type);
    }

    /**
     * Processes any buffered output, writing it to the log file, echoing it
     * out, or both.
     *
     * @since 3.3.4
     */
    public static function processBuffer()
    {
        $clear = false;

        $m = static::me();

        if ( $m->bufferedWrite ) {
            $m->writeToFile($m->msgQueue);
            $clear = true;
        }

        if ( $m->bufferedEcho ) {
            $m->echoEntries($m->msgQueue);
            $clear = true;
        }

        if ( $clear ) {
            $m->msgQueue = array();
        }
    }

    /**
     * This deprecated function exists to more closely match the public API of
     * the shared code Logger class in which an object of this class may be
     * used as the value of the Logger class' protected $instance class
     * variable.
     *
     * @since 3.3.4
     *
     * @deprecated 3.3.4
     * @param string  $msg
     * @param int     $type
     */
    public static function addUiMsg( $msg, $type )
    {
        static::me()->p_addUiMsg($msg, $type);
    }

    /**
     * Calls addUiMsg() with message level static::UI_INFO.
     *
     * @since 3.3.4
     *
     * @param string  $msg
     */
    public static function uiInfo( $msg )
    {
        static::addUiMsg($msg, static::UI_INFO);
    }

    /**
     * Calls addUiMsg() with message level static::UI_SUCC.
     *
     * @since 3.3.4
     *
     * @param string  $msg
     */
    public static function uiSuccess( $msg )
    {
        static::addUiMsg($msg, static::UI_SUCC);
    }

    /**
     * Calls addUiMsg() with message level static::UI_ERR.
     *
     * @since 3.3.4
     *
     * @param string  $msg
     */
    public static function uiError( $msg )
    {
        static::addUiMsg($msg, static::UI_ERR);
    }

    /**
     * Calls addUiMsg() with message level static::UI_WARN.
     *
     * @since 3.3.4
     *
     * @param string  $msg
     */
    public static function uiWarning( $msg )
    {
        static::addUiMsg($msg, static::UI_WARN);
    }

    /**
     *
     * @since 3.3.4
     *
     * @param string  $msg
     * @param int     $lvl
     */
    public static function logMsg( $msg, $lvl )
    {
        static::me()->log($msg, $lvl);
    }

    /**
     * Calls logMsg() with message level static::L_ERROR.
     *
     * @since 3.3.4
     *
     * @param string  $msg
     */
    public static function error( $msg )
    {
        static::logMsg($msg, static::L_ERROR);
    }

    /**
     * Calls logMsg() with message level static::L_WARN.
     *
     * @since 3.3.4
     *
     * @param string  $msg
     */
    public static function warn( $msg )
    {
        static::logMsg($msg, static::L_WARN);
    }

    /**
     * Calls logMsg() with message level static::L_NOTICE.
     *
     * @since 3.3.4
     *
     * @param string  $msg
     */
    public static function notice( $msg )
    {
        static::logMsg($msg, static::L_NOTICE);
    }

    /**
     * Calls logMsg() with message level static::L_INFO.
     *
     * @since 3.3.4
     *
     * @param string  $msg
     */
    public static function info( $msg )
    {
        static::logMsg($msg, static::L_INFO);
    }

    /**
     * Calls logMsg() with message level static::L_VERBOSE.
     *
     * @since 3.3.4
     *
     * @param string  $msg
     */
    public static function verbose( $msg )
    {
        static::logMsg($msg, static::L_VERBOSE);
    }

    /**
     * Calls logMsg() with message level static::L_DEBUG.
     *
     * @since 3.3.4
     *
     * @param string  $msg
     */
    public static function debug( $msg )
    {
        static::logMsg($msg, static::L_DEBUG);
    }

    /**
     *
     * @since 3.3.4
     *
     * @return WhmPluginLogger
     * @throws WhmPluginException
     */
    protected static function me()
    {
        if ( static::$instance == null ) {
            throw new WhmPluginException('Plugin logger class uninitialized.',
                    WhmPluginException::E_PROGRAM);
        }

        return static::$instance;
    }

    /**
     *
     * @since 3.3.4
     *
     * @param string  $mesg
     * @param int     $lvl
     */
    protected function log( $mesg, $lvl )
    {
        $entry = new WhmPluginLogEntry($mesg, $lvl);

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
     * @since 3.3.4
     *
     * @param WhmPluginLogEntry[]  $entries
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

            // Normally escalating here to write to real log file.
        }

        // Normally unlinking temp log file at this point.
    }

    /**
     *
     * @since 3.3.4
     *
     * @param WhmPluginLogEntry[]  $entries
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
     * @since 3.3.4
     *
     * @param int  $lvl
     * @return string
     */
    public static function getLvlDescr( $lvl )
    {
        switch ($lvl) {

            case static::L_ERROR:
                return 'ERROR';

            case static::L_WARN:
                return 'WARN';

            case static::L_NOTICE:
                return 'NOTICE';

            case static::L_INFO:
                return 'INFO';

            case static::L_VERBOSE:
                return 'DETAIL';

            case static::L_DEBUG:
                return 'DEBUG';

            default:
                /**
                 * Do silently.
                 */
                return '';
        }
    }

    /**
     * This deprecated function exists to more closely match the public API of
     * the shared code Logger class in which an object of this class may be
     * used as the value of the Logger class' protected $instance class
     * variable.
     *
     * @since 3.3.4
     *
     * @deprecated 3.3.4
     * @param int  $lvl
     * @return boolean
     */
    public static function setLogFileLvl( $lvl )
    {
        $lvl = (int)$lvl;

        if ( static::isValidLogFileLvl($lvl) ) {

            if ( $lvl > static::L_DEBUG ) {
                $lvl = static::L_DEBUG;
            }

            static::me()->p_setLogFileLvl($lvl);

            return true;
        }

        return false;
    }

    /**
     *
     * @since 3.3.4
     *
     * @param int  $lvl
     * @return boolean
     */
    protected static function isValidLogFileLvl( $lvl )
    {
        if ( is_int($lvl) && $lvl >= 0 ) {
            return true;
        }

        return false;
    }

    /**
     * Prevent cloning here and in extending classes.
     *
     * @since 3.3.4
     */
    final protected function __clone() {}

}
