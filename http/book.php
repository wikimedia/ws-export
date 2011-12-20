<?php
$basePath = '..';
include('../book/init.php');

try {
        if(!isset($_GET['page']) || $_GET['page'] == '') throw new HttpException('Not Found', 404);
        $title = htmlspecialchars(urldecode($_GET['page']));
        $format = isset($_GET['format']) ? htmlspecialchars(urldecode($_GET['format'])) : 'epub';
        $withPictures = isset($_GET['pictures']) ? (bool) $_GET['pictures'] : true;
        $api = new Api();
        $provider = new BookProvider($api, $withPictures);
        $data = $provider->get($title);
        if($format == 'epub-2' | $format == 'epub') {
                include('../book/formats/Epub2Generator.php');
                $generator = new Epub2Generator();
        } else if($format == 'odt') {
                include('../book/formats/OdtGenerator.php');
                $generator = new OdtGenerator();
        } else if($format == 'xhtml') {
                include('../book/formats/XhtmlGenerator.php');
                $generator = new XhtmlGenerator();
        } else {
                throw new HttpException('Bad Request', 400);
        }
        $file = $generator->create($data);
        header('Content-Type: ' . $generator->getMimeType());
        header('Content-Disposition: attachment; filename="'. $title . '.' . $generator->getExtension() . '"');
        header('Content-length: ' . strlen($file));
        echo $file;
        flush();
} catch(HttpException $exception) {
        $exception->show();
}
