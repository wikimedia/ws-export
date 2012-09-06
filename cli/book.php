#!/usr/bin/php
<?php

$wsexportConfig = array(
        'basePath' => '..',
        // Need to refer to the same directory as in the website module
        'tempPath' => '../../website/temp/wsexport/www/wiki',
        'stat' => true
);
include('../book/init.php');

if(!isset($_SERVER['argc']) || $_SERVER['argc'] < 3) {
        echo getFile('help/book.txt');
} else {
        $long_opts = array(
                'lang:',
                'title:',
                'format:',
                'path:',
                'debug'
                );

        $lang = null;
        $title = null;
        $format = 'epub';
        $path = './';

        $opts = getopt('l:t:f:p', $long_opts);
        foreach ($opts as $opt => $value) {
                switch ($opt) {
                case 'lang':
                        $lang = $value;
                        break;
                case 'title':
                        $title = $value;
                        break;
                case 'format':
                        $format = $value;
                        break;
                case 'path':
                        $path = $value . '/';
                        break;
                case 'debug':
                        error_reporting(E_STRICT|E_ALL);
                        break;
                }
        }
        if (!$lang or !$title) {
                echo getFile('help/book.txt');
                exit(1);
        }

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
                } else {
                        error_log('Unable to create output file: ' . $path . "\n");
                        exit(1);
                }
                echo "The ebook $path is created !\n";
        } catch(Exception $exception) {
                echo "Error: $exception\n";
        }
}
