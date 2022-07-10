<?php

/** ******************************************
 * LiteSpeed Web Server Plugin for WHM
 *
 * @author: LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright: (c) 2013-2020
 * ******************************************* */

namespace LsPanel;

use \Lsc\Wp\WPInstallStorage;
use \LsPanel\WhmMod_LiteSpeed_CPanelConf as CPanelConf;

class WhmMod_LiteSpeed_View
{

    private $icons;
    public static $isDone = false;

    public function __construct()
    {
        define('CP_TOKEN', $_ENV['cp_security_token']);
        define('MODULE_URL', '/cgi/lsws/');

        $this->css = array(
            'ie6' => '/themes/x/ie6.css'
        );

        $this->icons = array(
            'm_logo_lsws' => 'static/icons/Logo_centered.svg',
            'm_server_version' => 'static/icons/lsCurrentVersion.svg',
            'm_server_install' => 'static/icons/install.png',
            'm_server_php' => 'static/icons/suexec_conf.svg',
            'm_server_buildphp' => 'static/icons/buildMatchingLSPHP.svg',
            'm_server_definehome' => 'static/icons/lsws-home.png',
            'm_server_uninstall' => 'static/icons/uninstall.svg',
            'm_cache_root_setup' => 'static/icons/cacheRootSetup.svg',
            'm_cache_manage' => 'static/icons/manageCacheInstallations.svg',
            'm_cache_mass_op' => 'static/icons/massEnableDisableCache.svg',
            'm_cache_ver_manage' => 'static/icons/lscwpCurrentVersion.svg',
            'm_dash_notifier' => 'static/icons/wpNotifier.svg',
            'm_control_config' => 'static/icons/lsConfiguration.svg',
            'm_control_config_suexec' => 'static/icons/suexec_conf.svg',
            'm_control_restart' => 'static/icons/restartLs.svg',
            'm_control_restart_php' => 'static/icons/restartDetachedPHP.svg',
            'm_license_check' => 'static/icons/licenseStatus.svg',
            'm_license_change' => 'static/icons/changeLicense.svg',
            'm_license_transfer' => 'static/icons/transferLicense.svg',
            'm_switch_apache' => 'static/icons/switchToApache.svg',
            'm_switch_lsws' => 'static/icons/switchToLiteSpeed.svg',
            'm_switch_port' => 'static/icons/changePortOffset.svg',
            'm_cpanel_install' => 'static/icons/cPanelInstall.png',
            'm_cpanel_uninstall' => 'static/icons/cPanelUninstall.png',
            'm_cpanel_settings' => 'static/icons/cPanelSettings.png',
        );
    }

    public function PageHeader( $do )
    {
        $buf = <<<EEN
<html>
  <head>
    <title>LiteSpeed Web Server - WHM Plugin</title>
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!--[if lt IE 7]>
      <link rel="stylesheet" href="{$this->css['ie6']}" />
    <![endif]-->
    <!--[if IE]>
      <style type="text/css">
        h3{font-size:11px;}
      </style>
    <![endif]-->
  </head>
  <body>
    <div id="lsws-container">
      <form name="lswsform">
        <input type="hidden" name="step" value="1"/>
        <input type="hidden" name="do" value="{$do}"/>
EEN;
        echo $buf;
    }

    public function PageFooter()
    {
        echo "</form></div></body></html>\n";
    }

    private function tool_list_block( $list )
    {
        $buf = '<div class="tools-list">';

        foreach ( $list as $li ) {
            $buf .= '<div class="item" role="page"><a class="item-link" ';

            if ( $li['link'] ) {
                $buf .= "href=\"{$li['link']}\" ";
            }

            if ( substr($li['link'], 0, 4) == 'http' ) {
                $buf .= 'target="_blank" rel="noopener noreferrer" ';
            }

            $buf .= 'title="';

            $buf .= ($li['info'] != '') ? $li['info'] : $li['name'];

            $buf .= '">';

            if ( $li['icon'] != '' ) {
                $buf .= "<img class=\"itemImageWrapper\" src=\"{$li['icon']}\"></img>";
            }

            $buf .= "<span class=\"itemTextWrapper\">{$li['name']}</span></a></div>";
        }

        $buf .= "</div>\n";

        return $buf;
    }

