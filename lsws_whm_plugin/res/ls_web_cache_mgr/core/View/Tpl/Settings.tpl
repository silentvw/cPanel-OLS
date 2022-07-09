<?php

use LsUserPanel\View\Model\SettingsViewModel as ViewModel;

$logFile = $this->viewModel->getTplData(ViewModel::FLD_LOG_FILE);
$currLogLvl = $this->viewModel->getTplData(ViewModel::FLD_CURR_LOG_LVL);
$logLvls = $this->viewModel->getTplData(ViewModel::FLD_LOG_LVLS);
$errMsgs = $this->viewModel->getTplData(ViewModel::FLD_ERR_MSGS);
$succMsgs = $this->viewModel->getTplData(ViewModel::FLD_SUCC_MSGS);

$errMsgCnt = count($errMsgs);
$succMsgCnt = count($succMsgs);

if ( $errMsgCnt > 0 || $succMsgCnt > 0 ) {
    $msgsDisplay = 'initial';
}
else {
    $msgsDisplay = 'none';
}

?>

<div class="uk-container">

  <div id="display-msgs" style="display:<?php echo $msgsDisplay; ?>;">
    <button class="accordion accordion-error" type="button"
            style="display: <?php echo ($errMsgCnt > 0) ? 'initial' : 'none'; ?>">
      <?php echo _('Error Messages'); ?>
      <span id ="errMsgCnt" class="badge errMsg-badge">
          <?php echo $errMsgCnt; ?>
      </span>
    </button>
    <div class="panel panel-error">

      <?php

      $d = array(
          'id' => 'errMsgs',
          'msgs' => $errMsgs,
          'class' => 'scrollable',
      );
      $this->loadTplBlock('DivMsgBox.tpl', $d);

      ?>

    </div>
    <div id="succMsgs"
         style="display: <?php echo ($succMsgCnt > 0) ? 'initial' : 'none'; ?>">
      <ul>

        <?php

        if ( !is_array($succMsgs) ) {
            $succMsgs = array( $succMsgs );
        }

        $cleanedMsgs = array_map('htmlspecialchars', $succMsgs);

        echo '<li>' . implode('</li><li>', $cleanedMsgs) . '</li>';

        ?>

      </ul>
    </div>

  </div>

  <h2 class="uk-margin-bottom-remove ls-text-bold ls-text-slateblue">
    <i class="uk-icon uk-icon-cog ls-text-skyblue">&nbsp;</i>
    <?php echo _('Settings'); ?>
  </h2>
  <hr class="uk-margin-top-remove uk-width-large-3-10 uk-width-medium-1-1
        uk-width-small-1-1 ls-border" />
  <div id="settings">
    <span class="setting-title"><?php echo _('Log File Level'); ?></span>
    <select id="lscmLogLvl">

      <?php foreach ( $logLvls as $lvl => $val ): ?>

          <option value ="<?php echo $val; ?>"
                  <?php echo ($val == $currLogLvl) ? 'selected="selected"' : ''; ?> >
            <?php echo $lvl; ?>
          </option>

      <?php endforeach; ?>

    </select>
    <div class="setting-descr">
      <?php echo _('Log file location:') . " {$logFile}"; ?>
    </div>
  </div>

  <button class="uk-button uk-button-muted uk-margin uk-margin-large
          uk-width-medium-1-10 uk-width-small-1-5"
          onclick="javascript:lswsform.do.value = 'main';lswsform.submit();">
    <?php echo _('Back'); ?>
  </button>

  <button type="button" class="uk-button uk-button-primary ls-button-padding"
          title="Switch LiteSpeed Cache plugin to the version selected"
          onclick="javascript:lscmSaveSettings();">
    <?php echo _('Save'); ?>
  </button>

</div>

<script type="text/javascript">lswsInitDropdownBoxes();</script>