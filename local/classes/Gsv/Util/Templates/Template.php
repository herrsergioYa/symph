<?php

namespace Gsv\Util\Templates;

class Template
{
    /**
     * @var string $template
     */
    protected /*string*/ $template = '';

    /**
     * @var ?TemplateEngine $templateEngine
     */
    protected ?TemplateEngine $templateEngine;

    /**
     * @param ?TemplateEngine $templateEngine
     * @param string $template
     */
    public function __construct(?TemplateEngine $templateEngine = null, /*string*/ $template = '')
    {
        $this->setTemplateEngine($templateEngine);
        $this->setTemplate($template);
    }

    public function begin()
    {
        $this->setTemplate('');
        ob_start();
    }

    public function end()
    {
        $template = ob_get_clean();
        $this->setTemplate($template);
    }

    /**
     * @param array $data
     * @return string
     */
    public function render(/*array*/ $data = [])
    {
        $template = $this->getTemplate();
        return $this->getTemplateEngine()->renderTemplate($template, $data);
    }

    /**
     * @param array $data
     * @return void
     */
    public function display(/*array*/ $data = [])
    {
        $template = $this->getTemplate();
        $this->getTemplateEngine()->displayTemplate($template, $data);
    }

    /**
     * @param ?string|array|false $id
     * @return string
     */
    public function script(/*?string|array|false*/ $id)
    {
        $template = $this->getTemplate();
        return static::scriptTemplate($id, $template);
    }

    /**
     * @param ?string|array|false $id
     * @return void
     */
    public function echo(/*?string|array|false*/ $id)
    {
        echo $this->script($id);
    }

    /**
     * @param ?string|array|false $id
     * @param string $template
     * @return string
     */
    public function scriptTemplate(/*?string|array|false*/ $id, /*string*/ $template)
    {
        $result = "<script type=\"text/template\"";

        if(is_array($id)) {
            $classes = [];
            $attrs = [];
            foreach ($id as $k => $v) {
                if(is_numeric($k)) {
                    $classes[] = htmlentities($v);
                } else {
                    $attrs[] = "data-" . htmlentities($k) . "=\"" . htmlentities($v) . "\"";
                }
            }
            if($classes) {
                $result .= " class=\"" . implode(' ', $classes) . "\"";
            }
            if($attrs) {
                $result .= " " . implode(' ', $attrs);
            }
        } else if($id === '') {
            //Nothing to do
        } else if(is_string($id)) {
            $htmlId = htmlentities($id);
            $result .= " id=\"$htmlId\"";
        } else if($id === null || $id === false) {
            //No wrapper at all
            return static::escapeScriptTag($template);
        } else {
            throw new \Exception("InvalidArgument id = " . var_export($id, true));
        }
        $result .= ">";

        //TODO: Escape '</script>' somehow...
        $result .= static::escapeScriptTag($template);
        $result .= '</script>';
        return $result;
    }

    protected function escapeScriptTag($template)
    {
        return $this->getTemplateEngine()->escapeScriptTag($template);
    }


    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * @param string $template
     */
    public function setTemplate(/*string*/ $template): void
    {
        $this->template = $template;
    }

    /**
     * @return ?TemplateEngine
     */
    public function getTemplateEngine(): ?TemplateEngine
    {
        return $this->templateEngine;
    }

    /**
     * @param ?TemplateEngine $templateEngine
     */
    public function setTemplateEngine(/*?TemplateEngine*/ $templateEngine): void
    {
        $this->templateEngine = $templateEngine;
    }


    /**
     * @param string $file
     */
    public function loadFromFile(/*string*/ $file, /*bool*/ $inDocRoot = true)
    {
        if($inDocRoot) {
            $file = $this->getTemplateEngine()->fileFromDocRoot($file);
        }

        $template = $this->getTemplateEngine()->load($file);
        if($template !== false) {
            $this->setTemplate($template);
            return true;
        } else {
            //$this->setTemplate('');
            return false;
        }
    }
}