    public function MainMenu( $info )
    {
        $buf = '<div id="heading"><div class="center"><img class="header-logo" '
                . 'alt="LiteSpeed Web Server" '
                . "src=\"{$this->icons['m_logo_lsws']}\" "
                . 'onclick="window.open(\'https://www.litespeedtech.com\')" >'
                . '</div></div>';

        //if ( $info['latest_whm_plugin_ver'] != WhmMod_LiteSpeed_ControlApp::MODULE_VERSION ) {
        //    $buf .= $this->info_msg(
        //        'LiteSpeed Web Server WHM plugin v'
        //            . $info['latest_whm_plugin_ver'] . ' is available. '
        //            . "<a href =\"?do=updateWhmPlugin\">Update Now</a>"
        //    );
        //}

        $buf .= $this->show_running_status($info);

    //    if ( $info['spool_warning'] == true ) {
    //        $buf .= $this->getSpoolWarning();
    //    }

      //  $buf .= $this->checkFileProtectStatus($info['file_protect_warning']);

    //    if ( isset($info['timezonedb_warning']) ) {
     //       $buf .= $this->checkTimezoneDBStatus($info['timezonedb_warning']);
      //  }

      //  if ( isset($info['data_file_error']) ) {
       //     $buf .= $this->checkDataFile($info['data_file_error']);
       // }

        $buf .= '<div>';

        if ( $info['is_installed'] ) {

            $buf .= $this->section_title('Manage LiteSpeed Web Server');
            $list = array();

            $li_version = array(
                'icon' => $this->icons['m_server_version'],
                'link' => '',
                'name' => "Current Version: {$info['lsws_version']}",
                'info' => 'Version Management: upgrade/downgrade/force '
                        . 'reinstall.'
            );

            if ( !empty($info['lsws_build']) ) {
                $li_version['name'] .= " (build {$info['lsws_build']})";
            }

            $li_version['name'] .= '<div class="release-alert small red">';

         //   if ( !empty($info['new_build']) ) {
         //       $li_version['name'] .=
        //                "<span>Latest Build: {$info['new_build']}</span><br />";
      //      }

    //        if ( $info['new_version'] != ''
  //                  && $info['new_version'] != $info['lsws_version'] ) {
//
          //      $li_version['name'] .=
          //              "<span>Latest Release: {$info['new_version']}</span>";
          //  }

            $li_version['name'] .= '</div>';

            $list[] = $li_version;

//            $list[] = array(
  //              'icon' => $this->icons['m_control_config'],
    //            'link' => '?do=config_lsws',
      //          'name' => 'LiteSpeed Configuration',
        //        'info' => 'Configure LiteSpeed settings.'
         //   );

            $list[] = array(
                'icon' => $this->icons['m_control_restart'],
                'link' => '?do=restart_lsws',
                'name' => 'Restart LiteSpeed',
                'info' => 'Gracefully restart LiteSpeed.'
            );

     //       $list[] = array(
       //         'icon' => $this->icons['m_control_restart_php'],
         //       'link' => '?do=restart_detached_php',
           //     'name' => 'Restart Detached PHP Processes.',
             //   'info' => ''
           // );


            $buf .= $this->tool_list_block($list);

            $cache_check = $manage_link = $massEnableDisable_link
                    = $verManager_link = '';

            if ( $info['has_cache'] == WhmMod_LiteSpeed_Util::LSCACHE_STATUS_UNKNOWN ) {
                $cache_check =
                    '(Please start LiteSpeed to access these features)';
            }
            elseif ( $info['has_cache'] == WhmMod_LiteSpeed_Util::LSCACHE_STATUS_MISSING ) {
                $cache_check = '(This feature requires '
                        . '<a href="https://docs.litespeedtech.com/licenses/how-to/#add-cache-to-an-existing-license" '
                        . 'target="_blank" '
                        . 'rel="noopener noreferrer">LSCache</a>)';
            }
            elseif ( $info['has_cache'] == WhmMod_LiteSpeed_Util::LSCACHE_STATUS_DETECTED ) {
                $manage_link = '?do=lscwp_manage';
                $massEnableDisable_link = '?do=lscwp_mass_enable_disable';
                $verManager_link = '?do=lscwpVersionManager';
            }

            if ( $info['has_cache'] != WhmMod_LiteSpeed_Util::LSCACHE_STATUS_NOT_SUPPORTED ) {
                $title = 'LiteSpeed Cache For WordPress Management '
                        . $cache_check;

                $buf .= $this->section_title($title);
                $list = array();

                $list[] = array(
                    'icon' => $this->icons['m_cache_manage'],
                    'link' => $manage_link,
                    'name' => 'Manage Cache Installations',
                    'info' => 'Enable/Disable cache or set a flag for known '
                            . 'WordPress installations.'
                );

                $list[] = array(
                    'icon' => $this->icons['m_cache_mass_op'],
                    'link' => $massEnableDisable_link,
                    'name' => 'Mass Enable/Disable Cache',
                    'info' => 'Enable/Disable cache for all non-flagged '
                            . 'WordPress installations'
                );

                $ver_mgr = array(
                    'icon' => $this->icons['m_cache_ver_manage'],
                    'link' => $verManager_link,
                    'info' => 'Change active cache plugin version or force a '
                            . 'version change for existing installations.'
                );

                if ( $info['lscwp_curr_ver'] == false ) {
                    $ver_mgr['name'] = 'LSCWP Version Manager';
                }
                else {
                    $ver_mgr['name'] = 'Current Version: '
                            . htmlspecialchars($info['lscwp_curr_ver']);

                    if ( $info['lscwp_latest'] != false
                            && $info['lscwp_latest'] != $info['lscwp_curr_ver'] ) {

                        $ver_mgr['name'] .= '<br /><span class="small red">'
                                . 'Latest Release: '
                                . htmlspecialchars($info['lscwp_latest'])
                                . '</span>';
                    }
                }

                $list[] = $ver_mgr;

                $list[] = array(
                    'icon' => $this->icons['m_dash_notifier'],
                    'link' => '?do=dash_notifier',
                    'name' => 'WordPress Dash Notifier',
                    'info' => 'Recommend a plugin or broadcast a message to '
                            . 'all discovered WordPress installations.'
                );

                $list[] = array(
                    'icon' => WhmMod_LiteSpeed_ControlApp::ICON_DIR
                            . '/quicCloudIps.svg',
                    'link' => '?do=quicCloudIps',
                    'name' => 'QUIC.cloud IPs',
                    'info' => 'View QUIC.cloud related IPs.'
                );

                $buf .= $this->tool_list_block($list);
            }

           // $buf .= $this->section_title('License Management');
           // $list = array();

            if ($info['serial'] == 'TRIAL') {
                $serial = '15-Day Trial License';
            }
            else {
                $serial = $info['serial'];
            }

           // $list[] = array(
           //     'icon' => $this->icons['m_license_check'],
           //     'link' => '?do=check_license',
           //     'name' => 'License Status <br/>'
           //             . "<span class=\"small cornflower-blue\">{$serial}"
           //             . '</span>',
           //     'info' => 'Check/Refresh current license.'
           // );

            //$list[] = array(
            //    'icon' => $this->icons['m_license_change'],
            //    'link' => '?do=change_license',
            //    'name' => 'Change License',
            //    'info' => 'Switch to another license.'
            //);

            if ( $info['serial'] != 'TRIAL' ) {

               // $list[] = array(
               //     'icon' => $this->icons['m_license_transfer'],
               //     'link' => '?do=transfer_license',
               //     'name' => 'Transfer License',
               //     'info' => 'Start license migration. Frees license for '
               //             . 'registration on another server while leaving '
               //             . 'license active on the current server for a '
               //             . 'limited time.'
               // );
            }

            //$buf .= $this->tool_list_block($list);

       //     $buf .= $this->section_title('Switch between Apache and LiteSpeed');
       //     $list = array();

      //      $list[] = array(
      //          'icon' => $this->icons['m_switch_apache'],
      //          'link' => '?do=switch_apache',
      //          'name' => 'Switch to Apache',
      //          'info' => 'Use Apache as main web server. This will update rc '
      //                  . 'scripts.'
      //      );

        //    $list[] = array(
         //       'icon' => $this->icons['m_switch_lsws'],
           //     'link' => '?do=switch_lsws',
             //   'name' => 'Switch to LiteSpeed',
               // 'info' => 'Use LiteSpeed as main web server. This will update '
              //          . 'rc scripts.'
            //);

           // $list[] = array(
            //    'icon' => $this->icons['m_switch_port'],
             //   'link' => '?do=change_port_offset',
              //  'name' => 'Change Port Offset',
               // 'info' => "LiteSpeed port offset is {$info['port_offset']}. "
                //        . 'This allows LiteSpeed and Apache to run in parallel.'
            //);

          //  $buf .= $this->tool_list_block($list);

            $buf .= $this->section_title('cPanel Plugin');
            $list = array();

            $list[] = array(
                'icon' => $this->icons['m_cpanel_settings'],
                'link' => '?do=cpanel_settings',
                'name' => 'Settings',
                'info' => 'LiteSpeed cPanel Plugin settings.'
            );

            if ( $info['cpanel_plugin_installed'] ) {

                $list[] = array(
                    'icon' => $this->icons['m_cpanel_uninstall'],
                    'link' => '?do=cpanel_uninstall',
                    'name' => 'Uninstall',
                    'info' => 'Uninstall the LiteSpeed cPanel Plugin.'
                );
            }
            else {

                $list[] = array(
                    'icon' => $this->icons['m_cpanel_install'],
                    'link' => '?do=cpanel_install',
                    'name' => 'Install',
                    'info' => 'Install the LiteSpeed cPanel Plugin.'
                );
            }

            $buf .= $this->tool_list_block($list);
        }
        else {
            $buf .= $this->section_title('Install LiteSpeed Web Server');
            $list = array();

            $list[] = array(
                'icon' => $this->icons['m_server_install'],
                'link' => '?do=install_lsws',
                'name' => 'Install LiteSpeed Web Server',
                'info' => 'Download and install the latest stable release.'
            );

            $list[] = array(
                'icon' => $this->icons['m_server_definehome'],
                'link' => '?do=define_home',
                'name' => 'Define LSWS_HOME',
                'info' => 'If you installed LiteSpeed Web Server before '
                        . 'installing this plugin, please specify your '
                        . 'LSWS_HOME direcotry before using the plugin.'
            );

            $buf .= $this->tool_list_block($list);
        }

        //$buf .= '<p id="info-tag">This plugin is developed by LiteSpeed '
          //      . 'Technologies.<br />LiteSpeed Web Server Plugin for WHM '
            //    . '<a href="https://www.litespeedtech.com/products/litespeed-web-server/control-panel-plugins/release-log" '
              //  . 'title="Release Log" target="_blank" '
              //  . 'rel="noopener norefferer">v'
              //  . WhmMod_LiteSpeed_ControlApp::MODULE_VERSION . '</a></p>'
              //  . '</div>';
$buf .= "</div>";
        echo $buf;
    }

