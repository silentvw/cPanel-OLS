<?php

/** ******************************************
 * LiteSpeed Web Server Plugin for WHM
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2013-2022
 * ******************************************* */

namespace LsPanel;

use \Lsc\Wp\Context\Context;
use \Lsc\Wp\Context\ContextOption;
use \Lsc\Wp\LSCMException;
use \Lsc\Wp\Panel\ControlPanel;
use \Lsc\Wp\Panel\CPanel;
use \Lsc\Wp\PanelController;
use \Lsc\Wp\PluginVersion;
use \Lsc\Wp\View\Model as LscViewModel;
use \Lsc\Wp\WPDashMsgs;
use \Lsc\Wp\WPInstallStorage;
use \LsPanel\WhmMod_LiteSpeed_CPanelConf as CPanelConf;
use \LsPanel\WhmMod_LiteSpeed_Util as Util;
use \LsPanel\View\Model as WhmViewModel;
use \LsPanel\View\View;

class WhmMod_LiteSpeed_ControlApp
{

    const MODULE_VERSION = '4.1.10.1';
    const SHARED_API_VER = '1.13.13.1';
    const CGI_DIR = '/usr/local/cpanel/whostmgr/docroot/cgi/lsws';
    const LSWS_HOME_DEF_FILE = self::CGI_DIR . '/LSWS_HOME.config';
    const ICON_DIR = 'static/icons';

    /**
     * @var WhmMod_LiteSpeed_View
     */
    private $view;

    /**
     * @var Util
     */
    private $util;


    /**
     *
     * @throws WhmPluginException  Thrown indirectly.
     */
    public function __construct()
    {
        $this->init();

        $this->util = new Util();
        $this->view = new WhmMod_LiteSpeed_View();
    }

    /**
     *
     * @since 3.3.4
     *
     * @throws WhmPluginException
     */
    private function init()
    {
        if ( file_exists(self::LSWS_HOME_DEF_FILE) ) {
            $tmp = file_get_contents(self::LSWS_HOME_DEF_FILE);

            if ( $tmp !== FALSE
                    && preg_match("/LSWS_HOME=(.+)$/", $tmp, $matches) ) {

                $path = trim($matches[1]);

                if ( is_executable("{$path}/bin/lshttpd") ) {
                    define('LSWS_HOME', $path);

                    WhmPluginLogger::changeLogFileUsed(
                            LSWS_HOME . '/logs/webcachemgr.log');

                    if ( $this->includeWebCacheMgrBootstrap() ) {
                        define('SHARED_CODE_LOADED', true);

                        /**
                         * Use log file name set in shared code.
                         */
                        WhmPluginLogger::changeLogFileUsed(LSWS_HOME . '/logs/'
                                . ContextOption::LOG_FILE_NAME);

                        try {
                            Context::getOption()->setIconDir(self::ICON_DIR);
                        }
                        catch ( LSCMException $e ) {
                            $msg = $e->getMessage()
                                    . ' Unable to set icon directory.';
                            WhmPluginLogger::debug($msg);

                            throw new WhmPluginException($msg);
                        }
                    }
                }
            }

            if ( ! defined('LSWS_HOME') ) {
                unlink(self::LSWS_HOME_DEF_FILE);
            }
        }

        if ( ! defined('SHARED_CODE_LOADED') ) {
            define('SHARED_CODE_LOADED', false);
        }
    }

    /**
     *
     * @param string  $path
     * @return string
     */
    private function saveLSWS_HOME( $path )
    {
        $error = '';
        $content = "LSWS_HOME={$path}\n";

        if ( file_put_contents(self::LSWS_HOME_DEF_FILE, $content) === false ) {
            $error = 'Fail to write to file ' . self::LSWS_HOME_DEF_FILE
                . '. Please check the directory permission.';
        }
        else {
            define('LSWS_HOME', $path);
            WhmPluginLogger::changeLogFileUsed(
                LSWS_HOME . '/logs/webcachemgr.log'
            );
            $this->util->Init();
        }

        return $error;
    }

