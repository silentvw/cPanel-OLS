<div class="uk-container">

  <div id="display-msgs" style="display: none;">
  <button class="accordion accordion-error" type="button"
          style="display: none;">
    <?php echo _('Error Messages'); ?>
    <span id ="errMsgCnt" class="badge errMsg-badge">
      0
    </span>
  </button>
  <div class="panel panel-error">

    <?php

    $d = array(
        'id' => 'errMsgs',
        'msgs' => array(),
        'class' => 'scrollable',
    );
    $this->loadTplBlock('DivMsgBox.tpl', $d);

    ?>

  </div>
  <div id="succMsgs"
       style="display: none;"
  >
    <ul></ul>
  </div>
</div>

  <h2 class="uk-margin-bottom-remove ls-text-bold ls-text-slateblue">
    <i class="uk-icon uk-icon-cog ls-text-skyblue">&nbsp;</i>
    <?php echo _('Advanced Operations'); ?>
  </h2>
  <hr class="uk-margin-top-remove uk-width-large-3-10 uk-width-medium-1-1
        uk-width-small-1-1 ls-border" />
  <br />
  <h3 class="uk-text-muted uk-margin-small-bottom uk-text-bold">
    <?php echo _('Restart Detached PHP Processes'); ?>
  </h3>
  <div class="uk-text-muted uk-grid uk-margin-bottom">
    <div class="uk-width-large-2-3 uk-width-medium-1-1 uk-width-small-1-1
           uk-margin-bottom">
      <div style="margin-top: 1.5em; margin-bottom: .25em;">
        <button type="button" value="Restart PHP"
                class="uk-button ls-button-warning ls-text-bold uk-button-large
                  uk-text-contrast uk-margin-left"
                onclick="javascript:restartPHP();"
                title="<?php echo _('Restart Detached PHP Processes'); ?>">
          <?php echo _('Restart'); ?>
        </button>
      </div>

      <p class="uk-margin-left">

        <?php

        echo _('This will inform LiteSpeed Web Server to restart all owned detached PHP '
                . 'processes the next time the server uses that PHP handler.');

        ?>

      </p>
    </div>
  </div>

  <button class="uk-button uk-button-muted uk-margin uk-margin-large
          uk-width-medium-1-10 uk-width-small-1-5"
        onclick="javascript:lswsform.do.value = 'main';lswsform.submit();">
    <?php echo _('Back'); ?>
</button>

</div>

<script type="text/javascript">lswsInitDropdownBoxes();</script>