<?php

/* * ******************************************
 * LiteSpeed Web Server Plugin for WHM
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2019
 * @since 3.3.4
 * ******************************************* */

namespace LsPanel\View\Model;

use \LsPanel\View\Model\BaseViewModel;

/**
 * @since 3.3.4
 */
class MissingTplViewModel extends BaseViewModel
{

    /**
     * @since 3.3.4
     * @var string
     */
    const FLD_MSG = 'msg';

    /**
     *
     * @since 3.3.4
     *
     * @param string  $msg
     */
    public function __construct( $msg )
    {
        parent::__construct();

        $this->init($msg);
    }

    /**
     *
     * @since 3.3.4
     *
     * @param string  $msg
     */
    private function init( $msg )
    {
        $this->tplData[self::FLD_MSG] = $msg;
    }

    /**
     *
     * @since 3.3.4
     *
     * @return string
     */
    public function getTpl()
    {
        return realpath(__DIR__ . '/../Tpl') . '/MissingTpl.tpl';
    }

}
