<?php
define('GSV_DEBUG', 'Y');

require_once  __DIR__ .'/../../php_interface/init.php';

$tmpl = new \Gsv\Util\Templates\Template(new Gsv\Util\Templates\TwigTemplateEngine());
$tmpl->loadFromFile('/local/twig/test/test.twig');

$tmpl->echo('twig');
$tmpl->display(['value' => 77]);

//displayTwigFileInDocRoot('/local/twig/test/test.twig', ['value' => 77]);
?>

<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script>window.global = window.global || window;</script>
<script src="/local/twig/twig.js@1.17.1/twig.js"></script>
<script src="/local/twig/twig.jq.js"></script>
<script src="/local/js/gsv-helpers.js"></script>
<script>
    debugger;
    let html = gsvJsHelper.renderTwig('twig', {value: 89});
    /*let twig = Twig.twig;
    let template = document.getElementById('twig');
    let twigTemplate = twig({data:template.innerHTML});
    let html = twigTemplate.render({value: 7});*/
    let $html = $('#twig').$twig({value:7});
    debugger;
    let $html1 = $('#twig1').$twig({value:7});
    debugger;
    //Twig.renderFile('test.tig');
</script>
