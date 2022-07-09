<?php

use \LsUserPanel\View\Model\MainViewModel as ViewModel;

$pluginVersion = $this->viewModel->getTplData(ViewModel::FLD_PLUGIN_VER);
$vhCacheDir = $this->viewModel->getTplData(ViewModel::FLD_VH_CACHE_DIR);
$vhCacheDirExists =
        $this->viewModel->getTplData(ViewModel::FLD_VH_CACHE_DIR_EXISTS);
$ecCertAllowed = $this->viewModel->getTplData(ViewModel::FLD_EC_ALLOWED);
$iconDir = $this->viewModel->getTplData(ViewModel::FLD_ICON_DIR);
$errMsgs = $this->viewModel->getTplData(ViewModel::FLD_ERR_MSGS);
$succMsgs = $this->viewModel->getTplData(ViewModel::FLD_SUCC_MSGS);

?>

<div class="uk-container">

  <?php

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

  <p class="uk-text-large uk-margin-large-bottom">

    <?php

    echo _('Welcome to LiteSpeed\'s Web Cache Management plugin for easily deploying LiteSpeed '
            . 'Cache (LSCache).');

    ?>

    <a href="https://docs.litespeedtech.com/cp/cpanel/cpanel-plugin/"
        class="ls-link-small" target="_blank" rel="noopener">
      <?php echo _('Learn More'); ?> >>
    </a>
  </p>

</div>

<div class="uk-container">
  <h2 class="uk-margin-bottom-remove ls-text-bold ls-text-slateblue">
    <i class="uk-icon uk-icon-question ls-text-skyblue">&nbsp;</i>
    <?php echo _('Why Use LSCache'); ?>
  </h2>
  <hr class="uk-margin-top-remove uk-width-large-3-10 uk-width-medium-1-1
        uk-width-small-1-1 ls-border" />
  <div class="uk-text-muted uk-margin-left">
    <p>

      <?php

      echo _('LiteSpeed\'s built-in server-level cache (LSCache) is a highly-customizable caching '
              . 'solution.');

      ?>

    </p>

    <p><?php echo _('With LSCache, you can:'); ?></p>
    <ul>
      <li><?php echo _('dramatically reduce page load times'); ?></li>
      <li><?php echo _('provide an exceptional user experience'); ?></li>
      <li><?php echo _('handle traffic spikes with ease'); ?></li>
      <li>
        <?php echo _('manage cache with minimal fuss and powerful Smart Purge technology'); ?>
      </li>
    </ul>

    <p>

      <?php

      echo _('Free LSCache plugins are available for a variety of popular web apps. Hyper-charge your '
              . 'sites today!');

      ?>

    </p>
  </div>
  <div>
    <label class="uk-link uk-pull-1-10 uk-align-right" accesskey="" for="modal-1">
      <u><?php echo _('Available LSCache Plugins'); ?> ></u>
    </label>
  </div>
</div>

<div class="uk-container">
  <hr class="uk-margin-large-bottom ls-hr-dotted">
</div>

