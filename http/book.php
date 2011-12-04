<?php

include('../utils/HttpException.php');
include('../utils/Api.php');
include('../book/Generator.php');
include('../book/Page.php');
include('../book/Book.php');
include('../book/BookProvider.php');

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
        $generator->send($file);
} catch(HttpException $exception) {
        $exception->show();
}