    /**
     *
     * @throws WhmPluginException  Thrown indirectly.
     * @throws LSCMException  Thrown indirectly.
     */
    public function Run()
    {
        $step = $mgrStep = $dashStep = 0;

        $do = $this->util->get_request_var('do');

        if ( ($ret = $this->util->get_request_var('step')) !== null ) {
            $step = $ret;
        }

        if ( ($ret = $this->util->get_request_var('mgr_step')) !== null ) {
            $mgrStep = $ret;
        }

        if ( ($ret = $this->util->get_request_var('dash_step')) !== null ) {
            $dashStep = $ret;
        }

        $supported_actions = array(
            'define_home', 'versionManager',
            'restart_lsws', 'switch_lsws', 'switch_apache',
            'change_port_offset',
            'check_license', 'change_license', 'transfer_license',
            'config_lsws', 'config_lsws_suexec',
            'install_lsws', 'uninstall_lsws',
            'installTimezoneDB', 'updateTimezoneDB',
            'lscwp_manage', 'lscwp_mass_enable_disable', 'lscwpVersionManager',
            'dash_notifier', 'quicCloudIps',
            'cacheRootSetup',
            'cpanel_install', 'cpanel_uninstall', 'cpanel_settings',
            'restart_detached_php', 'updateSharedCode', 'updateWhmPlugin',
            '', 'main'
        );

        $steps = $mgrSteps = array( 0, 1, 2, 3, 4 );
        $dashSteps = array( 0, 1, 2 );

        if ( !in_array($do, $supported_actions) || !in_array($step, $steps)
                || !in_array($mgrStep, $mgrSteps)
                || !in_array($dashStep, $dashSteps) ) {

            echo '<h1>Invalid Entrance</h1>';
            return;
        }

        ob_start();
        $this->view->PageHeader($do);

        $viewModel = null;

        switch ($do) {

            case 'change_license':
                $this->change_license($step);
                break;

            case 'change_port_offset':
                $this->change_port_offset($step);
                break;

            case 'check_license':
                $this->check_license();
                break;

            case 'config_lsws':
                $viewModel = new WhmViewModel\LswsConfigViewModel($this->util);
                break;

            case 'config_lsws_suexec':
                $viewModel = $this->config_lsws_suexec($step);
                break;

            case 'define_home':
                $this->define_home($step);
                break;

            case 'install_lsws':
                $this->install_lsws($step);
                break;

            case 'installTimezoneDB':
                $this->TimezoneDBAction('install', $step);
                break;

            case 'quicCloudIps':
                $viewModel = new WhmViewModel\QuicCloudIpsViewModel();
                break;

            case 'restart_detached_php':
                $viewModel = $this->restartDetachedPHP($step);
                break;

            case 'restart_lsws':
                $this->restart_lsws($step);
                break;

            case 'switch_apache':
                $this->switch_apache($step);
                break;

            case 'switch_lsws':
                $this->switch_lsws($step);
                break;

            case 'transfer_license':
                $this->transfer_license($step);
                break;

            case 'uninstall_lsws':
                $this->uninstall_lsws($step);
                break;

            case 'updateSharedCode':
                $viewModel = $this->updateSharedCode();
                break;

            case 'updateTimezoneDB':
                $this->TimezoneDBAction('update', $step);
                break;

            case 'versionManager':
                $viewModel = $this->versionManager($step);
                break;

            case 'updateWhmPlugin':
                $viewModel = $this->updateWhmPlugin();
                break;

            case 'cacheRootSetup':
            case 'cpanel_install':
            case 'cpanel_settings':
            case 'cpanel_uninstall':
            case 'dash_notifier':
            case 'lscwp_manage':
            case 'lscwp_mass_enable_disable':
            case 'lscwpVersionManager':
                $viewModel = $this->doSharedCodeDependentAction(
                    $do,
                    $step,
                    $mgrStep,
                    $dashStep
                );
                break;

            case 'main':
            default:
                $this->main_menu();
        }

        if ( !empty($viewModel) ) {
            $this->display($viewModel);
        }

        $this->view->PageFooter();
        ob_flush();
    }

    /**
     *
     * @since 3.3.4
     *
     * @param string  $do
     * @param int     $step
     * @param int     $mgrStep
     * @param int     $dashStep
     * @return null|object  ViewModel object.
     * @throws LSCMException  Thrown indirectly.
     */
    private function doSharedCodeDependentAction($do, $step, $mgrStep,
            $dashStep)
    {
        if ( ! SHARED_CODE_LOADED
                || version_compare(ControlPanel::PANEL_API_VERSION, '1.9.7', '<')
                || ! ControlPanel::meetsMinAPIVerRequirement() ) {

            return (new WhmViewModel\SharedCodeUpdateRequiredViewModel());
        }

        $panelAPICompatStatus =
                ControlPanel::checkPanelAPICompatibility(self::SHARED_API_VER);

        switch ( $panelAPICompatStatus ) {

            case ControlPanel::PANEL_API_VERSION_TOO_HIGH:
                return (new WhmViewModel\SharedCodeUpdateRequiredViewModel());

            case ControlPanel::PANEL_API_VERSION_TOO_LOW:
            case ControlPanel::PANEL_API_VERSION_UNKNOWN:
                return (new WhmViewModel\PluginUpdateRequiredViewModel());

            //no default
        }

        $viewModel = null;

        switch ($do) {

            case 'cacheRootSetup':
                $viewModel = $this->cacheRootSetup($step);
                break;

            case 'cpanel_install':
                $this->cpanelAction('Install', $step);
                break;

            case 'cpanel_settings':
                $this->cpanel_settings($step);
                break;

            case 'cpanel_uninstall':
                $this->cpanelAction('Uninstall', $step);
                break;

            case 'dash_notifier':
                $viewModel = $this->dashNotifier($dashStep);
                break;

            case 'lscwp_manage':
                $viewModel = $this->lscwpManage($mgrStep);
                break;

            case 'lscwp_mass_enable_disable':
                $viewModel = $this->lscwpMassEnableDisableControl($step);
                break;

            case 'lscwpVersionManager':
                $viewModel = $this->lscwpVersionManager($step);
                break;

            //no default
        }

        return $viewModel;
    }

