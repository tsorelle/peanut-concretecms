<?php defined('C5_EXECUTE') or die("Access Denied."); ?>
<!-- template collapsible-knockout-view -->
<?php if ($addwrapper) { ?>
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <?php }
            $c = Page::getCurrentPage();
            if ($c->isEditMode()) {
                echo '<div style="border: 1px double black; padding: 50px"><b>Knockout View disabled in edit mode.</b></div>';
            }
            else {
            $collapseId = 'panel-'.$contextId;
            $collapseClass = $openpanel ? 'collapse show' : 'collapse';
            if (empty($paneltitle)) {
                $paneltitle = $collapseId;
            }
            ?>
            <div class="panel-group">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <?php echo "<h4  class='panel-title' id='$collapseId"."-title'>"; ?>
                            <?php
                                echo "<a title='Click to expand' data-bs-toggle='collapse' href='#$collapseId'>$paneltitle</a>";
                              ?>
                        </h4>
                    </div>

                    <?php echo "<div id='$collapseId' class='panel-collapse $collapseClass'>"; ?>
                    <div class="panel-body">
                        <?php echo "\n$content\n"; ?>
                        <!-- collapse wrapper footer start -->
                    </div>
                    <div class="panel-footer">

                    </div>
                </div>
            </div>
        </div>
        <!-- collaps wrapper footer end -->

        <?php
        }

        if ($addwrapper) {
        ?>
    </div>
</div>
    </div>
<?php } ?>
<!-- End: template collapsible-knockout-view -->

