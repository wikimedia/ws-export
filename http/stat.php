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
foreach($stat as $format => $temp) {
    foreach($temp as $lang => $num) {
        if(!in_array($lang, $val))
            $val[$lang] = array(
                'epub' => 0,
                'odt' => 0,
                'xhtml' => 0
                );
        $val[$lang][$format] = $num;
    }
}
ksort($val);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta content="text/html;charset=UTF-8" http-equiv="content-type"/>
    <title>WSexport</title>
    <link type="text/css" href="bootstrap.min.css" rel="stylesheet" />
    <style type="text/css">
    html, body {
        background-color: #eee;
    }
    .container > footer p {
        text-align: center;
    }
    .content {
        background-color: #fff;
        padding: 20px;
        margin: 0 -20px; /* negative indent the amount of the padding to maintain the grid system */
        -webkit-border-radius: 0 0 6px 6px;
        -moz-border-radius: 0 0 6px 6px;
        border-radius: 0 0 6px 6
        -webkit-box-shadow: 0 1px 2px rgba(0,0,0,.15);
        -moz-box-shadow: 0 1px 2px rgba(0,0,0,.15);
        box-shadow: 0 1px 2px rgba(0,0,0,.15);
    }
    .page-header {
        background-color: #f5f5f5;
        padding: 20px 20px 10px;
        margin: -20px -20px 20px;
    }
</style>
</head>
<body>
    <div class="container">
        <div class="content">
            <div class="page-header">
                <h1>Export tool of Wikisource books in many file formats.</h1>
            </div>
            <table class="table table-striped">
                <caption>Stats for <?php echo $month,'/',$year ?></caption>
                <thead>
                    <tr><th>Lang</th><th>epub</th><th>xhtml</th><th>odt</th></tr>
                </thead>
                <tbody>
                <?php foreach($val as $lang => $temp) {
                    echo '<tr><th scope="row">' . $lang . '</th><td>' . $temp['epub'] . '</td><td>' . $temp['xhtml'] . '</td><td>' . $temp['odt'] . '</td></tr>' . "\n";
                } ?>
                </tbody>
            </table>
            <form class="form-inline">
                <label>Change month:</label>
                <input name="month" id="month" type="number" placeholder="month" size="2" maxlength="2" min="1" max="12" required="required" value="<?php echo $month ?>" class="input-small">
                <input name="year" id="year" type="number" placeholder="year" size="4" maxlength="4" min="2012" max="<?php echo $date['year'] ?>" required="required" value="<?php echo $year ?>" class="input-small">
                <button class="btn" type="submit">Go</button>
            </form>
        </div>
        <footer>
            <p>Tool under <a rel="licence" href="http://www.gnu.org/licenses/gpl.html">GNU GPLv2+ licence.</a></p>
        </footer>
    </div>
</body>
</html>
<?php exit(); ?>