    /**
     *
     * @return boolean
     */
    private function includeWebCacheMgrBootstrap()
    {
        if ( ! file_exists(LSWS_HOME . '/add-ons/webcachemgr/autoloader.php')
                || ! include_once __DIR__ . '/webcachemgrBootstrap.php' ) {

            return false;
        }

        return true;
    }

    /**
     *
     * @since 3.3.4
     *
     * @return object  ViewModel object.
     */
    private function updateSharedCode()
    {
        $useOldMethod = false;

        if ( ! SHARED_CODE_LOADED
                || version_compare(ControlPanel::PANEL_API_VERSION, '1.9.7', '<') ) {

            $useOldMethod = true;
        }

        $updated = Util::updateSharedCode($useOldMethod);

        return (new WhmViewModel\SharedCodeUpdateResultViewModel($updated));
    }

    /**
     *
     * @return CPanel
     * @throws LSCMException  Thrown indirectly.
     */
    private function getControlPanelInstance()
    {
        /** @var CPanel $panel */
        $panel = ControlPanel::getClassInstance('\Lsc\Wp\Panel\CPanel');

        return $panel;
    }

    /**
     * Populates $info array with LiteSpeed and Apache PID entries and
     * LiteSpeed's Apache Port Offset setting value if possible.
     *
     * @param mixed[] $info
     */
    private function populateProcessAndOffsetInfo( &$info )
    {
        $spoolWarning = false;
        $portOffset = 0;

        $litespeedPID = $this->util->getLSPID();
        $apachePID = $this->util->getApachePID();

        if ( defined('LSWS_HOME') ) {
            $portOffset = $this->util->GetApachePortOffset();

            if ( Util::isServerSpooling($litespeedPID, $apachePID, $portOffset) ) {
                /**
                 * Wait a few seconds and check again to give the LiteSpeed
                 * process time to cleanup/terminate after any failed
                 * start/restart actions.
                 */
                sleep(3);
                $litespeedPID = $this->util->getLSPID();
                $apachePID = $this->util->getApachePID();
                $spoolWarning = Util::isServerSpooling(
                    $litespeedPID,
                    $apachePID,
                    $portOffset
                );
            }
        }

        $info['ls_pid'] = $litespeedPID;
        $info['ap_pid'] = $apachePID;
        $info['port_offset'] = $portOffset;
        $info['spool_warning'] = $spoolWarning;
    }

    /**
     *
     * @return void
     * @throws LSCMException  Thrown indirectly.
     */
    private function main_menu()
    {
        $info = array();

        $info['latest_whm_plugin_ver'] = Util::getLatestWhmPluginVer();

        $this->populateProcessAndOffsetInfo($info);

        if ( defined('LSWS_HOME') ) {
            $info['is_installed'] = true;

            $this->util->populateVersionInfo($info);

            $info['serial'] = $this->util->GetCurrentSerialNo();
            $info['admin_url'] = $this->util->GetAdminUrl();

            $res = $this->util->GetLicenseType();
            $info['has_cache'] = $res['has_cache'];

            if ( $info['has_cache'] && SHARED_CODE_LOADED ) {
                $vermgr = PluginVersion::getInstance();

                try {
                    $info['lscwp_curr_ver'] = $vermgr->getCurrentVersion();
                }
                catch ( LSCMException $e ) {
                    WhmPluginLogger::debug(
                        $e->getMessage()
                            . ' Could not get active LSCWP version.'
                    );

                    $info['lscwp_curr_ver'] = false;
                }

                try {
                    $info['lscwp_latest'] = $vermgr->getLatestVersion();
                }
                catch ( LSCMException $e ) {
                    WhmPluginLogger::debug(
                        $e->getMessage()
                            . ' Could not get latest LSCWP version.'
                    );

                    $info['lscwp_latest'] = false;
                }

                $lscmDataFiles = Context::getLSCMDataFiles();
                $wpInstallStorage = new WPInstallStorage(
                    $lscmDataFiles['dataFile'],
                    $lscmDataFiles['custDataFile']
                );

                $info['data_file_error'] = $wpInstallStorage->getError();
            }

            $info['file_protect_warning'] =
                $this->util->CheckFileProtectWarning();

            $eaPHP_vers = array(
                '54',
                '55',
                '56',
                '70',
                '71',
                '72',
                '73',
                '74',
                '80'
            );
            $info['timezonedb_warning'] =
                $this->util->checkTimezoneDBStatus($eaPHP_vers);

            $info['cpanel_plugin_installed'] = (
                file_exists(CPanelConf::THEME_JUPITER_PLUGIN_DIR)
                    || file_exists(CPanelConf::THEME_PAPER_LANTERN_PLUGIN_DIR)
            );
        }
        else {

            if ( $info['ls_pid'] > 0 ) {
                $this->define_home(2);
                return;
            }

            $info['is_installed'] = false;
        }

        $this->view->MainMenu($info);
    }

