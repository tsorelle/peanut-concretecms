<?php defined('C5_EXECUTE') || die('Access Denied.'); ?>
<?php
use Concrete\Core\Area\GlobalArea;

?>

<footer>
    <section class="mb-4">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <?php
                    $area = new GlobalArea('Footer Column 1');
$area->display();
?>
                </div>
                <div class="col-md-2 col-6">
                    <?php
$area = new GlobalArea('Footer Column 2');
$area->display();
?>
                </div>
                <div class="col-md-2 col-6">
                    <?php
$area = new GlobalArea('Footer Column 3');
$area->display();
?>
                </div>
                <div class="col-md-5 col-12">
                    <hr class="d-md-none">
                    <?php
$area = new GlobalArea('Footer Column 4');
$area->display();
?>

                </div>
            </div>
        </div>
    </section>
    <section class="concrete-branding">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <?=t('Copyright %s. ', date('Y'))?>
                    <?=t(/*i18n: %1$s = open tag; %2$s = close tag*/'Built with %1$sConcrete CMS%2$s.', '<strong><a href="https://www.concretecms.org" class="title" rel="nofollow">', '</a></strong>')?>
                </div>
                <div class="col-md-6 text-md-end">
                    <?=app('helper/navigation')->getLogInOutLink()?>
                </div>
            </div>
        </div>
    </section>
</footer>

<?php $view->inc('elements/footer_bottom.php');?>
