<?php

/**
 * Expects: $d['vhCacheDir'], $d['vhCacheDirExists']
 * Optional: $d['extraClasses']
 */

if ( !isset($d['extraClasses']) ) {
    $d['extraClasses'] = array();
}
elseif ( is_string($d['extraClasses']) ) {
    $d['extraClasses'] = array($d['extraClasses']);
}

$classes = 'uk-button ls-button-warning ls-text-bold uk-button-large uk-text-contrast';

foreach ( $d['extraClasses'] as $class ) {
    $classes .= " {$class}";
}

?>

<button type="button" value="Flush Cache" class="<?php echo $classes; ?>"

        <?php

        if ( $d['vhCacheDirExists'] ):
            $title = sprintf(_('Click to remove all cache files under %s'),
                    htmlspecialchars($d['vhCacheDir']));

        ?>

        onclick="javascript:flushCache('<?php echo $d['vhCacheDir']; ?>');"

        <?php

        else:
            $title = _('Cache Directory Not Found');

        ?>

        disabled

        <?php endif; ?>

        title="<?php echo $title; ?>"
      >
  <?php echo _('Flush All'); ?>
</button>