    public function RestartLswsConfirm( $info )
    {
        $buf = $this->screen_title('Confirm Operation... Restart LiteSpeed',
                $this->icons['m_control_restart']);

        $buf .= $this->show_running_status($info);

        if ( $info['spool_warning'] == true ) {
            $buf .= $this->getSpoolWarning();
        }

        $goNext = 'Restart';

        $msg = array();

        if ( $info['port_offset'] != 0 ) {
            $msg[] = "Apache port offset is {$info['port_offset']}.";
            $msg[] = 'LiteSpeed will be running in parallel with Apache. When you are ready to '
                    . 'replace Apache with LiteSpeed, use the <b>Switch to LiteSpeed</b> option.';
        }

        if ( $info['ap_pid'] > 0 && $info['port_offset'] == 0 ) {
            /**
             * use switch no matter lsws run or not
             */
            $msg[] = 'Apache port offset is 0. If you wish to use LiteSpeed as your main web server, '
                    . 'please use the <b>Switch to LiteSpeed</b> option.';
            $msg[] = 'If you need to run LiteSpeed in parallel with Apache, please use the '
                    . '<b>Change Port Offset</b> option.';
            $goNext = NULL;
        }
        else {
            $msg[] = 'This will do a graceful restart of LiteSpeed Web Server.';
        }

        $buf .= $this->div_p_msg($msg);
        $buf .= $this->button_panel_back_next('Cancel', $goNext);

        echo $buf;
    }

    public function RestartLsws( $info )
    {
        $buf = $this->screen_title('Restart LiteSpeed', $this->icons['m_control_restart']);
        $buf .= $this->show_running_status($info);

        if ( $info['spool_warning'] == true ) {
            $buf .= $this->getSpoolWarning();
        }

        if ( $info['ls_pid'] > 0 ) {
            $buf .= $this->success_msg('LiteSpeed restarted successfully.');
        }
        else {
            $buf .= $this->error_msg($info['output'],
                    'LiteSpeed is not running! Please check the web server log file for errors.');
        }

        $buf .= $this->button_panel_back_next('OK');

        echo $buf;
    }

    private function extractMsgs( $msgs )
    {
        $buf = '';

        if ( !empty($msgs['succ']) ) {
            $buf .= $this->success_msg($msgs['succ']);
        }

        if ( !empty($msgs['warn']) ) {
            $buf .= $this->warning_msg($msgs['warn']);
        }

        if ( !empty($msgs['err']) ) {
            $buf .= $this->warning_msg($msgs['err']);
        }

        return $buf;
    }

    public function TimezoneDBResults( $action, $msgs )
    {
        $title = '';

        if ( $action == 'install' ) {
            $title = 'Install Missing TimezoneDB Extensions';
        }
        elseif ( $action == 'upgrade' ) {
            $title = 'Update TimezoneDB Extensions';
        }

        $buf = $this->screen_title($title, $this->icons['m_server_php']);

        $buf .= $this->extractMsgs($msgs);

        $buf .= $this->button_panel_back_next('OK');

        echo $buf;
    }

    public function TimezoneDBConfirm( $action )
    {
        $buf = '';
        $msg = array();

        if ( $action == 'install' ) {
            $title = 'Confirm Operation... Install Missing TimezoneDB Extensions';

            $buf = $this->screen_title($title, $this->icons['m_server_php']);

            $msg[] = 'Some pre-built EasyApache PHP installations may be missing the '
                    . 'timezoneDB extension. This will severely impact PHP performance.';

            $msg[] = 'By confirming below, the missing timezoneDB extensions will be added to your '
                    . 'EasyApache PHP installations automatically. Any existing timezoneDB '
                    . 'extensions will be updated to the latest version if a newer version is available';
        }
        elseif ( $action == 'update' ) {
            $buf = $this->screen_title('Confirm Operation... Update TimezoneDB Extensions',
                    $this->icons['m_server_php']);

            $msg[] = 'Update detected for installed EasyApache PHP timezoneDB extension(s).';

            $msg[] = 'By confirming below, all timezoneDB extensions will be updated to the '
                    . 'latest versions automatically.';
        }

        $buf .= $this->info_msg($msg);
        $buf .= $this->button_panel_back_next('Cancel', 'Confirm');

        echo $buf;
    }

    public function Switch2LswsConfirm( $info )
    {
        $buf = $this->screen_title('Confirm Operation... Switch to LiteSpeed',
                $this->icons['m_switch_lsws']);
        $buf .= $this->show_running_status($info);

        if ( $info['spool_warning'] == true ) {
            $buf .= $this->getSpoolWarning();
        }

        if ( $info['stop_msg'] != NULL ) {
            $buf .= $this->error_msg($info['stop_msg']);
            $buf .= $this->button_panel_back_next('OK');
        }
        else {
            $msg = array();

            if ( $info['ap_pid'] > 0 ) {
                $msg[] = 'This action will stop Apache and restart LiteSpeed as the main web server. '
                        . 'It may take a little while for Apache to stop completely.';
            }

            if ( $info['port_offset'] != 0 ) {
                $warn = "Apache port offset is {$info['port_offset']}. This action will change port "
                        . "offset to 0.";
                $buf .= $this->warning_msg($warn);
            }

            $msg[] = 'This will restart <strong>LiteSpeed as main web server</strong>!';

            $buf .= $this->div_p_msg($msg);
            $buf .= $this->button_panel_back_next('Cancel', 'Switch to LiteSpeed');
        }

        echo $buf;
    }

    public function Switch2Lsws( $info )
    {
        $buf = $this->screen_title('Switch To LiteSpeed', $this->icons['m_switch_lsws']);
        $buf .= $this->show_running_status($info);

        if ( $info['spool_warning'] == true ) {
            $buf .= $this->getSpoolWarning();
        }

        if ( $info['stop_msg'] != NULL ) {
            $buf .= $this->error_msg($info['stop_msg']);
        }
        else {
            $out = $info['output'];

            if ( $info['port_offset'] != 0 ) {
                $out[] = 'Failed to set Apache port offset to 0. Please check config file permissions.';
            }
            else {
                $out[] = 'Apache port offset has been set to 0.';
            }

            if ( $info['ls_pid'] > 0 ) {
                $buf .= $this->success_msg($out, 'Switched to LiteSpeed Successfully');
            }
            else {
                $buf .= $this->error_msg($out, 'Failed to bring up LiteSpeed');
            }
        }

        $buf .= $this->button_panel_back_next('OK');

        echo $buf;
    }

