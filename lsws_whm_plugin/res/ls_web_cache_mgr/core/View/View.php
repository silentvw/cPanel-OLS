<?php

/* * ******************************************
 * LiteSpeed Web Cache Management Plugin for cPanel
 * @Author: LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @Copyright: (c) 2018-2019
 * ******************************************* */

namespace LsUserPanel\View;

use \LsUserPanel\Ls_WebCacheMgr_Util;
use \LsUserPanel\Lsc\UserLSCMException;

class View
{

    /**
     * @var object
     */
    private $viewModel;

    /**
     *
     * @param object  $viewModel
     */
    public function __construct( $viewModel )
    {
        $this->viewModel = $viewModel;
    }

    /**
     *
     * @throws UserLSCMException  Thrown indirectly.
     */
    public function display()
    {
        $this->loadTpl($this->viewModel->getTpl());
    }

    /**
     *
     * @param string  $tplPath
     * @throws UserLSCMException  Thrown directly and indirectly.
     */
    private function loadTpl( $tplPath )
    {
        if ( file_exists($tplPath) ) {
            $do = Ls_WebCacheMgr_Util::get_request_var('do');

            $d = array(
                'do' => $do
            );
            $this->loadTplBlock('PageHeader.tpl', $d);

            include $tplPath;

            $d = array();
            $this->loadTplBlock('PageFooter.tpl', $d);
        }
        else {
            throw new UserLSCMException("Could not load page template {$tplPath}.");
        }
    }

    /**
     * Used by the page template to load sub-template blocks.
     *
     * @param string  $tplName
     * @param array   $d        Sub-template data.
     * @throws UserLSCMException
     */
    private function loadTplBlock( $tplName, $d )
    {
        $tplPath = __DIR__ . "/Tpl/Blocks/{$tplName}";

        if ( file_exists($tplPath) ) {
            include $tplPath;
        }
        else {
            throw new UserLSCMException("Could not load block template {$tplPath}.");
        }
    }

}
