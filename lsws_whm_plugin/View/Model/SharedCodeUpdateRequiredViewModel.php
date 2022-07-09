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
class SharedCodeUpdateRequiredViewModel extends BaseViewModel
{

    /**
     *
     * @since 3.3.4
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     *
     * @since 3.3.4
     *
     * @return string
     */
    public function getTpl()
    {
        return realpath(__DIR__ . '/../Tpl') . '/SharedCodeUpdateRequired.tpl';
    }

}
