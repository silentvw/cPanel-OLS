<?php

/* * ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 * @author: LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright: (c) 2018
 * ******************************************* */

namespace LsUserPanel\View\Model;

use \LsUserPanel\Lsc\UserLogger;

class AdvancedOperationsViewModel
{

    const FLD_ERR_MSGS = 'errMsgs';
    const FLD_SUCC_MSGS = 'succMsgs';

    /**
     * @var string[]
     */
    private $errMsgs;

    /**
     * @var string[]
     */
    private $succMsgs;

    public function __construct()
    {
        $this->init();
    }

    private function init()
    {
        $this->setMsgData();
    }

    /**
     *
     * @param string  $field
     * @return null|string[]
     */
    public function getTplData( $field )
    {
        switch ($field) {
            case self::FLD_ERR_MSGS:
                return $this->errMsgs;
            case self::FLD_SUCC_MSGS:
                return $this->succMsgs;
            default:
                return null;
        }
    }

    private function setMsgData()
    {
        $this->errMsgs = UserLogger::getUiMsgs(UserLogger::UI_ERR);
        $this->succMsgs = UserLogger::getUiMsgs(UserLogger::UI_SUCC);
    }

    /**
     *
     * @return string
     */
    public function getTpl()
    {
        return realpath(__DIR__ . '/../Tpl') . '/AdvancedOperations.tpl';
    }

}
