<?php

/** ******************************************
 * LiteSpeed Web Server Plugin for WHM
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2013-2020
 * ******************************************* */

namespace LsPanel;

use \Lsc\Wp\Panel\CPanel;

class WhmMod_LiteSpeed_Util
{

    const LSCACHE_STATUS_NOT_SUPPORTED = 0;
    const LSCACHE_STATUS_MISSING = 1;
    const LSCACHE_STATUS_DETECTED = 2;
    const LSCACHE_STATUS_UNKNOWN = 3;

    /**
     * @var string
     */
    private $moduleCmd;

    /**
     * @var string
     */
    private $latestVersFile;

    /**
     * @var string
     */
    private $newBuildFile;

    /**
     * @var string
     */
    private $serial_file;

    /**
     * @var string
     */
    private $trial_key_file;

    /**
     * @var string
     */
    private $version_file;

    /**
     * @var string
     */
    private $build_file;

    /**
     * @var string
     */
    private $stable_file;

    /**
     * @var string
     */
    private $admin_conf;

    /**
     * @var string
     */
    private $serv_conf;

    /**
     * @var string[]
     */
    private static $success_msgs = array();

    /**
     * @var string[]
     */
    private static $err_msgs = array();

    public function __construct()
    {
        $this->Init();
    }

    public function Init()
    {
        $lswsHome = (defined('LSWS_HOME')) ? LSWS_HOME : '/usr/local/lsws';

        $this->moduleCmd = WhmMod_LiteSpeed_ControlApp::CGI_DIR
            . '/bin/lsws_cmd.sh ' . escapeshellarg($lswsHome);
        $this->latestVersFile =
                WhmMod_LiteSpeed_ControlApp::CGI_DIR . '/latest_vers';
        $this->newBuildFile =
            WhmMod_LiteSpeed_ControlApp::CGI_DIR . '/new_build';
        $this->serial_file = "{$lswsHome}/conf/serial.no";
        $this->trial_key_file = "{$lswsHome}/conf/trial.key";
        $this->version_file = "{$lswsHome}/VERSION";
        $this->build_file = "{$lswsHome}/BUILD";
        $this->stable_file = "{$lswsHome}/autoupdate/follow_stable";
        $this->admin_conf = "{$lswsHome}/admin/conf/admin_config.xml";
        $this->serv_conf = "{$lswsHome}/conf/httpd_config.xml";
    }

    public static function add_success_msg( $success_mesg )
    {
        if ( is_array($success_mesg) ) {
            self::$success_msgs = array_merge(self::$success_msgs,
                    $success_mesg);
        }
        else {
            self::$success_msgs[] = $success_mesg;
        }
    }

    public static function get_success_msg()
    {
        return self::$success_msgs;
    }

    public static function add_error_msg( $err_mesg )
    {
        if ( is_array($err_mesg) ) {
            self::$err_msgs = array_merge(self::$err_msgs, $err_mesg);
        }
        else {
            self::$err_msgs[] = $err_mesg;
        }
    }

    public static function get_error_msg()
    {
        return self::$err_msgs;
    }

    /**
     *
     * @since 4.1  Added param $default.
     *
     * @param string  $tag
     * @param mixed   $default  The default value to be passed back when the
     *                          request var is not found.
     * @return mixed|null|string
     */
    public static function get_request_var( $tag, $default = null )
    {
        $varValue = $default;

        if ( isset($_REQUEST[$tag])  ) {
            $varValue = $_REQUEST[$tag];

            if ( $varValue !== null ) {
                $varValue = trim($varValue);
            }
        }

        return $varValue;
    }

    /**
     *
     * @since 4.1  Added param $default.
     *
     * @param string  $tag
     * @param mixed   $default  The default value to be passed back when the
     *                          request list is not found.
     * @return mixed|null|array
     *
     * @noinspection PhpUnused
     */
    public static function get_request_list( $tag, $default = null )
    {
        $listValue = $default;

        if ( isset($_REQUEST[$tag]) ) {
            $result = $_REQUEST[$tag];

            $listValue = (is_array($result)) ? $result : $default;
        }

        return $listValue;
    }

    /**
     *
     * @param string   $url
     * @param boolean  $headerOnly
     * @return string
     */
    public static function get_url_contents( $url, $headerOnly = false )
    {
        if ( ini_get('allow_url_fopen') ) {
            /**
             * silence warning when OpenSSL missing while getting LSCWP ver
             * file.
             */
            $url_content = @file_get_contents($url);

            if ( $url_content !== false ) {

                if ( $headerOnly ) {
                    return implode("\n", $http_response_header);
                }

                return $url_content;
            }
        }

        if ( function_exists('curl_version') ) {
            $ch = curl_init();

            curl_setopt_array(
                    $ch,
                    array(
                        CURLOPT_URL => $url,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HEADER => $headerOnly,
                        CURLOPT_NOBODY => $headerOnly,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
                    )
            );

            $url_content = curl_exec($ch);
            curl_close($ch);

            if ( $url_content !== false ) {
                return $url_content;
            }
        }

        $cmd = 'curl';

        if ( $headerOnly ) {
            $cmd .= ' -s -I';
        }

        exec("{$cmd} {$url}", $output, $ret);

        if ( $ret === 0 ) {
            $url_content = implode("\n", $output);
            return $url_content;
        }

        return '';
    }

