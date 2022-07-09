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
class SharedCodeUpdateResultViewModel extends BaseViewModel
{

    /**
     * @since 3.3.4
     * @var string
     */
    const FLD_UPDATED = 'updated';

    /**
     * @since 3.3.4
     * @var boolean
     */
    private $updated;

    /**
     *
     * @since 3.3.4
     *
     * @param boolean $updated
     */
    public function __construct( $updated )
    {
        parent::__construct();

        $this->updated = $updated;

        $this->init();
    }

    /**
     *
     * @since 3.3.4
     */
    private function init()
    {
        $this->setUpdatedData();
    }

    /**
     *
     * @since 3.3.4
     */
    private function setUpdatedData()
    {
        $this->tplData[self::FLD_UPDATED] = $this->updated;
    }

    /**
     *
     * @since 3.3.4
     *
     * @return string
     */
    public function getTpl()
    {
        return realpath(__DIR__ . '/../Tpl') . '/SharedCodeUpdateResult.tpl';
    }

}