<div>
  <input class="modal-state" id="modal-1" type="checkbox" />
  <div class="modal">
    <label class="modal__bg" for="modal-1"></label>
      <div class="modal__inner">
    <label class="modal__close" for="modal-1"></label>
    <h2 class="uk-text-warning uk-margin-small-bottom uk-text-center-small">
      <?php echo _('Available LiteSpeed Caching Solutions'); ?>
      <span class="uk-link uk-h4">
        <a href="https://litespeedtech.com/products/cache-plugins"
           class="ls-link-small" target='_blank' rel="noopener">
          <u><?php echo _('More Info'); ?> >></u>
        </a>
      </span>
    </h2>
    <hr class="uk-margin-top-remove" />
      <div class="uk-container uk-margin-large-top">
        <ul class=" uk-grid
              uk-grid-width-xlarge-1-3 uk-grid-width-large-1-2
              uk-grid-width-medium-1-2 uk-grid-width-small-1-1 uk-grid-match
              uk-grid-large"
            data-uk-grid-match="{target:'.uk-panel'}">

          <li>
            <div class="uk-panel">
              <figure class="uk-text-center">
                <img src="<?php echo $iconDir; ?>/icon-lscwp.png" alt="lscwp_img"
                     class="uk-margin-top" />
              </figure>
              <div class="uk-text-center">
                <h4 class="uk-text-muted uk-margin-small-bottom uk-text-bold
                      uk-text-center">
                  <?php echo _('LSCache for WordPress'); ?>
                </h4>
                <p class="uk-margin-small-top">

                  <?php

                  echo _('Public, private, and ESI cache; cache crawler; image optimization, CDN '
                          . 'support, minification, more!');

                  ?>

                </p>
              </div>
                <hr class="ls-hr-dotted">
            </div>
          </li>

          <li>
            <div class="uk-panel">
              <figure class="uk-text-center">
                <img src="<?php echo $iconDir; ?>/icon-prestashop.png" alt="lscps_img"
                     class="uk-margin-top" />
              </figure>
              <div class="uk-text-center">
                <h4 class="uk-text-muted uk-margin-small-bottom uk-text-bold
                      uk-text-center">
                  <?php echo _('LSCache for PrestaShop'); ?>
                </h4>
                <p class="uk-margin-small-top">

                  <?php

                  echo _('Supports guest and logged-in users with ESI, an eCommerce must! For '
                          . 'PrestaShop 1.6 & 1.7.');

                  ?>

                </p>
              </div>
                <hr class="ls-hr-dotted">
            </div>
          </li>

          <li>
            <div class="uk-panel">
              <figure class="uk-text-center">
                <img src="<?php echo $iconDir; ?>/icon-xenforo.png" alt="lscxf_img"
                     class="uk-margin-top" />
              </figure>
              <div class="uk-text-center">
                <h4 class="uk-text-muted uk-margin-small-bottom uk-text-bold
                      uk-text-center">
                  <?php echo _('LSCache for Xenforo'); ?>
                </h4>
                <p class="uk-margin-small-top">

                  <?php

                  echo _('Accelerate your forums! Serve forum visitors cached public content on '
                          . 'XenForo 1.x and 2.x.');

                  ?>

                </p>
              </div>
                <hr class="ls-hr-dotted">
            </div>
          </li>

          <li>
            <div class="uk-panel">
              <figure class="uk-text-center">
                <img src="<?php echo $iconDir; ?>/icon-litemage.png" alt="litemage_img"
                     class="uk-margin-top" />
              </figure>
              <div class="uk-text-center">
                <h4 class="uk-text-muted uk-margin-small-bottom uk-text-bold
                      uk-text-center">
                    <?php echo _('LiteMage for Magento'); ?>
                </h4>
                <p class="uk-margin-small-top">

                  <?php

                  echo _('Public cache, private cache, and ESI for Magento 1.x and 2.x; crawler to '
                          . 'keep cache warm.');

                  ?>

                </p>
              </div>
                <hr class="ls-hr-dotted">
            </div>
          </li>

          <li>
            <div class="uk-panel">
              <figure class="uk-text-center">
                <img src="<?php echo $iconDir; ?>/icon-lscjml.png" alt="joomla_img"
                     class="uk-margin-top" />
              </figure>
              <div class="uk-text-center">
                <h4 class="uk-text-muted uk-margin-small-bottom uk-text-bold
                    uk-text-center">
                  <?php echo _('LSCache for Joomla'); ?>
                </h4>
                <p class="uk-margin-small-top">

                  <?php

                  echo _('Cache guest and logged-in users; punch holes with ESI. Serve Joomla 3.x '
                          . 'content fast!');

                  ?>

                </p>
              </div>
                <hr class="ls-hr-dotted">
            </div>
          </li>

          <li>
            <div class="uk-panel">
              <figure class="uk-text-center">
                <img src="<?php echo $iconDir; ?>/icon-lscdpl.png" alt="drupal_img"
                     class="uk-margin-top" />
              </figure>
              <div class="uk-text-center">
                <h4 class="uk-text-muted uk-margin-small-bottom uk-text-bold
                      uk-text-center">
                    <?php echo _('LSCache for Drupal'); ?>
                </h4>
                <p class="uk-margin-small-top">

                  <?php

                  echo _('Speed up your Drupal 8.x site and serve cached content to logged-out and '
                          . 'logged-in users alike.');

                  ?>

                </p>
              </div>
                <hr class="ls-hr-dotted">
            </div>
          </li>

          <li>
            <div class="uk-panel">
              <figure class="uk-text-center">
                <img src="<?php echo $iconDir; ?>/icon-lscmwk.png" alt="mediawiki_img"
                     class="uk-margin-top" />
              </figure>
              <div class="uk-text-center">
                <h4 class="uk-text-muted uk-margin-small-bottom uk-text-bold
                      uk-text-center">
                  <?php echo _('LSCache for MediaWiki'); ?>
                </h4>
                <p class="uk-margin-small-top">

                  <?php

                  echo _('Serve cached pages to both logged-in and guest users. Speed up '
                          . 'MediaWiki 1.25+');

                  ?>

                </p>
              </div>
                <hr class="ls-hr-dotted">
            </div>
          </li>

          <li>
            <div class="uk-panel">
              <figure class="uk-text-center">
                <img src="<?php echo $iconDir; ?>/icon-lscoc.png" alt="opencart_img"
                     class="uk-margin-top" />
              </figure>
              <div class="uk-text-center">
                <h4 class="uk-text-muted uk-margin-small-bottom uk-text-bold
                      uk-text-center">
                  <?php echo _('LSCache for OpenCart'); ?>
                </h4>
                <p class="uk-margin-small-top">

                  <?php

                  echo _('Serve fully-cached content to guests and logged-in shoppers in OpenCart '
                          . '2.3+');

                  ?>

                </p>
              </div>
                <hr class="ls-hr-dotted">
            </div>
          </li>

        </ul>
      </div>
    </div>
  </div>
