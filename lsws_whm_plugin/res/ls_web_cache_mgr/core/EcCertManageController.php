<?php

/** *********************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2020
 * @since 2.1
 * *******************************************
 */

namespace LsUserPanel;

use \LsUserPanel\Lsc\UserLogger;

/**
 *
 * @since 2.1
 */
class EcCertManageController
{

    /**
     * @since 2.1
     * @var int
     */
    const MGR_STEP_NONE = 0;

    /**
     * @since 2.1
     * @var int
     */
    const MGR_STEP_UPDATE = 1;

    /**
     * @since 2.1
     * @var int
     */
    const MGR_STEP_GEN_ALL = 2;

    /**
     * @since 2.1
     * @var int
     */
    const MGR_STEP_REMOVE_ALL = 3;

    /**
     * @since 2.1
     * @var EcCertSiteStorage
     */
    private $ecCertSiteStorage;

    /**
     * @since 2.1
     * @var int
     */
    private $mgrStep;

    /**
     * @since 2.1
     *
     * @param EcCertSiteStorage  $ecCertSiteStorage
     * @param int                $mgrStep
     */
    public function __construct(EcCertSiteStorage $ecCertSiteStorage, $mgrStep )
    {
        $this->ecCertSiteStorage = $ecCertSiteStorage;
        $this->mgrStep = $mgrStep;
    }

    /**
     *
     * @since 2.1
     *
     * @return void|string[]
     */
    private function getCurrentAction()
    {
        $all_actions = array(
            'gen_single' => EcCertSiteStorage::CMD_GEN_EC,
            'remove_single' => EcCertSiteStorage::CMD_REMOVE_EC,
            'gen_sel' => EcCertSiteStorage::CMD_GEN_EC,
            'remove_sel' => EcCertSiteStorage::CMD_REMOVE_EC,
        );

        foreach ( $all_actions as $act => $doAct ) {

            if ( Ls_WebCacheMgr_Util::get_request_var($act) !== null ) {
                return array( 'act_key' => $act, 'action' => $doAct );
            }
        }
    }

    /**
     *
     * @since 2.1
     *
     * @return int
     */
    public function manageCacheOperations()
    {
        if ( $this->checkUpdate()
            || $this->checkGenAll()
            || $this->checkRemoveAll()
            || ($actionInfo = $this->getCurrentAction()) == NULL ) {

            return $this->mgrStep;
        }

        $actKey = $actionInfo['act_key'];
        $action = $actionInfo['action'];

        if ( strcmp(substr($actKey, -3), 'sel') == 0 ) {
            $this->doFormAction($action);
        }
        else {
            $serverName = Ls_WebCacheMgr_Util::get_request_var($actKey);
            $this->doSingleAction($action, $serverName);
        }

        return $this->mgrStep;
    }

    /**
     *
     * @since 2.1
     *
     * @return bool
     */
    private function checkUpdate()
    {
        if ( !Ls_WebCacheMgr_Util::get_request_var('update_list') ) {
            return false;
        }

        $this->mgrStep = self::MGR_STEP_UPDATE;
        $this->ecCertSiteStorage->updateList();

        return true;
    }

    /**
     *
     * @since 2.1
     *
     * @return bool
     */
    private function checkGenAll()
    {
        if ( !Ls_WebCacheMgr_Util::get_request_var('gen_all') ) {
            return false;
        }

        $this->mgrStep = self::MGR_STEP_GEN_ALL;

        $serverNames = $this->ecCertSiteStorage->getServerNames();

        $this->ecCertSiteStorage->doEcCertAction(
            EcCertSiteStorage::CMD_GEN_EC,
            $serverNames
        );

        return true;
    }

    /**
     *
     * @since 2.1
     *
     * @return bool
     */
    private function checkRemoveAll()
    {
        if ( !Ls_WebCacheMgr_Util::get_request_var('remove_all') ) {
            return false;
        }

        $this->mgrStep = self::MGR_STEP_REMOVE_ALL;

        $serverNames = $this->ecCertSiteStorage->getServerNames();

        $this->ecCertSiteStorage->doEcCertAction(
            EcCertSiteStorage::CMD_REMOVE_EC,
            $serverNames
        );

        return true;
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $action
     * @return void
     */
    private function doFormAction( $action )
    {
        $list = Ls_WebCacheMgr_Util::get_request_list('domains');

        /**
         * Empty list also checked earlier using JS.
         */
        if ( $list == NULL ) {
            UserLogger::addUiMsg(
                _('Please select at least one checkbox.'),
                UserLogger::UI_ERR
            );
            return;
        }

        foreach ( $list as $domain ) {

            if ( $this->ecCertSiteStorage->getEcCertSite($domain) === null ) {
                UserLogger::addUiMsg(
                    _('Invalid input value detected - No Action Taken'),
                    UserLogger::UI_ERR
                );
                return;
            }
        }

        $this->ecCertSiteStorage->doEcCertAction($action, $list);
    }

    /**
     *
     * @since 2.1
     *
     * @param string  $action
     * @param string  $serverName
     * @return void
     */
    private function doSingleAction( $action, $serverName )
    {
        $ecCertSite = $this->ecCertSiteStorage->getEcCertSite($serverName);

        if ( $ecCertSite === null ) {
            UserLogger::addUiMsg(
                _('Invalid input value detected - No Action Taken'),
                UserLogger::UI_ERR
            );
            return;
        }

        $this->ecCertSiteStorage->doEcCertAction($action, array( $serverName ));
    }

}
