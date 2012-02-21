<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>WSexport</title>
    <link type="text/css" href="bootstrap.min.css" rel="stylesheet" />
    <style type="text/css">
    html, body {
        background-color: #eee;
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
            <?php if(isset($success)) {
                echo '<div class="alert alert-success">' . $success . '</div>' . "\n";
            } ?>
