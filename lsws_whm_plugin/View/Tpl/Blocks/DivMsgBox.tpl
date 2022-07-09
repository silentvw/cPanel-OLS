<?php

/**
 * Expects: $d['class']
 * Optional: $d['id'], $d['msgs'], and $d['title']
 */

if ( !isset($d['id']) ) {
    $id = '';
}
else {
    $id = "id=\"{$d['id']}\"";
}

?>

<div <?php echo $id; ?> class="msg-box <?php echo $d['class']; ?>">
  <ul>

    <?php if ( !empty($d['title']) ): ?>

    <li>
      <div class="title"><?php echo $d['title']; ?></div>
    </li>

    <?php

    endif;

    if ( !empty($d['msgs']) ) :
        $msgs = (is_array($d['msgs'])) ? implode('</li><li>', $d['msgs']) : $d['msgs'];

    ?>

    <li><?php echo $msgs; ?></li>

    <?php endif; ?>

  </ul>
</div>