    public function Switch2ApacheConfirm( $info )
    {
        $buf = $this->screen_title('Confirm Operation... Switch to Apache',
                $this->icons['m_switch_apache']);
        $buf .= $this->show_running_status($info);

        if ( $info['spool_warning'] == true ) {
            $buf .= $this->getSpoolWarning();
        }

        if ( $info['stop_msg'] != NULL ) {
            $buf .= $this->error_msg($info['stop_msg']);
            $buf .= $this->button_panel_back_next('OK');
        }
        else {

            $msg = array();

            if ( $info['ls_pid'] > 0 ) {
                $msg[] = 'This action will stop LiteSpeed and restart Apache as the main web server. '
                        . 'It may take a little while for LiteSpeed to stop completely.';
            }

            $msg[] = 'This will restart <strong>Apache as main web server</strong>!';
            $buf .= $this->div_p_msg($msg);
            $buf .= $this->button_panel_back_next('Cancel', 'Switch to Apache');
        }

        echo $buf;
    }

    public function Switch2Apache( $info )
    {
        $buf = $this->screen_title('Switch To Apache', $this->icons['m_switch_apache']);
        $buf .= $this->show_running_status($info);

        if ( $info['spool_warning'] == true ) {
            $buf .= $this->getSpoolWarning();
        }

        if ( $info['stop_msg'] != NULL ) {
            $buf .= $this->error_msg($info['stop_msg']);
        }
        else {

            if ( $info['return'] != 0 ) {
                $buf .= $this->info_msg($info['output']);

                $msg = array();

                $msg[] = 'This may be due to a configuration error. To manually check this problem, '
                        . 'please ssh to your server.';
                $msg[] = 'Use the following steps to manually switch to Apache:';
                $msg[] = 'Stop LiteSpeed if lshttpd still running: <code>pkill -9 litespeed </code>';
                $msg[] = 'Restore Apache httpd if /usr/sbin/httpd_ls_bak exists: '
                        . '<code>mv -f /usr/sbin/httpd_ls_bak /usr/sbin/httpd</code>';
                $msg[] = 'Run the Apache restart command manually: '
                        . '<code>service httpd restart</code> and check for errors.';
                $buf .= $this->error_msg($msg, 'Failed to switch to Apache');
            }
            else {
                $buf .= $this->success_msg($info['output'],
                        'Switched to Apache Successfully');
            }
        }

        $buf .= $this->button_panel_back_next('OK');

        echo $buf;
    }

    public function ChangePortOffsetConfirm( $info )
    {
        $buf = $this->screen_title('Confirm Operation... Change LiteSpeed Port Offset',
                $this->icons['m_switch_port']);
        $buf .= $this->show_running_status($info);

        if ( $info['spool_warning'] == true ) {
            $buf .= $this->getSpoolWarning();
        }

        $buf .= '<div class="indent-box"><div class="hint"><p>Port offset allows you to run Apache '
                . 'and LiteSpeed in parallel by running LiteSpeed on a separate port.</p><p>For '
                . 'example, if Apache is running on port 80 and the LiteSpeed port offset is 2000, then '
                . 'you will be able to access LiteSpeed-powered web pages on port 2080.</p></div>'
                . '</div>';

        $warn = array();

        if ( $info['port_offset'] == 0 && $info['ap_pid'] == 0 ) {
            $warn[] = 'Apache is currently not running. We suggest your first <strong>switch to '
                    . 'Apache </strong> to avoid server down time.';
        }

        $buf .= $this->warning_msg($warn);

        $buf .= $this->section_title('Change Port Offset');

        $hint = "Current Port Offset is {$info['port_offset']}.";
        $input = $this->input_text('port_offset', $info['new_port_offset']);
        $buf .= $this->form_row_box('Set new port offset', $input, $info['error'], $hint);

        $buf .= $this->button_panel_back_next('Cancel', 'Change');

        echo $buf;
    }

    public function ChangePortOffset( $info )
    {
        $buf = $this->screen_title('Change LiteSpeed Port Offset',
                $this->icons['m_switch_port']);

        if ( $info['return'] != 0 ) {
            $buf .= $this->error_msg($info['output'], 'Failed to Change Port Offset');
        }
        else {
            $msg = "Port offset has been changed to {$info['new_port_offset']}.";

            $buf .= $this->success_msg($msg, 'Saved New Port Offset');
        }

        $buf .= $this->button_panel_back_next('OK');

        echo $buf;
    }

    public function cpanelInstallConfirm()
    {
        $title = $this->screen_title('Confirm Operation... Install LiteSpeed cPanel Plugin',
                $this->icons['m_cpanel_install']);

        $backBtn = $this->button_panel_back_next('Cancel', 'Install');

        $buf = <<<EOF
{$title}
<p>
  Install the LiteSpeed Web Cache Manager Plugin for cPanel.
  <br />
  <small>* This operation will automatically turn on the cPanel Plugin "Auto Install" setting.</small>
</p>
<h2>What Does It Do?</h2>
<p style="max-width: 760px;">
  The LiteSpeed Web Cache Manager facilitates the installation and management of LSCache plugins across
  all supported CMS sites. Sites with LSCache plugins installed have access to easy cache management
  tools, public and private cache, tag-based smart purge, ESI and other cutting-edge cache technologies.
</p>
<p>The LiteSpeed Web Cache Manager allows the cPanel end-user to...</p>
<ul>
  <li>Enable/Disable supported LiteSpeed Cache plugins on owned sites.</li>
  <li>Flush LSCache for all owned sites.</li>
  <li>
    Flag and unflag owned sites as excluded from WHM Mass Enable/Disable Cache
    operations.
  </li>
  <li>
    (When enabled through "cPanel Settings" in WHM plugin) Generate EC SSL
    certificates for owned domains, improving SSL performance with supporting
    browsers.
  </li>
</ul>
<h2>Hosting Provider Benefits</h2>
<p>
  The LiteSpeed Web Cache Manager allows hosting providers to...
</p>
<ul>
  <li>Provide a convenient cache management tool for end-users.</li>
  <li>
    Customize the plugin landing page to...
    <ul>
      <li>Advertise features or specialized services.</li>
      <li>Recommend or upsell related hosting packages.</li>
    </ul>
   </li>
</ul>
<p>
  <a href="https://docs.litespeedtech.com/cp/cpanel/cpanel-plugin/#installation"
       target="_blank" rel="noopener">
    Learn More >
  </a>
</p>
{$backBtn}
EOF;

        echo $buf;
    }

    public function cpanelInstallComplete()
    {
        $title = $this->screen_title('Install LiteSpeed cPanel Plugin',
                $this->icons['m_cpanel_install']);

        $errMsgs = $this->error_msg(WhmMod_LiteSpeed_Util::get_error_msg());
        $succMsgs = $this->success_msg(WhmMod_LiteSpeed_Util::get_success_msg());

        $backBtn = $this->button_panel_back_next('OK');

        $buf = <<<EOF
{$title}
{$errMsgs}
{$succMsgs}
{$backBtn}
EOF;

        echo $buf;
    }

