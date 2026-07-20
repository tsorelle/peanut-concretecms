<?php

namespace Tops\cms\nutshell;

use Tops\cms\TRouteFinder;
use Tops\cms\TRouter;
use Tops\sys\IUser;
use Tops\sys\TConfiguration;
use Tops\sys\TUser;
use Tops\sys\TWebSite;

class SiteMap
{
    private $xmldata;
    /**
     * @var IUser
     */
    private $user;

    private $currentUri;

    public function __construct($currentUri=null,$xmlFilePath=null)
    {
        $this->currentUri = $currentUri;
        if (!$xmlFilePath) {
            $xmlFilePath = DIR_CONFIGURATION.'/sitemap.xml';
        }
        $this->xmldata = simplexml_load_file($xmlFilePath);
        if ($this->xmldata === false) {
            throw new \Exception("Data file not found: ".$xmlFilePath);
        }
        $this->user = TUser::getCurrent();
    }

    public function test() {
        print "<h2>Hello from sitemap</h2>";
    }

    public function getMenuItem($itemName,$path='/*') {
/*        if (empty($path) || $path=='/*') {
            return null;
        }*/
        if (empty($path) || $path=='/') {
            $path = '/*';
        }
        $n = @$this->xmldata->xpath($path);
        if ($n) {
            $root = $n[0];
            $node = $root->$itemName ?? null;
            if ($node) {
                return $this->getItem($itemName,$node);
            }
        }
        return null;
    }

    public function getMenu($path='/*') {
        $n = $this->xmldata->xpath($path);
        $menu = [];
        if (!isset($n[0])) {
            return $menu;
        }
        foreach ($n[0] as $key => $node) {
            $item = $this->getItem($key,$node);
            $uri = $item->uri ?? null;
            // roles that are preassigned, either manually or by a process such as WordPressSiteMapBuilder
            // will override roles in routing.ini
            if (isset($item->roles)) {
                // roles from xml attribute
                $roles = $item->roles ?? '';
            }
            else {
                // roles from routing.ini
                $routes = TRouteFinder::GetRoutes();
                $roles = $routes[$path]['roles'] ?? '';
            }
            if ($this->authorized($item->roles ?? [])) {
                $menu[] = $item;
            }
        }
        return $menu;
    }

