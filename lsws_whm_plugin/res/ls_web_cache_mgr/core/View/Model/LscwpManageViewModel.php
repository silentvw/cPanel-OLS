<?php

/** ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author: LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright: (c) 2018-2020
 * ******************************************* */

namespace LsUserPanel\View\Model;

use \LsUserPanel\Ls_WebCacheMgr_Util;
use \LsUserPanel\Lsc\UserLogger;
use \LsUserPanel\Lsc\UserLSCMException;
use \LsUserPanel\Lsc\UserUtil;
use \LsUserPanel\Lsc\UserWPInstall;
use \LsUserPanel\Lsc\UserWPInstallStorage;

class LscwpManageViewModel
{

    const FLD_SHOW_LIST = 'showList';
    const FLD_SCAN_BTN_NAME = 'scanBtnName';
    const FLD_BTN_STATE = 'btnState';
    const FLD_VH_CACHE_DIR = 'vhCacheDir';
    const FLD_VH_CACHE_DIR_EXISTS = 'vhCacheDirExists';
    const FLD_HOME_DIR_LEN = 'homeDirLen';
    const FLD_LIST_DATA = 'listData';
    const FLD_INFO_MSGS = 'infoMsgs';
    const FLD_ERR_MSGS = 'errMsgs';
    const FLD_SUCC_MSGS = 'succMsgs';

    /**
     * @var UserWPInstallStorage
     */
    private $wpInstallStorage;

    /**
     * @var mixed[]
     */
    private $tplData = array();

    /**
     *
     * @param UserWPInstallStorage  $wpInstallStorage
     * @throws UserLSCMException  Thrown indirectly.
     */
    public function __construct( UserWPInstallStorage $wpInstallStorage )
    {
        $this->wpInstallStorage = $wpInstallStorage;
        $this->init();
    }

    /**
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function init()
    {
        $this->setBtnDataAndListVisibility();
        $this->setVhCacheDirData();
        $this->setHomeDirLenData();
        $this->setListData();
        $this->setMsgData();
    }

    /**
     *
     * @param string  $field
     * @return null|mixed
     */
    public function getTplData( $field )
    {
        if ( !isset($this->tplData[$field]) ) {
            return null;
        }

        return $this->tplData[$field];
    }

    private function setBtnDataAndListVisibility()
    {
        $scanBtnName = _('Scan');
        $btnState = 'disabled';

        if ( ($errStatus = $this->wpInstallStorage->getError()) !== 0 ) {
            $this->tplData[self::FLD_SHOW_LIST] = false;

            if ( $errStatus == UserWPInstallStorage::ERR_NOT_EXIST ) {
                $msg = _('Start by clicking Scan. This will discover all active WordPress installations and add them to the list below.');
            }
            elseif ( $errStatus == UserWPInstallStorage::ERR_VERSION_LOW ) {
                $msg = _('To further improve Cache Management features in this version, current installations must be re-discovered. Please perform a Scan now.');
            }
            else {
                $scanBtnName = _('Re-scan');
                $msg = _('Scan data could not be read. Please perform a Re-scan.');
            }

            UserLogger::addUiMsg($msg, UserLogger::UI_INFO);
        }
        else {
            $this->tplData[self::FLD_SHOW_LIST] = true;
            $discoveredCount = $this->wpInstallStorage->getCount();

            if ( $discoveredCount > 0 ) {
                $btnState = '';
            }
        }

        $this->tplData[self::FLD_SCAN_BTN_NAME] = $scanBtnName;
        $this->tplData[self::FLD_BTN_STATE] = $btnState;
    }

    private function setVhCacheDirData()
    {
        $cacheDir = UserUtil::getUserCacheDir();
        $exists = ($cacheDir == '') ? false : file_exists($cacheDir);

        $this->tplData[self::FLD_VH_CACHE_DIR] = $cacheDir;
        $this->tplData[self::FLD_VH_CACHE_DIR_EXISTS] = $exists;
    }

