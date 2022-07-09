<?php

/**
 * Expects: $d['title]
 * Optional: $d['icon']
 */

?>

<div class="section-title">

  <?php if ( !empty($d['icon']) ): ?>

  <span><img src="<?php echo $d['icon']; ?>" alt=""></span>

  <?php

  endif;

  echo $d['title'];

  ?>

</div>