    private function authorized($roles)
    {
        if (empty($roles) || $this->user->isAdmin()) {
            return true;
        }
        $roles = explode(',',$roles);
        if ($this->user->isAuthenticated()) {
            foreach ($roles as $role) {
                if ($role=='authenticated' || $this->user->isMemberOf($role)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function isExternal($href) {
        return (stripos($href,'http://') === 0 || stripos($href,'https://') === 0 );
    }

    /** @noinspection HtmlUnknownTarget */
    public function renderMenu($activeItem=null,$path=null) {
        if ($path === null) {
            $path = $this->currentUri;
        }

        if ($path == 'home' || $path == null) {
            $path = '';
        }
        if ($activeItem === null) {
            $parts = explode('/',$path);
            $activeItem = array_pop($parts);
        }
        $menu = $this->getMenu($path);
        $lines = [];
        $lines[] = '  <div class="nutshell-vertical-menu">';
        $lines[] = '    <ul class="nav flex-column">';
        foreach ($menu as $item) {
            $lines[] = '      <li class="nav-item">';
            $active = $item->name == $activeItem;
            if ($active) {
                $lines[] = sprintf('        <a class="nav-link disabled" href="#">%s</a>', $item->title);
            }
            else {
                // $description = empty($item->description) ? $item->name : $item->description;
                $description = $item->description ?? '';
                $href = empty($item->uri) ? $item->name : $item->uri;
                if (empty($item->uri)) {
                    $href = $item->name;
                    $href = '/' . $path . '/' . $href;
                    $attrs = sprintf('href="%s"',$href);
                }
                else {
                    $href = $item->uri;
                    if ($this->isExternal($href)) {
                        $attrs = sprintf('href="%s" target="_blank"',$href);
                    } else {
                        $attrs = sprintf('href="/%s"',$href);
                    }
                }


                $lines[] = sprintf('        <a class="nav-link" '.$attrs.' title="%s">%s</a>', $description, $item->title);
            }
            $lines[] = '      </li>';
        }
        $lines[] = '    </ul>';
        $lines[] = '  </div>';
        return implode("\n",$lines)."\n";
    }

    /** @noinspection HtmlUnknownTarget */
    public function renderTopNav($activePath = null) {
        if ($activePath === null) {
            $activePath = $this->currentUri;
        }
        if ($activePath == 'home') {
            $activePath = '';
        }
        $parts = explode('/',$activePath);
        $activeItem = array_pop($parts);
        $menu = $this->getMenu();
        $lines = [];
        foreach ($menu as $item) {
            $active = $item->name == $activeItem;
            $href =  empty($item->uri) ? $item->name : $item->uri;
            $external =  $this->isExternal($href);
            if (!$external) {
                $href = '/'.$href;
            }
            $children = $external ? [] : $this->getMenu($item->name);
            // $description = empty($item->description) ? $item->name : $item->description;
            $description = $item->description ?? '';
            // $linkText = $item->icon ? sprintf('<i class="%s" ></i>',$item->icon) : $item->title;
            $linkText = empty($item->icon) ? $item->title : sprintf('<i class="%s" ></i>',$item->icon);
            if (empty($children)) {
                $activeClass = $active ? ' active' : '';
                $lines[] = '    <li class="nav-item">';
                $lines[] = sprintf('      <a class="nav-link'.$activeClass.'" href="%s" title="%s">%s</a>',
                     $href, $description,  $linkText);
            }
            else {
                $id = "dropdown-".$item->name;
                $lines[] = '    <li class="nav-item dropdown">';
                $lines[] = sprintf(
                    '        <a class="nav-link dropdown-toggle" href="%s" id="%s" data-bs-toggle="dropdown" '.
                        'aria-expanded="false" title="%s">%s</a>',$href,$id,$description,
                            $linkText);
                $lines[] = "        <ul class='dropdown-menu' aria-labelledby='$id'>";
                foreach ($children as $child) {
                    // $description = empty($child->description) ? $child->name : $child->description;
                    $description = $child->description ?? '';
                    $href = empty($child->uri) ? $item->name.'/'.$child->name : $child->uri;
                    if ($href !== null && !str_starts_with($href, '/')) {
                        $href = '/'.$href;
                    }
                    $lines[] = sprintf('          <li><a class="dropdown-item" href="%s" title="%s">%s</a></li>',
                        $href,$description, $child->title);
                }
                $lines[] = '        </ul>';
            }
            $lines[] = '    </li>';
        }
        return implode("\n",$lines)."\n";
    }

    public function getBreadCrumbItems($activePath = null) {
        if ($activePath === null) {
            $activePath = $this->currentUri;
        }
        $names = explode('/',$activePath);
        $items = [];
        while (!empty($names)) {
            $itemName = array_pop($names);
            $path = implode('/', $names);
            if ($itemName) {
                $item = $this->getMenuItem($itemName,$path);
                array_unshift($items,$item);
            }
        }
        return $items;
    }

    /** @noinspection HtmlUnknownTarget */
    public function renderBreadcrumbs($activePath,$divider=null) {
        if ($activePath === null) {
            $activePath = $this->currentUri;
        }
        if (empty($activePath) || $activePath=='home') {
            return '';
        }
        $items = $this->getBreadCrumbItems($activePath);
        $count = count($items);
        $last = $count-1;
        $crumbs = [];
        $uriPath = '';
        $nav = '<nav ';
        if ($divider) {
          $nav .= "style=\"--bs-breadcrumb-divider: '>';\" ";
        }
        $nav .= 'aria-label="breadcrumb">';
        $crumbs[] = $nav;
        $crumbs[] = '  <ol class="breadcrumb">';
        $crumbs[] = '    <li class="breadcrumb-item"><a href="/">Home</a></li>';
        for($i = 0; $i<$count; $i++) {
            $item = $items[$i];
            if (!$item) {
                continue;
            }
            $uriPath .= '/'.$item->name;
            // $href = $uriPath.$item->name;
            if ($i == $last) {
                $crumbs[] = sprintf('    <li class="breadcrumb-item active" aria-current="page">%s</li>',$item->title);
            }
            else {
                // $description = empty($item->description) ? $item->name : $item->description;
                $description = $item->description ?? '';
                $crumbs[] = sprintf('    <li class="breadcrumb-item"><a href="%s" title="%s">%s</a></li>', $uriPath, $description, $item->title);
            }
        }
        $crumbs[] = '  </ol>';
        $crumbs[] = '</nav>';
        return implode("\n",$crumbs)."\n";
    }

    public function printTopMenu($activePath = null) {
        print $this->renderTopNav($activePath);
    }

    public function printChildMenu($activePath = null)
    {
        print $this->renderMenu($activePath);
    }
    public function printSiblingMenu($activePath = null)
    {
        if ($activePath === null) {
            $activePath = $this->currentUri;
        }
        $path = explode('/',$activePath);
        $activeItem = array_shift($path);
        $activePath = implode('/',$path);
        print $this->renderMenu($activeItem,$activePath);
    }
    public function printBreadcrumbMenu($divider=null, $activePath = null) {
        print $this->renderBreadcrumbs($activePath,$divider);
    }

    /**
     * @param $key
     * @param $node
     * @param array $menu
     * @return \stdClass
     */
    protected function getItem($key, $node)
    {
        $item = new \stdClass();
        $item->name = $key;
        foreach ($node->attributes() as $name => $value) {
            $item->{$name} = sprintf('%s', $value);
        }
        if (isset($item->uri) && strpos($item->uri, '/') === 0) {
            $item->uri = $this->expandUri($item->uri);
        }
        return $item;
    }

    private static $baseUrl;
    private function expandUri($uri)
    {
        if (!isset(self::$baseUrl)) {
            self::$baseUrl = TWebSite::getBaseUrl();
        }
        return self::$baseUrl . $uri;
    }

}