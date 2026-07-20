<?php

namespace Tops\sys;

class TSystemEvents
{
    const HANDLER_CLASS_KEY = 'system.eventhandler';
    const ON_AUTHORIZATIONS_CHANGED = 'system.authorizations.changed';
    const ON_SYSTEM_START = 'system.start';
    const ON_SYSTEM_STOP = 'system.stop';
    const ON_SYSTEM_ERROR = 'system.error';
}