    /**
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function setHomeDirLenData()
    {
        $this->tplData[self::FLD_HOME_DIR_LEN] =
                Ls_WebCacheMgr_Util::getHomeDirLen();
    }

    private function setListData()
    {
        $listData = array();

        if ( ($wpInstalls = $this->wpInstallStorage->getWPInstalls()) !== null ) {

            foreach ( $wpInstalls as $wpInstall ) {

                if ( !$wpInstall->shouldRemove() ) {
                    $info = array(
                        'statusData' => $this->getStatusDisplayData($wpInstall),
                        'flagData' => $this->getFlagDisplayData($wpInstall),
                        'siteUrl' => UserUtil::tryIdnToUtf8(
                            $wpInstall->getData(UserWPInstall::FLD_SITEURL)
                        ),
                        'isLscwpEnabled' => $wpInstall->isLscwpEnabled()
                    );

                    $listData[$wpInstall->getPath()] = $info;
                }
            }
        }

        $this->tplData[self::FLD_LIST_DATA] = $listData;
    }

    /**
     *
     * @param UserWPInstall  $wpInstall
     * @return string[][]
     */
    private function getStatusDisplayData( UserWPInstall $wpInstall )
    {
        $statusInfo = array(
            'disabled' => array(
                'state' => '<font color="#AAAAAA">' . _('Disabled') . '</font>',
                'btn_name' => 'enable_single',
                'btn_action' => _('Enable'),
                'btn_msg' => sprintf(_('Enable cache for %s'), ''),
                'btn_icon' => '<span class="glyphicon glyphicon-flash"></span>'
            ),
            'enabled' => array(
                'state' => '<font color="#00D000">' . _('Enabled') . '</font>',
                'btn_name' => 'disable_single',
                'btn_action' => _('Disable'),
                'btn_msg' => sprintf(_('Disable & uninstall cache for %s'), ''),
                'btn_icon' => '<span class="glyphicon glyphicon-remove"></span>'
            ),
            'adv_cache' => array(
                'state' => '<span title="'
                . _('LSCache is enabled but not caching. Please visit the WordPress Dashboard for more information.')
                . '"><font color="#F0BC3F">' . _('Warning') . '</font></span>',
                'btn_name' => 'disable_single',
                'btn_action' => _('Disable'),
                'btn_msg' => sprintf(_('Disable & uninstall cache for %s'), ''),
                'btn_icon' => '<span class="glyphicon glyphicon-remove"></span>'
            ),
            'error' => array(
                /**
                 * 'state' added individually later.
                 */
                'btn_name' => 'enable_single',
                'btn_action' => _('Enable'),
                'btn_msg' => sprintf(_('Enable cache for %s'), ''),
                'btn_icon' => '<span class="glyphicon glyphicon-flash"></span>'
            )
        );

        $wpStatus = $wpInstall->getStatus();

        if ( $wpInstall->hasFatalError($wpStatus) ) {
            $link = 'https://docs.litespeedtech.com/cp/cpanel'
                . '/wp-cache-management/#whm-plugin-cache-manager-error-status';

            if ( $wpStatus & UserWPInstall::ST_ERR_EXECMD ) {
                $stateMsg =
                        _('WordPress fatal error encountered during action execution. This is most likely caused by custom code in this WordPress installation.');
                $link .= '#fatal_error_encountered_during_action_execution';
            }
            if ( $wpStatus & UserWPInstall::ST_ERR_EXECMD_DB ) {
                $stateMsg = _('Error establishing WordPress database connection.');
                $link .= '#';
            }
            elseif ( $wpStatus & UserWPInstall::ST_ERR_SITEURL ) {
                $stateMsg = _('Could not retrieve WordPress siteURL.');
                $link .= '#could_not_retrieve_wordpress_siteurl';
            }
            elseif ( $wpStatus & UserWPInstall::ST_ERR_DOCROOT ) {
                $stateMsg = _('Could not match WordPress siteURL to a known cPanel docroot.');
                $link .= '#could_not_match_wordpress_siteurl_to_a_known_cpanel_docroot';
            }
            elseif ( $wpStatus & UserWPInstall::ST_ERR_WPCONFIG ) {
                $stateMsg = _('Could not find a valid wp-config.php file.');
                $link .= '#could_not_find_a_valid_wp-configphp_file';
            }

            $stateMsg .= ' ' . _('Click for more information.');

            $currStatusData = $statusInfo['error'];
            $currStatusData['state'] = "<u><a href=\"{$link}\" target=\"_blank\" rel=\"noopener\" "
                    . "title =\"{$stateMsg}\"><font color=\"#DD0000\">" . _('Error')
                    . '</font></a></u>';
        }
        elseif ( ($wpStatus & UserWPInstall::ST_PLUGIN_INACTIVE ) ) {
            $currStatusData = $statusInfo['disabled'];
        }
        elseif ( !($wpStatus & UserWPInstall::ST_LSC_ADVCACHE_DEFINED) ) {
            $currStatusData = $statusInfo['adv_cache'];
        }
        else {
            $currStatusData = $statusInfo['enabled'];
        }

        return $currStatusData;
    }

    /**
     *
     * @param UserWPInstall  $wpInstall
     * @return string[][]
     */
    private function getFlagDisplayData( UserWPInstall $wpInstall )
    {
        $flagInfo = array(
            //unflagged
            0 => array(
                'icon' => '',
                'btn_name' => 'flag_single',
                'btn_action' => _('Flag'),
                'btn_msg' => sprintf(_('Set flag for %s'), '')
            ),
            //flagged
            1 => array(
                'icon' => '<span class="glyphicon glyphicon-flag" '
                        . 'title="' . _('Flagged as excluded from WHM Mass Enable/Disable')
                        . '"></span>',
                'btn_name' => 'unflag_single',
                'btn_action' => _('Unflag'),
                'btn_msg' => sprintf(_('Unset flag for %s'), '')
            )
        );

        $wpStatus = $wpInstall->getStatus();

        if ( ($wpStatus & UserWPInstall::ST_FLAGGED ) ) {
            $currFlagData = $flagInfo[1];
        }
        else {
            $currFlagData = $flagInfo[0];
        }

        return $currFlagData;
    }

    private function setMsgData()
    {
        $this->tplData[self::FLD_INFO_MSGS] =
                UserLogger::getUiMsgs(UserLogger::UI_INFO);
        $this->tplData[self::FLD_ERR_MSGS] =
                UserLogger::getUiMsgs(UserLogger::UI_ERR);
        $this->tplData[self::FLD_SUCC_MSGS] =
                UserLogger::getUiMsgs(UserLogger::UI_SUCC);
    }

    /**
     *
     * @return string
     */
    public function getTpl()
    {
        return realpath(__DIR__ . '/../Tpl') . '/LscwpManage.tpl';
    }

}
