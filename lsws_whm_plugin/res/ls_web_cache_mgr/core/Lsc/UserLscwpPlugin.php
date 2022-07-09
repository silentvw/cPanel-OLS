<?php

/** *********************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 * @Author: LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @Copyright: (c) 2019
 * *******************************************
 */

namespace LsUserPanel\Lsc;

use \LsUserPanel\CPanelWrapper;

class UserLscwpPlugin
{

    public static function retrieveTranslation( $locale, $pluginVer )
    {
        UserLogger::info("Downloading LSCache for WordPress {$locale} translation...");

        $cpanel = CPanelWrapper::getCpanelObj();

        $result = $cpanel->uapi('lsws', 'retrieveLscwpTranslation',
                array( 'locale' => $locale, 'pluginVer' => $pluginVer ));

        return (bool) $result['cpanelresult']['result']['data']['result'];
    }

    public static function removeTranslationZip( $locale, $pluginVer )
    {
        UserLogger::info("Removing LSCache for WordPress {$locale} translation...");

        $cpanel = CPanelWrapper::getCpanelObj();

        $cpanel->uapi('lsws', 'removeLscwpTranslationZip',
                array( 'locale' => $locale, 'pluginVer' => $pluginVer ));
    }

}