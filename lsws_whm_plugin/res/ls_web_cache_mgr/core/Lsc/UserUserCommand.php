<?php

/** *********************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author Michael Alegre
 * @copyright (c) 2018-2022 LiteSpeed Technologies, Inc.
 * *******************************************
 */

namespace LsUserPanel\Lsc;

use LsUserPanel\CPanelWrapper;
use LsUserPanel\Lsc\Context\UserContext;
use LsUserPanel\Lsc\Panel\UserControlPanel;
use LsUserPanel\PluginSettings;

/**
 * Running as user - suexec
 */
class UserUserCommand
{

    /**
     * @var int
     */
    const EXIT_ERROR = 1;

    /**
     * @var int
     */
    const EXIT_SUCC = 2;

    /**
     * @var int
     */
    const EXIT_FAIL = 4;

    /**
     * @var int
     */
    const EXIT_INCR_SUCC = 8;

    /**
     * @var int
     */
    const EXIT_INCR_FAIL = 16;

    /**
     * @var string
     */
    const RETURN_CODE_TIMEOUT = 124;

    /**
     * @var string
     */
    const CMD_STATUS = 'status';

    /**
     * @var string
     */
    const CMD_DIRECT_ENABLE = 'direct_enable';

    /**
     * @var string
     */
    const CMD_DISABLE = 'disable';

    /**
     * Sub-command.
     *
     * @var string
     */
    const CMD_UPDATE_TRANSLATION = 'update_translation';

    /**
     * Sub-command.
     *
     * @var string
     */
    const CMD_REMOVE_LSCWP_PLUGIN_FILES = 'remove_lscwp_plugin_files';

    /**
     * @since 2.1
     * @var string
     */
    const CMD_GET_QUICCLOUD_API_KEY = 'getQuicCloudApiKey';

    private function __construct(){}

    /**
     * Handles logging unexpected error output (or not if too long) and returns
     * a crafted message to be displayed instead.
     *
     * @param UserWPInstall $wpInstall  WordPress Installation object.
     * @param string        $err        Complied error message.
     * @param int           $lines      Number of $output lines read into the
     *                                  error msg.
     *
     * @return string  Message to be displayed instead.
     */
    private static function handleUnexpectedError(
        UserWPInstall $wpInstall,
                      $err,
                      $lines )
    {
        $msg = 'Unexpected Error Encountered!';
        $path = $wpInstall->getPath();

        /**
         * $lines > 500 are likely some custom code triggering a page render.
         * Throw out actual message in this case.
         */
        if ( $lines < 500 ) {
            $commonErrs = array(
                UserWPInstall::ST_ERR_EXECMD_DB =>
                    _('Error establishing a database connection.')
            );

            $match = false;

            foreach ( $commonErrs as $statusBit => $commonErr ) {

                if ( strpos($err, $commonErr) !== false ) {
                    $wpInstall->unsetStatusBit(UserWPInstall::ST_ERR_EXECMD);
                    $wpInstall->setStatusBit($statusBit);

                    $msg .= " $commonErr";
                    $match = true;
                    break;
                }
            }

            if ( !$match ) {
                UserLogger::logMsg("$path - $err", UserLogger::L_ERROR);
                return "$msg "
                    . _('See ls_webcachemgr.log for more information.');
            }
        }

        UserLogger::logMsg("$path - $msg", UserLogger::L_ERROR);
        return $msg;
    }

    /**
     *
     * @since 2.1
     *
     * @param UserWPInstall $wpInstall
     * @param string        $output
     */
    private static function handleGetTranslationOutput(
        UserWPInstall $wpInstall,
                      $output )
    {
        $translationInfo = explode(' ', $output);
        $locale = $translationInfo[0];
        $lscwpVer = $translationInfo[1];

        if ( UserLscwpPlugin::retrieveTranslation($locale, $lscwpVer) ) {
            $subAction = self::CMD_UPDATE_TRANSLATION;
            $subCmd = self::getIssueCmd($subAction, $wpInstall);

            $result = CPanelWrapper::getCpanelObj()->uapi(
                'lsws',
                'execIssueCmd',
                array( 'cmd' => $subCmd )
            );

            $data = $result['cpanelresult']['result']['data'];
            $subReturn_var = $data['retVar'];
            $subResOutput = $data['output'];

            if ( !empty($subResOutput) ) {
                $subOutput = explode("\n", $subResOutput);
            }
            else {
                $subOutput = array();
            }

            UserLogger::debug(
                "Issue command $subAction=$subReturn_var $wpInstall\n$subCmd"
            );
            UserLogger::debug('output = ' . var_export($subOutput, true));

            foreach ( $subOutput as $subLine ) {

                if ( preg_match('/BAD_TRANSLATION=(.+)/', $subLine, $m) ) {
                    $translationInfo = explode(' ', $m[1]);
                    $locale = $translationInfo[0];
                    $lscwpVer = $translationInfo[1];

                    UserLscwpPlugin::removeTranslationZip($locale, $lscwpVer);
                }
            }
        }
    }