    public function cpanelUninstallConfirm()
    {
        $title = $this->screen_title('Confirm Operation... Uninstall LiteSpeed cPanel Plugin',
                $this->icons['m_cpanel_uninstall']);

        $backBtn = $this->button_panel_back_next('Cancel', 'Uninstall');

        $buf = <<<EOF
{$title}
<p>
  <small>
    * This operation will automatically turn off the cPanel Plugin
    "Auto Install" setting.
  </small>
</p>
<div class="release-alert small red">
  <span>
    Caution: Any EC certificates generated by users through the user-end cPanel
    plugin will also be removed.
  </span>
</div>
{$backBtn}
EOF;

        echo $buf;
    }

    public function cpanelUninstallComplete()
    {
        $title = $this->screen_title('Uninstall LiteSpeed cPanel Plugin',
                $this->icons['m_cpanel_uninstall']);

        $errMsgs = $this->error_msg(WhmMod_LiteSpeed_Util::get_error_msg());
        $succMsgs = $this->success_msg(WhmMod_LiteSpeed_Util::get_success_msg());

        $backBtn = $this->button_panel_back_next('OK');

        $buf = <<<EOF
{$title}
{$errMsgs}
{$succMsgs}
{$backBtn}
EOF;

        echo $buf;
    }

    /**
     *
     * @param CPanelConf  $cpanelConf  cPanel conf Model object.
     */
    public function cpanelSettings( $cpanelConf )
    {
        $autoCbState = $custTplName = $uctInputState = $uctCbState = $style =
        '';

        $title = $this->screen_title(
            'LiteSpeed cPanel Plugin Settings',
            $this->icons['m_cpanel_settings']
        );

        $errMsgs =
            $this->error_msg(WhmMod_LiteSpeed_Util::get_error_msg(), '', true);
        $succMsgs = $this->success_msg(
            WhmMod_LiteSpeed_Util::get_success_msg(),
            '',
            true
        );

        if ( $cpanelConf->getData(CPanelConf::FLD_CPANEL_PLUGIN_AUTOINSTALL) == 1 ) {
            $autoCbState = 'checked';
        }

        if( !$cpanelConf->getData(CPanelConf::FLD_CPANEL_PLUGIN_INSTALLED) ) {
            $uctCbState = $uctInputState = 'disabled';
        }
        else {

            if ( $cpanelConf->getData(CPanelConf::FLD_USE_CUST_TPL) != 1 ) {
                $uctInputState = 'readonly="true"';
            }
            else {
                $uctCbState = 'checked';
            }

            $safeCustTplName = htmlspecialchars(
                $cpanelConf->getData(CPanelConf::FLD_CUST_TPL_NAME)
            );
        }

        $genEcCertsOff = CPanelConf::SETTING_OFF;
        $genEcCertsOn = CPanelConf::SETTING_ON;
        $genEcCertsAuto = CPanelConf::SETTING_ON_PLUS_AUTO;

        $genEcCertsSel_off = $genEcCertsSel_on = $genEcCertsSel_auto = '';

        $genEcCertsValue =
            $cpanelConf->getData(CPanelConf::FLD_GENERATE_EC_CERTS);

        switch ( $genEcCertsValue ) {

            case CPanelConf::SETTING_ON_PLUS_AUTO:
                $selectedEcCertsOption = &$genEcCertsSel_auto;
                break;

            case CPanelConf::SETTING_ON:
                $selectedEcCertsOption = &$genEcCertsSel_on;
                break;

            case CPanelConf::SETTING_OFF:
            default:
                $selectedEcCertsOption = &$genEcCertsSel_off;
        }

        $selectedEcCertsOption = 'selected';

        $buf = <<<EOF
{$title}
{$errMsgs}
{$succMsgs}
<p>
  Use this page to Mange various LiteSpeed cPanel Plugin settings. Some
  settings may be disabled if the LiteSpeed cPanel Plugin is not installed.
</p>
<br />
<div>
  <input id="cpanelAutoInstall" type="checkbox" name="cpanelAutoInstall"
      value="1" {$autoCbState}/>
  <label for="cpanelAutoInstall" class="normal-weight">Auto Install</label>
  <br />
  <div class="setting-descr">
    <span class="uk-mute uk-text-small">
      Auto install the cPanel plugin when installing or uprading the WHM plugin.
    </span>
  </div>
</div>
<br />
<div>
  <label for="genEcCert" class="normal-weight">
    Enable EC Certificate Generation
  </label>
  <select id="genEcCert" name="genEcCert"
      style="min-width: 200px;border-color: #bbbbbb; border-radius: 5px;">
    <option value="{$genEcCertsOff}" {$genEcCertsSel_off}>
      Off
    </option>
    <option value="{$genEcCertsOn}" {$genEcCertsSel_on}>On</option>
    <option value="{$genEcCertsAuto}" {$genEcCertsSel_auto}>
      On + Auto Gen
    </option>
  </select>
  <br />
  <div class="setting-descr">
    <div class="release-alert small red">
      <span>
        Caution: Setting this feature to "Off" will also remove all EC
        certificates generated by users through the user-end cPanel plugin.
      </span>
    </div>
    <span class="uk-mute uk-text-small">
      Enable the EC certificate generation feature for user-end cPanel plugin
      users. When this feature is enabled, cPanel plugin users will be able to
      generate Let's Encrypt signed EC certificates for owned domains. These
      EC certificates will then be used when serving traffic to supporting
      browsers through LiteSpeed Web Server, improving performance. The
      "On + Auto Gen" option allows a Let's Encrypt signed EC certificate to
      be automatically generated via the cPanel plugin's
      "Upload SSL Cert to QUIC.cloud" button, if the domain does not already
      have an SSL certificate. (Please ensure that LiteSpeed Web Server setting
      <b>Enable Multiple SSL Certificates</b> is set to <b>Yes</b> under
      "Configuration > Server > Tuning > SSL Global Settings" when using this
      feature)
    </span>
  </div>
</div>
<br />
<div>
  <input id="useCustTpl" type="checkbox" name="useCustTpl" value="1"
      onChange="lswsCPanelCustTplState()" {$uctCbState} />
  <label for="useCustTpl" class="normal-weight">Use Custom Template</label>
  <label for="custTpl" style="display: none;">Custom Template</label>
  <input id="custTpl" "type="text" name="custTpl"
      value="{$safeCustTplName}" {$uctInputState}/>
  <br />
  <div class="setting-descr">
    <span class="uk-mute uk-text-small">
      <a href="https://docs.litespeedtech.com/cp/cpanel/cpanel-plugin/#use-custom-template"
          target="_blank" rel="noopener norefferer">
        More info on creating a custom template
      </a>
    </span>
  </div>
</div>
<br />
<div class="btns-box">
  <button class="lsws-secondary-btn"
      onclick="javascript:lswsform.do.value='main';lswsform.submit();">
    Back
  </button>
  <input type="submit" value="Save" class="lsws-secondary-btn"
      onclick="return confirm('Use these settings?');"/>
</div>
EOF;

        echo $buf;
    }

