<?php

/**
 * Expects: $d['title']
 * Optional: $d['icon']
 */

?>

<div id="heading">
  <h1>

    <?php if ( !empty($d['icon']) ): ?>

    <span><img src="<?php echo $d['icon']; ?>" alt=""></span>

    <?php

    endif;

    echo $d['title'];

    ?>

  </h1>
</div>
<br />