    public function getLSPID()
    {
        $cmd = "{$this->moduleCmd} CHECK_LSWS_RUNNING";
        exec($cmd, $output, $return_var);

        /**
         * pid
         */
        return $output[0];
    }

    public function getApachePID()
    {
        $cmd = "{$this->moduleCmd} CHECK_AP_RUNNING";
        exec($cmd, $output);

        /**
         * pid
         */
        return $output[0];
    }

    /**
     *
     * @since 4.1.3
     *
     * @return string
     */
    private static function getLatestWhmPluginVerFilePath()
    {
        return WhmMod_LiteSpeed_ControlApp::CGI_DIR . '/LATEST_VER';
    }

    /**
     *
     * @since 3.3.7
     *
     * @param int  $litespeedPID
     * @param int  $apachePID
     * @param int  $portOffset
     * @return boolean
     */
    public static function isServerSpooling( $litespeedPID, $apachePID,
            $portOffset )
    {
        if ( $litespeedPID > 0 && $apachePID > 0 && $portOffset === 0 ) {
            return true;
        }

        return false;
    }

    public function GetCurrentSerialNo()
    {
        return 'Lifetime License';
    }

    private function GetCacheType( $line, &$res )
    {
        if ( preg_match('/FEATURES: ([0-9\.]+)/', $line, $m) ) {
            $feature = $m[1];

            if ( ($feature & 1) == 1 ) {
                $res['has_cache'] = self::LSCACHE_STATUS_DETECTED;
                $feature &= 11;

                switch ($feature) {
                    case 1:
                        $res['lic_type'] .= ' (with LSCache Standard)';
                        break;
                    case 9:
                        $res['lic_type'] .= ' (with LiteMage Starter)';
                        break;
                    case 11:
                        $res['lic_type'] .= ' (with LiteMage Standard)';
                        break;
                    case 3:
                        $res['lic_type'] .= ' (with LiteMage Unlimited)';
                        break;
                    //no default case
                }
            }
            else {
                $res['has_cache'] = self::LSCACHE_STATUS_MISSING;
            }
        }
        else {
            $res['has_cache'] = self::LSCACHE_STATUS_NOT_SUPPORTED;
        }
    }

    /**
     * Takes license type string and returns the expanded translation. In the
     * case where no translation is available (old format) , the license type
     * string is returned as is.
     *
     * @param string  $type  License type string.
     * @return string
     */
     private function translateLicType( $type )
    {
        if ( strlen($type) > 1 && strncmp($type, '9:', 2) == 0 ) {
            return 'Web Host Elite (X-Worker = ' . substr($type, 2) . ')';
        }

        switch ($type) {
            /**
             * old license types to be retired
             */
            case 'V':
                return 'VPS (1-Worker with 2GB Memory Limit)';
            case 'U':
            case 'VU':
            case 'U1':
                return 'UltraVPS (1-Worker with 8GB Memory Limit)';
            case '8':
                return '8-CPU (8-Worker)';

            /**
             * current license types
             */
            case 'F':
                return 'Free Starter (1-Domain & 1-Worker with 2GB Memory '
                        . 'Limit)';
            case 'S':
                return 'Site Owner Plus (5-Domains & 1-Worker)';
            case 'SM':
                return 'Site Owner (5-Domains & 1-Worker with 8GB Memory '
                        . 'Limit)';

            /**
             * 'D' and 'DM' license types are reserved and not used.
             */
            case 'D':
                return 'Domain Limited (Limited-Domain & 1-Worker)';
            case 'DM':
                return 'Domain Limited (Limited-Domain & 1-Worker with 8GB '
                        . 'Memory Limit)';

            case '1M':
                return 'Web Host Lite (1-Worker with 8GB Memory Limit)';
            case '1':
                return 'Web Host Essential (1-Worker)';
            case '2':
                return 'Web Host Professional (2-Worker)';
            case '3':
                return 'Dedicated (3-Worker)';
            case '4':
                return 'Web Host Enterprise (4-Worker)';
            case 'X':
            case '9':
                /**
                 * Failsafe case, should not happen.
                 */
                return 'Web Host Elite (X-Worker)';
            default :
                return $type;
        }
    }

    public function GetLicenseType()
    {
        $res = array( 'lic_type' => '' );
        $statusFile = '/tmp/lshttpd/.status';

        if ( !file_exists($statusFile) ) {
            $res['has_cache'] = self::LSCACHE_STATUS_UNKNOWN;
        }
        else {
            $f = fopen($statusFile, 'r');

            if ( $f === false ) {
                $res['has_cache'] = self::LSCACHE_STATUS_UNKNOWN;
            }
            else {
                $type = '';

                fseek($f, -128, SEEK_END);
                $line = fread($f, 128);
                fclose($f);

                if ( preg_match('/TYPE: (.+)/', $line, $m) ) {
                    $type = $m[1];

                    $translation = $this->translateLicType($type);

                    $res['lic_type'] = "{$translation} License";
                }

                $this->GetCacheType($line, $res);

                /**
                 * Special case to avoid old 1-CPU license w/o LSCache from
                 * appearing as the new license type assumed to include LSCache.
                 */
                if ( $type == '1' &&
                        $res['has_cache'] != self::LSCACHE_STATUS_DETECTED ) {

                    $res['lic_type'] = '1-CPU (1-Worker) License';
                }
            }
        }

        return $res;
    }

