<?php

namespace Tops\concrete5;

use Tops\sys\TRecaptcha;

class Concrete5Recaptcha extends TRecaptcha
{

    /**
     * @param $page
     * @return bool
     */
    protected function captchaRequired($page)
    {
        if ($page !== null) {
            $value = $page->getAttribute('require_captcha');
            if ($value === true) {
                return true;
            }
        }
        return false;

    }
}