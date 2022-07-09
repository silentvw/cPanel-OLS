<?php

use LsPanel\View\Model\RestartDetachedPHPViewModel as ViewModel;

$iconDir = $this->viewModel->getTplData(ViewModel::FLD_ICON_DIR);

?>

<div id="heading">
  <h1>

    <?php if ( $iconDir != '' ) : ?>

    <span>
      <img src="<?php echo $iconDir; ?>/restartDetachedPHP.svg"
           alt="restart_detached_php_icon" />
    </span>

    <?php endif; ?>

    Confirm Operation... Restart Detached PHP Processes
  </h1>
</div>

<div>
  <p>
    This will inform LiteSpeed Web Server to restart all detached PHP processes
    the next time the server uses that PHP handler.
  </p>
</div>

<?php

$d = array(
    'back' => 'Back',
    'backDo' => 'main',
    'next' => 'Restart'
);
$this->loadTplBlock('ButtonPanelBackNext.tpl', $d);