    /**
     * Adds LSWS stable version and build info to the referenced array.
     *
     * @param array  $info
     */
    private function populateStableVersionInfo( &$info )
    {
        $newVer = $newBuild = '';

        $info['lsws_version'] = $currVer = $this->GetCurrentVersion();

        $updateInfo = file_get_contents($this->latestVersFile);

        if ( preg_match('/.*LSWS_STABLE=([0-9\.]*)/', $updateInfo, $matches) ) {
            $stableVer = $matches[1];

            if ( version_compare($stableVer, $currVer, '>') ) {
                $newVer = $stableVer;
            }
        }

        $info['new_version'] = $newVer;

        if ( version_compare($info['lsws_version'], '5.2.2', '>=') ) {
            $info['lsws_build'] = $currBuild = $this->GetCurrentBuild();

            if ( isset($stableVer) ) {

                if ( $currVer == $stableVer ) {

                    if ( preg_match('/.*LSWS_STABLE=[0-9\.]* BUILD ([0-9]*)/',
                            $updateInfo, $matches) ) {

                        $stableBuild = $matches[1];

                        if ( $stableBuild > $currBuild ) {
                            $newBuild = $stableBuild;
                        }
                    }
                }
                elseif ( version_compare($currVer, $stableVer, '<') ) {
                    $newBuild = $this->GetNewBuild($currVer, $currBuild);
                }
            }
        }

        $info['new_build'] = $newBuild;
    }

    /**
     * Adds LSWS version and build info to the referenced array.
     *
     * @param array  $info
     */
    public function populateVersionInfo( &$info )
    {
        if ( !file_exists($this->latestVersFile)
                || (time() - filemtime($this->latestVersFile)) > 86400 ) {

            $updateInfo = $this->get_url_contents(
                'http://update.litespeedtech.com/ws/latest.php'
            );

            file_put_contents($this->latestVersFile, $updateInfo);
        }

        if ( file_exists($this->stable_file) ) {
            $this->populateStableVersionInfo($info);
        }
        else {
            $info['lsws_version'] = $this->GetCurrentVersion();
            $info['new_version'] = $this->GetNewVersion($info['lsws_version']);

            if ( version_compare($info['lsws_version'], '5.2.2', '>=') ) {
                $info['lsws_build'] = $this->GetCurrentBuild();
                $info['new_build'] = $this->GetNewBuild($info['lsws_version'],
                        $info['lsws_build']);
            }
        }
    }

    /**
     *
     * @return string
     */
    private function GetCurrentVersion()
    {
        return trim(file_get_contents($this->version_file));
    }

    /**
     *
     * @param string  $currVer
     * @return string
     */
    private function GetNewVersion( $currVer )
    {
        $new_version = '';

        $info = file_get_contents($this->latestVersFile);

        if ( preg_match('/.*LSWS=([0-9\.]*)/', $info, $matches) ) {
            $new = $matches[1];

            if ( version_compare($new, $currVer, '>') ) {
                $new_version = $new;
            }
        }

        return $new_version;
    }

    /**
     *
     * @return string
     */
    private function GetCurrentBuild()
    {
        return trim(file_get_contents($this->build_file));
    }

    /**
     *
     * @param string  $currVersion
     * @param string  $currBuild
     * @return string
     */
    private function getNewBuildFileVer( $currVersion, $currBuild )
    {
        $newBuildFile = $this->newBuildFile;
        $newBuild = '';

        if ( file_exists($newBuildFile) ) {
            $buildFileContent = file_get_contents($newBuildFile);

            preg_match('/ver: (.+)/', $buildFileContent, $verMatches );

            if ( isset($verMatches[1]) && $verMatches[1] == $currVersion ) {
                preg_match('/build: (\d+)/', $buildFileContent, $buildMatches);

                if ( isset($buildMatches[1])
                        && ($fileBuild = $buildMatches[1]) > $currBuild ) {

                    $newBuild = $fileBuild;
                }
            }

            if ( empty($newBuild) ) {
                unlink($newBuildFile);
            }
        }

        return $newBuild;
    }

    /**
     *
     * @param string  $currVersion
     * @param string  $newBuild
     */
    private function writeNewBuildFileVer( $currVersion, $newBuild )
    {
        $content = <<<EOF
ver: {$currVersion}
build: {$newBuild}
EOF;

        file_put_contents($this->newBuildFile, $content);
    }

    /**
     *
     * @param string  $currVersion
     * @param string  $currBuild
     * @return string
     */
    private function GetNewBuild( $currVersion, $currBuild )
    {
        $newBuild = $this->getNewBuildFileVer($currVersion, $currBuild);

        if ( !empty($currBuild)
                && (time() - filemtime($this->build_file)) > 86400 ) {

            $verDir = "{$currVersion[0]}.0";

            $file_content = $this->get_url_contents(
                    "https://www.litespeedtech.com/packages/{$verDir}/"
                    . "lsws-{$currVersion}-ent-x86_64-linux.tar.gz.lastbuild");

            $lastBuild = trim($file_content);

            if ( $currBuild < $lastBuild ) {
                $newBuild = $lastBuild;

                $this->writeNewBuildFileVer($currVersion, $newBuild);
            }

            touch($this->build_file);
        }

        return $newBuild;
    }