    public function CheckLicense( $info )
    {
        $buf = $this->screen_title(
                "Current License Status <small>( Serial: {$info['serial']})</small>",
                $this->icons['m_license_check']);

        $lic_type = (empty($info['lic_type'])) ? '' : "{$info['lic_type']} - ";

        if ( $info['return'] != 0 ) {
            $buf .= $this->error_msg($info['output'],
                    "{$lic_type}Error when checking license status");
        }
        else {
            $buf .= $this->info_msg($info['output'],
                    "{$lic_type}Check against license server");
        }

        if ( $info['lictype'] == 'trial' ) {
            $msg = 'Note: For trial licenses, the expiration date above is based on the most recent'
                    . ' trial license you have downloaded. All trial licenses are valid for 15 days from'
                    . ' the day you apply. Each IP address, though, may only use trial licenses for'
                    . ' 30 days from the date of the first application. The expiration date above'
                    . ' does not reflect how much longer your IP may use trial licenses. If you are on'
                    . ' your second or third trial license, your actual trial period may end earlier than'
                    . ' the above date.';
            $buf .= $this->warning_msg($msg);
        }
        elseif ( $info['lictype'] == 'migrated' ) {
            $msg = 'Note: You have started the license migration process. You can now use the'
                    . ' same serial number to register on a new machine. If you decide you want to'
                    . ' continue using the license on this machine instead, you must re-register the'
                    . ' license here. Use the Change License function with the serial number to'
                    . ' re-register.';
            $buf .= $this->warning_msg($msg);
        }

        $buf .= $this->button_panel_back_next('OK');

        echo $buf;
    }

    public function UninstallLswsPrepare( $info )
    {
        $buf = $this->screen_title('Confirm Operation... Uninstall LiteSpeed Web Server',
                $this->icons['m_server_uninstall']);
        $buf .= $this->show_running_status($info);

        if ( $info['spool_warning'] == true ) {
            $buf .= $this->getSpoolWarning();
        }

        if ( $info['stop_msg'] != NULL ) {
            $buf .= $this->error_msg($info['stop_msg']);
            $buf .= $this->button_panel_back_next('OK', '', 'config_lsws');
        }
        else {
            $buf .= $this->section_title('Uninstall Options');

            $msg = array();

            if ( $info['ls_pid'] > 0 ) {
                $msg[] = "LiteSpeed is currently running on port offset {$info['port_offset']} and "
                        . 'will be stopped first.';
            }

            $msg[] = 'All subdirectories created under ' . LSWS_HOME . ' during installation will be '
                    . 'removed! The conf/ and logs/ subdirectories can be preserved using the '
                    . 'check boxes below.';
            $msg[] = 'If you want to preserve any files under other subdirectories created by the '
                    . 'installation script, please manually back them up before proceeding.';
            $buf .= $this->warning_msg($msg);

            $input = $this->input_checkbox('keep_conf', 1, true);
            $buf .= $this->form_row('Keep conf/ directory', $input, NULL, NULL, TRUE);

            $input = $this->input_checkbox('keep_log', 1, true);
            $buf .= $this->form_row('Keep logs/ directory', $input, NULL, NULL, TRUE);

            $buf .= $this->button_panel_back_next('Cancel', 'Uninstall', 'config_lsws');
        }

        echo $buf;
    }

    public function UninstallLsws( $info )
    {
        $buf = $this->screen_title('Uninstall LiteSpeed Web Server',
                $this->icons['m_server_uninstall']);

        $buf .= $this->show_running_status($info);

        if ( $info['return'] != 0 ) {

            if ( $info['spool_warning'] == true ) {
                $buf .= $this->getSpoolWarning();
            }

            $buf .= $this->error_msg($info['output'], 'Error when uninstalling LiteSpeed');
        }
        else {
            $buf .= $this->success_msg($info['output'], 'Uninstalled Successfully');
        }

        $buf .= $this->button_panel_back_next('OK');

        echo $buf;
    }

    private function show_choose_license( $info )
    {
        $buf = '';

        if ( $info['error'] != NULL ) {
            $buf .= $this->error_msg('Please fix the following error(s) first.');
        }

        $buf .= '<div><iframe src="' . CP_TOKEN . MODULE_URL . 'static/LICENSE.html" '
                . 'width="650" height="400"></iframe></div>';

        $input = $this->input_checkbox('license_agree', 'agree',
                ($info['license_agree'] == 'agree'));
        $buf .= $this->form_row('I agree', $input, $info['error']['license_agree'], NULL,
                TRUE);

        $buf .= $this->section_title('Choose a License Type');

        $input = $this->input_radio('install_type', 'prod', ($info['install_type'] == 'prod'));
        $buf .= $this->form_row('Use an Enterprise license', $input,
                $info['error']['install_type'], NULL, TRUE);

        $input = $this->input_text('serial_no', $info['serial_no'], 1);

        $hints = array(
            'Your serial number is sent via email when you purchase a LiteSpeed Web Server license.',
            'You can also copy it from your service details in our client area (store.litespeedtech.com).'
        );

        $buf .= $this->form_row('Enter your serial number:', $input,
                $info['error']['serial_no'], $hints);

        $buf .= $this->warning_msg('If your license is currently running on another server, you '
                . 'will need to transfer the license (using the Transfer License function) before '
                . 'registering it on this server.');

        $input = $this->input_radio('install_type', 'trial', ($info['install_type'] == 'trial'));

        $hints = array(
            'This will retrieve a trial license from the LiteSpeed license server.',
            'Each trial license is valid for 15 days from the date you apply.',
            'Each IP address can only use trial licenses for 30 days from the date of your first application.',
            'If you need to extend your trial period, please contact the sales department at '
                . 'litespeedtech.com.'
        );

        $buf .= $this->form_row('Request a trial license', $input,
                $info['error']['install_type'], $hints, TRUE);

        return $buf;
    }

    public function InstallLswsPrepare( $info )
    {
        $buf = $this->screen_title('Installing LiteSpeed Web Server',
                $this->icons['m_server_install']);

        $buf .= $this->show_choose_license($info);

        $buf .= $this->section_title('Installation Options');

        $input = $this->input_text('lsws_home_input', $info['lsws_home_input'], 1);
        $buf .= $this->form_row('Installation directory (define LSWS_HOME):', $input,
                $info['error']['lsws_home_input']);

        $input = $this->input_text('port_offset', $info['port_offset']);

        $hints = array(
            'Setting a port offset allows you to run LiteSpeed on a different port in parallel with Apache. '
                . 'The port offset will be added to your Apache port number to determine your LiteSpeed '
                . 'port.',
            'It is recommended that you run LiteSpeed in parallel first, so you can fully test it before '
                . 'switching to LiteSpeed.'
        );

        $buf .= $this->form_row('Port offset: ', $input, $info['error']['port_offset'],
                $hints);

        $input = $this->input_text('admin_email', $info['admin_email'], 2);

        $hints = array(
            '(Use commas to separate multiple email addresses.)',
            'Email addresses specified will receive messages about important events, such as server '
                . 'crashes or license expirations.'
        );

        $buf .= $this->form_row('Administrator email(s):', $input,
                $info['error']['admin_email'], $hints);

        $buf .= $this->section_title('WebAdmin Console Login');

        $input = $this->input_text('admin_login', $info['admin_login']);
        $buf .= $this->form_row('Username:', $input, $info['error']['admin_login']);

        $input = $this->input_password('admin_pass', $info['admin_pass']);
        $buf .= $this->form_row('Password:', $input, $info['error']['admin_pass']);

        $input = $this->input_password('admin_pass1', $info['admin_pass1']);
        $buf .= $this->form_row('Retype password:', $input, $info['error']['admin_pass1']);

        $buf .= $this->button_panel_back_next('Cancel', 'Install');

        echo $buf;
    }

