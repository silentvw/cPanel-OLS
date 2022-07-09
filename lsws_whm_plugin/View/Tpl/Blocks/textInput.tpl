<?php

/**
 * Expects: $d['name'], $d['value']
 * Optional: $d['size']
 */

if ( !isset($d['size']) ) {
    $d['size'] = 0;
}

?>

<input type="text"

       <?php if ( $d['size'] == 1 ) : ?>

       size="40"

       <?php elseif ( $d['size'] == 2 ) : ?>

       size="90"

       <?php else : ?>

       class="input-text"

       <?php endif; ?>

       name="<?php echo $d['name']; ?>"
       value="<?php echo $d['value']; ?>"/>