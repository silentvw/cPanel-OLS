<?php

/** ******************************************
 * LiteSpeed Web Server Plugin for WHM
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2020
 * @since 4.1
 * ******************************************* */

namespace LsPanel\View\Model;

use \LsPanel\WhmMod_LiteSpeed_ControlApp as Controller;
use \LsPanel\WhmMod_LiteSpeed_Util as Util;

class QuicCloudIpsViewModel extends BaseViewModel
{

    const FLD_ICON_DIR = 'iconDir';
    const FLD_QUIC_CLOUD_IPS = 'quicCloudIps';

    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    private function init()
    {
        $this->setIconDir();
        $this->setQuicCloudIps();
    }

    private function setIconDir()
    {
        $this->tplData[self::FLD_ICON_DIR] = Controller::ICON_DIR;
    }

    private function setquicCloudIps()
    {
        $this->tplData[self::FLD_QUIC_CLOUD_IPS] = Util::getQuicCloudIps();
    }

    /**
     *
     * @since 4.1
     *
     * @return string
     */
    public function getTpl()
    {
        return realpath(__DIR__ . '/../Tpl') . '/QuicCloudIps.tpl';
    }

}