    /**
     *
     * @param int  $step
     * @return WhmViewModel\PhpSuExecQuickConfViewModel
     */
    private function config_lsws_suexec( $step )
    {
        $info = array();
        $fields = array( 'phpSuExec', 'phpSuExecMaxConn' );

        $c = $this->util->GetLSConfig($fields);

        if ( $step == 1 ) {

            foreach ( $fields as $f) {
                $info['cur'][$f] = $c[$f];
                $info['new'][$f] = $this->util->get_request_var($f);
            }

            $info['error'] = $this->util->Validate_ConfigSuExec($info);

            if ( empty($info['error']) ) {
                $this->util->ConfigSuExec($info);
                $c = $this->util->GetLSConfig($fields);
            }
        }

        foreach ( $fields as $f ) {

            $info['cur'][$f] = $c[$f];
        }

        return (new WhmViewModel\PhpSuExecQuickConfViewModel($info));
    }

    /**
     *
     * @param string  $action
     * @param int     $step
     */
    private function TimezoneDBAction( $action, $step )
    {
        if ( $step == 1 ) {
            $script = self::CGI_DIR . '/buildtimezone_ea4.sh';
            $cmd = "{$script} y";

            exec($cmd, $output, $return_var);

            $msgs = $this->util->getTimezoneDBMsgs($output);
            $this->view->TimezoneDBResults($action, $msgs);
        }
        elseif ( $step == 0 ) {
            $this->view->TimezoneDBConfirm($action);
        }
    }

    /**
     *
     * @param int  $mgrStep
     * @return object  View model object.
     * @throws LSCMException  Thrown indirectly.
     */
    private function lscwpManage( $mgrStep )
    {
        $panelEnv = $this->getControlPanelInstance();

        if ( !$panelEnv->areCacheRootsSet() ) {
            return (new LscViewModel\CacheRootNotSetViewModel());
        }

        $lscmDataFiles = Context::getLSCMDataFiles();
        $model = new WPInstallStorage(
            $lscmDataFiles['dataFile'],
            $lscmDataFiles['custDataFile']
        );

        $panelController = new PanelController($panelEnv, $model, $mgrStep);

        $mgrStep = $panelController->manageCacheOperations2();

        switch ($mgrStep) {
            case PanelController::MGR_STEP_SCAN:
            case PanelController::MGR_STEP_DISCOVER_NEW:
                return (new LscViewModel\ScanProgressStepViewModel($mgrStep));

            case PanelController::MGR_STEP_REFRESH_STATUS:
                return (new LscViewModel\RefreshStatusProgressViewModel());

            case PanelController::MGR_STEP_MASS_UNFLAG:
                return (new LscViewModel\UnflagAllProgressViewModel());

            default:
                return (new LscViewModel\ManageViewModel($model));
        }
    }

    /**
     *
     * @param int  $step
     * @return object  View model object.
     * @throws LSCMException  Thrown indirectly.
     */
    private function lscwpMassEnableDisableControl( $step )
    {
        $lscmDataFiles = Context::getLSCMDataFiles();
        $model = new WPInstallStorage(
            $lscmDataFiles['dataFile'],
            $lscmDataFiles['custDataFile']
        );

        if ( $step == 1 || $step == 2 ) {
            $allowedActions = array( 'enable', 'disable' );

            $action = $this->util->get_request_var('act');

            if ( in_array($action, $allowedActions) ) {
                return $this->lscwpMassEnableDisable($action, $step, $model);
            }
        }
        elseif ( $step == 0 ) {

            if ( $model->getError() != 0 ) {
                return (new LscViewModel\DataFileMsgViewModel($model));
            }

            $panelEnv = $this->getControlPanelInstance();

            if ( !$panelEnv->areCacheRootsSet() ) {
                return (new LscViewModel\CacheRootNotSetViewModel());
            }
        }

        return (new LscViewModel\MassEnableDisableViewModel($model));
    }

