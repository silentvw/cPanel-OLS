<?php

use LsPanel\View\Model\PhpSuExecQuickConfViewModel as ViewModel;

$iconDir = $this->viewModel->getTplData(ViewModel::FLD_ICON_DIR);
$suExecInfo = $this->viewModel->getTplData(ViewModel::FLD_SUEXEC_INFO);
$suExecOptions = $this->viewModel->getTplData(ViewModel::FLD_SUEXEC_OPTIONS);
$maxConnInfo = $this->viewModel->getTplData(ViewModel::FLD_MAX_CONN_INFO);
$warnMsgs = $this->viewModel->getTplData(ViewModel::FLD_WARN_MSGS);
$errMsgs = $this->viewModel->getTplData(ViewModel::FLD_ERR_MSGS);


$d = array (
    'title' => 'PHP suEXEC Quick Configuration',
    'icon' => ($iconDir != '') ? "{$iconDir}/suexec_conf.svg" : ''
);
$this->loadTplBlock('Title.tpl', $d);

$d = array(
    'msgs' => 'Changes to these settings do not take effect until LiteSpeed Web Server is restarted.',
    'class' => 'msg-info',
);
$this->loadTplBlock('DivMsgBox.tpl', $d);

if ( !empty($warnMsgs) ) {
    $d = array(
        'msgs' => $warnMsgs,
        'class' => 'msg-warn',
    );
    $this->loadTplBlock('DivMsgBox.tpl', $d);
}

if ( !empty($errMsgs) ) {
    $d = array(
        'msgs' => $errMsgs,
        'class' => 'msg-error scrollable',
    );
    $this->loadTplBlock('DivMsgBox.tpl', $d);
}

?>

<p>
  These settings can also be found in the WebAdmin Console under
  Configuration » General » Using Apache Configuration File.
</p>
<p>
  With PHP suEXEC, the server will run PHP scripts for each website as the
  owner of the site's document root directory. LiteSpeed's PHP suEXEC does not
  slow performance like other PHP suEXEC implementations, and .htaccess
  configurations are fully supported.
</p>
<p>
  (Note: You may need to fix some file/directory permissions if PHP suEXEC or
  suPHP were not used with Apache.)
</p>

<table class="sortable" width="90%" border="0" cellpadding="5" cellspacing="1">
  <tr class="tblheader0">
    <th nonsortable="true">Options</th>
    <th nonsortable="true">Configured Value</th>
    <th nonsortable="true">New Value</th>
  </tr>

  <tr>
    <td>
      Enable PHP suEXEC
      <img src="static/info.gif"
           title="Specifies whether to run PHP scripts in suEXEC mode when User and Group
                            are specified for a virtual host. \nWhen set to yes, PHP scripts will be executed
                            under the user (and group unless Force GID is set) specified. \nWhen set to User
                            Home Directory Only, scripts outside a user\'s home directory will run as the global
                            user/group that the web server runs as. \nDefault value is no." />
    </td>
    <td align="center">
        <?php echo $suExecOptions[$suExecInfo['curr']]; ?>
    </td>
    <td align="center">

      <?php if ( $suExecInfo['err'] != false ) : ?>

      <span class="error"><?php echo $suExecInfo['err']; ?></span>
      <br />

      <?php endif; ?>

      <select name="<?php echo $suExecInfo['id']; ?>">

        <?php

        if ( $suExecInfo['new'] != false ) {
           $value = $suExecInfo['new'];
        }
        else {
            $value = $suExecInfo['curr'];
        }

        foreach ( $suExecOptions as $k => $v ) :
            $sel = ($value == $k) ? 'selected' : '';

        ?>

        <option value="<?php echo $k; ?>" <?php echo $sel; ?>>
          <?php echo $v; ?>
        </option>

        <?php endforeach; ?>

      </select>
    </td>
  </tr>

  <tr>
    <td>
      PHP suEXEC Max Conn
      <img src="static/info.gif"
           title="Specifies the maximum number of concurrent PHP processes each user will
                              have access to when running PHP scripts in suEXEC mode. \nDefault value is 5." />
    </td>
    <td align="center">
        <?php echo $maxConnInfo['curr']; ?>
    </td>
    <td align="center">

      <?php if ( $maxConnInfo['err'] != false ) : ?>

      <span class="error"><?php echo $maxConnInfo['err']; ?></span>
      <br />

      <?php

      endif;

      if ( $maxConnInfo['new'] != false ) {
          $value = $maxConnInfo['new'];
      }
      else {
          $value = $maxConnInfo['curr'];
      }

      $d = array(
          'name' => $maxConnInfo['id'],
          'value' => $value
      );
      $this->loadTplBlock('textInput.tpl', $d);

      ?>

    </td>
  </tr>
</table>

<?php

$d = array(
    'back' => 'Back',
    'backDo' => 'config_lsws',
    'next' => 'Update'
);
$this->loadTplBlock('ButtonPanelBackNext.tpl', $d);
