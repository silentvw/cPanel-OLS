<?php

/** *********************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author: LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright: (c) 2018-2020
 * *******************************************
 */

namespace LsUserPanel\Lsc;

use \LsUserPanel\Ls_WebCacheMgr_Util;
use \LsUserPanel\Lsc\Panel\UserControlPanel;

class UserPanelController
{

    const MGR_STEP_SCAN = 1;
    const MGR_STEP_REFRESH_STATUS = 2;

    /**
     * @var UserControlPanel
     */
    private $panelEnv;

    /**
     * @var UserWPInstallStorage
     */
    private $wpInstallStorage;

    /**
     * @var int
     */
    private $mgrStep;

    /**
     *
     * @param UserControlPanel      $panelEnv
     * @param UserWPInstallStorage  $wpInstallStorage
     * @param int                   $mgrStep
     */
    public function __construct( UserControlPanel $panelEnv,
            UserWPInstallStorage $wpInstallStorage, $mgrStep )
    {
        $this->panelEnv = $panelEnv;
        $this->wpInstallStorage = $wpInstallStorage;
        $this->mgrStep = $mgrStep;
    }

    /**
     *
     * @return void|string[]
     */
    private function getCurrentAction()
    {
        $all_actions = array(
            'enable_single' => 'direct_enable',
            'disable_single' => 'disable',
            'flag_single' => 'flag',
            'unflag_single' => 'unflag',
            'upload_ssl_cert_single' =>
                UserWPInstallStorage::CMD_QUICCLOUD_UPLOAD_SSL_CERT,
            'enable_sel' => 'direct_enable',
            'disable_sel' => 'disable',
            'flag_sel' => 'flag',
            'unflag_sel' => 'unflag'
        );

        foreach ( $all_actions as $act => $doAct ) {

            if ( Ls_WebCacheMgr_Util::get_request_var($act) !== null ) {
                return array( 'act_key' => $act, 'action' => $doAct );
            }
        }
    }

    /**
     *
     * @return int
     * @throws UserLSCMException  Thrown indirectly.
     */
    public function manageCacheOperations()
    {
        if ( $this->checkScanAction()
                || $this->checkRefreshAction()
                || ($actionInfo = $this->getCurrentAction()) == NULL ) {

            return $this->mgrStep;
        }

        $actKey = $actionInfo['act_key'];
        $action = $actionInfo['action'];

        if ( strcmp(substr($actKey, -3), 'sel') == 0 ) {
            $this->doFormAction($action);
        }
        else {
            $path = Ls_WebCacheMgr_Util::get_request_var($actKey);
            $this->doSingleAction($action, $path);
        }

        return $this->mgrStep;
    }

    /**
     *
     * @return boolean
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function checkScanAction()
    {
        if ( !Ls_WebCacheMgr_Util::get_request_var('re-scan') ) {
            return false;
        }

        $this->mgrStep = self::MGR_STEP_SCAN;

        $docrootInfo = $this->panelEnv->getDocroots();

        $this->wpInstallStorage->doAction('scan', $docrootInfo);

        $msgs = $this->wpInstallStorage->getAllCmdMsgs();
        $errMsgs = array_merge($msgs['fail'], $msgs['err']);

        foreach ( $errMsgs as $msg ) {
            UserLogger::addUiMsg($msg, UserLogger::UI_ERR);
        }

        return true;
    }

    /**
     *
     * @return boolean
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function checkRefreshAction()
    {
        if ( !Ls_WebCacheMgr_Util::get_request_var('refresh_status') ) {
            return false;
        }

        $this->mgrStep = self::MGR_STEP_REFRESH_STATUS;

        $wpInstallPaths = $this->wpInstallStorage->getPaths();

        $this->wpInstallStorage->doAction('status', $wpInstallPaths);

        $msgs = $this->wpInstallStorage->getAllCmdMsgs();
        $errMsgs = array_merge($msgs['fail'], $msgs['err']);

        foreach ( $errMsgs as $msg ) {
            UserLogger::addUiMsg($msg, UserLogger::UI_ERR);
        }

        return true;
    }

    /**
     *
     * @param string  $action
     * @return void
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function doFormAction( $action )
    {
        $list = Ls_WebCacheMgr_Util::get_request_list('installations');

        /**
         * Empty list also checked earlier using JS.
         */
        if ( $list == NULL ) {
            UserLogger::addUiMsg(_('Please select at least one checkbox.'),
                    UserLogger::UI_ERR);
            return;
        }

        foreach ( $list as $wpPath ) {

            if ( $this->wpInstallStorage->getWPInstall($wpPath) === null ) {
                UserLogger::addUiMsg(_('Invalid input value detected - No Action Taken'),
                        UserLogger::UI_ERR);
                return;
            }
        }

        $this->wpInstallStorage->doAction($action, $list);

        $msgs = $this->wpInstallStorage->getAllCmdMsgs();
        $errMsgs = array_merge($msgs['fail'], $msgs['err']);
        $succMsgs = $msgs['succ'];

        foreach ( $errMsgs as $errMsg ) {
            UserLogger::addUiMsg($errMsg, UserLogger::UI_ERR);
        }

        foreach ( $succMsgs as $succMsg ) {
            UserLogger::addUiMsg($succMsg, UserLogger::UI_SUCC);
        }
    }

    /**
     *
     * @param string  $action
     * @param string  $path
     * @return void
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function doSingleAction( $action, $path )
    {
        $wpInstall = $this->wpInstallStorage->getWPInstall($path);

        if ( $wpInstall === null ) {
            UserLogger::addUiMsg(_('Invalid input value detected - No Action Taken'),
                    UserLogger::UI_ERR);
            return;
        }

        $this->wpInstallStorage->doAction($action, array( $path ));

        $msgs = $this->wpInstallStorage->getAllCmdMsgs();
        $errMsgs = array_merge($msgs['fail'], $msgs['err']);
        $succMsgs = $msgs['succ'];

        foreach ( $errMsgs as $errMsg ) {
            UserLogger::addUiMsg($errMsg, UserLogger::UI_ERR);
        }

        foreach ( $succMsgs as $succMsg ) {
            UserLogger::addUiMsg($succMsg, UserLogger::UI_SUCC);
        }
    }

}