    /**
     *
     * @param string            $action
     * @param int               $step
     * @param WPInstallStorage  $model
     * @return LscViewModel\MassEnableDisableProgressViewModel|void
     * @throws LSCMException  Thrown indirectly.
     */
    private function lscwpMassEnableDisable( $action, $step,
            WPInstallStorage $model )
    {
        $err = $model->getError();

        if ( $err != 0 ) {
            $this->main_menu();
            return;
        }

        $panelEnv = $this->getControlPanelInstance();
        $panelController = new PanelController($panelEnv, $model);

        $panelController->massEnableDisable($action, $step);

        return (new LscViewModel\MassEnableDisableProgressViewModel($action));
    }

    /**
     *
     * @param int  $step
     * @return object  View model object.
     * @throws LSCMException  Thrown indirectly.
     */
    private function lscwpVersionManager( $step )
    {
        $lscmDataFiles = Context::getLSCMDataFiles();
        $model = new WPInstallStorage(
            $lscmDataFiles['dataFile'],
            $lscmDataFiles['custDataFile']
        );

        if ( $step == 2 || $step == 3 ) {
            $action = $this->util->get_request_var('act');

            switch ($action) {
                case 'switchTo':
                    $ver_num = $this->util->get_request_var('version_num');

                    try {
                        PluginVersion::getInstance()->setActiveVersion(
                            $ver_num
                        );
                    }
                    catch ( LSCMException $e ) {
                        $msg = $e->getMessage()
                            . " Could not switch active version to v{$ver_num}";

                        WhmPluginLogger::error($msg);
                        WhmPluginLogger::uiError($msg);
                    }

                    return (new LscViewModel\VersionManageViewModel($model));

                case 'upgradeTo':
                    $panelEnv = $this->getControlPanelInstance();
                    $panelController = new PanelController($panelEnv, $model);

                    $panelController->prepVersionChange($step);

                    return (new LscViewModel\VersionChangeViewModel());
            }
        }

        return (new LscViewModel\VersionManageViewModel($model));
    }

    /**
     * Creates a View object with the passed ViewModel and displays to screen.
     *
     * @param object  $viewModel  ViewModel object.
     * @throws WhmPluginException  Thrown indirectly.
     */
    private function display( $viewModel )
    {
        $view = new View($viewModel);

        try {
            $view->display();
        }
        catch ( WhmPluginException $e ) {
            $this->displayMissingTpl($e->getMessage());
        }
    }

    /**
     *
     * @since 3.3.4
     *
     * @param string  $msg
     * @throws WhmPluginException  Thrown indirectly.
     */
    private function displayMissingTpl( $msg )
    {
        $viewModel = new WhmViewModel\MissingTplViewModel($msg);
        $view = new View($viewModel);
        $view->display();
    }

    /**
     *
     * @param int  $step
     * @return WhmViewModel\CacheRootSetupViewModel
     * @throws LSCMException  Thrown indirectly.
     */
    private function cacheRootSetup( $step )
    {
        $panelEnv = $this->getControlPanelInstance();

        if ( $step == 1 ) {

            try {
                $panelEnv->verifyCacheSetup();
            }
            catch ( LSCMException $e ) {
                $msg = $e->getMessage();
                WhmPluginLogger::error($msg);
                WhmPluginLogger::uiError($msg);
            }
        }

        CPanelConf::verifyCpanelPluginConfFile();

        return (new WhmViewModel\CacheRootSetupViewModel($panelEnv));
    }

    /**
     *
     * @param int  $step
     */
    private function restart_lsws( $step )
    {
        $info = array();

        if ( $step == 1 ) {
            $output = array();

            $this->util->RestartLSWS($output);

            $info['output'] = $output;
        }

        $this->populateProcessAndOffsetInfo($info);

        if ( $step == 0 ) {
            $this->view->RestartLswsConfirm($info);
        }
        else {
            $this->view->RestartLsws($info);
        }
    }

