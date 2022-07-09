<?php

/** ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2018-2020
 * ******************************************* */

namespace LsUserPanel\Lsc\Context;

use \LsUserPanel\Ls_WebCacheMgr_Util;
use \LsUserPanel\Lsc\UserLogger;
use \LsUserPanel\Lsc\UserLSCMException;

/**
 * UserContext is a singleton
 */
class UserContext
{

    /**
     * @var UserContextOption
     */
    protected $options;

    /**
     * @var string
     */
    protected $dataFile;

    /**
     * @var null|string
     */
    protected $flagContent;

    /**
     * @var null|string
     */
    protected $readmeContent;

    /**
     *
     * @var null|UserContext
     */
    protected static $instance;

    /**
     *
     * @param UserContextOption  $contextOption
     * @throws UserLSCMException  Thrown indirectly.
     */
    protected function __construct( UserContextOption $contextOption )
    {
        $this->options = $contextOption;
        $this->dataFile =
            Ls_WebCacheMgr_Util::getUserLSCMDataDir() . '/lscm.data';
    }

    /**
     *
     * @return UserContextOption
     */
    public static function getOption()
    {
        return self::me(true)->options;
    }

    /**
     *
     * @param UserContextOption  $contextOption
     * @throws UserLSCMException  Thrown directly and indirectly.
     */
    public static function initialize( UserContextOption $contextOption )
    {
        if ( self::$instance != null ) {
            /**
             * Do not allow, UserContext already initialized.
             */
            throw new UserLSCMException('Context cannot be initialized twice.',
                    UserLSCMException::E_PROGRAM);
        }

        self::$instance = new self($contextOption);
        UserLogger::Initialize($contextOption);
    }

    /**
     *
     * @return UserContext
     * @throws UserLSCMException
     */
    protected static function me()
    {
        if ( self::$instance == null ) {
            /**
             * Do not allow, must initialize first.
             */
            throw new UserLSCMException('Uninitialized context.',
                    UserLSCMException::E_NON_FATAL);
        }

        return self::$instance;
    }

    /**
     *
     * @return string
     * @throws UserLSCMException
     */
    public static function getUserLSCMDataFile()
    {
        try {
            return self::me()->dataFile;
        }
        catch ( UserLSCMException $e ) {
            $msg = $e->getMessage() . ' Could not get data file.';
            UserLogger::logMsg($msg, UserLogger::L_DEBUG);

            throw new UserLSCMException($msg);
        }
    }

    /**
     *
     * @return int
     */
    public static function getScanDepth()
    {
        return self::me()->options->getScanDepth();
    }

    /**
     *
     * @return string
     */
    public static function getFlagFileContent()
    {
        $m = self::me();

        if ( $m->flagContent == null ) {
            $m->flagContent = <<<CONTENT
This file was created by LiteSpeed Web Cache Manager

When this file exists, your LiteSpeed Cache plugin for WordPress will NOT be affected
by Mass Enable/Disable operations performed through LiteSpeed Web Cache Manager.

Please DO NOT ATTEMPT to remove this file unless you understand the above.

CONTENT;
        }

        return $m->flagContent;
    }

}
