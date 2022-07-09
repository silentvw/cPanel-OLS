<?php

use \LsUserPanel\View\Model\EcCertManageViewModel as ViewModel;

$showList = $this->viewModel->getTplData(ViewModel::FLD_SHOW_LIST);
$btnState = $this->viewModel->getTplData(ViewModel::FLD_BTN_STATE);
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

<div class="uk-container">
  <div align="left" class="uk-margin-top" style="padding-bottom:5px">
    <input type="submit" name="update_list"
        value="<?php echo _('Update List'); ?>"
        title="<?php echo _('Update EC certificate management list'); ?>"
        class="uk-button uk-button-primary" style="margin:4px;"/>
    <input type="submit" name="gen_all" value="Generate All"
        title="<?php echo _('Generate EC certificates for all listed sites with SSL enabled'); ?>"
        class="uk-button uk-button-primary" style="margin:4px;"
        <?php echo $btnState; ?>
    />
    <input type="submit" name="remove_all" value="Remove All"
        title="<?php echo _('Remove all generated EC certificates'); ?>"
        class="uk-button uk-button-primary" style="margin:4px;"
        <?php echo $btnState; ?>
    />
  </div>
  <table id="ec-cert-data-table"
      class="uk-table uk-table-striped uk-table-hover uk-table-condensed">
    <thead>
      <tr>
        <th class="uk-text-center"></th>
        <th class="uk-text-left uk-width-3-10 uk-text-bold">
          <?php echo _('Discovered Domains'); ?>
        </th>
        <th class="uk-text-center uk-width-1-10 uk-text-bold">
          <?php echo _('EC Cert Exists'); ?>
        </th>
        <th class="uk-text-center uk-width-3-10 uk-text-bold">
          <?php echo _('Domains Covered By Cert'); ?>
        </th>
        <th class="uk-text-center uk-width-3-10 uk-text-bold">
          <?php echo _('Last Generation Message'); ?>
        </th>
      </tr>
    </thead>
    <tbody>

      <?php

      if ( $showList ) :
          $classes = 'icon-btn';

          foreach ( $listData as $serverName => $info ):
              $safeServerName = htmlspecialchars($serverName);
              $safeDocRoot = htmlspecialchars($info['docroot']);
              $shortDocRoot = " ~" . substr($safeDocRoot, $homeDirLen);

              $hasSslVh = $info['hasSslVh'];
              $ecCertExists = $info['ecCertExists'];

              $safeCoveredDomainsString = implode(
                  "\n",
                  array_map('htmlspecialchars', $info['coveredDomains'])
              );

              $safeLastGenMsg = htmlspecialchars($info['lastGenMsg']);

              $checkboxState = ($hasSslVh) ? '' : 'disabled';

      ?>

      <tr class="uk-table-middle">
        <td>
          <label for="domainSelection" style="display: none;">
              Domain Selection Checkbox
          </label>
          <input id="domainSelection" type="checkbox" name="domains[]"
              value="<?php echo $serverName; ?>"
              <?php echo $checkboxState; ?>
          />
        </td>
        <td class="uk-width-3-10" style="word-wrap:break-word;">
          <?php echo $safeServerName; ?>
          <br />
          <small><?php echo $shortDocRoot; ?></small>
        </td>
        <td class="uk-text-center uk-width-1-10">

          <?php if ($ecCertExists): ?>

          <span style="color: #00D000;">Yes</span>

          <?php else: ?>

          <span style="color: #9d9d9d;">No</span>

          <?php endif; ?>

          <br />

          <?php

          if( $hasSslVh ):

              if ($ecCertExists) {
                  $name = 'remove_single';
                  $action = 'Remove';
                  $classString = 'glyphicon glyphicon-remove-circle';
              }
              else {
                  $name = 'gen_single';
                  $action = 'Generate';
                  $classString = 'glyphicon glyphicon-list-alt';
              }

          ?>

          <button type="submit"
              name="<?php echo $name; ?>"
              value="<?php echo $serverName; ?>" class="btn btn-link"
              title="<?php echo "{$action} EC certificate for this domain";?>"
              onclick="return confirm('<?php echo $action; ?> EC certificate for this domain?')"
              style="text-decoration: underline;">
            <span class="<?php echo $classString; ?>"></span>
            <?php echo $action; ?>
          </button>

          <?php else: ?>
          <span
              title="No SSL Vhost exists for this domain. No actions can be
                performed."
              style="color: #9d9d9d;">
            No SSL VH
          </span>

          <?php endif; ?>

        </td>
        <td class="uk-text-center uk-width-3-10">
          <textarea
              rows="6"
              style="width: 100%; resize: vertical;
                white-space: pre; font-size: 12px;
                border-color: #cecece; overflow-x: auto;"
              readonly><?php echo $safeCoveredDomainsString; ?></textarea>
        </td>
        <td class="uk-text-center uk-width-3-10">
            <textarea
                rows="6"
                style="width: 100%; resize: vertical; border-color: #cecece;
                  font-size: 12px;"
                readonly><?php echo $safeLastGenMsg; ?></textarea>
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
    <button type="button" name="gen_sel" value="Generate for Selected"
        title="<?php echo _('Generate EC certificate for all selected domains'); ?>"
        class="lsws-secondary-btn"
        onclick="ecCertValidateSelectFormSubmit(this.name, this.value);"
        <?php echo $btnState; ?>
    >
      <?php echo _('Generate'); ?>
    </button>

    <button type="button" name="remove_sel" value="Remove for Selected"
        title="<?php echo _('Remove EC certificate for all selected domains'); ?>"
        class="lsws-secondary-btn"
        onclick="ecCertValidateSelectFormSubmit(this.name, this.value);"
        <?php echo $btnState; ?>
    >
      <?php echo _('Remove'); ?>
    </button>
  </div>
  <br />
  <div>
    <small>
      * Renewal for generated certificates will be automatically handled by
      this plugin.
    </small>
  </div>
  <button class="uk-button uk-button-muted uk-margin uk-margin-large
        uk-width-medium-1-10 uk-width-small-1-5"
      onclick="lswsform.do.value='main';lswsform.submit();"
  >
    <?php echo _('Back'); ?>
  </button>
  <br />