    private function check_license()
    {
        $output = array();

        $info['return'] = $this->util->GetCurrentLicenseStatus($output);
        $info['output'] = $output;
        $info['serial'] = $this->util->GetCurrentSerialNo();

        $outstr = implode(' ', $output);

        if ( strpos($outstr, 'trial') > 0 ) {
            $info['lictype'] = 'trial';
        }
        elseif ( preg_match('/ -[0-9]+ /', $outstr) ) {
            $info['lictype'] = 'migrated';
        }

        $res = $this->util->GetLicenseType();

        if ( !empty($res['lic_type']) ) {
            $info['lic_type'] = $res['lic_type'];
        }

        $this->view->CheckLicense($info);
    }

    /**
     *
     * @param int  $step
     */
    private function switch_lsws( $step )
    {
        $info = array();

        $this->populateProcessAndOffsetInfo($info);

        if ( $step == 1 && $info['stop_msg'] == NULL ) {
            $info['return'] = $this->util->Switch2LSWS($output);
            $info['output'] = $output;

            /**
             * Note: PID info set here may be temporarily different than the
             * actual "final" PID info if there was a problem switching to LSWS.
             * This web server behavior is intentional.
             */
            $this->populateProcessAndOffsetInfo($info);
        }

        if ( $step == 0 ) {
            $this->view->Switch2LswsConfirm($info);
        }
        else {
            $this->view->Switch2Lsws($info);
        }
    }

    /**
     *
     * @param int  $step
     */
    private function switch_apache( $step )
    {
        $info = array();

        $this->populateProcessAndOffsetInfo($info);

        if ( $step == 1 && $info['stop_msg'] == NULL ) {
            $info['return'] = $this->util->Switch2Apache($output);
            $info['output'] = $output;

            $this->populateProcessAndOffsetInfo($info);
        }

        if ( $step == 0 ) {
            $this->view->Switch2ApacheConfirm($info);
        }
        else {
            $this->view->Switch2Apache($info);
        }
    }

    /**
     *
     * @param int $step
     */
    private function change_port_offset( $step )
    {
        $info = array();

        $this->populateProcessAndOffsetInfo($info);

        if ( $step == 1 ) {
            $info['new_port_offset'] =
                $this->util->get_request_var('port_offset');
            $info['error'] = $this->util->Validate_NewPortOffset(
                $info['new_port_offset'],
                $info['port_offset']
            );

            if ( $info['error'] != NULL ) {
                $step = 0;
            }
            else {
                $info['return'] = $this->util->ChangePortOffset(
                    $info['new_port_offset'],
                    $output
                );
                $info['output'] = $output;

                if ( $info['return'] == 0 ) {
                    /**
                     * Purposely not used.
                     */
                    $output2 = array();

                    $this->util->RestartLSWS($output2);
                }
            }
        }

        if ( $step == 0 ) {
            $this->view->ChangePortOffsetConfirm($info);
        }
        else {
            $this->view->ChangePortOffset($info);
        }
    }

    /**
     *
     * @param string  $action
     * @param int     $step
     */
    private function cpanelAction( $action, $step )
    {
        $prefix = "cpanel{$action}";

        if ( $step == 1 ) {

            $funcName = "{$prefix}Plugin";

            $this->util->$funcName();
        }

        if ( $step == 0 ) {
            $funcName = "{$prefix}Confirm";
        }
        else {
            $funcName = "{$prefix}Complete";
        }

        $this->view->$funcName();
    }

    /**
     *
     * @param int  $step
     */
    private function cpanel_settings( $step )
    {
        $cPanelConf = new CPanelConf();

        if ( $step == 1 ) {

            if ( Util::get_request_var('cpanelAutoInstall') === NULL ) {
                $autoInstall = 0;
            }
            else {
                $autoInstall = 1;
            }

            $cPanelConf->setAutoInstallUse($autoInstall);

            $genEcCerts = (int)Util::get_request_var('genEcCert', 0);

            $cPanelConf->setGenerateEcCerts($genEcCerts);

            if ( Util::get_request_var('useCustTpl') === NULL ) {
                $useCustTpl = false;
            }
            else {
                $useCustTpl = true;
            }

            $newCustTpl = Util::get_request_var('custTpl');

            if ( $newCustTpl === NULL ) {
                $newCustTpl = '';
            }

            $isNewCustTplValid = true;

            if ( $useCustTpl ) {

                if ( !$cPanelConf->setTplName($newCustTpl)
                        || $cPanelConf->getData(CPanelConf::FLD_CUST_TPL_NAME) == '' ) {

                    $isNewCustTplValid = false;
                }
            }
            elseif ( $newCustTpl === '' ) {
                /**
                 * Clear old saved template name if set.
                 */
                $cPanelConf->clearTplName();
            }

            if ( $isNewCustTplValid ) {
                $cPanelConf->setTplUse($useCustTpl);
            }

            $cPanelConf->trySaveConf();
        }

        $this->util->add_error_msg($cPanelConf->getErrMsgs());
        $this->util->add_success_msg($cPanelConf->getSuccMsgs());

        $this->view->cpanelSettings($cPanelConf);
    }

