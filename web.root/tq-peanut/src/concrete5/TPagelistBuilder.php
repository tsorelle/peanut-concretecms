<?php

namespace Tops\concrete5;

use Concrete\Core;
use Concrete\Core\User\UserInfo;
use Tops\sys\TUser;


class TPagelistBuilder
{
    public $test;

    const buttonClasses = 'ccm-block-page-list-read-more';
    const entryClasses = 'ccm-block-page-list-page-entry';

    private $editModeBlock = null;
    private $pages = [];
    private $pageCount = 0;
    private $pageIndex = 0;
    private $includeEntryText = true;
    private $linkText = 'Read more...';
    private $pageListTitleBlock = '';
    private $rssBlock = '';
    private $noResultsBlock = '';
    private $isEditMode = false;
    private $pagination = '';
    private $startEntry = 0;
    private $entriesSelected = 0;
    private $useButtonForLink = true;
    private $textHelper;
    private $controller;
    private $displayThumbnail;
    private $includeName;
    private $showExpired = false;
    private $includeDescription;
    private $canEdit = false;
    public $filterMethod = null;
    private $today;

    public function __construct(
        $mainPage,
        $textHelper,
        $controller,
        $listTitleFormat,
        $pages,
        $displayThumbnail,
        $noResultsMessage,
        $showPagination,
        $pagination,
        $buttonLinkText,
        $pageListTitle,
        $useButtonForLink,
        $includeName,
        $includeDescription,
        $rssUrl,
        $showExpired = false
    ) {
        // check for edit mode
        $this->isEditMode = (is_object($mainPage) && $mainPage->isEditMode());
        if ($this->isEditMode && $controller->isBlockEmpty()) {
            $this->editModeBlock = sprintf('<div class="ccm-edit-mode-disabled-item">%s</div>', t("Empty Page List Block."));
        }

        if ($this->isEditMode) {
            $noResultsMessage = 'List not visible in edit mode';
        }
        else if (empty($noResultsMessage)) {
            $noResultsMessage = 'No news today.';
        }
        $this->noResultsBlock = sprintf('<div class="ccm-block-page-list-no-pages">%s</div>',
                h($noResultsMessage)
        );

        if ($showPagination) {
            $this->pagination = $pagination;
        }

        if ($this->isEditMode) {
           return;
        }


        $user = TUser::GetCurrent();
        // $contentManager = $user->isMemberOf('Content managers');
        $this->canEdit = $user->isAdmin() || $user->isMemberOf('Content Managers');

        $this->showExpired = $showExpired;

        if (is_array($pages)) {
            $this->pages = $pages;
            $this->pageCount = count($pages);
            // $this->printBlock('test',"$this->pageCount items to process");
        }
        if ($this->pageCount) {
            // $this->filterMethod = $filterMethod;
            $this->includeName = $includeName;
            $this->includeDescription = $includeDescription;
            $this->includeEntryText = ($includeName || $includeDescription||$useButtonForLink);
            $this->linkText = $useButtonForLink && isset($buttonLinkText) ? $buttonLinkText : 'Read more...';
            $this->textHelper = $textHelper;
            $this->controller = $controller;
            $this->displayThumbnail = $displayThumbnail;

            $this->today = new \datetime('today');
            if ($pageListTitle) {
                $this->pageListTitleBlock =
                    sprintf('<div class="ccm-block-page-list-header">%s,%s,%s</div>',
                        $listTitleFormat,h($pageListTitle),$listTitleFormat);
            }

            if ($rssUrl) {
                $this->rssBlock = sprintf(
                    '<a href="%s" target="_blank" class="ccm-block-page-list-rss-feed">'.
                    '<i class="fas fa-rss"></i></a>', $rssUrl);
            }
        }
    }

    private function checkSelected($page)
    {
        /*
         * Note:
         *   $this->filterMethod is an anonymous function passed from the calling script, view.php.
         *      $filter = function($page) {
         *          return true; // or false based on filter rules
         *      }
         *   closure stored as class variable must be assigned to a local variable
         *   before it can be invoked.  (go figure) TLS
         */
        $filter = $this->filterMethod;
        if ($filter) {
            $ok = $filter($page);
        }
        $selected = (!$filter) || $filter($page);
        if (!$selected) {
            return false;
        }
        if (!$this->showExpired) {
            $expirationdate = $page->getAttribute('pnut_expiration_date');
            if ($expirationdate && new $this->today >= $expirationdate) {
                return false;
            }
        }

        if ($this->startEntry) {
            $this->startEntry--;
            return false;
        }
        return true;
    }

