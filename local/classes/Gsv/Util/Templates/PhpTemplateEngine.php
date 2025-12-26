<?php

namespace Gsv\Util\Templates;

class PhpTemplateEngine extends TemplateEngine
{
    /**
     * @param string $template
     * @param array $data
     * @return string
     */
    public function renderTemplate($template, $data)
    {
        ob_start();
        eval($template);
        return ob_get_clean();
    }

    /**
     * @param string $template
     * @param array $data
     * @return void
     */
    public function displayTemplate($template, $data)
    {
        eval($template);
    }

    /**
     * @param string $file
     * @param array $data
     * @return string
     */
    public function renderFile(string $file, array $data)
    {
        ob_start();
        require $this->getFile($file);
        return ob_get_clean();
    }

    /**
     * @param string $file
     * @param array $data
     * @return void
     */
    public function displayFile(string $file, array $data)
    {
        require $this->getFile($file);
    }

    /**
     * @param string $template
     * @throws \Exception
     * @return string
     */
    public function escapeScriptTag($template)
    {
        //TODO: Uplift this restriction somehow
        throw new \Exception('Not supported in JS');
    }

    public function getFile($file)
    {
          return $_SERVER['DOCUMENT_ROOT'].'/'.ltrim($file, '/\\');
    }
}