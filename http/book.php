<?php
$wsexportConfig = array(
        'basePath' => '..',
        'tempPath' => '../temp',
        'stat' => true
);

include('../book/init.php');

try {
        $api = new Api();

        $options = array();
        $options['images'] = isset($_GET['images']) ? filter_var($_GET['images'], FILTER_VALIDATE_BOOLEAN) : true;
        $options['fonts'] = isset($_GET['fonts']) ? filter_var($_GET['fonts'], FILTER_VALIDATE_BOOLEAN) : true;

        if(isset($_GET['refresh'])) {
                include $wsexportConfig['basePath'].'/book/Refresh.php';
                $refresh = new Refresh($api->lang);
                $refresh->refresh();
                $success = 'The cache is updated for ' . $api->lang . ' language.';
                include 'templates/book.php';
        }

        if(!isset($_GET['page']) || $_GET['page'] == '')
                include 'templates/book.php';

        $title = htmlspecialchars(urldecode($_GET['page']));
        $format = isset($_GET['format']) ? htmlspecialchars(urldecode($_GET['format'])) : 'epub';
        $provider = new BookProvider($api, $options);
        $data = $provider->get($title);
        if($format == 'epub-2' | $format == 'epub') {
                include($basePath . '/book/formats/Epub2Generator.php');
                $generator = new Epub2Generator();
        } else if($format == 'odt') {
                include($basePath . '/book/formats/OdtGenerator.php');
                $generator = new OdtGenerator();
        } else if($format == 'xhtml') {
                include($basePath . '/book/formats/XhtmlGenerator.php');
                $generator = new XhtmlGenerator();
        } else {
                include 'help/book.php';
        }
        $file = $generator->create($data);
        header('Content-Type: ' . $generator->getMimeType());
        header('Content-Disposition: attachment; filename="'. $title . '.' . $generator->getExtension() . '"');
        header('Content-length: ' . strlen($file));
        echo $file;
        if(isset($wsexportConfig['stat']))
                Stat::add($format, $api->lang);
        flush();
} catch(HttpException $exception) {
        $exception->show();
}
