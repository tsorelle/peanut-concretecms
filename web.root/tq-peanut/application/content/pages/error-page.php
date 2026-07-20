<?php
if (!isset($errorMessage)) {
    $errorMessage = 'Unknown error occurred';
}
?>
<h3>Sorry, a processing error occured</h3>
<p>
    <?php print $errorMessage; ?>
</p>