    public function InstallLsws( $info )
    {
        $buf = $this->screen_title('Install LiteSpeed Web Server',
                $this->icons['m_server_install']);
        $buf .= $this->show_running_status($info);

        if ( $info['return'] != 0 ) {
            $buf .= $this->error_msg($info['output'], 'Error when installing LiteSpeed');
        }
        else {

            if ( $info['spool_warning'] == true ) {
                $buf .= $this->getSpoolWarning();
            }

            $buf .= $this->success_msg($info['output'], 'LiteSpeed Installed Successfully');
        }

        $buf .= $this->button_panel_back_next('OK');

        echo $buf;
    }

    public function ChangeLicensePrepare( $info )
    {
        $buf = $this->screen_title('Changing Software License for LiteSpeed Web Server',
                $this->icons['m_license_change']);
        $buf .= $this->show_choose_license($info);
        $buf .= $this->button_panel_back_next('Cancel', 'Switch');

        echo $buf;
    }

    public function ChangeLicense( $info )
    {
        $buf = $this->screen_title('Changing Software License for LiteSpeed Web Server',
                $this->icons['m_license_change']);
        $buf .= $this->show_running_status($info);

        if ( $info['spool_warning'] == true ) {
            $buf .= $this->getSpoolWarning();
        }

        if ( $info['return'] != 0 ) {
            $buf .= $this->error_msg($info['output'], 'Error when activating new license');
        }
        else {
            $buf .= $this->success_msg($info['output'],
                    'New license activated successfully');
        }

        $buf .= $this->button_panel_back_next('OK');

        echo $buf;
    }

    public function TransferLicenseConfirm( $info )
    {
        $buf = $this->screen_title('LiteSpeed Web Server License Migration Confirm',
                $this->icons['m_license_transfer']);

        $msg = 'You can transfer your license from one server to another. This migration process will '
                . 'allow you to continue to use your current server for 3 days while you migrate to your '
                . 'new server. If, after 3 days, you still need more time to use LiteSpeed on this '
                . 'server, please download a 15-day trial license. (You will need to contact '
                . 'LiteSpeed Technologies to reset your trial record if this server has previously used '
                . 'trial licenses.)';

        $buf .= $this->div_p_msg($msg);

        $buf .= $this->info_msg($info['licstatus_output'], 'Current License Status');

        if ( $info['error'] != NULL ) {
            $buf .= $this->error_msg($info['error']);
            $buf .= $this->button_panel_back_next('OK');
        }
        else {
            $msg = 'Click Transfer if you are ready to go ahead and transfer your current license. You '
                    . 'can continue using this server for up to 3 days.';
            $buf .= $this->warning_msg($msg);

            $buf .= $this->button_panel_back_next('Cancel', 'Transfer');
        }

        echo $buf;
    }

    public function TransferLicense( $info )
    {
        $buf = $this->screen_title('LiteSpeed Web Server License Migration',
                $this->icons['m_license_transfer']);

        if ( $info['return'] == 0 ) {
            $buf .= $this->success_msg('Successfully migrated your license.');
        }
        else {
            $buf .= $this->error_msg($info['output'], 'Failed to migrate your license.');
        }

        $buf .= $this->button_panel_back_next('OK');

        echo $buf;
    }

    public function DefineHome( $info )
    {
        $title  = 'Define LSWS_HOME Location for Existing LiteSpeed Installation';

        $buf = $this->screen_title($title, $this->icons['m_server_definehome']);

        $buf .= $this->info_msg('If LiteSpeed is already installed on this server, please specify'
                . ' the LSWS_HOME location in order for this plugin to work properly.');

        $buf .= $this->section_title('Define $LSWS_HOME');

        if ( isset($info['do_action']) ) {
            $buf .= $this->input_hidden('do', $info['do_action']);
        }

        $hints = array();

        $hints[] = 'Your LiteSpeed binary is located at $LSWS_HOME/bin/lshttpd.';
        $hints[] = 'Common locations for LSWS_HOME include /usr/local/lsws, /opt/lsws';
        $input = $this->input_text('lsws_home_input', $info['lsws_home_input'], 1);
        $buf .= $this->form_row_box('$LSWS_HOME location', $input, $info['error'],
                $hints);

        $buf .= $this->button_panel_back_next('Cancel', 'Save');

        echo $buf;
    }

    private function div_p_msg( $mesg )
    {
        $div = '<div><p>';

        if ( is_array($mesg) ) {
            $div .= implode('</p><p>', $mesg);
        }
        else {
            $div .= $mesg;
        }

        $div .= '</p></div>';

        return $div;
    }

    private function div_msg_box( $msg, $subtype, $title, $scrollable )
    {
        if ( empty($msg) ) {
            return '';
        }

        $class = 'msg-box';

        if ( $subtype != '' ) {
            $class .= " $subtype";
        }

        if ( $scrollable ) {
            $class .= ' scrollable';
        }

        $div = "<div class=\"{$class}\"><ul><li>";

        if ( $title != '' ) {
            $div .= "<div class=\"title\">{$title}</div></li><li>";
        }

        if ( is_array($msg) ) {
            $div .= implode('</li><li>', $msg);
        }
        else {
            $div .= $msg;
        }

        $div .= '</li></ul></div>';

        return $div;
    }

    private function status_msg( $msg, $title = '', $scrollable = false )
    {
        return $this->div_msg_box($msg, 'msg-status', $title, $scrollable);
    }

    private function info_msg( $msg, $title = '', $scrollable = false )
    {
        return $this->div_msg_box($msg, 'msg-info', $title, $scrollable);
    }

    private function error_msg( $msg, $title = '', $scrollable = false )
    {
        return $this->div_msg_box($msg, 'msg-error', $title, $scrollable);
    }

    private function warning_msg( $msg, $title = '', $scrollable = false )
    {
        return $this->div_msg_box($msg, 'msg-warn', $title, $scrollable);
    }

    private function success_msg( $msg, $title = '', $scrollable = false )
    {
        return $this->div_msg_box($msg, 'msg-success', $title, $scrollable);
    }

    /**
     *
     * @param mixed[]  $info
     * @return string
     */
    private function show_running_status( $info )
    {
        $ret = '';

        $criticalAlert = WhmMod_LiteSpeed_Util::getCriticalAlertMsg();
        $ret .= $this->error_msg($criticalAlert, 'Critical Alert');

        if ( $info['ls_pid'] > 0 ) {
            $msg = "LiteSpeed is running (PID = {$info['ls_pid']}";

            if ( isset($info['port_offset']) ) {
                $msg .= ", Apache_Port_Offset = {$info['port_offset']}";
            }

            $msg .= ').';
        }
        else {
            $msg = 'LiteSpeed is not running.';
        }

        if ( $info['ap_pid'] > 0 ) {
            $msg .= " Apache is running (PID = {$info['ap_pid']}).";
        }
        else {
            $msg .= ' Apache is not running.';
        }

        $ret .= $this->status_msg($msg);

        return $ret;
    }

