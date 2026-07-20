<?php

namespace Peanut\PeanutMailings\sys;

use Tops\sys\TObjectContainer;

class SubscriptionManager
{
    private static ISubscriptionManager $instance;

    /**
     * @return ISubscriptionManager
     */
    public static function getInstance(): ISubscriptionManager
    {
        if (!isset(self::$instance)) {
            if (TObjectContainer::HasDefinition('peanut.subscription_manager')) {
                self::$instance = TObjectContainer::Get('peanut.subscription_manager');
            } else {
                throw new \Exception('Instance of peanut.subscription_manager not created');
            }
        }
        return self::$instance;
    }

    /*************************
     * Example implementation from QNut directory services
     *
     */
}