    private function renderEntry($format,$page)
    {
        // $this->printBlock('test message',"<div><p>ENTRY HERE</p></div>");
        if ($format->useShortTitle) {
            $title = $page->getAttribute('pnut_short_title');
            if (!$title) {
                $title = $page->getCollectionName();
            }
        }
        else {
            $title = $page->getCollectionName();
        }
        $summary = $page->getAttribute('pnut_summary');
        $hasDetails = $page->getAttribute('pnut_has_detail');
        $linkToPage = ($this->useButtonForLink && ($hasDetails || ($summary && $format->descriptionType == 2)));

        if ($page->getCollectionPointerExternalLink() != '') {
            $url = $page->getCollectionPointerExternalLink();
            if ($page->openCollectionPointerExternalLinkInNewWindow()) {
                $target = '_blank';
            }
        } else {
            $url = $page->getCollectionLink();
            $target = $page->getAttribute('nav_target');
        }
        $target = empty($target) ? '_self' : $target;

        $descriptionType = $format->descriptionType;
        if ($descriptionType) {
            $description =
                $descriptionType == 1 ?
                    $summary : null;
            if (!$description) {
                $description = $page->getCollectionDescription();
                $description = $this->controller->truncateSummaries ?
                    $this->textHelper->wordSafeShortText($description,
                        $this->controller->truncateChars) : $description;
                $description = h($description);
            }
        }

        $thumbnail = false;
        if ($format->imageWidth) {
            $thumbnail = $page->getAttribute('thumbnail');
        }
        if (is_object($thumbnail) && $this->includeEntryText) {
            $entryClasses = 'ccm-block-page-list-page-entry-horizontal';
        }

        $imageOrientation = $format->imageOrientation;

        if (is_object($thumbnail) && $format->imageWidth > 0) {
            if ($format->imageOrientation == 3) {
                $imageOrientation = $this->entriesSelected % 2 + 1;
            }

            $imageUrl = $format->useBlogThumbnail ?
                $thumbnail->getThumbnailURL('blog_entry_thumbnail') :
                $thumbnail->getThumbnailURL(null);
                $classes = 'img-fluid';
/* debug stuff
                if (empty($imageUrl)) {
                    $this->printBlock('test','<div>NO THUMBNAIL URL!</div>');
                }
                else {
                    $this->printBlock('test','<div>FOUND THUMBNAIL URL!</div>');
                }
*/
                if (!$page->getAttribute('pnut_no_img_frame')) {
                    $classes.= ' img-thumbnail';
                }

                $thumbTag = sprintf(
                    '<picture><!--[if IE 9]><video style="display: none;"><![endif]--><!--[if IE 9]></video><![endif]-->'.
                    '<img src="%s" alt="#" class="%s"></picture>',
                    $imageUrl,$classes);

            $mainWidth = $imageOrientation == 4 ? 12 : 12 - $format->imageWidth;
            if ($linkToPage) {
                $thumbnailBlock = sprintf(
                    '<div class="col-md-%d fma-news-thumbnail"><a href="%s" target="%s">%s</a></div>',
                    $format->imageWidth,
                    h($url),
                    h($target),
                    $thumbTag);
            }
            else {
                $thumbnailBlock = sprintf(
                    '<div class="col-md-%d fma-news-thumbnail">%s</div>',
                    $format->imageWidth,
                    $thumbTag);
            }
        } else {
            // for debug:
            // $this->printBlock('test','<div>NO IMAGE width='.$format->imageWidth.'</div>');
            $thumbnailBlock = '';
            $imageWidth = 0;
            $imageOrientation = 0;
            $mainWidth = 12;
        }

        $descriptionBlock =
            $format->descriptionType ?
                sprintf('<div class="col-md-%d pnut-news-description">'.
                    '<div class="pnut-news-description-body">%s</div></div>',
                        $mainWidth,
                        $description) : '';

        if ($format->bylinePosition > 0) {
            $author = null;
            if ($format->bylineType == 2) {
                $author = $page->getAttribute('pnut_author_name'); // todo:test this
                if (!$author) {
                    $page_owner = UserInfo::getByID($page->getCollectionUserID());
                    if (is_object($page_owner)) {
                        $author = $page_owner->getAttributeValue('full_name');
                    }
                }
            }

            $date = $page->getCollectionDatePublicObject()->format($format->dateFormat);
            if ($format->bylineType == 1 || !$author)
            {
                $byline = $date;
            }
            else {
                $byline = "By $author, $date";
            }
        }

        $this->printBeginBlock('Entries', sprintf('<div class="%s">', self::entryClasses));

        if ($this->includeEntryText) {
            $this->printBeginBlock('Item header row', '<div class="row">');
            $this->printBeginBlock('Item header column', '<div class="col-md-12 pnut-news-item-header">');
            if ($format->titleFormat) {
                if ($hasDetails || $this->canEdit) { // enable title links
                    $classes = "ccm-block-page-list-title pnut-news-item-title";
                    $content = sprintf('<a href="%s" target="%s">%s</a>',
                        $url, $target, $title);

                } else {
                    $classes = "pagelist-static-title pnut-news-item-title";
                    $content = $title;
                }
                $this->printBlock('Title',
                    sprintf('<div class="%s"><h%s>%s</h%s></div>',
                        $classes,
                        $format->titleFormat,
                        $content,
                        $format->titleFormat
                    )
                );
            }
            if ($format->bylinePosition == 1) {
                $this->printBlock('byline', "<div class='fma-news-byline'>$byline</div>");
            }

            $this->printEndBlock('Item header column');
            $this->printEndBlock('Item header row');
        }

        $imageWidth = $imageWidth ?? 0;
        $rowComment = $imageWidth == 12 ? 'Thumbnail row' : 'Thumbnail/Description row';
        $this->printBeginBlock($rowComment,'<div class="row">');
        if ($imageOrientation == 1 || $imageOrientation == 4) {
            $this->printBlock('Thumbnail', $thumbnailBlock);
        }

        if ($format->imageWidth == 12) {
            // divide entry into rows
            $this->printEndBlock($rowComment);
            $rowComment = 'description row';
            $this->printBeginBlock($rowComment, '<div class="row">');
        }
        $this->printBlock('Description',$descriptionBlock);

        if ($imageOrientation == 2) {
            // print thumbnail in right columm
            $this->printBlock('Thumbnail', $thumbnailBlock);
        }
/*        else {
            // print description in right column
            $this->printBlock('Description',$descriptionBlock);
        }*/
        $this->printEndBlock($rowComment);

        $this->printBeginBlock('Item footer row', '<div class="row">');
        $this->printBeginBlock('Item footer column', '<div class="col-md-12 fma-news-footer">');

        if ($linkToPage) {
            $this->printBlock('Read more',
                sprintf('<div class="ccm-block-page-list-page-entry-read-more">' .
                    '<a href="%s" target="%s" class="%s">%s</a></div>', h($url), h($target),
                    h(self::buttonClasses), h($this->linkText))
            );
        }
        if ($format->bylinePosition == 2) {
            $this->printBlock('byline', "<div class='fma-news-byline'>$byline</div>");
        }

        $this->printEndBlock('Item footer column');
        $this->printEndBlock('Item footer row');
        $this->printEndBlock('Entries');

        // $this->entriesSelected++;

    }

