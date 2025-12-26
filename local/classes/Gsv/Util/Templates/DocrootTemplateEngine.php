<?php

namespace Gsv\Util\Templates;

abstract class DocrootTemplateEngine extends TemplateEngine
{
    /**
     * @param string $file
     * @param array $data
     * @return string
     */
    public function renderFile(string $file, array $data)
    {
        if(($locfile = static::fileToDocRoot($file)) !== false) {
            return static::renderFileInDocRoot($locfile, $data);
        }
        return parent::renderFile($file, $data);
    }

    /**
     * @param string $file
     * @param array $data
     * @return bool
     */
    public function displayFile(string $file, array $data)
    {
        if(($locfile = static::fileToDocRoot($file)) !== false) {
            static::displayFileInDocRoot($locfile, $data);
            return !!is_file($file);
        }
        return parent::displayFile($file, $data);
    }

    /**
     * @param string $file
     * @param array $data
     * @return string
     */
    public function renderFileInDocRoot(string $file, array $data)
    {
        $file = static::fileFromDocRoot($file);
        //Fear not! It is done just in case.
        //The method renderFileInDocRoot() should be overridden. I hope it will.
        $result = parent::renderFile($file, $data);
        return $result;
    }

    /**
     * @param string $file
     * @param array $data
     * @return void
     */
    public function displayFileInDocRoot(string $file, array $data)
    {
        echo static::renderFileInDocRoot($file, $data);
    }
}