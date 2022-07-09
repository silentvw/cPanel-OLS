<?php

/**
 * Expects: $d['do']
 *
 * cPanel Header and CSS/JS file includes are handled in template toolkit
 * file ls_web_cache_manager.html.tt.
 */

?>

<div id="lsws-container" class="uk-margin-large-bottom">
  <form name="lswsform">
    <input type="hidden" name="step" value="1"/>
    <input type="hidden" name="do" value="<?php echo $d['do']; ?>"/>