    /**
     *
     * @return string[]
     */
    public function GetInstalledVersions()
    {
        $installed_releases = array();
        $dir = LSWS_HOME . '/bin';
        $dh = @opendir($dir);

        if ( $dh ) {

            while ( ($fname = readdir($dh)) !== false ) {
                $matches = array();

                if ( preg_match('/^lswsctrl\.(.*)$/', $fname, $matches) ) {
                    $installed_releases[] = $matches[1];
                }
            }

            closedir($dh);
        }

        return $installed_releases;
    }

    /**
     * Checks for the existence of any modules that will cause file permission
     * problems with LSWS and returns the first module name found.
     *
     * @since 2.2.2
     *
     * @return string
     */
    public function CheckFileProtectWarning()
    {
        $modules = array( 'ruid2', 'mpm_itk' );

        foreach ( $modules as $module ) {
            $cmd = "httpd -M | grep {$module} | wc -l";
            $val = exec($cmd);

            if ( $val === '1' ) {
                return $module;
            }
        }

        return '';
    }

    /**
     * Only checked for EA4.
     *
     * @param string[]  $eaPHP_vers
     * @return int                   1 = missing timezonedb
     *                                2 = update available
     *                                0  = no change.
     */
    public function checkTimezoneDBStatus( $eaPHP_vers )
    {
        foreach ( $eaPHP_vers as $verNum ) {
            $eaPHP_path = "/opt/cpanel/ea-php{$verNum}/root/usr";

            if ( file_exists("{$eaPHP_path}/bin/phpize")
                    && !file_exists("{$eaPHP_path}/lib64/php/modules/timezonedb.so") ) {

                return 1;
            }
        }

        /**
         * Check daily for updates.
         */
        $timezoneDB_flag = WhmMod_LiteSpeed_ControlApp::CGI_DIR
                . '/litespeed_timezonedb';

        if ( !file_exists($timezoneDB_flag)
                || (time() - filemtime($timezoneDB_flag)) > 86400 ) {

            $script = WhmMod_LiteSpeed_ControlApp::CGI_DIR
                    . '/buildtimezone_ea4.sh';
            $cmd = "{$script} y true";

            $output = shell_exec($cmd);

            if ( preg_match('/Newer version found/', $output) ) {
                return 2;
            }

            touch($timezoneDB_flag);
        }

        return 0;
    }

    public function getTimezoneDBMsgs( $output )
    {
        $msgs = array(
            'succ' => preg_grep('/^EA4 PHP ([0-9]+\.)?[0-9]+\.[0-9]+ (?!not)/',
                    $output),
            'warn' => preg_grep('/^EA4 PHP ([0-9]+\.)?[0-9]+\.[0-9]+ not/',
                    $output),
            'err' => preg_grep('/^\*\*ERROR\*\*/', $output)
        );

        $msgs['succ'] = array_merge($msgs['succ'],
                preg_grep('/^CageFS updated/', $output));

        return $msgs;
    }

    /**
     *
     * @noinspection PhpUnused
     */
    public function cpanelInstallPlugin()
    {
        exec(__DIR__ . '/res/ls_web_cache_mgr/install.sh');

        WhmMod_LiteSpeed_CPanelConf::verifyCpanelPluginConfFile();

        CPanel::turnOnCpanelPluginAutoInstall();

        $this->add_success_msg(
            'LiteSpeed cPanel Plugin Installed Successfully');
    }

    /**
     *
     * @noinspection PhpUnused
     */
    public function cpanelUninstallPlugin()
    {
        $cmd = 'res/ls_web_cache_mgr/uninstall.sh';
        exec($cmd);

        CPanel::turnOffCpanelPluginAutoInstall();

        $this->add_success_msg(
            'LiteSpeed cPanel Plugin Uninstalled Successfully');
    }

    private function unparseURL( $urlParts )
    {
        $scheme = isset($urlParts['scheme']) ? "{$urlParts['scheme']}://" : '';
        $host = isset($urlParts['host']) ? $urlParts['host'] : '';
        $port = isset($urlParts['port']) ? ":{$urlParts['port']}" : '';
        $user = isset($urlParts['user']) ? $urlParts['user'] : '';
        $pass = isset($urlParts['pass']) ? ":{$urlParts['pass']}" : '';
        $pass = ($user || $pass) ? "{$pass}@" : '';
        $path = isset($urlParts['path']) ? $urlParts['path'] : '';
        $query = isset($urlParts['query']) ? "?{$urlParts['query']}" : '';
        $fragment =
            isset($urlParts['fragment']) ? "#{$urlParts['fragment']}" : '';

        return $scheme . $user . $pass . $host . $port . $path . $query
                . $fragment;
    }

    private function removeExtURLPort( &$url )
    {
        $urlParts = parse_url($url);

        if ( !isset($urlParts['port']) ) {
            return;
        }

        unset($urlParts['port']);

        $url = $this->unparseURL($urlParts);
    }

