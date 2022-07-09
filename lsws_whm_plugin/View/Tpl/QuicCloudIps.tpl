<?php

use \LsPanel\View\Model\QuicCloudIpsViewModel as ViewModel;

$iconDir = $this->viewModel->getTplData(ViewModel::FLD_ICON_DIR);
$quicCloudIps = $this->viewModel->getTplData(ViewModel::FLD_QUIC_CLOUD_IPS);

?>

<div id="heading">
  <h1>

    <?php if ( $iconDir != '' ) : ?>

    <span>
      <!--suppress HtmlUnknownTarget -->
      <img src="<?php echo $iconDir; ?>/quicCloudIps.svg"
          alt="restart_detached_php_icon" />
    </span>

    <?php endif; ?>

    QUIC.cloud IPs
  </h1>
</div>
<div>
  <p>
    The following list of QUIC.cloud IPs should be
    <a
        href="https://quic.cloud/docs/cdn/setup/adding-quic-cloud-ips-to-allowlist/"
        target="_blank"
        rel="noopener noreferrer"
    >
      whitelisted by your server's firewall
    </a>
    to prevent issues when communicating with QUIC.cloud through the LiteSpeed
    Cache for WordPress plugin (Image Optimization, API calls, etc).
  </p>
  <p>
    <b>*</b> Listed IPs are retrieved in real time and may change in the future.
  </p>
  <br />
  <p style="border: 1px solid; width: 150px; padding: 1em;">

    <?php

    foreach ( $quicCloudIps as $ip ) {
        echo htmlspecialchars($ip) . '<br />';
    }

    ?>

  </p>
</div>

<?php

$d = array(
    'back' => 'Back',
);
$this->loadTplBlock('ButtonPanelBackNext.tpl', $d);
