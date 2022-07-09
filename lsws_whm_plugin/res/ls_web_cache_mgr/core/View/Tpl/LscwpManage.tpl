<?php

use \LsUserPanel\View\Model\LscwpManageViewModel as ViewModel;

$showList = $this->viewModel->getTplData(ViewModel::FLD_SHOW_LIST);
$scanBtnName = $this->viewModel->getTplData(ViewModel::FLD_SCAN_BTN_NAME);
$btnState = $this->viewModel->getTplData(ViewModel::FLD_BTN_STATE);
$vhCacheDir = $this->viewModel->getTplData(ViewModel::FLD_VH_CACHE_DIR);
$vhCacheDirExists =
        $this->viewModel->getTplData(ViewModel::FLD_VH_CACHE_DIR_EXISTS);
$listData = $this->viewModel->getTplData(ViewModel::FLD_LIST_DATA);
$homeDirLen = $this->viewModel->getTplData(ViewModel::FLD_HOME_DIR_LEN);
$infoMsgs = $this->viewModel->getTplData(ViewModel::FLD_INFO_MSGS);
$errMsgs = $this->viewModel->getTplData(ViewModel::FLD_ERR_MSGS);
$succMsgs = $this->viewModel->getTplData(ViewModel::FLD_SUCC_MSGS);

if ( !empty($infoMsgs) ) :

?>

<div class="uk-alert uk-alert-warning">

  <?php

  $lastMsg = array_pop($infoMsgs);

  foreach ( $infoMsgs as $infoMsg ) {
      echo htmlspecialchars($infoMsg) . '<br />';
  }

  echo htmlspecialchars($lastMsg);

  ?>

</div>

<?php

endif;

$errMsgCnt = count($errMsgs);
$succMsgCnt = count($succMsgs);

if ( $errMsgCnt > 0 || $succMsgCnt > 0 ) {
    $msgsDisplay = 'initial';
}
else {
    $msgsDisplay = 'none';
}

?>

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
       style="display: <?php echo ($succMsgCnt > 0) ? 'initial' : 'none'; ?>"
  >
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

<div class="uk-container">
  <div align="left" class="uk-margin-top" style="padding-bottom:5px">
    <input type="submit" name="refresh_status" value="<?php echo _('Refresh Status'); ?>"
           title="<?php echo _('Refresh the cache status of all discovered WordPress installations'); ?>"
           class="uk-button uk-button-primary" style="margin:4px;"
           <?php echo $btnState; ?>/>
    <input type="submit" name="re-scan" value="<?php echo $scanBtnName; ?>"
           title="<?php echo _('Discover all WordPress installations'); ?>"
           class="uk-button uk-button-primary" style="margin:4px;"/>

      <?php

      $d = array(
          'vhCacheDir' => $vhCacheDir,
          'vhCacheDirExists' => $vhCacheDirExists,
          'extraClasses' => array('uk-align-right')
      );
      $this->loadTplBlock('FlushBtn.tpl', $d);

      ?>

  </div>
  <table id="lsws-data-table"
         class="uk-table uk-table-striped uk-table-hover uk-table-condensed">
    <thead>
      <tr>
        <th class="uk-text-center"></th>
        <th class="uk-text-left uk-width-3-6 uk-text-bold">
          <?php echo _('Discovered WordPress Installations'); ?>
        </th>
        <th class="uk-text-center uk-width-1-6 uk-text-bold">
          <?php echo _('Cache Status'); ?>
        </th>
        <th class="uk-text-center uk-width-1-6 uk-text-bold">
          <?php echo _('Flag'); ?>
        </th>
        <th class="uk-text-center uk-width-1-6 uk-text-bold">
          <?php echo _('Actions'); ?>
        </th>
      </tr>
    </thead>
    <tbody>

      <?php

      if ( $showList ) :

          $classes = 'icon-btn';

          foreach ( $listData as $path => $info ):
              $statusData = $info['statusData'];
              $flagData = $info['flagData'];
              $safeSiteUrl = htmlspecialchars($info['siteUrl']);
              $safePath = htmlspecialchars($path);
              $shortPath = " ~" . substr($safePath, $homeDirLen);
              $isLscwpEnabled = $info['isLscwpEnabled'];

      ?>

      <tr class="uk-table-middle">
        <td>
          <input type="checkbox" name="installations[]"
                 value="<?php echo $path; ?>" />
        </td>
        <td class="uk-width-3-6" style="word-wrap:break-word;">
          <?php echo $safeSiteUrl; ?>
          <br />
          <small><?php echo $shortPath; ?></small>
        </td>
        <td class="uk-text-center uk-width-1-6">
            <?php echo $statusData['state']; ?>
        </td>
        <td class="uk-text-center uk-width-1-6">
          <?php echo $flagData['icon']; ?>
        </td>
        <td class="uk-text-center uk-width-1-6">
          <button type="submit"
                  name="<?php echo $statusData['btn_name']; ?>"
                  value="<?php echo $path; ?>" class="btn btn-link"
                  title="<?php echo sprintf(_('%s cache for this site'), $statusData['btn_action']); ?>"
                  onclick="return confirm('<?php echo "{$statusData['btn_msg']}{$safePath}"; ?>?')"
                  style="width:89px">

            <?php

            echo "{$statusData['btn_icon']}{$statusData['btn_action']}";

            $btnTitle = sprintf(_('%s this installation as excluded during Mass Enable/Disable'),
                    $flagData['btn_action']);

            ?>

          </button>

          <button type="submit"
                  name="<?php echo $flagData['btn_name']; ?>"
                  value="<?php echo $path; ?>" class="btn btn-link"
                  title="<?php echo $btnTitle; ?> "
                  onclick="return confirm('<?php echo "{$flagData['btn_msg']}{$path}"; ?>?')"
                  style="width:83px">
            <span class="glyphicon glyphicon-flag"></span>
            <?php echo $flagData['btn_action']; ?>
          </button>

          <button type="submit"
              name="upload_ssl_cert_single"
              value="<?php echo $path; ?>" class="btn btn-link"
              title="Upload SSL Certificate to QUIC.cloud for this site"
              onclick="return confirm('Upload SSL cert for <?php echo $path; ?>?')"
              style="width:100%"
              <?php echo ($isLscwpEnabled) ? '' : 'disabled'; ?>
          >
            <span class="glyphicon glyphicon-cloud-upload"></span>
            Upload SSL Cert to QUIC.cloud
          </button>
        </td>
      </tr>

      <?php

      endforeach;

      endif;

      ?>

    </tbody>
  </table>

  <div>
    <?php echo _('With Selected:'); ?>
    <button type="button" name="enable_sel" value="Enable Selected"
           title="<?php echo _('Enable LSCWP for all selected WordPress installations (Ignores Flag)'); ?>"
           class="lsws-secondary-btn"
           onclick="lscwpValidateSelectFormSubmit(this.name,
                this.value);"
           <?php echo $btnState; ?>
    >
      <?php echo _('Enable'); ?>
    </button>
    <button type="button" name="disable_sel" value="Disable Selected"
           title="<?php echo _('Disable & uninstall LSCWP for all selected WordPress installations (Ignores Flag)'); ?>"
           class="lsws-secondary-btn"
           onclick="lscwpValidateSelectFormSubmit(this.name,
                this.value);"
           <?php echo $btnState; ?>
    >
      <?php echo _('Disable'); ?>
    </button>

    <button type="button" name="flag_sel" value="Flag Selected"
           title="<?php echo _('Flag all selected WordPress Installations'); ?>"
           class="lsws-secondary-btn"
           onclick="lscwpValidateSelectFormSubmit(this.name,
                this.value);"
           <?php echo $btnState; ?>
    >
      <?php echo _('Flag'); ?>
    </button>
    <button type="button" name="unflag_sel" value="Unflag Selected"
           title="<?php echo _('Unflag all selected WordPress Installations'); ?>"
           class="lsws-secondary-btn"
           onclick="lscwpValidateSelectFormSubmit(this.name,
                this.value);"
           <?php echo $btnState; ?>
    >
      <?php echo _('Unflag'); ?>
    </button>
  </div>

  <button class="uk-button uk-button-muted uk-margin uk-margin-large
            uk-width-medium-1-10 uk-width-small-1-5"
          onclick="lswsform.do.value='main';lswsform.submit();">
    <?php echo _('Back'); ?>
  </button>
  <br />