    private function renderEntries($format)
    {
        $pageQueue = [];
        // $this->printBlock('test',"<div>Format started</div>");

        if ($format->maxEntries < 1) {
            $lastOne = $this->pageCount;
        }
        else {
            $lastOne =  min($this->entriesSelected + $format->maxEntries,$this->pageCount);
        }

        // $this->printBlock('test',"<div>Selected=$this->entriesSelected Last=$lastOne Max=$format->maxEntries Pages: $this->pageCount Index: $this->pageIndex</div>");

        while ($this->entriesSelected < $lastOne && $this->pageIndex < $this->pageCount) {
            $selected = $this->checkSelected($this->pages[$this->pageIndex]);
            if ($selected) {
                $pageQueue[] = $this->pageIndex;
                $this->entriesSelected++;
                // $this->printBlock('test',"<div>Item $this->pageIndex selected. Last=$lastOne Max=$format->maxEntries Pages: $this->pageCount</div>");
            }
            else {
                // $this->printBlock('test',"Item $this->pageIndex rejected");
            }
            $this->pageIndex++;
        }

        $queueCount = count($pageQueue);
        if ($queueCount > 0) {

            $this->printBeginBlock('List pages', '<div class="ccm-block-page-list-pages">');
            if ($format->sectionTitle) {
                $this->printBlock('section title', sprintf('<div class="pnut-news-section-title">%s</div>', $format->sectionTitle));
            }
            $multiColumn = $format->itemColumns > 1;
            $gridColumnWidth = 12 / $format->itemColumns;

            // while ($this->entriesSelected < $lastOne && $this->pageIndex < $this->pageCount) {
            $entriesPrinted = 0;
            while ($entriesPrinted < $queueCount) {
                $columnCounter = $format->itemColumns;
                if ($multiColumn) {
                    $this->printBeginBlock('column set', '<div class="row">');
                }
                while ($columnCounter > 0 && $entriesPrinted < $queueCount) {
                    $page = $this->pages[$pageQueue[$entriesPrinted]];

                    if ($multiColumn) {
                        $this->printBeginBlock('item column',
                            sprintf('<div class="col-md-%d">', $gridColumnWidth));
                    }
                    $this->renderEntry($format, $page);
                    if ($multiColumn) {
                        $this->printEndBlock('item column');
                    }
                    $columnCounter--;

                    $entriesPrinted++;
                }
                if ($multiColumn) {
                    $this->printEndBlock('column set');
                }
                print $this->getTabs() . "<hr>\n\n";
            }
            $this->printEndBlock('List pages');
        }
        return ($this->pageIndex >= $this->pageCount);
    }


