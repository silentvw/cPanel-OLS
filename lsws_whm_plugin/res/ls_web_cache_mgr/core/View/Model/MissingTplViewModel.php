<?php

/* * ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 * @author: LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright: (c) 2018
 * ******************************************* */

namespace LsUserPanel\View\Model;

class MissingTplViewModel
{

    const FLD_MSG = 'msg';

    /**
     * @var string[]
     */
    private $tplData = array();

    /**
     *
     * @param string  $msg
     */
    public function __construct( $msg )
    {
        $this->init($msg);
    }

    /**
     *
     * @param string  $msg
     */
    private function init( $msg )
    {
        $this->tplData[self::FLD_MSG] = $msg;
    }

    /**
     *
     * @param string  $field
     * @return null|string
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
     * @return string
     */
    public function getTpl()
    {
        return realpath(__DIR__ . '/../Tpl') . '/MissingTpl.tpl';
    }

}
