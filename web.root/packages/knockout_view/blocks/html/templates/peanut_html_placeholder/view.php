<?php
    defined('C5_EXECUTE') or die("Access Denied.");
    $c = \Page::getCurrentPage();
    if ($c->isEditMode()) { ?>
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div style="border: 1px double black;padding:1rem"><b>HTML Block hidden in edit mode.</b></div>
            </div>
        </div>
    </div>

    <?php } else {echo $content;}?>
