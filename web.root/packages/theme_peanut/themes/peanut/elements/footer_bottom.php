<?php defined('C5_EXECUTE') || die('Access Denied.'); ?>
</div>

<?php View::element('footer_required'); ?>
<!-- These lines added for to load Peanut view models -->
<?php
if (!$c->isEditMode()) {
    if (class_exists('\Peanut\sys\ViewModelManager')) {
        \Peanut\sys\ViewModelManager::RenderStartScript();
    } else {
        print "ViewModelManager not found. Package 'tq_peanut' is required.";
    }
}
?>
<!-- end added lines -->
</body>
</html>

