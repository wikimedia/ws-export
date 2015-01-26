<?php
$wsexportConfig = array(
        'basePath' => '..',
        'tempPath' => '../temp',
        'stat' => true
);

include_once('../book/init.php');

try {
        $api = new Api();

        $options = array();
        $options['images'] = isset($_GET['images']) ? filter_var($_GET['images'], FILTER_VALIDATE_BOOLEAN) : true;
        if(in_array($api->lang, array('fr', 'en', 'de', 'it', 'es', 'pt', 'vec', 'pl', 'nl'))) {
            $options['fonts'] = isset($_GET['fonts']) ? strtolower(htmlspecialchars(urldecode($_GET['fonts']))) : '';
        } else {
            $options['fonts'] = isset($_GET['fonts']) ? strtolower(htmlspecialchars(urldecode($_GET['fonts']))) : 'freeserif';
            if(filter_var($options['fonts'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === false) {
                $options['fonts'] = '';
            }
        }

        if(isset($_GET['refresh'])) {
                include $wsexportConfig['basePath'].'/book/Refresh.php';
                $refresh = new Refresh($api->lang);
                $refresh->refresh();
                $success = 'The cache is updated for ' . $api->lang . ' language.';
                include 'templates/book.php';
        }

        if(!isset($_GET['page']) || $_GET['page'] == '')
                include 'templates/book.php';

        $title = trim(htmlspecialchars(urldecode($_GET['page'])));
        $format = isset($_GET['format']) ? htmlspecialchars(urldecode($_GET['format'])) : 'epub';
        $provider = new BookProvider($api, $options);
        $data = $provider->get($title);
        if($format == 'epub') {
                $format = 'epub-2';
        }
        if($format == 'epub-2') {
                include($basePath . '/book/formats/Epub2Generator.php');
                $generator = new Epub2Generator();
        } else if($format == 'epub-3') {
                include($basePath . '/book/formats/Epub3Generator.php');
                $generator = new Epub3Generator();
        } else if($format == 'odt') {
                include($basePath . '/book/formats/OdtGenerator.php');
                $generator = new OdtGenerator();
        } else if($format == 'xhtml') {
                include($basePath . '/book/formats/XhtmlGenerator.php');
                $generator = new XhtmlGenerator();
        } else if($format == 'atom') {
                $generator = new AtomGenerator();
        } else {
                throw new HttpException('Unsupported Media Type', 415);
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
		header('HTTP/1.1 ' . $exception->getCode() . ' ' . $exception->getMessage());
        $error = $exception->getMessage();
        include 'templates/book.php';
}
