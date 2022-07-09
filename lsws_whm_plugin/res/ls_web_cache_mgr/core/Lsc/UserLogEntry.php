<?php

/* * ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 * @author: LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright: (c) 2018-2019
 * ******************************************* */

namespace LsUserPanel\Lsc;

use \LsUserPanel\Lsc\UserLogger;

class UserLogEntry
{

    /**
     * @var int
     */
    protected $lvl;

    /**
     * @var string
     */
    protected $msg;

    /**
     *
     * @var null|string
     */
    protected $prefix;

    /**
     *
     * @var int[]
     */
    protected $timestamp;

    /**
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
     * @return int
     */
    public function getLvl()
    {
        return $this->lvl;
    }

    /**
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
     * @param int  $logLvl
     * @return string
     */
    public function getOutput( $logLvl )
    {
        if ( $this->lvl > $logLvl ) {
            return '';
        }

        $timestamp = date('Y-m-d H:i:s', $this->timestamp);
        $lvl = '[' . UserLogger::getLvlDescr($this->lvl) . ']';
        $msg = $this->getMsg();

        $output = "{$timestamp} {$lvl}  {$msg}\n";

        return $output;
    }

}
