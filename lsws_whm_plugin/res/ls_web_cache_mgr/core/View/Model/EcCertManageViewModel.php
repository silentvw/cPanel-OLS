<?php

/** ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2020
 * @since 2.1
 * ******************************************* */

namespace LsUserPanel\View\Model;

use \LsUserPanel\EcCertSite;
use \LsUserPanel\EcCertSiteStorage;
use \LsUserPanel\Ls_WebCacheMgr_Util;
use \LsUserPanel\Lsc\UserLogger;
use \LsUserPanel\Lsc\UserLSCMException;

/**
 *
 * @since 2.1
 */
class EcCertManageViewModel
{

    /**
     * @since 2.1
     * @var string
     */
    const FLD_SHOW_LIST = 'showList';

    /**
     * @since 2.1
     * @var string
     */
    const FLD_BTN_STATE = 'btnState';

    /**
     * @since 2.1
     * @var string
     */
    const FLD_HOME_DIR_LEN = 'homeDirLen';

    /**
     * @since 2.1
     * @var string
     */
    const FLD_LIST_DATA = 'listData';

    /**
     * @since 2.1
     * @var string
     */
    const FLD_INFO_MSGS = 'infoMsgs';

    /**
     * @since 2.1
     * @var string
     */
    const FLD_ERR_MSGS = 'errMsgs';

    /**
     * @since 2.1
     * @var string
     */
    const FLD_SUCC_MSGS = 'succMsgs';

    /**
     * @since 2.1
     * @var EcCertSiteStorage
     */
    private $ecCertSiteStorage;

    /**
     * @since 2.1
     * @var mixed[]
     */
    private $tplData = array();

    /**
     *
     * @since 2.1
     *
     * @param EcCertSiteStorage  $ecCertSiteStorage
     * @throws UserLSCMException  Thrown indirectly.
     */
    public function __construct( EcCertSiteStorage $ecCertSiteStorage )
    {
        $this->ecCertSiteStorage = $ecCertSiteStorage;
        $this->init();
    }

    /**
     *
     * @since 2.1
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function init()
    {
        $this->setBtnDataAndListVisibility();
        $this->setHomeDirLenData();
        $this->setListData();
        $this->setMsgData();
    }

    /**
     *
     * @since 2.1
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

    /**
     *
     * @since 2.1
     */
    private function setBtnDataAndListVisibility()
    {
        $btnState = 'disabled';

        if ( ($errStatus = $this->ecCertSiteStorage->getError()) !== 0 ) {
            $this->tplData[self::FLD_SHOW_LIST] = false;

            if ( $errStatus == EcCertSiteStorage::ERR_NOT_EXIST ) {
                $msg =
                    _('Start by clicking "Update List" to populate the list.');
            }
            elseif ( $errStatus == EcCertSiteStorage::ERR_VERSION_LOW ) {
                $msg = _(
                    'To further improve EC Management features in this '
                        . 'version, current list must be updated. Please '
                        . '"Update List" now.'
                );
            }
            else {
                $msg =
                    _('List data could not be read. Please "Update List".');
            }

            UserLogger::addUiMsg($msg, UserLogger::UI_INFO);
        }
        else {
            $this->tplData[self::FLD_SHOW_LIST] = true;
            $discoveredCount = $this->ecCertSiteStorage->getCount();

            if ( $discoveredCount > 0 ) {
                $btnState = '';
            }
        }

        $this->tplData[self::FLD_BTN_STATE] = $btnState;
    }

    /**
     *
     * @since 2.1
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    private function setHomeDirLenData()
    {
        $this->tplData[self::FLD_HOME_DIR_LEN] =
            Ls_WebCacheMgr_Util::getHomeDirLen();
    }

    /**
     *
     * @since 2.1
     */
    private function setListData()
    {
        $listData = array();

        if ( ($ecCertSites = $this->ecCertSiteStorage->getEcCertSites()) !== null ) {

            foreach ( $ecCertSites as $ecCertSite ) {
                $info = array(
                    'docroot' =>
                        $ecCertSite->getData(EcCertSite::FLD_DOCROOT),
                    'hasSslVh' =>
                        $ecCertSite->getData(EcCertSite::FLD_HAS_SSL_VH),
                    'ecCertExists' =>
                        $ecCertSite->getData(EcCertSite::FLD_EC_EXISTS),
                    'coveredDomains' =>
                        $ecCertSite->getData(EcCertSite::FLD_EC_CERT_COVERED),
                    'lastGenMsg' =>
                        $ecCertSite->getData(EcCertSite::FLD_LAST_GEN_MSG)
                );

                $listData[$ecCertSite->getData(EcCertSite::FLD_SERVERNAME)] =
                    $info;
            }
        }

        if ( !empty($listData) ) {
            ksort($listData);
        }

        $this->tplData[self::FLD_LIST_DATA] = $listData;
    }

    /**
     *
     * @since 2.1
     */
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
     * @since 2.1
     *
     * @return string
     */
    public function getTpl()
    {
        return realpath(__DIR__ . '/../Tpl') . '/EcCertManage.tpl';
    }

}
