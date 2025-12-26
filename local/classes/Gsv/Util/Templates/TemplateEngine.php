<?php

namespace Gsv\Util\Templates;

abstract class TemplateEngine
{
    /**
     * @param string $template
     * @param array $data
     * @return string
     */
    public abstract function renderTemplate(/*string*/ $template, /*array*/ $data);

    /**
     * @param string $template
     * @param array $data
     * @return void
     */
    public function displayTemplate(string $template, array $data)
    {
        echo $this->renderTemplate($template, $data);
    }

    /**
     * @param string $file
     * @param array $data
     * @return string|false
     */
    public function renderFile(string $file, array $data)
    {
        $template = static::load($file);
        if($template === false) {
            return false;
        }
        $result = $this->renderTemplate($template, $data);
        return $result;
    }

    /**
     * @param string $file
     * @param array $data
     * @return bool
     */
    public function displayFile(string $file, array $data)
    {
        $template = static::load($file);
        if($template === false) {
            return false;
        }
        $this->displayTemplate($template, $data);
        return true;
    }

    /**
     * @param string $file
     * @param array $data
     * @return bool
     */
    public function renderFileInDocRoot(string $file, array $data)
    {
        $file = static::fileFromDocRoot($file);
        $result = $this->renderFile($file, $data);
        return $result;
    }

    /**
     * @param string $file
     * @param array $data
     * @return bool
     */
    public function displayFileInDocRoot(string $file, array $data)
    {
        $file = static::fileFromDocRoot($file);
        $this->displayFile($file, $data);
    }

    public static function load($file)
    {
        return file_get_contents($file);
    }

    public static function fileFromDocRoot($file)
    {
        return rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($file, '/');
    }

    public static function fileToDocRoot($file)
    {
        //$file = realpath($file);
        $docRoot = /*realpath*/($_SERVER['DOCUMENT_ROOT']);
//        if($file === false || $docRoot === false) {
//            return false;
//        }
        $docRoot = rtrim($docRoot, '/') . '/';
        if(strpos($file, $docRoot) === 0) {
            $file = substr($file, strlen($docRoot) - 1);
        } else {
            $file = false;
        }
        return $file;
    }

    public abstract function escapeScriptTag($template);
}