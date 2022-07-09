<?php

/** ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2018-2020
 * ******************************************* */

namespace LsUserPanel\Lsc\Panel;

use \LsUserPanel\CPanelWrapper;
use \LsUserPanel\Lsc\UserLogger;
use \LsUserPanel\Lsc\UserWPInstall;

class UserCPanel extends UserControlPanel
{

    protected function __construct()
    {
        $this->panelName = 'cPanel/WHM';

        parent::__construct();
    }

    /**
     * Gets a list of found docroots and associated server names.
     * Only needed for scan.
     */
    protected function prepareDocrootMap()
    {
        $cpanel = CPanelWrapper::getCpanelObj();

        $result = $cpanel->uapi('lsws', 'getDocrootData');

        $lines = explode("\n", $result['cpanelresult']['result']['data']['docrootData']);

        /**
         * [0]=docroot, [1]=serveraliases, [2]=servername, [3]=docroot, etc.
         * Not unique & not sorted.
         *
         * Example:
         * documentroot: /home/user1/finches
         * serveralias: finches.com mail.finches.com www.finches.com www.finches.user1.com cpanel.finches.com autodiscover.finches.com whm.finches.com webmail.finches.com webdisk.finches.com
         * servername: finches.user1.com
         * documentroot: /home/user1/public_html/dookoo
         * serveralias: www.dookoo.user1.com
         * servername: dookoo.user1.com
         * documentroot: /home/user1/public_html/doo/doo2
         * serveralias: www.doo2.user1.com
         * servername: doo2.user1.com
         * documentroot: /home/user1/finches
         * serveralias: finches.com mail.finches.com www.finches.com www.finches.user1.com
         * servername: finches.user1.com
         */

        $cur = '';
        $docroots = array();

        foreach ( $lines as $line ) {

            if ( $cur == '' ) {

                if ( strpos($line, 'documentroot:') === 0 ) {
                    /**
                     * 14 is strlen('documentroot:')
                     */
                    $cur = trim(substr($line, 14));

                    if ( !isset($docroots[$cur]) ) {

                        if ( is_dir($cur) ) {
                            $docroots[$cur] = '';
                        }
                        else {
                            /**
                             * bad entry ignore
                             */
                            $cur = '';
                        }
                    }
                }
            }
            elseif ( strpos($line, 'serveralias:') === 0 ) {
                /**
                 * 12 is strlen('serveralias:')
                 */
                $docroots[$cur] .= substr($line, 12);
            }
            elseif ( strpos($line, 'servername:') === 0 ) {
                /**
                 * 11 is strlen('servername:')
                 */
                $docroots[$cur] .= substr($line, 11);

                /**
                 * looking for next docroot
                 */
                $cur = '';
            }
            else {
                UserLogger::logMsg("Unused line when preparing docroot map: {$line}.",
                        UserLogger::L_DEBUG);
            }
        }

        $roots = $servernames = array();
        $index = 0;

        foreach ( $docroots as $docroot => $line ) {
            $names = preg_split('/\s+/', trim($line), -1, PREG_SPLIT_NO_EMPTY);
            $names = array_unique($names);
            $roots[$index] = $docroot;

            foreach ( $names as $n ) {
                $servernames[$n] = $index;
            }

            $index ++;
        }

        $this->docRootMap = array( 'docroots' => $roots, 'names' => $servernames );
    }

    /**
     *
     * @param UserWPInstall  $wpInstall
     * @return string
     */
    public function getPhpBinary( UserWPInstall $wpInstall )
    {

        /**
         * cPanel php wrapper should accurately detect the correct binary in
         * EA4 when EA4 only directive '--ea-reference-dir' is provided.
         */
        $phpBin = '/usr/local/bin/php '
            . "--ea-reference-dir={$wpInstall->getPath()}/wp-admin";

        return "{$phpBin} {$this->phpOptions}";
    }

}
