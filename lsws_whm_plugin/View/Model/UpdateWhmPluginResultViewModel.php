<?php

/** ******************************************
 * LiteSpeed Web Server Plugin for WHM
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2020
 * @since 4.1.3
 * ******************************************* */

namespace LsPanel\View\Model;

/**
 * @since 4.1.3
 */
class UpdateWhmPluginResultViewModel extends BaseViewModel
{

    /**
     *
     * @since 4.1.3
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     *
     * @since 4.1.3
     *
     * @return string
     */
    public function getTpl()
    {
        return realpath(__DIR__ . '/../Tpl') . '/UpdateWhmPluginResult.tpl';
    }

}
