#!/usr/bin/php
<?php

$wsexportConfig = array(
        'basePath' => '..',
        // Need to refer to the same directory as in the website module
        'tempPath' => '../../website/temp/wsexport/www/wiki',
        'stat' => true
);
include('../book/init.php');


if($_SERVER['argc'] < 3 || $_SERVER['argc'] > 4) {
        echo getFile('help/book.txt');
} else {
        $lang = $_SERVER['argv'][1];
        $title = $_SERVER['argv'][2];
        $format = isset($_SERVER['argv'][3]) ? $_SERVER['argv'][3] : 'epub';
                $path = isset($_SERVER['argv'][4]) ? $_SERVER['argv'][4] . '/' : '';
        try {
                $api = new Api($lang);
                $options = array();
                $options['images'] = true;
                $provider = new BookProvider($api, $options);
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
                        throw new Exception('The file format is unknown');
                }
                $file = $generator->create($data);
                $path .= $title . '.' . $generator->getExtension();
                if($fp = fopen($path, 'w')) {
                        fputs($fp, $file);
                }
                echo "The ebook $path is created !\n";
        } catch(Exception $exception) {
                echo "Error: $exception\n";
        }
}