    /**
     *
     * @since 2.1
     *
     * @param UserWPInstall $wpInstall
     * @param string        $line
     * @param int           $retStatus
     * @param string        $err
     *
     * @return bool
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    private static function handleResultOutput(
        UserWPInstall $wpInstall,
                      $line,
                      &$retStatus,
                      &$err )
    {
        if ( preg_match('/SITEURL=(.+)/', $line, $m) ) {

            if ( !$wpInstall->populateDataFromUrl($m[1]) ) {
                /**
                 * Matching docroot could not be found, ignore other
                 * output. setCmdStatusAndMsg() etc already handled in
                 * populateDataFromUrl().
                 */
                return false;
            }
        }
        elseif ( preg_match('/STATUS=(.+)/', $line, $m) ) {
            $retStatus |= (int)$m[1];
        }
        elseif ( preg_match('/GET_TRANSLATION=(.+)/', $line, $m) ) {
            self::handleGetTranslationOutput($wpInstall, $m[1]);
        }
        else {
            $err .= "Unexpected result line $line\n";
        }

        return true;
    }

    /**
     *
     * @since 2.1
     *
     * @param UserWPInstall $wpInstall
     */
    private static function removeLeftoverLscwpFiles(
        UserWPInstall  $wpInstall )
    {
        $subAction = self::CMD_REMOVE_LSCWP_PLUGIN_FILES;
        $subCmd = self::getIssueCmd($subAction, $wpInstall);

        $result = CPanelWrapper::getCpanelObj()->uapi(
            'lsws',
            'execIssueCmd',
            array( 'cmd' => $subCmd )
        );

        $data = $result['cpanelresult']['result']['data'];
        $subReturn_var = $data['retVar'];
        $subResOutput = $data['output'];

        if ( !empty($subResOutput) ) {
            $subOutput = explode("\n", $subResOutput);
        }
        else {
            $subOutput = array();
        }

        UserLogger::debug(
            "Issue command $subAction=$subReturn_var $wpInstall\n$subCmd"
        );
        UserLogger::debug('output = ' . var_export($subOutput, true));

        $wpInstall->removeNewLscwpFlagFile();
    }

    /**
     *
     * @param string        $action
     * @param UserWPInstall $wpInstall
     * @param array         $extraArgs
     *
     * @return string
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    private static function  getIssueCmd(
        $action,
        UserWPInstall $wpInstall,
        array $extraArgs = array() )
    {
        $timeout = UserControlPanel::PHP_TIMEOUT;
        $phpBin = $wpInstall->getPhpBinary();
        $path = $wpInstall->getPath();
        $serverName = $wpInstall->getData(UserWPInstall::FLD_SERVERNAME);
        $env = UserContext::getOption()->getInvokerName();

        if ( $serverName === null ) {
            $serverName = $docRoot = 'x';
        }
        else {
            $docRoot = $wpInstall->getData(UserWPInstall::FLD_DOCROOT);

            if ( $docRoot === null ) {
                $docRoot = 'x';
            }
        }

        $modifier = implode(' ', $extraArgs);

        $lswsHome =
            PluginSettings::getSetting(PluginSettings::FLD_LSWS_HOME_DIR);
        $file = "$lswsHome/add-ons/webcachemgr/src/UserCommand.php";

        return "cd $path/wp-admin && timeout $timeout $phpBin $file "
            . "$action $path $docRoot $serverName $env"
            . (($modifier !== '') ? " $modifier" : '');
    }

    /**
     *
     * @since 2.1
     *
     * @param string        $action
     * @param UserWPInstall $wpInstall
     * @param string[]      $extraArgs
     *
     * @return null|mixed
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    public static function getValueFromWordPress(
        $action,
        UserWPInstall $wpInstall,
        array $extraArgs = array() )
    {
        if ( !self::preIssueValidation($action, $wpInstall, $extraArgs) ) {
            return null;
        }

        $cmd = self::getIssueCmd($action, $wpInstall, $extraArgs);

        $cpanel = CPanelWrapper::getCpanelObj();

        $result = $cpanel->uapi('lsws', 'execIssueCmd', array( 'cmd' => $cmd ));

        $return_var = $result['cpanelresult']['result']['data']['retVar'];
        $resOutput = $result['cpanelresult']['result']['data']['output'];

        $output = (!empty($resOutput)) ? explode("\n", $resOutput) : array();

        UserLogger::debug(
            "getValueFromWordPress command "
                . "$action=$return_var $wpInstall\n$cmd"
        );
        UserLogger::debug('output = ' . var_export($output, true));

        $debug = $upgrade = $err = '';
        $curr = &$err;

        $ret = null;

        foreach ( $output as $line ) {
            /**
             * If this line is not present in output, did not return normally.
             * This line will appear after any [UPGRADE] output.
             */
            if ( strpos($line, 'LS UserCommand Output Start') !== false ) {
                continue;
            }
            elseif ( strpos($line, '[RESULT]') !== false ) {

                if ( preg_match('/API_KEY=(.*)/', $line, $m) ) {
                    $ret = $m[1];
                }
                else {
                    $err .= "Unexpected result line $line\n";
                }
            }
            elseif ( ($pos = strpos($line, '[DEBUG]')) !== false ) {
                $debug .= substr($line, $pos + 7) . "\n";
                $curr = &$debug;
            }
            elseif ( strpos($line, '[UPGRADE]') !== false ) {
                //Ignore this output
                $curr = &$upgrade;
            }
            else {
                $curr .= "$line\n";
            }
        }

        $path = $wpInstall->getPath();

        if ( $debug ) {
            UserLogger::logMsg("$path - $debug", UserLogger::L_DEBUG);
        }

        if ( $err ) {
            UserLogger::logMsg("$path - $err", UserLogger::L_ERROR);
        }

        return $ret;
    }

    /**
     *
     * @param string        $action
     * @param UserWPInstall $wpInstall
     * @param string[]      $extraArgs
     *
     * @return bool
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    public static function issue(
        $action,
        UserWPInstall $wpInstall,
        array $extraArgs = array() )
    {
        if ( !self::preIssueValidation($action, $wpInstall, $extraArgs) ) {
            return false;
        }

        $cmd = self::getIssueCmd($action, $wpInstall, $extraArgs);

        $cpanel = CPanelWrapper::getCpanelObj();

        $result = $cpanel->uapi('lsws', 'execIssueCmd', array( 'cmd' => $cmd ));

        $data = $result['cpanelresult']['result']['data'];
        $return_var = $data['retVar'];
        $resOutput = $data['output'];

        $output = (!empty($resOutput)) ? explode("\n", $resOutput) : array();

        UserLogger::debug("Issue command $action=$return_var $wpInstall\n$cmd");
        UserLogger::debug('output = ' . var_export($output, true));

        if ( $wpInstall->hasNewLscwpFlagFile() ) {
            self::removeLeftoverLscwpFiles($wpInstall);
        }

        $errorStatus = $retStatus = $cmdStatus = 0;

        switch ($return_var) {
            case UserUserCommand::RETURN_CODE_TIMEOUT:
                $errorStatus |= UserWPInstall::ST_ERR_TIMEOUT;
                break;
            case UserUserCommand::EXIT_ERROR:
            case 255:
                $errorStatus |= UserWPInstall::ST_ERR_EXECMD;
                break;
            //no default
        }

        $path = $wpInstall->getPath();

        $isExpectedOutput = false;
        $unexpectedLines = 0;
        $succ = $upgrade =  $err = $msg = $logMsg = '';
        $logLvl = -1;
        $curr = &$err;

        foreach ( $output as $line ) {
            /**
             * If this line is not present in output, did not return normally.
             * This line will appear after any [UPGRADE] output.
             */
            if ( strpos($line, 'LS UserCommand Output Start') !== false ) {
                $isExpectedOutput = true;
            }
            elseif ( strpos($line, '[RESULT]') !== false ) {

                $resultOutputHandled = self::handleResultOutput(
                    $wpInstall,
                    $line,
                    $retStatus,
                    $err
                );

                if ( !$resultOutputHandled ) {
                    /**
                     * Problem handling RESULT output, ignore other output.
                     */
                    return false;
                }
            }
            elseif ( ($pos = strpos($line, '[SUCCESS]')) !== false ) {
                $succ .= substr($line, $pos + 9) . "\n";
                $curr = &$succ;
            }
            elseif ( ($pos = strpos($line, '[ERROR]')) !== false ) {
                $err .= substr($line, $pos + 7) . "\n";
                $curr = &$err;
            }
            elseif ( strpos($line, '[LOG]') !== false ) {

                if ( $logMsg != '' ) {
                    UserLogger::logMsg(trim($logMsg), $logLvl);
                    $logMsg = '';
                }

                if ( preg_match('/\[(\d+)\] (.+)/', $line, $m) ) {
                    $logLvl = $m[1];
                    $logMsg = "$path - $m[2]\n";
                }

                $curr = &$logMsg;
            }
            elseif ( strpos($line, '[UPGRADE]') !== false ) {
                /**
                 * Ignore this output
                 */
                $curr = &$upgrade;
            }
            else {

                if ( !$isExpectedOutput ) {
                    $line = htmlentities($line);
                    $unexpectedLines++;
                }

                $curr .= "$line\n";
            }
        }

        if ( $logMsg != '' ) {
            UserLogger::logMsg(trim($logMsg), $logLvl);
        }

        if ( !$isExpectedOutput && !$errorStatus ) {
            $errorStatus |= UserWPInstall::ST_ERR_EXECMD;
        }

        if ( $errorStatus ) {
            $wpInstall->addUserFlagFile();
            $errorStatus |= UserWPInstall::ST_FLAGGED;

            $cmdStatus |= UserUserCommand::EXIT_INCR_FAIL;
        }

        $newStatus = ($errorStatus | $retStatus);

        if ( $newStatus != 0 ) {
            $wpInstall->setStatus($newStatus);
        }

        if ( $succ ) {
            $cmdStatus |= UserUserCommand::EXIT_SUCC;
            $msg = $succ;
        }

        if ( $err ) {

            if ( $return_var == UserUserCommand::EXIT_FAIL ) {
                $cmdStatus |= UserUserCommand::EXIT_FAIL;
            }
            else {
                $cmdStatus |= UserUserCommand::EXIT_ERROR;
            }

            if ( $isExpectedOutput ) {
                $msg = $err;
                UserLogger::logMsg("$path - $err", UserLogger::L_ERROR);
            }
            else {
                $msg = self::handleUnexpectedError(
                    $wpInstall,
                    $err,
                    $unexpectedLines
                );
            }
        }

        $wpInstall->setCmdStatusAndMsg($cmdStatus, $msg);

        return true;
    }

    /**
     *
     * @param string        $action
     * @param UserWPInstall $wpInstall
     * @param string[]      $extraArgs  Not used at the moment.
     *
     * @return bool
     *
     * @throws UserLSCMException
     */
    private static function preIssueValidation(
        $action,
        UserWPInstall $wpInstall,
        array $extraArgs )
    {
        if ( !self::isSupportedCmd($action) ) {
            throw new UserLSCMException(
                "Illegal action $action.",
                UserLSCMException::E_PROGRAM
            );
        }

        if ( !$wpInstall->hasValidPath() ) {
            return false;
        }

        return true;
    }

    /**
     *
     * @param string $action
     *
     * @return bool
     */
    private static function isSupportedCmd( $action )
    {
        $supported = array(
            self::CMD_DIRECT_ENABLE,
            self::CMD_DISABLE,
            self::CMD_GET_QUICCLOUD_API_KEY,
            self::CMD_STATUS
        );

        return in_array($action, $supported);
    }

}
