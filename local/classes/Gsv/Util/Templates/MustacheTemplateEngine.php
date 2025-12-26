<?php

namespace Gsv\Util\Templates;

class MustacheTemplateEngine extends TemplateEngine
{
    /**
     * @var \Mustache_Engine $mustacheEngine;
     */
    protected $mustacheEngine;

    /**
     * @param string $template
     * @param array $data
     * @return string
     */
    public function renderTemplate(/*string*/ $template, /*array*/ $data)
    {
        $mustacheEngine = static::getMustacheEngine();
        $RESULT = $mustacheEngine->render($template, $data);
        return $RESULT;
    }

    public function getMustacheEngine()
    {
        if($this->mustacheEngine === null)
        {
            $this->mustacheEngine = new \Mustache_Engine(array(
                'entity_flags' => ENT_QUOTES,
                'strict_callables' => true,
                'helpers' => [
                    'helpers' => class_exists(\Gsv\Util\Templates\MustacheLambdaHelper::class) ? \Gsv\Util\Templates\MustacheLambdaHelper::getHelpers() : [],
                ],
            ));
        }
        return $this->mustacheEngine;
    }

    public function escapeScriptTag($template)
    {
        return preg_replace('#</\s*(script)\s*>#i', '<{{!A}}/{{!B}}$1{{!C}}>', $template);
    }
}