    public function GetAdminUrl()
    {
        $data = file_get_contents($this->admin_conf);
        $url = '';

        if ( $data != '' ) {
            /**
             * http://' . $_SERVER['HTTP_HOST'] . ':' . $admin_port . '/
             */

            /*
             * <address>*:7080</address>
             */
            $port = "7080";

            if ( preg_match("/<address>.*:(\d+)<\/address>/", $data, $matches) ) {
                $port = $matches[1];
            }

            /*
             * <secure>0</secure>
             */
            $is_secure = '';

            if ( preg_match("/<secure>(\d)<\/secure>/", $data, $matches)
                    && $matches[1] == 1 ) {

                $is_secure = 's';
            }

            $host = $_SERVER['HTTP_HOST'];
            $this->removeExtURLPort($host);

            $url = "http{$is_secure}://{$host}:{$port}";
        }

        return $url;
    }

    /**
     *
     * @param string|string[]|null  $fields
     * @return string[]
     */
    public function GetLSConfig( $fields = NULL )
    {
        $contents = file_get_contents($this->serv_conf);
        $entry = array();

        if ( is_array($fields) ) {

            foreach ( $fields as $f ) {
                $entry[$f] = '';
            }
        }
        else {
            $entry[$fields] = '';
        }

        foreach ( $entry as $key => $val ) {

            if ( preg_match("/<{$key}>(.*?)<\/{$key}>/s", $contents, $matches) ) {
                $entry[$key] = $matches[1];
            }
        }

        return $entry;
    }

    public function GetApachePortOffset()
    {
        $field = 'apachePortOffset';
        $d = $this->GetLSConfig($field);

        return (int)$d[$field];
    }

    public function ChangePortOffset( $new_port_offset, &$output )
    {
        $cmd = "{$this->moduleCmd} CHANGE_PORT_OFFSET {$new_port_offset}";
        exec($cmd, $output, $return_var);

        return $return_var;
    }

    /**
     *
     * @param string  $serial
     * @param array   $output
     * @return int
     */
    public function ChangeLicense( $serial, &$output )
    {
        $cmd = "{$this->moduleCmd} CHANGE_LICENSE {$serial}";
        exec($cmd, $output, $return_var);

        self::removeCriticalAlertMsg();

        return $return_var;
    }

    /**
     *
     * @param array  $output
     * @return int
     */
    public function RestartLSWS( &$output )
    {
        $cmd = "{$this->moduleCmd} RESTART_LSWS";
        exec($cmd, $output, $return_var);

        return $return_var;
    }

    public function Switch2LSWS( &$output )
    {
        $cmd = "{$this->moduleCmd} SWITCH_TO_LSWS";
        exec($cmd, $output, $return_var);

        return $return_var;
    }

    public function Switch2Apache( &$output )
    {
        $cmd = "{$this->moduleCmd} SWITCH_TO_APACHE";
        exec($cmd, $output, $return_var);

        return $return_var;
    }

    /**
     *
     * @since 3.3.7
     *
     * @return string
     */
    private static function getCriticalAlertFilePath()
    {
        if ( defined('LSWS_HOME') && LSWS_HOME != '' ) {
            return LSWS_HOME . '/logs/critical_alert';
        }

        return '';
    }

    /**
     * Return LSWS critical alert if one exists.
     *
     * @since 3.3.5
     *
     * @return string
     */
    public static function getCriticalAlertMsg()
    {
        $msg = '';

        if ( ($criticalAlertFile = self::getCriticalAlertFilePath()) != ''
                && file_exists($criticalAlertFile) ) {

            $msg = trim(file_get_contents($criticalAlertFile));

            /**
             * Messages stored in this file should only be license related.
             */
            $msg .= ' (If you have recently made a change to or a payment for '
                . 'this license and believe this message to be in error, '
                . 'please visit the '
                . '<a href="?do=check_license">License Status</a> page to '
                . 'refresh your license status)';
        }

        return $msg;
    }

    /**
     * Remove LSWS critical alert file if it exists.
     *
     * @since 3.3.7
     *
     * @return boolean
     */
    private static function removeCriticalAlertMsg()
    {
        if ( ($criticalAlertFile = self::getCriticalAlertFilePath()) != ''
                && file_exists($criticalAlertFile) ) {

            return unlink($criticalAlertFile);
        }

        return false;
    }

    public function GetCurrentLicenseStatus( &$output )
    {
        self::removeCriticalAlertMsg();

        $cmd = "{$this->moduleCmd} CHECK_LICENSE";
        exec($cmd, $output, $return_var);

        return $return_var;
    }

    public function UninstallLSWS( $keepConf, $keepLog, &$output )
    {
        $cmd = "{$this->moduleCmd} UNINSTALL {$keepConf} {$keepLog}";
        exec($cmd, $output, $return_var);

        return $return_var;
    }

    public function DetectLSWS_HOME()
    {
        /**
         * Testing possible locations
         */
        $possible_loc = array( '/usr/local/lsws', '/opt/lsws' );

        foreach ( $possible_loc as $path ) {

            if ( is_file("{$path}/bin/lshttpd") ) {
                return $path;
            }
        }

        return '';
    }