    public function getPageCount() {
        return ($this->pageCount);
    }

    private $nesting = 0;

    private function getTabs() {
        $tabs = "\n";
        for ($i= 0; $i < $this->nesting + 1; $i++) {
            $tabs .= "\t";
        }
        return $tabs;
    }

    private function printBlock($blockName,$block) {
        if ($block) {
            $tabs = $this->getTabs();
            print("\n$tabs<!-- Begin: $blockName -->");
            print $tabs.$block;
            print("$tabs<!-- End: $blockName -->\n");
        }
        return !empty($block);
    }

    private function printBeginBlock($blockName,$text)
    {
        if ($text) {
            $tabs = $this->getTabs();
            printf("\n%s<!-- Begin: %s -->%s%s",$tabs,$blockName,$tabs,$text);
            $this->nesting += 1;
        }
    }

    private function printEndBlock($blockName,$text = '</div>')
    {
        if ($text) {
            $this->nesting -= 1;
            $tabs = $this->getTabs();
            printf("%s%s%s<!-- End: %s -->\n",$tabs,$text,$tabs,$blockName);
        }
    }

    private $formats = [];


    /*
     * imageOrientation:
     *      0 = No miage
     *      1 = Left
     *      2 = Right
     *      3 = Alternate left/right
     *      4 = stacked.
     *
     *  imageWidth = in Bootstrap colums x of 12, or 0 to hide image
     *  columns = number of item colums usually 1 to 6
     *  titleFormat =  header tag  1 to 5 (h1,h2,h3...) or 0 to hide title
     *  $descriptionType  0 = no description, 1 = summary if available else description, 2 - always description,
     *  $dateFormat
     *
*/