    /**
     *
     * @param int  $step
     */
    private function uninstall_lsws( $step )
    {
        $info = array();

        $this->populateProcessAndOffsetInfo($info);

        /**
         * check if go ahead
         */
        if ( $info['ap_pid'] == 0 ) {
            $info['stop_msg'] = 'Apache is not running. Please use the '
                . '<strong>Switch to Apache</strong> option before '
                . 'uninstalling LiteSpeed.';
        }

        if ( $step == 1 && $info['stop_msg'] == NULL ) {
            $keepConf =
                ($this->util->get_request_var('keep_conf') == '1') ? 'Y' : 'N';
            $keepLog =
                ($this->util->get_request_var('keep_log') == '1') ? 'Y' : 'N';
            $info['return'] = $this->util->UninstallLSWS(
                $keepConf,
                $keepLog,
                $output
            );
            $info['output'] = $output;
        }

        if ( $step == 0 || $info['stop_msg'] != NULL ) {
            $this->view->UninstallLswsPrepare($info);
        }
        else {
            $this->view->UninstallLsws($info);
        }
    }

    /**
     *
     * @param int  $step
     * @return void
     * @throws LSCMException  Thrown indirectly.
     */
    private function define_home( $step )
    {
        $info = array();

        if ( $step == 1 ) {
            $info['lsws_home_input'] = $this->util->get_request_var(
                'lsws_home_input'
            );
            $info['error'] = $this->util->Validate_LSWS_HOME(
                $info['lsws_home_input'],
                true
            );

            if ( $info['error'] == NULL ) {
                $this->saveLSWS_HOME($info['lsws_home_input']);

                if ( $this->includeWebCacheMgrBootstrap() ) {
                    define('SHARED_CODE_LOADED', true);

                    /**
                     * Use log file name set in shared code.
                     */
                    WhmPluginLogger::changeLogFileUsed(
                        LSWS_HOME . '/logs/' . ContextOption::LOG_FILE_NAME
                    );

                    CPanelConf::verifyCpanelPluginConfFile();
                }

                if ( ! defined('SHARED_CODE_LOADED') ) {
                    define('SHARED_CODE_LOADED', false);
                }

                $this->main_menu();
                return;
            }
        }
        elseif ( $step == 2 ) {
            //redirect from mainmenu
            $info['do_action'] = 'define_home';
        }

        if ( ! defined('SHARED_CODE_LOADED') ) {
            define('SHARED_CODE_LOADED', false);
        }

        if ( $info['lsws_home_input'] == null ) {
            $info['lsws_home_input'] = $this->util->DetectLSWS_HOME();
        }

        $this->view->DefineHome($info);
    }

    /**
     *
     * @param int  $step
     */
    private function install_lsws( $step )
    {
        $info = array();

        if ( $step == 0 ) {
            /**
             * Populate default values
             */

            $info['license_agree'] = '';
            $info['install_type'] = '';
            $info['serial_no'] = '';
            $info['lsws_home_input'] = '/usr/local/lsws';
            $info['port_offset'] = '0';
            $info['admin_email'] = 'root@localhost';
            $info['admin_login'] = 'admin';
            $info['admin_pass'] = '';
            $info['admin_pass1'] = '';
        }
        else {
            $info['php_suexec'] = 2;

            $info['license_agree'] =
                $this->util->get_request_var('license_agree');
            $info['install_type'] =
                $this->util->get_request_var('install_type');
            $info['serial_no'] = $this->util->get_request_var('serial_no');
            $info['lsws_home_input'] =
                $this->util->get_request_var('lsws_home_input');
            $info['port_offset'] = $this->util->get_request_var('port_offset');

            $email = $this->util->get_request_var('admin_email');
            $emails = array_map(
                'trim',
                preg_split("/\s*,\s*/", $email, -1, PREG_SPLIT_NO_EMPTY)
            );
            $info['admin_email'] = implode(', ', $emails);

            $info['admin_login'] = $this->util->get_request_var('admin_login');
            $info['admin_pass'] = $this->util->get_request_var('admin_pass');
            $info['admin_pass1'] = $this->util->get_request_var('admin_pass1');
            $info['error'] = $this->util->Validate_InstallInput($info);

            if ( $info['error'] == NULL ) {

                if ( $info['install_type'] == 'trial' ) {
                    $info['serial_no'] = 'TRIAL';
                }

                $info['return'] = $this->util->InstallLSWS($info, $output);
                $info['output'] = $output;

                if ( $info['return'] == 0 ) {
                    $this->saveLSWS_HOME($info['lsws_home_input']);
                    $this->populateProcessAndOffsetInfo($info);
                }
            }
        }

        if ( $step == 0 || $info['error'] != NULL ) {
            $this->view->InstallLswsPrepare($info);
        }
        else {
            $this->view->InstallLsws($info);
        }
    }

