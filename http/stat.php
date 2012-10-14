<?php
$wsexportConfig = array(
        'basePath' => '..',
        'tempPath' => '../temp'
);

include('../book/init.php');

date_default_timezone_set('UTC');
$date = getdate();
$month = isset($_GET['month']) ? intval($_GET['month']) : $date['mon'];
$year = isset($_GET['year']) ? intval($_GET['year']) : $date['year'];

$stat = Stat::getStat($month, $year);
$val = array();
$total =  array(
        'epub-2' => 0,
        'epub-3' => 0,
        'odt' => 0,
        'xhtml' => 0
        );
foreach($stat as $format => $temp) {
        foreach($temp as $lang => $num) {
                if(!array_key_exists($lang, $val)) {
                        $val[$lang] = array(
                                'epub-2' => 0,
                                'epub-3' => 0,
                                'odt' => 0,
                                'xhtml' => 0
                                );
                }
                $val[$lang][$format] = $num;
                $total[$format] += $num;
        }
}
ksort($val);
include 'templates/stat.php';