    public function addEntryFormat(
        $maxEntries = -1,       // default all
        $itemColumns = 1,       // entries per per row
        $imageOrientation = null,  // null=based on columns, 0 = No image, 1 = Left, 2 = Right, 3 = Alternating, 4 = stacked.
        $sectionTitle = null,
        $useBlogThumbnail = null, //
        $imageWidth = null,        // default 4 of 12
        $descriptionType = null, // select based on user settings or itemColumns
        $bylinePosition = null,    //  0 = hide, 1 = top, 2 = bottom
        $bylineType = 2,        // 1 = date only, 2 = author/date
        $useShortTitle = null,  // true to use short title
        $titleFormat = null,    // select based on user settings and number of columns
        $dateFormat = null

    )
    {
        /*
         * Orientation:
         *      0 = No miage
         *      1 = Left
         *      2 = Right
         *      3 = Alternate left/right
         *      4 = stacked.
        */

        if ((!$this->displayThumbnail) || $imageOrientation === 0 || $imageWidth === 0) {
            // hide image
            $imageOrientation = 0;
            $imageWidth = 0;
            $itemColumns = 1;
        }
        else {
            if ($imageOrientation == null) {
                switch($itemColumns) {
                    case 1:
                        $imageOrientation = 3;
                        break;
                    case 2:
                        $imageOrientation = 1;
                        break;
                    default:
                        $imageOrientation = 4;
                }
            }

            if ($imageOrientation == 4) {
                $imageWidth = 12;
            }
            else if ($imageWidth === null) {
                switch($itemColumns) {
                    case 1:
                        $imageWidth = 5;
                        break;
                    case 2:
                        $imageWidth = 4;
                        break;
                    default:
                        $imageWidth = 3;
                }
            }
        }

        if (!$this->includeName) {
            $titleFormat = 0;
        }
        else  if ($titleFormat == null) {
            $titleFormat = $itemColumns > 4 ? 5 : $itemColumns + 1;
        }

        if (!$this->includeDescription) {
            $descriptionType = 0;
        }
        else if ($descriptionType == null) {
            $descriptionType = ($itemColumns < 3) ?
                1 : // summary if available else description
                2;  // description only
        }

        if (!$this->includeEntryText) {
            $bylinePosition = 0;
            $bylineType = 0;
        }
        else {
            if ($bylinePosition === null) {
                $bylinePosition = $itemColumns > 2 ? 0 : 1;
            }
            if ($dateFormat == null) {
                if ($itemColumns > 3) {
                    $dateFormat = 'n/j/Y';
                }
                else if ($itemColumns > 1) {
                    $dateFormat = 'M j, Y';
                }
                else {
                    $dateFormat = 'F j, Y';
                }
            }
        }

        if($useBlogThumbnail == null) {
            $useBlogThumbnail = $itemColumns > 1;
        }

        if ($useShortTitle === null) {
            $useShortTitle = ($itemColumns > 2);
        }

        $format = new \stdClass();
        $format->maxEntries       = $maxEntries   ;
        $format->imageOrientation = $imageOrientation;
        $format->imageWidth       = $imageWidth;
        $format->itemColumns      = $itemColumns;
        $format->descriptionType  = $descriptionType;
        $format->titleFormat      = $titleFormat;
        $format->sectionTitle     = $sectionTitle;
        $format->bylinePosition   = $bylinePosition;
        $format->bylineType       = $bylineType;
        $format->useShortTitle    = $useShortTitle;
        $format->useBlogThumbnail = $useBlogThumbnail;
        $format->dateFormat = $dateFormat;
        array_push($this->formats,$format);
    }

    public function render() {
        if ($this->printBlock('Check edit mode',$this->editModeBlock)) {
            return; // nothing to render
        }
        $this->printBeginBlock('Page list wrapper',
            '<div class="ccm-block-page-list-wrapper pnut-news-page-list">');

        $this->printBlock('Page list title',$this->pageListTitleBlock);
        $this->printBlock('RSS feed',$this->rssBlock);
        $this->pageIndex = 0;
        foreach ($this->formats as $format) {
            $this->renderEntries($format);
        }

        $this->printEndBlock('Page list wrapper');

        if ($this->entriesSelected == 0) {
            $this->printBlock('No results',$this->noResultsBlock);
        }
        $this->printBlock('Pagination',$this->pagination);

        if ($this->nesting != 0) {
            print '<div class="text-danger">Uneven div nesting in TPageListBuilder</div>';
        }

    }

    public function skipFirstEntries($i) {
        $this->startEntry = $i;
    }
    public function  setFilter($f) {
        $this->filterMethod = $f;
    }
}