<?php defined('C5_EXECUTE') || die('Access Denied.'); ?>
<!DOCTYPE html>
<html lang="<?=Localization::activeLanguage()?>">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <?php
    View::element('header_required', [
        'pageTitle' => $pageTitle ?? '',
        'pageDescription' => $pageDescription ?? '',
        'pageMetaKeywords' => $pageMetaKeywords ?? ''
    ]);
?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<div class="theme-basic-bedrock <?=$c->getPageWrapperClass()?>">
