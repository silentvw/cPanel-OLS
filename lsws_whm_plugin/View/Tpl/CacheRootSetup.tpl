<?php

use \LsPanel\View\Model\CacheRootSetupViewModel as ViewModel;

$icon = $this->viewModel->getTplData(ViewModel::FLD_ICON);
$svrCacheRoot = $this->viewModel->getTplData(ViewModel::FLD_SVR_CACHE_ROOT);
$vhCacheRoot = $this->viewModel->getTplData(ViewModel::FLD_VH_CACHE_ROOT);
$missing = $this->viewModel->getTplData(ViewModel::FLD_MISSING);
$errMsgs = $this->viewModel->getTplData(ViewModel::FLD_ERR_MSGS);

$d = array(
    'title' => 'Cache Root Setup',
    'icon' => $icon
);
$this->loadTplBlock('Title.tpl', $d);

if ( !empty($errMsgs) ) {
    $d = array(
        'msgs' => $errMsgs,
        'class' => 'msg-error',
    );
    $this->loadTplBlock('DivMsgBox.tpl', $d);
}

?>

<div class="margin-left-medium">
  <p class="no-left-margin">
    You can view your current cache root settings below. Both Server and
    Virtual Host cache roots should be set before attempting to install and use
    the LiteSpeed Cache for WordPress plugin.
  </p>

  <table class="datatable cacheroot-setup" border="1">
    <thead>
      <tr>
        <th></th>
        <th>Current</th>
        <th>Recommended</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="left-label">
          <b>Server level</b>
        </td>
        <td><?php echo $svrCacheRoot; ?></td>
        <td>/home/lscache/</td>
      </tr>
      <tr>
        <td class="left-label"><b>VHost level</b></td>
        <td><?php echo $vhCacheRoot; ?></td>
        <td>lscache<b>*</b></td>
      </tr>
    </tbody>
  </table>
  <div class="margin-left15">
    <small>
      <b>*</b> Virtual host cache root path is relative to each users home
      directory.
    </small>
  </div>
</div>

<?php

$d = array(
    'back' => 'Back',
    'backDo' => 'config_lsws',
    'next' => $missing ? 'Set Missing Cache Roots' : ''
);
$this->loadTplBlock('ButtonPanelBackNext.tpl', $d);

$msgs = array(
    'Server level cache root can be set in Apache\'s pre_main_global.conf file or LiteSpeed\'s native '
    . 'configuration.',
    'Virtual host level cache root is set under a file in Apache\'s userdata directory.',
    'Manually set the Server and Virtual Host level cache root by following our '
    . '<a href="https://docs.litespeedtech.com/cp/cpanel/lscache/#cache_storage_settings" '
    . 'target="_blank" rel="noopener noreferrer">configuration guide</a>.'
);

$d = array(
    'msgs' => $msgs,
    'class' => 'msg-info',
);
$this->loadTplBlock('DivMsgBox.tpl', $d);
