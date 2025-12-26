<?php

namespace Gsv\Util\Templates;

class HandlebarsTemplateEngine extends DocrootTemplateEngine
{
    /**
     * @var \Handlebars\Handlebars $handlebars;
     */
    protected $handlebars;

    public function getHandlebars()
    {
        if($this->handlebars === null)
        {
            $partialsDir = $_SERVER['DOCUMENT_ROOT'];
            $partialsLoader = new \Handlebars\Loader\FilesystemLoader($partialsDir,
                [
                    "extension" => "handlebars",
                ]
            );

            $this->handlebars = new \Handlebars\Handlebars([
                "loader" => $partialsLoader,
                "partials_loader" => $partialsLoader,
                "enableDataVariables" => true,
            ]);
        }
        return $this->handlebars;
    }

    /**
     * @param string $template
     * @param array $data
     * @return string
     */
    public function renderTemplate($template, $data)
    {
        return static::handlebars($template)->render($data);
    }

    public function handlebars($template)
    {
        $handlebars = static::getHandlebars();
        $result = $handlebars->loadString($template);
        return $result;
    }

    /**
     * @param string $file
     * @param array $data
     * @return string
     */
    public function renderFileInDocRoot(string $file, array $data)
    {
        return static::getHandlebars()->render($file, $data);
    }

    public function escapeScriptTag($template)
    {
        return preg_replace('#</\s*(script)\s*>#i', '<{{!--A--}}/{{!--B--}}$1{{!--C--}}>', $template);
    }
}