<?php

include('../book/init.php');

try {
        if(!isset($_GET['page'])) throw new HttpException('Not Found', 404);
        $title = htmlspecialchars(urldecode($_GET['page']));
        $format = isset($_GET['format']) ? htmlspecialchars(urldecode($_GET['format'])) : 'epub';
        $api = new Api();
        $provider = new BookProvider($api);
        $data = $provider->get($title);
        if($format == 'epub-2' | $format == 'epub') {
                include('../book/formats/Epub2Generator.php');
                $generator = new Epub2Generator();
        } else {
                throw new HttpException('Bad Request', 400);
        }
        $file = $generator->create($data);
        header('Content-Type: ' . $generator->getMimeType());
        header('Content-Disposition: attachment;filename="' . $title . '.' . $generator->getExtension() . '"');
        header('Content-Description: File Transfert');
        header('Content-Transfer-Encoding: binary');
        header('Content-length: ' . strlen($file));
        echo $file;
        flush();
} catch(HttpException $exception) {
        $exception->show();
}