    /**
     * Gets spool warning message HTML.
     *
     * Note: this can be moved to a logger msg later on.
     *
     * @return string
     */
    private function getSpoolWarning()
    {
        $lsSwitch = '<a href="?do=switch_lsws" '
                . 'title="Use LiteSpeed as main web server. This will update rc scripts.">'
                . 'Switch to LiteSpeed</a>';

        $apSwitch = '<a href="?do=switch_apache" '
                . 'title="Use Apache as main web server. This will update rc scripts.">'
                . 'Switch to Apache</a>';

        $changePO = '<a href="?do=change_port_offset" '
                . 'title="This allows LiteSpeed and Apache to run in parallel.">'
                . 'Change Port Offset</a>';

        $msg = 'Both LiteSpeed and Apache are running with Apache_Port_Offset = 0. '
                . "This can cause unintended server behavior. Please do one of the following: "
                . "{$lsSwitch} | {$apSwitch} | {$changePO} to a non-zero value.";

        return $this->warning_msg($msg);
    }

    private function checkDataFile( $dataFileError )
    {
        if ( $dataFileError == WPInstallStorage::ERR_NOT_EXIST ) {
            $msg = '<p><b>No Cache Management data file found</b></p>'
                    . '<p>If you have never scanned and deployed LiteSpeed Cache for WordPress '
                    . 'across discovered WordPress sites, visit the '
                    . '<a href="?do=lscwp_manage">Manage Cache Installations</a> '
                    . 'page and perform a Scan to get started. Sever-wide deployment can lead to '
                    . 'significant performance improvements for said sites, as well as an overall '
                    . 'reduction in server load.</p>'
                    . '<p>If you\'ve been here before, your data file may have been removed due to a '
                    . 'necessary data file update. Please perform a new scan to rebuild the file. We '
                    . 'apologize for any inconvenience.</p>';
        }
        elseif ( $dataFileError == WPInstallStorage::ERR_VERSION_LOW ) {
            $msg = 'Cache Management data file format has been changed for this version. '
                    . 'Please perform a <a href="?do=lscwp_manage"">Re-scan</a> before '
                    . 'attempting any Cache Management operations.';
        }
        else {
            return '';
        }

        return $this->warning_msg($msg);
    }

    /**
     * Returns a warning message if $module (ruid2 or mpm_itk) is set.
     *
     * @since 2.2.2
     * @since 4.0  Removed parameter $isEA4.
     *
     * @param string  $module  Detected module name.
     * @return string
     */
    private function checkFileProtectStatus( $module )
    {
        if ( !$module ) {
            return '';
        }

        $msg = "The {$module} module is incompatible with LSWS and will cause "
            . 'file permission problems if not disabled. Please go to '
            . '"EasyApache 4" under Software and disable this module.';

        return $this->warning_msg($msg);
    }

    private function checkTimezoneDBStatus( $timezoneDBWarning )
    {
        switch ($timezoneDBWarning) {
            case 1:
                $msg = "Some pre-built EasyApache PHP installations may be missing the "
                    . "timezoneDB extension. This will severely impact PHP performance. "
                    . "<a href =\"?do=installTimezoneDB\">Resolve Now</a>";
                break;
            case 2:
                $msg = "Update detected for installed EasyApache PHP timezoneDB extension(s). "
                    . "<a href =\"?do=updateTimezoneDB\">Update Now</a>";
                break;
            default:
                return '';
        }

        return $this->warning_msg($msg);
    }

    private function screen_title( $title, $icon = '' )
    {
        $div = '<div id="heading"><h1>';

        if ( $icon != '' ) {
            $div .= "<span><img src=\"{$icon}\" alt> </span>";
        }

        $div .= "{$title}</h1></div>\n";

        return $div;
    }

    private function section_title( $title, $icon = '' )
    {
        $div = '<div class="section-title">';

        if ( $icon != '' ) {
            $div .= "<span><img src=\"{$icon}\" alt> </span>";
        }

        $div .= "{$title}</div>\n";

        return $div;
    }

    private function input_text( $name, $value, $size_class = 0 )
    {
        /**
         * size 0 : default, size 1: f-middle-size, 2: long
         */

        $iclass = 'input-text';

        if ( $size_class == 1 ) {
            $iclass = '" size="40';
        }
        elseif ( $size_class == 2 ) {
            $iclass = '" size="90';
        }

        $input = '<input type="text" '
                . "class=\"{$iclass}\" name=\"{$name}\" value=\"{$value}\"/>";

        return $input;
    }

    private function input_password( $name, $value )
    {
        $input = "<input type=\"password\" name=\"{$name}\" value=\"{$value}\"/>";

        return $input;
    }

    private function input_checkbox( $name, $value, $ischecked )
    {
        $checked = ($ischecked) ? 'checked="checked"' : '';

        $input = "<input type=\"checkbox\" class=\"checkbox\" name=\"{$name}\" "
                . "value=\"{$value}\" {$checked} />";

        return $input;
    }

    private function input_radio( $name, $value, $ischecked )
    {
        $checked = ($ischecked) ? 'checked="checked"' : '';

        $input = '<input type="radio" class="radiobox" '
                . "name=\"{$name}\" value=\"{$value}\" {$checked} />";

        return $input;
    }

    private function input_hidden( $name, $value )
    {
        $input = "<input type=\"hidden\" name=\"{$name}\" value=\"{$value}\" />";

        return $input;
    }

    private function form_row_box( $label, $field, $err, $hints = NULL,
            $is_single = FALSE )
    {
        $buf = '<div class="form-box">';
        $buf .= $this->form_row($label, $field, $err, $hints, $is_single);
        $buf .= '</div>';

        return $buf;
    }

    private function form_row( $label, $field, $err, $hints = NULL,
            $is_single = FALSE )
    {
        $divclass = 'form-row';
        $errspan = '';
        $hintspan = '';

        if ( $err != NULL ) {
            $divclass .= ' error';
            $errspan = "&nbsp;<span class=\"error-hint\">{$err}</span>";
        }

        if ( $hints != NULL ) {

            if ( is_array($hints) ) {
                $hintspan = '<span class="hint">' . implode('<br>', $hints) . '</span>';
            }
            else {
                $hintspan = "<span class=\"hint\">{$hints}</span>";
            }
        }

        $div = "<div class=\"{$divclass}\">";

        if ( $is_single ) {
            $div .= "<div class=\"single-row\">{$field}<label>&nbsp;{$label}</label>";
        }
        else {
            $div .= "<div class=\"field-name\"><label>{$label}&nbsp;</label></div>"
                    . "<div class=\"field-value\">{$field}";
        }

        $div .= "{$errspan}{$hintspan}</div></div>\n";

        return $div;
    }

    private function button_panel_back_next( $back_title, $next_title = '',
            $back_do = 'main', $extra_class = '' )
    {
        $buf = '<div class="btns-box">'
                . "<button class=\"input-button {$extra_class}\" "
                . "onclick=\"javascript:lswsform.do.value='{$back_do}';lswsform.submit();\">"
                . "{$back_title}</button>";

        if ( $next_title != '' ) {
            $buf .= "<button class=\"input-button {$extra_class}\" type=\"submit\">"
                    . "{$next_title}</button>";
        }

        $buf .= '</div>';

        return $buf;
    }

}