    public function Validate_LSWS_HOME( $lsws_home_input,
            $hasInstalled = false )
    {

        if ( $lsws_home_input == '' ) {
            return 'Missing input!';
        }

        if ( $hasInstalled && !is_file($lsws_home_input . '/bin/lshttpd') ) {
            return "Invalid path: cannot find {$lsws_home_input}/bin/lshttpd!";
        }
        else {

            if ( strpos($lsws_home_input, ' ') !== FALSE ) {
                return 'Do not allow space in the path!';
            }

            /**
             * New installation, prohibit certain paths.
             */
            $forbiddenDirs = array(
                '/etc', '/usr/sbin', '/usr/bin', '/usr/lib', '/usr/local/bin',
                '/usr/local/sbin', '/usr/local/lib'
            );

            foreach ( $forbiddenDirs as $dir ) {

                if ( strpos($lsws_home_input, $dir) !== FALSE ) {
                    return 'It is not safe to install under this system '
                            . 'directory';
                }
            }
        }

        return NULL;
    }

    public function Validate_NewPortOffset( $new_port_offset,
            $old_port_offset )
    {
        $err = $this->validate_port_offset($new_port_offset);

        if ( $err == NULL && $new_port_offset == $old_port_offset ) {
            $err = 'New value is same as current one';
        }

        return $err;
    }

    public function Validate_ChangeLicenseInput( $input )
    {
        return $this->validate_license_type($input);
    }

    public function Validate_InstallInput( $input )
    {
        $errors = $this->validate_license_type($input);

        $err = $this->Validate_LSWS_HOME($input['lsws_home_input']);

        if ( $err != NULL ) {
            $errors['lsws_home_input'] = $err;
        }

        $err = $this->validate_port_offset($input['port_offset']);

        if ( $err != NULL ) {
            $errors['port_offset'] = $err;
        }

        if ( $input['admin_login'] == '' ) {
            $errors['admin_login'] = 'Missing login ID!';
        }
        elseif ( !preg_match('/^[a-zA-Z0-9_\-]+$/', $input['admin_login']) ) {
            $errors['admin_login'] =
                'Accepted characters for login ID are [a-zA-Z0-9_\-]';
        }

        if ( $input['admin_pass'] == '' ) {
            $errors['admin_pass'] = 'Missing login password';
        }
        elseif ( $input['admin_pass1'] == '' ) {
            $errors['admin_pass1'] = 'Missing login password';
        }
        elseif ( $input['admin_pass'] != $input['admin_pass1'] ) {
            $errors['admin_pass1'] = 'Passwords do not match!';
        }
        elseif ( strlen($input['admin_pass']) < 6 ) {
            $errors['admin_pass'] = 'Password must be at least 6 characters!';
        }
        elseif ( strlen($input['admin_pass']) > 64 ) {
            $errors['admin_pass'] =
                'Password is too long, must be less than 64 characters!';
        }
        elseif ( strpos($input['admin_pass'], ' ') !== FALSE ) {
            $errors['admin_pass'] = 'Password cannot contain space!';
        }

        if ( $input['admin_email'] == '' ) {
            $errors['admin_email'] = 'Missing Admin Email!';
        }
        else {
            $emails = preg_split("/, /", $input['admin_email'], -1,
                    PREG_SPLIT_NO_EMPTY);

            foreach ( $emails as $em ) {

                if ( !preg_match("/^[[:alnum:]._-]+@[[:alnum:]._-]+$/", $em) ) {
                    $errors['admin_email'] = "invalid email format - {$em}";
                    break;
                }
            }
        }

        return $errors;
    }

    public function InstallLSWS( $input, &$output )
    {
        $install_cmd =
                WhmMod_LiteSpeed_ControlApp::CGI_DIR . '/install_lsws_cp.sh '
                . escapeshellarg($input['lsws_home_input']) . ' '
                . escapeshellarg($input['serial_no']) . ' '
                . "{$input['port_offset']} "
                . "{$input['php_suexec']} "
                . escapeshellarg($input['admin_login']) . ' '
                . escapeshellarg($input['admin_pass']) . ' '
                . escapeshellarg($input['admin_email']);

        exec($install_cmd, $output, $return_var);

        return $return_var;
    }

    /**
     *
     * @param mixed[]  $info
     * @return string|string[]
     */
    public function Validate_LicenseTransfer( $info )
    {
        $error = '';

        if ( $info['licstatus_return'] != 0 ) {
            $error = 'Current license can no longer be used, not valid for '
                    . 'transfer.';
        }
        else {
            $buf = implode('<br/>', $info['licstatus_output']);

            if ( preg_match('/ -[0-9]+ /', $buf) ) {
                /**
                 * Has been migrated.
                 */
                $error = array();
                $error[] = 'Current license has been transferred. You cannot '
                        . 'transfer this license again!';
                $error[] = 'You can use the same serial number to register a '
                        . 'new license on a new machine.';
                $error[] = 'If you want to reuse the same serial number on '
                        . 'this machine, use the "change license" option with '
                        . 'the same serial number to get a new license key if '
                        . 'it has not been used elsewhere. Otherwise, you will '
                        . 'need to release the serial number before you can '
                        . 'activate it on another machine.';
            }
        }

        return $error;
    }

    public function TransferLicense( &$output )
    {
        $cmd = "{$this->moduleCmd} TRANSFER_LICENSE";
        exec($cmd, $output, $return_var);

        return $return_var;
    }

