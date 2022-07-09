<?php

use LsPanel\View\Model\LswsConfigViewModel as ViewModel;

$iconDir = $this->viewModel->getTplData(ViewModel::FLD_ICON_DIR);
$adminConsoleUrl =
        $this->viewModel->getTplData(ViewModel::FLD_ADMIN_CONSOLE_URL);
$suExecState = $this->viewModel->getTplData(ViewModel::FLD_SUEXEC_STATE);
$hasCache = $this->viewModel->getTplData(ViewModel::FLD_HAS_CACHE);

?>

<div id="lsws-config-categories">

  <?php

  $d = array(
      'title' => 'LiteSpeed Configuration',
      'icon' => ($iconDir != '') ? "{$iconDir}/lsConfiguration.svg" : ''
  );
  $this->loadTplBlock('Title.tpl', $d);

  if ( $adminConsoleUrl != '' ) {
      $webAdminTitle = "<a href=\"{$adminConsoleUrl}\" target=\"_blank\" "
              . 'rel="noopener noreferrer">WebAdmin Console</a>';
  }
  else {
      $webAdminTitle = 'WebAdmin Console (Please start LiteSpeed to access this)';
  }

  ?>

  <table class="container-flex">
    <tr class="row-flex">
      <td class="item-flex">
        <img src="<?php echo $iconDir; ?>/webadmin_console.svg" alt="webadmin_img"
             class="icon-flex"/>
      </td>
      <td class="item-flex">
        <h2><?php echo $webAdminTitle; ?></h2>
        <p>
          A centralized control panel to control and configure all
          LiteSpeed Web Server settings.
        </p>
        <p>
          <a href="https://docs.litespeedtech.com/products/lsws/commands/#misc-commands"
             target="_blank" rel="noopener">
            How to reset WebAdmin Console password
          </a>
        </p>
      </td>
    </tr>

    <?php if ( $hasCache ) : ?>

    <tr class="row-flex">
      <td class="item-flex">
        <img src="<?php echo $iconDir; ?>/cacheRootSetup.svg"
             alt="cache_root_setup_img" class="icon-flex"/>
      </td>
      <td class="item-flex">
        <h2><a href="?do=cacheRootSetup">Cache Root Setup</a></h2>
        <p>
          View server/virtual host cache root settings and set if necessary.
        </p>
      </td>
    </tr>

    <?php endif; ?>

    <tr class="row-flex">
      <td class="item-flex">
        <img src="<?php echo $iconDir; ?>/suexec_conf.svg" alt="suexec_conf_img"
             class="icon-flex"/>
      </td>
      <td class="item-flex">
        <h2>
          <a href="?do=config_lsws_suexec">PHP suEXEC Quick Configuration</a>
          <span class="note">(Currently <?php echo $suExecState; ?>)</span>
        </h2>
        <p>
          With PHP suEXEC, the server will run PHP scripts for each website as
          the owner of the site's document root directory.
      </td>
    </tr>

    <tr class="row-flex">
      <td class="item-flex">
        <img src="<?php echo $iconDir; ?>/uninstall.svg" alt="uninstall_img"
             class="icon-flex"/>
      </td>
      <td class="item-flex">
        <h2><a href="?do=uninstall_lsws">Uninstall LiteSpeed</a></h2>
      </td>
    </tr>
  </table>

  <?php

  $d = array(
      'back' => 'Back',
  );
  $this->loadTplBlock('ButtonPanelBackNext.tpl', $d);

  ?>

</div>