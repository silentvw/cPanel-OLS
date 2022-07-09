<?php

/* * ******************************************
 * LiteSpeed Web Server Plugin for WHM
 *
 * @author LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @copyright (c) 2018-2019
 * ******************************************* */

namespace LsPanel\View;

use \Lsc\Wp\Context\Context;
use \Lsc\Wp\LSCMException;
use \LsPanel\WhmPluginException;
use \LsPanel\WhmPluginLogger;

class View
{

    /**
     * @var object
     */
    private $viewModel;

    /**
     * @var string
     */
    private $panelViewDir = __DIR__;

    /**
     *
     * @param object  $viewModel
     */
    public function __construct( $viewModel )
    {
        $this->viewModel = $viewModel;
    }

    /**
     * @throws WhmPluginException  Thrown indirectly.
     */
    public function display()
    {
        $this->loadTpl($this->viewModel->getTpl());
    }

    /**
     *
     * @param string  $tplPath
     * @throws WhmPluginException
     */
    private function loadTpl( $tplPath )
    {
        $tplFile = basename($tplPath);
        $custTpl = "{$this->panelViewDir}/Tpl/Cust/{$tplFile}";

        if ( file_exists($custTpl) ) {
            include $custTpl;
        }
        elseif ( file_exists($tplPath) ) {
            include $tplPath;
        }
        else {
            throw new WhmPluginException("Could not load page template {$tplPath}.");
        }
    }

    /**
     * Used by the page template to load sub-template blocks.
     *
     * @param string  $tplName
     * @param array   $d        Sub-template data.
     * @param bool    $shared   True if block tpl is found in shared directory.
     * @return null
     * @throws WhmPluginException
     */
    private function loadTplBlock( $tplName, $d, $shared = false )
    {
        $custTpl = "{$this->panelViewDir}/Tpl/Cust/Blocks/{$tplName}";

        if ( file_exists($custTpl) ) {
            include $custTpl;
            return;
        }

        if ( $shared
                && include_once LSWS_HOME . '/add-ons/webcachemgr/src/Context/Context.php' ) {

            try {
                $sharedTplDir = Context::getOption()->getSharedTplDir();
                $tplPath = "{$sharedTplDir}/Blocks/{$tplName}";
            }
            catch ( LSCMException $e ) {
                $msg = $e->getMessage() . ' Unable to get shared template directory.';
                WhmPluginLogger::error($msg);

                throw new WhmPluginException($msg);
            }
        }
        else {
            $tplPath = $this->panelViewDir . "/Tpl/Blocks/{$tplName}";
        }

        if ( file_exists($tplPath) ) {
            include $tplPath;
        }
        else {
            throw new WhmPluginException("Could not load block template {$tplPath}.");
        }
    }

}