</div>

<div>
    <button class="accordion cachemgr-help" type="button">

      <?php

      echo _('This plugin provides simple tools for bulk managing LiteSpeed Cache across all of your '
              . 'WordPress installations.');

      ?>

      &nbsp;&nbsp;
      <a href="https://docs.litespeedtech.com/cp/cpanel/cpanel-plugin/#litespeed-cache-management-wordpress-cache"
         target="_blank" rel="noopener">
        <?php echo _('Learn More'); ?> >
      </a>
    </button>

    <div class="panel panel-info">
        <p>
          <b><?php echo _('Scan or Re-scan:'); ?></b>

          <?php

          echo _('Discover all WordPress installations. This tool searches under each cPanel user\'s '
                  . 'known document root, saves any discovered installations to a data file, and '
                  . 'displays them on the manager screen. If this data file is removed or corrupted, '
                  . 'simply scan again to re-populate the list.');

          ?>

        </p>
        <p>
          <b><?php echo _('Refresh Status:'); ?></b>

          <?php

          echo _('Cache Status is displayed for each listed WordPress installation. Use the Refresh '
                  . 'Status button to see which installations have LiteSpeed Cache currently '
                  . 'enabled.');

          ?>

        </p>
        <p>
          <b><?php echo _('Flush All:'); ?></b>

          <?php

          echo _('This button clears the cache for ALL of your LSCache-enabled sites, even those '
                  . 'using a different CMS. To flush LSCache for a single site, please do so directly '
                  . 'in the site\'s admin dashboard.');

          ?>

        </p>
        <p>
          <b><?php echo _('Enable/Disable:'); ?></b>

          <?php

          echo _('These buttons will enable and disable LSCWP for all of the selected WordPress '
                  . 'installations in bulk. To enable or disable an individual site, use the link in the '
                  . 'Actions column next to that site.');
          ?>

        </p>
        <p>
          <b><?php echo _('Flag/Unflag:'); ?></b>

          <?php

          echo _('When a site is flagged, it is excluded from all mass operations. Use this button to flag '
                  . 'or unflag selected sites in bulk. To flag or unflag an individual site, use the link in '
                  . 'the Actions column next to that site.');

          ?>

        </p>
        <p>
          <b>
            <?php echo sprintf(_('Upload SSL Cert to %s:'), 'QUIC.cloud'); ?>
          </b>

          <?php

          echo sprintf(
              _(
                  'This button will attempt to detect a site\'s SSL '
                      . 'certificate information and upload it to the %s '
                      . 'account linked to the given WordPress installation. '
                      . 'This action requires that a %s domain key has already '
                      . 'been generated in the LiteSpeed Cache for Wordpress '
                      . 'Plugin (%s).'
              ),
              'QUIC.cloud',
              'QUIC.cloud',
              '"LiteSPeed Cache -> General"'
          );

          ?>

        </p>
    </div>
  </div>

<script type="text/javascript">lswsInitDropdownBoxes();</script>
