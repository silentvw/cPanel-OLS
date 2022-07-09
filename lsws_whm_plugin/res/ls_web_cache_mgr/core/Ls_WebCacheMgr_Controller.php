<?php

/** ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author: LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright: (c) 2018-2022
 * ******************************************* */

namespace LsUserPanel;

use \LsUserPanel\Lsc\Context\UserContext;
use \LsUserPanel\Lsc\Context\UserPanelContextOption;
use \LsUserPanel\Lsc\Panel\UserControlPanel;
use \LsUserPanel\Lsc\Panel\UserCPanel;
use \LsUserPanel\Lsc\UserLogger;
use \LsUserPanel\Lsc\UserLSCMException;
use \LsUserPanel\Lsc\UserPanelController;
use \LsUserPanel\Lsc\UserUtil as LscUtil;
use \LsUserPanel\Lsc\UserWPInstallStorage;
use \LsUserPanel\View\Model as ViewModel;
use \LsUserPanel\View\View;

class Ls_WebCacheMgr_Controller
{

    const MODULE_VERSION = '2.1.3.5';
    const TPL_DIR = 'core/View/Tpl';

    public function __construct()
    {
        $this->init();
    }

    /**
     *
     * @throws UserLSCMException  Thrown directly and indirectly.
     */
    private function init()
    {
        Ls_WebCacheMgr_Util::setTextDomain();

        UserContext::initialize(new UserPanelContextOption('cpanel'));

        try {
            UserContext::getOption()->setIconDir(
                'ls_web_cache_manager/static/icons');
        }
        catch ( UserLSCMException $e ) {
            $msg = $e->getMessage() . ' Unable to set icon directory.';
            UserLogger::logMsg($msg, UserLogger::L_DEBUG);

            throw new UserLSCMException($msg);
        }
    }

    /**
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    public function run()
    {
        $do = Ls_WebCacheMgr_Util::get_request_var('do');

        switch ($do) {
            case '':
            case 'main':
                $this->main();
                break;
            case 'lscwp_manage':
                $this->lscwpManage();
                break;
            case 'ec_cert_manage':
                $this->ecCertManage();
                break;
            case 'flush':
                $this->flushLSCache();
                break;
            case 'settings':
                $this->settings();
                break;
            case 'save_settings':
                $this->saveSettings();
                break;
            case 'advanced':
                $this->advancedOperations();
                break;
            case 'restartPHP':
                $this->restartDetachedPHP();
                break;
            default:
                echo '<h1>' . _('Invalid Entrance') . '</h1>';
        }
    }

    /**
     *
     * @throws UserLSCMException  Thrown indirectly.
     *
     */
    private function main()
    {
        /** Throws UserLSCMException */
        $viewModel = new ViewModel\MainViewModel();
        $this->display($viewModel);
    }

    /**
     *
     * @return UserCPanel
     */
    private function getUserControlPanelInstance()
    {
        $invokerName = UserContext::getOption()->getInvokerName();
        UserControlPanel::init($invokerName);

        return UserControlPanel::getInstance();
    }

    /**
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function lscwpManage()
    {
        $panelEnv = $this->getUserControlPanelInstance();
        $wpInstallStorage =
                new UserWPInstallStorage(UserContext::getUserLSCMDataFile());

        $userPanelController = new UserPanelController($panelEnv,
                $wpInstallStorage, 0);

        $userPanelController->manageCacheOperations();

        $viewModel = new ViewModel\LscwpManageViewModel($wpInstallStorage);
        $this->display($viewModel);
    }

    /**
     *
     * @since 2.1
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function ecCertManage()
    {
        /** Throws UserLSCMException */
        $ecCertSiteStorage = new EcCertSiteStorage();

        $ecCertMangeController = new EcCertManageController(
            $ecCertSiteStorage,
            EcCertManageController::MGR_STEP_NONE
        );

        $ecCertMangeController->manageCacheOperations();

        $viewModel = new ViewModel\EcCertManageViewModel($ecCertSiteStorage);
        $this->display($viewModel);
    }

    private function flushLSCache()
    {
        if ( ($cacheDir = LscUtil::getUserCacheDir()) != '' ) {
            LscUtil::flushVHCacheRoot($cacheDir);
        }
        else {
            UserLogger::addUiMsg(_('Virutual Host Cache Root Not Set.'),
                    UserLogger::UI_ERR);
        }

        $ajaxReturn = array(
            'succMsgs' => UserLogger::getUiMsgs(UserLogger::UI_SUCC),
            'errMsgs' => UserLogger::getUiMsgs(UserLogger::UI_ERR)
        );

        Ls_WebCacheMgr_Util::ajaxReturn($ajaxReturn);
    }

    /**
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function settings()
    {
        $viewModel = new ViewModel\SettingsViewModel();

        $this->display($viewModel);
    }

    private function saveSettings()
    {
        $logLvl = Ls_WebCacheMgr_Util::get_request_var('log_file_lvl');

        if ( ctype_digit($logLvl) ) {
            $logLvl = (int)$logLvl;
        }

        $settings = array(
            UserSettings::FLD_LOG_FILE_LVL => $logLvl
        );

        UserSettings::setSettings($settings);

        $ajaxReturn = array(
            'succMsgs' => UserLogger::getUiMsgs(UserLogger::UI_SUCC),
            'errMsgs' => UserLogger::getUiMsgs(UserLogger::UI_ERR)
        );

        Ls_WebCacheMgr_Util::ajaxReturn($ajaxReturn);
    }

    /**
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function advancedOperations()
    {
        $viewModel = new ViewModel\AdvancedOperationsViewModel();
        $this->display($viewModel);
    }

    /**
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function restartDetachedPHP()
    {
        Ls_WebCacheMgr_Util::restartDetachedPHP();

        $ajaxReturn = array(
            'succMsgs' => UserLogger::getUiMsgs(UserLogger::UI_SUCC),
            'errMsgs' => UserLogger::getUiMsgs(UserLogger::UI_ERR)
        );

        Ls_WebCacheMgr_Util::ajaxReturn($ajaxReturn);
    }

    /**
     * Creates a View object with the passes ViewModel and displays to screen.
     *
     * @param object  $viewModel  ViewModel object.
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function display( $viewModel )
    {
        $view = new View($viewModel);

        try {
            $view->display();
        }
        catch ( UserLSCMException $e ) {
            $viewModel = new ViewModel\MissingTplViewModel($e->getMessage());
            $view = new View($viewModel);
            $view->display();
        }
    }

}
