<?php

namespace Gsv\Util\Templates;

class HashTemplateEngine extends TemplateEngine
{
    /**
     * @param string $template
     * @param array $data
     * @return string
     */
    public function renderTemplate(/*string*/ $template, /*array*/ $data)
    {
        $search = $replace = [];
        foreach ($data as  $key => $value) {
            $search[] = "#$key#";
            $replace[] = $value;
        }
        $result = str_replace($search, $replace, $template);
        return $result;
    }

    public function escapeScriptTag($template)
    {
        if(preg_match('#</\s*(script)\s*>#i', $template)) {
            throw new \Exception('HashTemplate\'s script template CANNOT contain the closing script-tag!');
        }
        return $template;
    }
}