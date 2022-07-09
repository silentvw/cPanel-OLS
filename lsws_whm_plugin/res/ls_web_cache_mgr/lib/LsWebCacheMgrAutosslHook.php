#!/usr/local/cpanel/3rdparty/bin/php -q

<?php

/********************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2020-2021
 * @since 2.1
 *********************************************/

use LsUserPanel\PluginSettings;

// Get decoded input.
$input = get_passed_data();

list($result_result, $result_message) = doUpdateEcCertHook($input);

// Return the return variables.
echo "$result_result $result_message";

/**
 *
 * @since 2.1
 *
 * @param array  $input
 * @return array
 */
function doUpdateEcCertHook($input )
{
    $result = 1;
    $msg = "Unexpected return.";

    $lsWebCacheMgr = new LsWebCacheMgr($input);

    try {
        $lsWebCacheMgr->tryUpdateEcCert();
    }
    catch ( Exception $e ) {
        $result =  $e->getCode();
        $msg = $e->getMessage();
    }

    return array( $result, $msg);
}

/**
 * Process data from STDIN.
 *
 * @return array[]|mixed
 */
function get_passed_data()
{

    $raw_data = '';

    $stdin_fh = fopen('php://stdin', 'r');

    if ( is_resource($stdin_fh) ) {
        stream_set_blocking($stdin_fh, 0);

        while ( ($line = fgets( $stdin_fh, 1024 )) !== false ) {
            $raw_data .= trim($line);
        }

        fclose($stdin_fh);
    }

    if ( $raw_data != '' ) {
        $input_data = json_decode($raw_data, true);
    }
    else {
        $input_data = array(
            'context'=>array(),
            'data'=>array(),
            'hook'=>array()
        );
    }

    // Return the output.
    return $input_data;
}

class LsWebCacheMgr
{

    /**
     * @since 2.1
     * @var int
     */
    const EXIT_SUCC = 0;
    /**
     * @since 2.1
     * @var int
     */
    const EXIT_ERR = 1;

    /**
     * @deprecated 2.1.3  No longer used.
     * @since 2.1
     * @var string
     */
    const PLUGIN_DIR =
        '/usr/local/cpanel/base/frontend/paper_lantern/ls_web_cache_manager';

    /**
     * @since 2.1.3
     * @var string
     */
    const THEME_JUPITER_PLUGIN_DIR =
        '/usr/local/cpanel/base/frontend/jupiter/ls_web_cache_manager';

    /**
     * @since 2.1.3
     * @var string
     */
    const THEME_PAPER_LANTERN_PLUGIN_DIR =
        '/usr/local/cpanel/base/frontend/paper_lantern/ls_web_cache_manager';

    /**
     * @since 2.1
     * @var array
     */
    private $input;

    /**
     *
     * @since 2.1
     *
     * @param $input
     */
    public function __construct( $input )
    {
        $this->input = $input;
    }

    /**
     *
     * @since 2.1
     */
    private function selfRemoveHook()
    {
        $cmd = '/usr/local/cpanel/bin/manage_hooks delete script '
            . '/usr/local/cpanel/3rdparty/bin/LsWebCacheMgrAutosslHook.php '
            . '--manual 1 --category Whostmgr --event AutoSSL::installssl '
            . '--stage pre';

        exec($cmd);

        unlink(__FILE__);
    }

    /**
     *
     * @since 2.1
     *
     * @throws Exception
     */
    private function runEcCertGen()
    {
        if ( !isset($this->input['data']['web_vhost_name']) ) {
            throw new Exception(
                'Expected input param \'web_vhost_name\' not set',
                self::EXIT_ERR
            );
        }

        $domain = $this->input['data']['web_vhost_name'];

        $combinedEcCertFile = "/var/cpanel/ssl/apache_tls/$domain/combined.ecc";

        if ( !file_exists($combinedEcCertFile) ) {
            throw new Exception(
                'No EC cert exist for this domain. Nothing to do.',
                self::EXIT_SUCC
            );
        }

        exec(
            '/usr/local/cpanel/bin/whmapi1 getdomainowner '
                . 'domain=' . escapeshellarg($domain)
                . ' --output=json',
            $output1,
            $ret
        );

        $data = json_decode($output1[0], true);

        if ( !isset($data['data']['user']) ) {
            throw new Exception(
                'Failed to get domain owner.',
                self::EXIT_ERR
            );
        }

        $user = $data['data']['user'];

        if ( file_exists(self::THEME_JUPITER_PLUGIN_DIR) ) {
            $pluginDir = self::THEME_JUPITER_PLUGIN_DIR;
        }
        else {
            $pluginDir = self::THEME_PAPER_LANTERN_PLUGIN_DIR;
        }

        exec(
            "$pluginDir/scripts/cert_action_entry geneccert "
                . '-user ' . escapeshellarg($user)
                . ' -domain ' . escapeshellarg($domain),
            $output2,
            $ret
        );

        $msg = $output2[0];

        if ( $ret == 0 ) {
            throw new Exception($msg, self::EXIT_SUCC);
        }
        else {
            throw new Exception($msg, self::EXIT_ERR);
        }
    }

    /**
     *
     * @since 2.1
     *
     * @throws Exception  Thrown directly and indirectly.
     */
    public function tryUpdateEcCert()
    {
        if ( file_exists(self::THEME_JUPITER_PLUGIN_DIR) ) {
            $pluginDir = self::THEME_JUPITER_PLUGIN_DIR;
        }
        elseif ( file_exists(self::THEME_PAPER_LANTERN_PLUGIN_DIR) ) {
            $pluginDir = self::THEME_PAPER_LANTERN_PLUGIN_DIR;
        }
        else {
            $this->selfRemoveHook();

            throw new Exception(
                'ls_web_cache_mgr user-end cPanel plugin not installed. Hook '
                    . 'removed.',
                self::EXIT_ERR
            );
        }

        $pluginSettingClassFile = "$pluginDir/core/PluginSettings.php";

        if ( !file_exists($pluginSettingClassFile) ) {
            throw new Exception(
                'Could not find cPanel user-end plugin file '
                    . 'PluginSettings.php. Aborting.',
                self::EXIT_ERR
            );
        }

        include_once $pluginSettingClassFile;

        if ( $this->getEcCertSetting() == PluginSettings::SETTING_OFF ) {
            exec("$pluginDir/scripts/cert_support_remove.sh");

            throw new Exception(
                'EC cert support is not enabled for ls_web_cache_mgr user-end '
                    . 'cPanel plugin. Hook removed.',
                self::EXIT_ERR
            );
        }

        $this->runEcCertGen();
    }

    /**
     *
     * @since 2.1
     *
     * @return int
     */
    public function getEcCertSetting()
    {
        if ( file_exists(PluginSettings::PLUGIN_CONF_FILE) ) {
            $confFileContents =
                file_get_contents(PluginSettings::PLUGIN_CONF_FILE);

            if ( $confFileContents != false ) {
                preg_match('/GENERATE_EC_CERTS = (\d)/', $confFileContents, $m);

                if ( isset($m[1]) ) {
                    return (int)$m[1];
                }
            }
        }

        return PluginSettings::SETTING_OFF;
    }

}