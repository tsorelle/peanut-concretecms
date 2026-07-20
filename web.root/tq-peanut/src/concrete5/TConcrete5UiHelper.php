<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 1/18/2019
 * Time: 10:26 AM
 */

namespace Tops\concrete5;


// use Concrete\Core\Routing as URL;
use Concrete\Core\User\User;
use Concrete\Core\Validation\CSRF\Token;
use Tops\sys\TConfiguration;
use Tops\sys\TLanguage;

class TConcrete5UiHelper
{
    public static function renderLogInOutLink()
    {
        if (!id(new User())->isLoggedIn()) {
            $url = \URL::to('/login');
            $label = t('Sign in');
        } else {
            // $url = '#';//  \URL::to('/login', 'do_logout', id(new Token())->generate('do_logout'));
            $url = \URL::to('/login', 'do_logout', id(new Token())->generate('do_logout'));
            $label = t('Sign out');
        }

        print sprintf('<i class="fas fa-user"></i>&nbsp<a href="%s">%s</a>', $url, $label);
    }

    public static function renderRegistrationLink($appended=false) {
        if (id(new User())->isLoggedIn()) {
            return '';
        }
        if ($appended) {
            print "<span style='margin-left:1rem;margin-right:1rem'>|</span> ";
        }
        $registrationUrl = TConfiguration::getValue('registrationForm','pages');
        $registrationLabel = TLanguage::text('label_registration','Register');
        print sprintf('<a href="%s">%s</a>', $registrationUrl, $registrationLabel);
    }
}