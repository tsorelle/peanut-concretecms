<?php defined('C5_EXECUTE') or die("Access Denied."); ?>


<div id="ccm-block-knockout-value-viewmodel">
    <label for="ccm-block-knockout-value-viewmodel-input">View Model </label>
    <input id="ccm-block-knockout-value-viewmodel-input" class="form-control" value="<?php  echo $viewmodel ?? '' ?>" name="viewmodel" />

</div>

<div id="ccm-block-knockout-value-inputvalue">
    <label for="ccm-block-knockout-value-inputvalue-input">Input value</label>
    <input id="ccm-block-knockout-value-inputvalue-input" class="form-control" value="<?php  echo $inputvalue ?? '' ?>" name="inputvalue" />

</div>

<div id="ccm-block-knockout-value-paneltitle">
    <label for="ccm-block-knockout-value-paneltitle-input">Panel title </label>
        <input id="ccm-block-knockout-value-paneltitle-input" class="form-control" value="<?php  echo $paneltitle ?? '' ?>" name="paneltitle" />

</div>
<?php
if (!isset($openpanel)) {
    $openpanel = true;
}
if (!isset($addwrapper)) {
    $addwrapper = true;
}

?>
<div class="checkbox" id="ccm-block-knockout-value-openpanel">
    <label for="ccm-block-knockout-value-openpanel-input">
        <input type="checkbox" id="ccm-block-knockout-value-openpanel-input" value="1" name="openpanel"
            <?php echo $openpanel ? 'checked' : '' ?> />
        Open panel
    </label>
</div>

<div class="checkbox" id="ccm-block-knockout-value-addwrapper">
    <label for="ccm-block-knockout-value-addwrapper-input">
        <input type="checkbox" id="ccm-block-knockout-value-addwrapper-input" value="1" name="addwrapper"
               <?php echo $addwrapper  ? 'checked' : '' ?> />
        Add Wrapper Markup
    </label>
</div>

<div style="margin-top: 10px"><b>Content</b></div>
<div id="ccm-block-html-value"><?php echo htmlspecialchars($content ?? '',ENT_QUOTES,APP_CHARSET) ?></div>
<textarea style="display: none" id="ccm-block-html-value-textarea" name="content"></textarea>

<style type="text/css">

    #ccm-block-html-value {
        width: 100%;
        border: 1px solid #eee;
        height: 490px;
    }
</style>

<script type="text/javascript">
    $(function() {
        // Tried to replace $() with pure javascript, but this did not work
     // document.addEventListener("DOMContentLoaded", function() {
        var editor = ace.edit("ccm-block-html-value");
        editor.setTheme("ace/theme/eclipse");
        editor.getSession().setMode("ace/mode/html");
        refreshTextarea(editor.getValue());
        editor.getSession().on('change', function() {
            refreshTextarea(editor.getValue());
        });
    });

    function refreshTextarea(contents) {
        document.getElementById('ccm-block-html-value-textarea').value = contents;
        // $('#ccm-block-html-value-textarea').val(contents);
    }
</script>