</div>

<div>
  <button class="accordion cachemgr-help" type="button">

    <?php

    echo _(
        'This plugin provides simple tools for bulk managing plugin generated '
            . 'EC certificates accross all of your domains.'
    );

    ?>
  </button>

  <div class="panel panel-info">
    <p>
      <b><?php echo _('Update List:'); ?></b>

      <?php

      echo _(
          'Update EC certificate management list. This will re-check for all'
              . 'user owned sites and list whether these sites have SSL '
              . 'enabled (based on the existence of an SSL VHost for that '
              . 'domain) and a generated EC certificate exists.'
      );

      ?>

    </p>
    <p>
      <b><?php echo _('Generate All:'); ?></b>

      <?php

      echo _(
          'Generate an EC certificate for all listed sites who have SSL '
              . 'enabled (based on the existence of an SSL VHost for that '
              . 'domain).'
      );

      ?>

    </p>
    <p>
      <b><?php echo _('Remove All:'); ?></b>

      <?php

      echo _(
          'Remove generated EC certificated for all listed sites.'
      );

      ?>

    </p>
    <p>
      <b><?php echo _('Generate/Remove:'); ?></b>

      <?php

      echo _(
          'These buttons will generate or remove plugin managed EC '
              . 'certificates for all selected sites. To generate or remove '
              . 'EC certificates for an individual site, use the buttons in '
              . 'the Actions column next to that site in the EC certificate '
              . 'management list.'
      );
      ?>

    </p>
  </div>
</div>

<script type="text/javascript">lswsInitDropdownBoxes();</script>
