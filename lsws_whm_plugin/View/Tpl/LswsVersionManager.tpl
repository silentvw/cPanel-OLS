<?php

use LsPanel\View\Model\LswsVersionManagerViewModel as ViewModel;

$iconDir = $this->viewModel->getTplData(ViewModel::FLD_ICON_DIR);
$newVer = $this->viewModel->getTplData(ViewModel::FLD_LSWS_NEW_VER);
$currVer = $this->viewModel->getTplData(ViewModel::FLD_LSWS_VER);
$currBuild = $this->viewModel->getTplData(ViewModel::FLD_LSWS_CURR_BUILD);
$newBuild = $this->viewModel->getTplData(ViewModel::FLD_LSWS_NEW_BUILD);
$installed = $this->viewModel->getTplData(ViewModel::FLD_LSWS_INSTALLED_VERS);
$errMsgs = $this->viewModel->getTplData(ViewModel::FLD_ERR_MSGS);
$succMsgs = $this->viewModel->getTplData(ViewModel::FLD_SUCC_MSGS);

?>

<input type="hidden" name="act" /><input type="hidden" name="actId" />

<?php

$d = array (
    'title' => 'Version Management',
    'icon' => ($iconDir != '') ? "{$iconDir}/lsCurrentVersion.svg" : ''
);
$this->loadTplBlock('Title.tpl', $d);

if ( !empty($errMsgs) ) {
    $d = array(
        'msgs' => $errMsgs,
        'class' => 'msg-error scrollable',
    );
    $this->loadTplBlock('DivMsgBox.tpl', $d);
}

if ( !empty($succMsgs) ) {
    $d = array(
        'msgs' => $succMsgs,
        'class' => 'msg-success scrollable',
    );
    $this->loadTplBlock('DivMsgBox.tpl', $d);
}

?>

<div class="content-area">
  <table class="datatable">
    <tbody>

      <?php if ( $newVer != '' && !in_array($newVer, $installed) ) : ?>

      <tr>
        <th>Latest Release</th>
        <th>Action</th>
      </tr>
      <tr class="odd">
        <td class="center"><?php echo $newVer; ?></td>
        <td>
          <button class="input-button"
                  onclick="vermgr('download','<?php echo $newVer; ?>');">
            Upgrade
          </button>
        </td>
      </tr>

      <?php endif; ?>

      <tr>
        <th>Installed Versions</th>
        <th>Actions</th>
      </tr>

      <?php

      $d = array(
          'title' => 'Installed Versions'
      );
      $this->loadTplBlock('SectionTitle.tpl', $d);

      $i = 0;

      foreach ( $installed as $rel ) :
          $style = ((( ++$i) % 2) == 0) ? 'even' : 'odd';

      ?>

      <tr class="<?php echo $style; ?>">
        <td class="center">
          <?php echo $rel; ?>

          <?php

          if ( $rel == $currVer ) :

              if ( !empty($currBuild) ) :

          ?>

          (build <?php echo $currBuild; ?>)

          <?php

              endif;

              if ( $iconDir != '' ) :

          ?>


          <img title="Current Active Version"
               src="<?php echo "{$iconDir}/checkmark.png"; ?>" alt="Active" />

          <?php

              endif;
          endif;

          ?>

        </td>
        <td>

          <?php if ( $rel == $currVer && !empty($newBuild) ) : ?>

          <button
              class="input-button"
              type="button"
              title="Update to the latest build of version <?php echo $rel; ?>."
              onclick="vermgr('download','<?php echo $rel; ?>');"
          >
            Update
          </button>
          <span class="red">Build <?php echo $newBuild; ?> Available</span>

          <?php else: ?>

          <button
              class="input-button"
              type="button"
              title="Reinstall version <?php echo $rel; ?>."
              onclick="vermgr('download','<?php echo $rel; ?>');"
          >
            Force Reinstall
          </button>

          <?php

          endif;

          if ( $rel != $currVer ) :

          ?>

          <button class="input-button" type="button"
                  title="Switch to version <?php echo $rel; ?>."
                  onclick="vermgr('switchTo','<?php echo $rel; ?>');">
            Switch To
          </button>
          <button class="input-button" type="button"
                  title="Delete version <?php echo $rel; ?> from disk."
                  onclick="vermgr('remove','<?php echo $rel; ?>');">
            Remove
          </button>

          <?php endif; ?>

         </td>
      </tr>

      <?php endforeach; ?>

    </tbody>
  </table>
  <br />

  <?php

  $d = array(
      'back' => 'Back',
  );
  $this->loadTplBlock('ButtonPanelBackNext.tpl', $d);

  ?>

</div>