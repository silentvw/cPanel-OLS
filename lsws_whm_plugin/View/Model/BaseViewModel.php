<?php

/* * ******************************************
 * LiteSpeed Web Server Plugin for WHM
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2019
 * @since 3.3.4
 * ******************************************* */

namespace LsPanel\View\Model;

/**
 * @since 3.3.4
 */
abstract class BaseViewModel
{

    /**
     * @since 3.3.4
     * @var mixed[]
     */
    protected $tplData = array();

    /**
     *
     * @since 3.3.4
     */
    public function __construct(){}

    /**
     *
     * @since 3.3.4
     *
     * @param string  $field
     * @return mixed
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
     * @since 3.3.4
     *
     * @return string
     */
    abstract public function getTpl();

}