    /**
     *
     * @param int  $step
     */
    private function change_license( $step )
    {
        $info = array();

        if ( $step == 0 ) {
            /**
             * Populate defaults.
             */
            $info['license_agree'] = '';
            $info['install_type'] = '';
            $info['serial_no'] = '';
        }
        else {
            $info['license_agree'] =
                $this->util->get_request_var('license_agree');
            $info['install_type'] =
                $this->util->get_request_var('install_type');
            $info['serial_no'] = $this->util->get_request_var('serial_no');
            $info['error'] = $this->util->Validate_ChangeLicenseInput($info);

            if ( $info['error'] == NULL ) {

                if ( $info['install_type'] == 'trial' ) {
                    $info['serial_no'] = 'TRIAL';
                }

                $output = array();

                $info['return'] =
                        $this->util->ChangeLicense($info['serial_no'], $output);
                $info['output'] = $output;
            }
        }

        if ( $step == 0 || $info['error'] != NULL ) {
            $this->view->ChangeLicensePrepare($info);
        }
        else {
            $this->populateProcessAndOffsetInfo($info);

            $this->view->ChangeLicense($info);
        }
    }

    /**
     *
     * @param int  $step
     */
    private function transfer_license( $step )
    {
        $output = array();

        if ( $step == 0 ) {
            $info['licstatus_return'] =
                $this->util->GetCurrentLicenseStatus($output);
            $info['licstatus_output'] = $output;
            $info['error'] = $this->util->Validate_LicenseTransfer($info);

            $this->view->TransferLicenseConfirm($info);
        }
        else {
            $info['return'] = $this->util->TransferLicense($output);
            $info['output'] = $output;

            $this->view->TransferLicense($info);
        }
    }

    /**
     *
     * @param int  $step
     * @return WhmViewModel\LswsVersionManagerViewModel
     */
    private function versionManager( $step )
    {
        if ( $step == 2 ) {
            $info['act'] = $this->util->get_request_var('act');
            $info['actId'] = $this->util->get_request_var('actId');
            $error = $this->util->Validate_VersionManage($info);

            if ( $error != NULL ) {
                WhmPluginLogger::uiError($error);
            }
            else {
                $this->util->VersionManage($info['act'], $info['actId']);
            }
        }

        return (new WhmViewModel\LswsVersionManagerViewModel($this->util));
    }

    /**
     *
     * @since 4.1.3
     *
     * @return WhmViewModel\UpdateWhmPluginResultViewModel
     */
    private function updateWhmPlugin()
    {
        Util::updateToLatestWhmPlugin();

        return (new WhmViewModel\UpdateWhmPluginResultViewModel());
    }

    /**
     *
     * @param int  $step
     * @return WhmViewModel\RestartDetachedPHPViewModel
     */
    private function restartDetachedPHP( $step )
    {
        if ( $step == WhmViewModel\RestartDetachedPHPViewModel::STEP_DO_ACTION ) {
            $this->util->restartDetachedPHP();
        }

        return (new WhmViewModel\RestartDetachedPHPViewModel($step));
    }

    /**
     *
     * @param int  $dashStep
     * @return object  ViewModel object.
     * @throws LSCMException  Thrown indirectly.
     */
    private function dashNotifier( $dashStep )
    {
        $panelEnv = $this->getControlPanelInstance();

        $lscmDataFiles = Context::getLSCMDataFiles();
        $wpInstallStorage = new WPInstallStorage(
            $lscmDataFiles['dataFile'],
            $lscmDataFiles['custDataFile']
        );

        $panelController = new PanelController($panelEnv, $wpInstallStorage);

        $wpDashMsgs = new WPDashMsgs();

        $dashStep =
                $panelController->manageDashOperations($dashStep, $wpDashMsgs);

        switch ( $dashStep ) {
            case PanelController::DASH_STEP_MASS_DASH_NOTIFY:
                $viewModel = new LscViewModel\MassDashNotifyProgressViewModel();
                break;
            case PanelController::DASH_STEP_MASS_DASH_DISABLE:
                $viewModel =
                    new LscViewModel\MassDashDisableProgressViewModel();
                break;
            default:
                $viewModel = new LscViewModel\DashNotifierViewModel(
                    $wpInstallStorage,
                    $wpDashMsgs
                );
        }

        return $viewModel;
    }

}
