<?php

use \LsPanel\View\Model\SharedCodeUpdateResultViewModel as ViewModel;

$updated = $this->viewModel->getTplData(ViewModel::FLD_UPDATED);

if ( $updated ) :

?>

<div>
  <p align="center">
    Shared code updated.
  </p>
</div>

<?php else : ?>

<div>
  <p align="center">
    Encountered an issue when attempting to update shared code.
  </p>
</div>

<?php endif; ?>

<div>
  <p align="center">
    <button class="lsws-secondary-btn"
            onclick="javascript:lswsform.do.value='main';lswsform.submit();">
      OK
    </button>
  </p>
</div>