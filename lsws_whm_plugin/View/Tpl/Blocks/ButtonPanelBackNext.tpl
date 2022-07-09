<?php

/**
 * Expects: $d['back']
 * Optional: $d['next'], $d['backDo'], $d['extraClass'], and $d['visibility']
 */

if ( !isset($d['backDo']) ) {
    $d['backDo'] = 'main';
}

if ( !isset($d['extraClass']) ) {
    $d['extraClass'] = '';
}

if ( !isset($d['visibility']) ) {
   $d['visibility'] = 'visible';
}

?>

<div id ="backNextBtns" class="btns-box"
     style="visibility: <?php echo $d['visibility']; ?>">
  <button class="lsws-secondary-btn <?php echo $d['extraClass']; ?>"
          onclick="javascript:lswsform.do.value='<?php echo $d['backDo']; ?>';
              lswsform.submit();">
    <?php echo $d['back']; ?>
  </button>

  <?php if ( !empty($d['next']) ): ?>

  <button class="lsws-secondary-btn <?php echo $d['extraClass']; ?>" type="submit">
    <?php echo $d['next']; ?>
  </button>

  <?php endif; ?>

</div>