</div>

<div class="uk-container">
  <h2 class="uk-margin-bottom-remove ls-text-bold ls-text-slateblue">
    <i class="uk-icon uk-icon-trash ls-text-skyblue">&nbsp;</i>
    <?php echo _('Flush LSCache'); ?>
  </h2>
  <hr class="uk-margin-top-remove uk-width-large-3-10 uk-width-medium-1-1
        uk-width-small-1-1 ls-border" />
  <div class="uk-text-muted uk-grid uk-margin-bottom">
    <div class="uk-width-large-2-3 uk-width-medium-1-1 uk-width-small-1-1
           uk-margin-bottom">

      <div style="margin-top: 1.5em; margin-bottom: .25em;">

        <?php

        $d = array(
            'vhCacheDir' => $vhCacheDir,
            'vhCacheDirExists' => $vhCacheDirExists,
            'extraClasses' => array('uk-margin-left')
        );
        $this->loadTplBlock('FlushBtn.tpl', $d);

        ?>

      </div>
      <p class="uk-margin-left">
        <?php echo _('Caution: This will clear the cache for all owned LSCache-enabled sites.'); ?>
        <br />

        <?php

        echo _('If you would like to flush LSCache for a single site, please do so through the '
                . 'administrator backend/dashboard for that site.');

        ?>

      </p>
    </div>
  </div>
</div>

<div class="uk-container">
  <hr class="uk-margin-large-bottom ls-hr-dotted">
</div>

<div class="uk-container">
  <h2 class="uk-margin-bottom-remove ls-text-bold ls-text-slateblue">
    <i class="uk-icon uk-icon-folder-open ls-text-skyblue">&nbsp;</i>
    <?php echo _('LiteSpeed Cache Management'); ?>
  </h2>
  <hr class="uk-margin-top-remove uk-width-large-3-10 uk-width-medium-1-1
        uk-width-small-1-1 ls-border" />
  <div class="uk-margin-large-top uk-margin uk-margin-left">
    <a href="?do=lscwp_manage"
       title="<?php echo _('Manage LSCache for known WordPress installations.'); ?>">
      <img src="<?php echo $iconDir; ?>/icon-wordpress.png"
           alt="WordPress_Cache_Manager_Icon"
           style="height:auto;" width="35">
      <span class="uk-text-large uk-button-link uk-text-primary
            uk-margin-small-left uk-text-middle">
        <?php echo _('WordPress Cache'); ?>
      </span>
    </a>
  </div>
</div>

<div class="uk-container">
  <hr class="uk-margin-large-bottom ls-hr-dotted">
</div>

<?php if ($ecCertAllowed): ?>

<div class="uk-container">
  <h2 class="uk-margin-bottom-remove ls-text-bold ls-text-slateblue">
    <i class="uk-icon uk-icon-folder-open ls-text-skyblue">&nbsp;</i>
    <?php echo _('EC Certificate Management'); ?>
  </h2>
  <hr class="uk-margin-top-remove uk-width-large-3-10 uk-width-medium-1-1
      uk-width-small-1-1 ls-border" />
  <div class="uk-margin-large-top uk-margin uk-margin-left">
    <a href="?do=ec_cert_manage"
        title="<?php echo _('Manage generated EC Certificates.'); ?>">
      <img src="<?php echo $iconDir; ?>/icon-ec-cert-manage.png"
          alt="EC Certificate Management Icon"
          style="height:auto;" width="35">
      <span class="uk-text-large uk-button-link uk-text-primary
          uk-margin-small-left uk-text-middle">
        <?php echo _('Manage EC Certificates'); ?>
      </span>
    </a>
  </div>
</div>

<div class="uk-container">
  <hr class="uk-margin-large-bottom ls-hr-dotted">
</div>

<?php endif; ?>

<div class="uk-container">
  <h4>
    <i class="uk-icon uk-icon-cog ls-text-silver">&nbsp;</i>
    <a href="?do=settings"><u><?php echo _('Settings'); ?></u></a>
    &nbsp;
    <i class="uk-icon ls-icon-cogs ls-text-silver">&nbsp;</i>
    <a href="?do=advanced"><u><?php echo _('Advanced'); ?></u></a>
  </h4>
  <div align="right">
    <a href="https://docs.litespeedtech.com/cp/cpanel/cpanel-plugin/"
       target="_blank" rel="noopener" class="plugin-ver"
    >
      LiteSpeed Web Cache Manager Plugin v<?php echo $pluginVersion; ?>
    </a>
  </div>
</div>

<script type="text/javascript">lswsInitDropdownBoxes();</script>
