<?php
    defined('C5_EXECUTE') or die("Access Denied.");
    $c = \Page::getCurrentPage();
    if ($c->isEditMode()) {
        echo '<div style="border: 1px double black; padding: 1rem"><b>Attribute field disabled in edit mode.</b></div>';
    }
    else {
        $attributename = 'vm_context_value';
        $fieldid = 'peanut-vm-context';
        // echo "<div id='service-messages-container'><service-messages></service-messages></div>";
            // echo "<div id='$viewcontainerid'  style='display: none'>\n$content\n</div>";
        if (!empty($attributename)) {
            $value = $c->getAttribute($attributename);
            if ($value) {
                // $id = empty($fieldid) ? strtolower($attributename).'-value' : $fieldid;
                echo "<input type='hidden' id='$fieldid' value='$value' >";
            }
        }
    }

    ?>