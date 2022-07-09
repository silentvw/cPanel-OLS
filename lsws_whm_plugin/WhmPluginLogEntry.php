<?php

/* * ******************************************
 * LiteSpeed Web Server Plugin for WHM
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2019
 * @since 3.3.4
 * ******************************************* */

namespace LsPanel;

use \LsPanel\WhmPluginLogger;

/**
 * @since 3.3.4
 */
class WhmPluginLogEntry
{

    /**
     * @since 3.3.4
     * @var int
     */
    protected $lvl;

    /**
     * @since 3.3.4
     * @var string
     */
    protected $msg;

    /**
     * @since 3.3.4
     * @var null|string
     */
    protected $prefix;

    /**
     * @since 3.3.4
     * @var int[]
     */
    protected $timestamp;

    /**
     *
     * @since 3.3.4
     *
     * @param string  $msg
     * @param int     $lvl
     */
    public function __construct( $msg, $lvl )
    {
        $this->msg = $msg;
        $this->lvl = $lvl;
        $this->timestamp = time();
    }

    /**
     *
     * @since 3.3.4
     *
     * @param string  $msg
     */
    public function append( $msg )
    {
        $this->msg .= $msg;
    }

    /**
     *
     * @since 3.3.4
     *
     * @return int
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
     *
     * @since 3.3.4
     *
     * @return string
     */
    public function getMsg()
    {
        $msg = ($this->prefix == null) ? '' : "{$this->prefix} ";

        if ( $this->msg ) {
            $msg .= $this->msg;
        }

        return $msg;
    }

    /**
     *
     * @since 3.3.4
     *
     * @param int  $logLvl
     * @return string
     */
    public function getOutput( $logLvl )
    {
        if ( $this->lvl > $logLvl ) {
            return '';
        }

        $timestamp = date('Y-m-d H:i:s', $this->timestamp);
        $addTagInfo = WhmPluginLogger::getAdditionalTagInfo();
        $lvl = '[' . WhmPluginLogger::getLvlDescr($this->lvl) . ']';
        $msg = $this->getMsg();

        $output = "{$timestamp} {$addTagInfo} {$lvl}  {$msg}\n";

        return $output;
    }

}
