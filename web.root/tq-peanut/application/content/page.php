<!DOCTYPE html>
<html lang="en">
<?php
// deploy location: web.root/application/content/page.php
    if (!isset($title)) {
        $browserTitle = 'Title';
    }
    if (!isset($theme)) {
        $theme = 'default';
    }
    if (!isset($maincolsize)) {
        $maincolsize = 12;
    }

    // varibles from Router.php
    /** @var \Nutshell\cms\SiteMap $sitemap */
    /** @var int $maincolsize */
    /** @var int $colsize */
    /** @var string $menu */
    /** @var string $version */
    /** @var string $menutype */
    /** @var string $themeIncludePath */
    /** @var string $loaderScript */
    /** @var int $siteheader */
    /** @var int $sitefooter */
    /** @var int $pageheader */
    /** @var int $frontpage */
    /** @var int $specialheader */
    /** @var int $bscdn */
    /** @var int $fasrc */
    /** @var int $embed */

// $embed=0;
if ($embed===1) {
    $sitefooter = 0;
}
?>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php
        printf('<link rel="icon" type="image/x-icon" href="%s/assets/img/favicon.ico">',URL_APPLICATION)
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php
    $version = Tops\sys\TConfiguration::getValue('applicationVersionNumber','peanut','version');
        if ($bscdn === 1) {
            printf(
                '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" '.
                '  rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" '.
                '  crossorigin="anonymous">'."\n"
            );
        }

    /** @noinspection HtmlUnknownTarget */
        print Peanut\sys\CssManager::getStyleSheets($themePath ?? null);
        // printf('<link rel="stylesheet" type="text/css" href="%s/styles.css"/>',$themePath ?? 'Error missing theme path');
        if (isset($extraStyles)) {
            /** @noinspection HtmlUnknownTarget */
            printf('<link rel="stylesheet" type="text/css" href="%s/extra.css%s"/>',
                    $themePath ?? 'Error missing theme path',
                    "?v=$version");
        }
    ?>
    <title>
        <?php
            print $title?? ''
        ?>
    </title>
    <script src="<?php print $fasrc?>" crossorigin="anonymous"></script>
    <?php if ($embed === 1) { ?>
    <style>
        #page-content {
            margin-left: 0;
            margin-right: 0;
            max-width: 100%;
        }
        #nutshell-main-section {
            padding-bottom: 0;
        }
    </style>
    <?php }  else { ?>
    <style>
        #nutshell-main-section {
            padding-bottom: 10ex;
        }
    </style>
   <?php } ?>
</head>
<body>
    <div id="page-top"></div>

    <?php
    if ($embed !== 1) {
        if (!empty($specialheader)) {
            include $themeIncludePath."/site-header2.php";
        }
    if ($siteheader === 1) {
        include $themeIncludePath."/site-header.php";
    }

    if ($frontpage === 1) {
        include $themeIncludePath . "/front-header.php";
    }

    if ($pageheader === 1) {
        include $themeIncludePath."/page-header.php";
    }
    }
    ?>


    <!-- main content -->
    <div  id="nutshell-main-section">
        <div class="container" id="page-content">
    <?php
        if (!empty($pagetitle)) {
            printf("<div class='row'><div class='col-md-12'><h2>%s</h2></div></div>\n",
                    $pagetitle);
        }
    ?>
            <div class="row pagecontent-row">
                <?php
                if (isset($menu) && $menu=='left') {
                    include $themeIncludePath."/menu-column.php";
                }
                print sprintf("<div class='main-content-section col-md-%s'>\n",$maincolsize);
                    if (isset($view)) {
                        include $view;
                    }
                print '</div>';

                if (isset($menu) && $menu=='right') {
                    include $themeIncludePath."/menu-column.php";
                }

                ?>
            </div>
        <?php ?>
        </div>
    </div> <!-- end main section -->

    <!-- todo: yagni - page footer -->

    <?php
    if ($sitefooter === 1) {
        include $themeIncludePath."/site-footer.php";
    }
    ?>

    <?php if (isset($pageVars)) {
        print "\n<form id='page-data'>\n";
        foreach ($pageVars as $key => $value) {
            printf("<input type='hidden' id='%s' name='%s' value='%s'>\n",$key,$key,$value);
        }
        print "</form>\n";
    }

    ?>
    <!-- late loading scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>

    <!-- for Peanut support -->
    <?php if (isset($mvvm)) {
        print('<script src="https://cdnjs.cloudflare.com/ajax/libs/headjs/1.0.3/head.load.js" integrity="sha512-XDpsu7o5F1+SqCmdXgSfbx7yPA99X0IQs8RsbiQSrJ4kxOZSlbJtgCJjmVbLiAPKOhnffctq61O/VMlD88GcxA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>'."\n");
        $test = sprintf('<script src="%s/pnut/core/%s"></script>', URL_PEANUT_ROOT, $loaderScript);
        printf('<script src="%s/pnut/core/%s"></script>'."\n", URL_PEANUT_ROOT, $loaderScript);
        \Peanut\sys\ViewModelManager::RenderStartScript();
    }
    ?>

</body>
</html>