    /**
     * @param string[]  $info
     * @return string|null
     */
    public function Validate_VersionManage( $info )
    {
        if ( !in_array($info['act'], array( 'download', 'switchTo', 'remove' )) ) {
            return 'Invalid action';
        }

        if ( !preg_match('/^[1-9]+\.[0-9RC\.]+$/', $info['actId']) ) {
            return 'Invalid version';
        }

        return null;
    }

    /**
     *
     * @param string  $act
     * @param string  $actId
     * @return void
     */
    public function VersionManage( $act, $actId )
    {
        $cmd = $this->moduleCmd;

        switch ($act) {
            case 'download':
                /**
                 * '>/dev/null 2>&1' needed since lsws/admin/misc file
                 * 'ap_lsws.sh.in' changed it's restart cmd to
                 * 'service lsws restart' (LSWS 5.2.4+) causing exec() to stall
                 * after/at the end of 'lsws_cmd' script execution. This will
                 * hide (redirect) said output, fixing the issue.
                 */
                $cmd .= " VER_UP {$actId} >/dev/null 2>&1";

                break;
            case 'switchTo':
                $cmd .= " VER_SWITCH {$actId}";

                break;
            case 'remove':
                $cmd .= " VER_DEL {$actId}";

                break;
            default:
                return;
        }

        exec($cmd, $output, $return_var);

        if ( $return_var == 0 ) {
            if ( $act == 'switchTo' ) {
               /**
                * Purposely not used.
                */
                $output2 = array();

                $this->RestartLSWS($output2);
            }

            WhmPluginLogger::uiSuccess('Successfully switched version.');
        }
        else {
            $outputMsg = implode('<br />', $output);

            /**
             * $act must be checked here as 'download' will return 2 when
             * failing to download.
             */
            if ( $act == 'remove' && $return_var == 2 ) {
                $msg = "{$outputMsg}<br /><br />Successfully removed the "
                        . 'selected version';
                WhmPluginLogger::uiSuccess($msg);
            }
            elseif ( $act == 'download' ) {
                WhmPluginLogger::uiError(
                    "Failed to force reinstall LSWS {$actId}");
            }
            else {
                WhmPluginLogger::uiError($outputMsg);
            }
        }
    }

    private function validate_serial_no( $serial )
    {
        $error = '';

        if ( $serial == "" ) {
            $error = 'Missing serial number!';
        }
        else {
            $pattern = '[a-zA-Z0-9/+]{4}';

            $validSerial = "%^{$pattern}-{$pattern}-{$pattern}-{$pattern}$%";

            if ( strlen($serial) != 19 || !preg_match($validSerial, $serial) ) {
                $error = 'Invalid serial number';
            }
        }

        return $error;
    }

    private function validate_port_offset( $port_offset )
    {
        if ( !preg_match('/^[0-9]+$/', $port_offset) ) {
            return 'Invalid number.';
        }

        $port = intval($port_offset);

        if ( $port < 0 || $port > 65535 ) {
            return 'Number out of range (0~65535)';
        }

        return NULL;
    }

    /**
     * Used in install & change license.
     *
     * @param (int|string)[]  $input
     * @return string[]
     */
    private function validate_license_type( $input )
    {
        $errors = NULL;

        if ( $input['license_agree'] != 'agree' ) {
            $errors['license_agree'] =
                'Cannot proceed without agreement to EULA';
        }

        if ( $input['install_type'] == '' ) {
            $errors['install_type'] = 'Please select one';
        }
        elseif ( $input['install_type'] == 'prod' ) {
            $err = $this->validate_serial_no($input['serial_no']);

            if ( $err != NULL ) {
                $errors['serial_no'] = $err;
            }
        }
        elseif ( $input['install_type'] == 'trial' ) {

            if ( $input['serial_no'] != '' ) {
                $errors['serial_no'] =
                    'Cannot select trial license with serial number.';
            }
        }
        else {
            $errors['install_type'] = 'Invalid type';
        }

        return $errors;
    }

    public function Validate_ConfigSuExec( $info )
    {
        $errors = array();
        $val_phpSuExec = $info['new']['phpSuExec'];
        $val_phpSuExecMaxConn = $info['new']['phpSuExecMaxConn'];

        if ( $val_phpSuExec !== '0' && $val_phpSuExec !== '1'
                && $val_phpSuExec !== '2' ) {

            $errors['phpSuExec'] = 'Invalid selection';
        }

        $val_phpSuExecMaxConn = intval($val_phpSuExecMaxConn);

        if ( $val_phpSuExecMaxConn <= 0 ) {
            $errors['phpSuExecMaxConn'] =
                'Need an integer number larger than 0';
        }

        if ( $info['cur']['phpSuExec'] == $val_phpSuExec
                && $info['cur']['phpSuExecMaxConn'] == $val_phpSuExecMaxConn ) {

            WhmPluginLogger::uiError(
                'No fields have been changed. No need to update.');
        }

        return $errors;
    }

