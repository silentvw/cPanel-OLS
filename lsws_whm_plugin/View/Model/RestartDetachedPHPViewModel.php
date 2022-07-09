<?php

/* * ******************************************
 * LiteSpeed Web Server Plugin for WHM
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2018-2020
 * ******************************************* */

namespace LsPanel\View\Model;

use \LsPanel\View\Model\BaseViewModel;
use \LsPanel\WhmMod_LiteSpeed_ControlApp;

class RestartDetachedPHPViewModel extends BaseViewModel
{

    const STEP_CONFIRM = 0;
    const STEP_DO_ACTION = 1;

    const FLD_ICON_DIR = 'iconDir';

    /**
     * @var int
     */
    private $step;

    /**
     *
     * @param int  $step
     */
    public function __construct( $step )
    {
        parent::__construct();

        $this->step = $step;

        $this->init();
    }

    private function init()
    {
        $this->setIconDir();
    }

    private function setIconDir()
    {
        $this->tplData[self::FLD_ICON_DIR] =
                WhmMod_LiteSpeed_ControlApp::ICON_DIR;
    }

    /**
     *
     * @return string
     */
    public function getTpl()
    {
        $tplDir = realpath(__DIR__ . '/../Tpl');

        if ( $this->step == self::STEP_DO_ACTION) {
            return  "{$tplDir}/RestartDetachedPHP.tpl";
        }

        return "{$tplDir}/RestartDetachedPHPConfirm.tpl";
    }

}
