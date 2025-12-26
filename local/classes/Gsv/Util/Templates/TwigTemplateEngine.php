<?php

namespace Gsv\Util\Templates;

class TwigTemplateEngine extends DocrootTemplateEngine
{
    /**
     * @var \Twig\Environment $twigEnvironment
     */
    protected $twigEnvironment;

    /**
     * @param string $template
     * @param array $data
     * @return string
     */
    public function renderTemplate($template, $data)
    {
        return static::twig($template)->render($data);
    }

    /**
     * @param string $template
     * @param array $data
     * @return void
     */
    public function displayTemplate($template, $data)
    {
        static::twig($template)->display($data);
    }

    public function twig($template)
    {
        $twigEnvironment = static::getTwigEnvironment();
        $result = $twigEnvironment->createTemplate($template);
        return $result;
    }

    /**
     * @param string $file
     * @param array $data
     * @return string
     */
    public function renderFileInDocRoot(string $file, array $data)
    {
        return static::getTwigEnvironment()->render($file, $data);
    }

    /**
     * @param string $file
     * @param array $data
     * @return void
     */
    public function displayFileInDocRoot(string $file, array $data)
    {
        static::getTwigEnvironment()->display($file, $data);
    }

    public function getTwigEnvironment()
    {
        if($this->twigEnvironment === null)
        {
            $loader = new \Twig\Loader\FilesystemLoader($_SERVER['DOCUMENT_ROOT']);
            $options = [];
            if(!gsv_is_debug()) {
                $options['cache'] = $_SERVER['DOCUMENT_ROOT'] . '/local/twig/cache';
            }
            $this->twigEnvironment = new \Twig\Environment($loader, $options);
        }

        return $this->twigEnvironment;
    }

    public function escapeScriptTag($template)
    {
        return preg_replace('#</\s*(script)\s*>#i', '{{"<"}}{{"/$1"}}{{">"}}', $template);
    }
}