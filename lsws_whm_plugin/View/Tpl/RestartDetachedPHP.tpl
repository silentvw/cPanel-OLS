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

    Restart Detached PHP Processes
  </h1>
</div>

<div class="msg-box msg-success">
  <ul>
    <li>
      LSWS notified to restart all detached PHP processes.
    </li>
  </ul>
</div>

<?php

$d = array(
    'back' => 'OK',
    'backDo' => 'main',
);
$this->loadTplBlock('ButtonPanelBackNext.tpl', $d);
