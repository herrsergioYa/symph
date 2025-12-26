<?php

namespace Gsv\Util\Templates;

use Mustache_Context;
use Mustache_Engine;
use Mustache_LambdaHelper;

class MustacheLambdaHelper extends Mustache_LambdaHelper
{
    /**
     * @var Mustache_Engine $mustache
     */
    protected $mustache;
    /**
     * @var Mustache_Context $context
     */
    protected $context;
    /**
     * @var ?string $delims
     */
    protected $delims;

    /**
     * @var Mustache_LambdaHelper $helper
     */
    protected $helper;

    public function __construct(Mustache_LambdaHelper $helper)
    {
        $mustacheProperty = new \ReflectionProperty(Mustache_LambdaHelper::class, 'mustache');
        $mustacheProperty->setAccessible(true);

        $contextProperty = new \ReflectionProperty(Mustache_LambdaHelper::class, 'context');
        $contextProperty->setAccessible(true);

        $delimsProperty = new \ReflectionProperty(Mustache_LambdaHelper::class, 'delims');
        $delimsProperty->setAccessible(true);

        parent::__construct(
            $this->mustache = $mustacheProperty->getValue($helper),
            $this->context = $contextProperty->getValue($helper),
            $this->delims = $delimsProperty->getValue($helper)
        );

        $this->helper = $helper;
    }

    public function render($string)
    {
        return $this->helper->render($string);
    }

    public function push($value)
    {
        $this->context->push($value);
    }

    public function pop()
    {
        return $this->context->pop();
    }

    public function get()
    {
        return $this->context->last();
    }

    public function renderWithValue($string, $value)
    {
        $this->push($value);
        try {
            return $this->helper->render($string);
        } finally {
            $this->pop();
        }
    }

    /*public static function iterKeysHelper($text, Mustache_LambdaHelper $helper)
    {
        //Stub to deceive Mustache on the PHP side
//        $self = new static($helper);
//        $value = $self->get();
//        return $self->renderWithValue($text, $value);
        return $helper->render($text);
//        return $text;
    }*/

    public static function iterKeysHelper($text, Mustache_LambdaHelper $helper)
    {
        $self = new static($helper);
        $value = (array)$self->get();
        $result = [];
        foreach ($value as $key => $val) {
            $result[] = $self->renderWithValue($text, $key);
        }
        return implode('', $result);
    }

    public static function iterValuesHelper($text, Mustache_LambdaHelper $helper)
    {
        $self = new static($helper);
        $value = (array)$self->get();
        $result = [];
        foreach ($value as $key => $val) {
            $result[] = $self->renderWithValue($text, $val);
        }
        return implode('', $result);
    }

    public static function iterObjHelper($text, Mustache_LambdaHelper $helper)
    {
        $self = new static($helper);
        $value = (array)$self->get();
        $result = [];
        foreach ($value as $key => $val) {
            $result[] = $self->renderWithValue($text, [
                'key' => $key,
                'value' => $val,
            ]);
        }
        return implode('', $result);
    }

    public static function getHelpers()
    {
        $result = [];
        foreach (get_class_methods(static::class) as $method) {
            if(substr(strtolower($method), -6) == 'helper')  {
                $callable = [static::class, $method];
                $method = substr($method, 0, -6);
                $result[$method] = function ($text, Mustache_LambdaHelper $helper) use($callable) {
                    return $callable($text, $helper);
                };
            }
        }
        return $result;
    }
}