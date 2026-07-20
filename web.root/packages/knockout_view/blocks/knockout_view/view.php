<?php defined('C5_EXECUTE') or die("Access Denied."); ?>
<?php if ($addwrapper) { ?>
<div class="container">
    <div class="row">
        <div class="col-md-12">
<?php }
    $c = Page::getCurrentPage();
    if ($c->isEditMode()) {
        $msg = empty($viewmodel) ? 'Knockout view ' : 'View '.$viewmodel;
        echo '<div style="border: 1px double black; padding: 50px"><b>'.$msg.' disabled in edit mode.</b></div>';
    }
    else {
        // echo "<div id='service-messages-container'><service-messages></service-messages></div>";
            // echo "<div id='$viewcontainerid'  style='display: none'>\n$content\n</div>";
        echo "\n$content\n";
    }

    if ($addwrapper) {
                ?>
        </div>
    </div>
</div>
<?php } ?>


