<?php
    defined('C5_EXECUTE') or die('Access Denied.');
    /** @var \Concrete\Block\Content\Controller $controller */
    /** @var string $content */

    $c = \Concrete\Core\Page\Page::getCurrentPage();
    if (Tops\sys\TUser::GetCurrent()->isAuthenticated()) {
        echo $content;
    }
 ?>
