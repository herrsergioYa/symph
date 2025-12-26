<?php

namespace Gsv\Api;

interface IDecorator
{
    function decorate($request, $callable);
}
