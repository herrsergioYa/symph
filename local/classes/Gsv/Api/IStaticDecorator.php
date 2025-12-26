<?php

namespace Gsv\Api;

interface IStaticDecorator
{
    static function decorateStatic($request, $callable);
}