    public function ConfigSuExec( $info )
    {
        $contents = file_get_contents($this->serv_conf);

        if ( $contents == FALSE ) {
            WhmPluginLogger::uiError('Failed to read config file.');
            return;
        }

        $pattern = array();
        $replacement = array();

        foreach ( $info['new'] as $key => $val ) {
            $pattern[] = "/<{$key}>(.*?)<\/{$key}>/s";
            $replacement[] = "<{$key}>{$val}</{$key}>";
        }

        $new_contents = preg_replace($pattern, $replacement, $contents, 1);

        if ( $new_contents != NULL ) {
            file_put_contents($this->serv_conf, $new_contents);

            $msg = 'Updated configuration file successfully. Changes to these '
                    . 'settings do not take effect until LiteSpeed Web Server '
                    . 'is restarted.';
            WhmPluginLogger::uiSuccess($msg);
        }
        else {
            WhmPluginLogger::uiError(preg_last_error());
        }
    }

    /**
     * Touches a flag file to inform LiteSpeed Web Server to restart running
     * detached PHP processes the next time the server uses that PHP handler.
     */
    public function restartDetachedPHP()
    {
        $restartPHPFlagFile = LSWS_HOME . '/admin/tmp/.lsphp_restart.txt';

        touch($restartPHPFlagFile);
    }

    /**
     * Recursively a directory's contents and optionally the directory itself.
     *
     * @param string   $dir         Directory path
     * @param boolean  $keepParent  Only remove directory contents when true.
     * @return boolean
     *
     * @noinspection PhpUnused
     */
    public static function rrmdir( $dir, $keepParent = false )
    {
        if ( $dir != '' && is_dir($dir) ) {

            foreach ( glob("{$dir}/*") as $file ) {

                if ( is_dir($file) ) {
                    self::rrmdir($file);
                }
                else {
                    unlink($file);
                }
            }

            if ( !$keepParent ) {
                rmdir($dir);
            }

            return true;
        }

        return false;
    }

    /**
     *
     * @param string  $dir
     * @return false|string
     *
     * @noinspection PhpUnused
     */
    public static function DirectoryMd5( $dir )
    {
        if ( !is_dir($dir) ) {
            return false;
        }

        $filemd5s = array();
        $d = dir($dir);

        while ( ($entry = $d->read()) !== false ) {

            if ( $entry != '.' && $entry != '..' ) {

                $currEntry = "{$dir}/{$entry}";

                if ( is_dir($currEntry) ) {
                    $filemd5s[] = self::DirectoryMd5($currEntry);
                }
                else {
                    $filemd5s[] = md5_file($currEntry);
                }
            }
        }

        $d->close();
        return md5(implode('', $filemd5s));
    }

    /**
     *
     * @since 3.3.4
     * @since 3.3.7  Added param $useOldMethod with a default value of false
     *               for backward compatibility.
     *
     * @param boolean $useOldMethod  Use old method of force updating shared
     *                               code library.
     * @return boolean
     */
    public static function updateSharedCode( $useOldMethod = false )
    {
        $updateCmd = LSWS_HOME . '/admin/misc/lscmctl';
        $updateCmd .= ($useOldMethod) ? ' --update-lib' : ' --force-update-lib';

        exec($updateCmd, $output, $ret);

        return ($ret == 0);
    }

    /**
     *
     * @since 4.1
     *
     * @return string[]
     */
    public static function getQuicCloudIps()
    {
        $ipList = array();

        $content = self::get_url_contents('https://quic.cloud/ips?json');

        if ( $content != '' ) {
            $ipList = json_decode($content, true);
        }

        return $ipList;
    }

    /**
     *
     * @param mixed[]  $ajaxInfo
     *
     * @noinspection PhpUnused
     */
    public static function ajaxReturn( $ajaxInfo )
    {
        echo json_encode($ajaxInfo);
        exit;
    }

    /**
     *
     * @since 4.1.3
     * @since 4.1.7  Function now returns a string value.
     *
     * @return string  Updated "Latest WHM plugin ver" file contents.
     */
    private static function updateLatestWhmPluginVerFile()
    {
        $newVersionFile = self::getLatestWhmPluginVerFilePath();

        $content = trim(
            self::get_url_contents(
                'https://www.litespeedtech.com/packages/cpanel/WHM_LATEST_VER'
            )
        );

        if ( empty($content)
                || ! preg_match('/^\d+\.(?:\d+\.)*\d+$/', $content) ) {

            $content = WhmMod_LiteSpeed_ControlApp::MODULE_VERSION;
        }

        file_put_contents($newVersionFile, $content);

        return $content;
    }

    /**
     *
     * @since 4.1.3
     *
     * @return string
     */
    public static function getLatestWhmPluginVer()
    {
        $newVersionFile = self::getLatestWhmPluginVerFilePath();

        if ( !file_exists($newVersionFile)
                || (time() - filemtime($newVersionFile)) > 86400 ) {

            return self::updateLatestWhmPluginVerFile();
        }

        return trim(file_get_contents($newVersionFile));
    }

    /**
     * Update current WHM plugin to the latest release version.
     *
     * @since 4.1.3
     */
    public static function updateToLatestWhmPlugin()
    {
        exec(
            WhmMod_LiteSpeed_ControlApp::CGI_DIR
                . '/lsws_whm_plugin_install.sh'
        );

        if ( defined('LSWS_HOME') && LSWS_HOME != '' ) {
            file_put_contents(
                WhmMod_LiteSpeed_ControlApp::CGI_DIR .'/LSWS_HOME.config',
                'LSWS_HOME=' . LSWS_HOME
            );
        }

        WhmPluginLogger::uiSuccess('Plugin updated successfully');
    }

}
