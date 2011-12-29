<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
        <meta content="text/html;charset=UTF-8" http-equiv="content-type"/>
        <title>WSexport</title>
        <link type="text/css" href="http://twitter.github.com/bootstrap/1.4.0/bootstrap.min.css" rel="stylesheet" />
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
                        <form method="get" action="book.php">
                                <fieldset>
                                        <legend>Export a file</legend>
                                        <div class="clearfix">
                                                <label for="lang">Lang: </label>
                                                <div class="input">
                                                        <input name="lang" id="lang" type="text" size="2" maxlength="2" required="required" value="<?php echo Api::getHttpLang(); ?>" />
                                                        <span class="help-inline">The code lang of the Wikisource like en or fr</span>
                                               </div>
                                        </div>
                                        <div class="clearfix">
                                                <label for="page">Title of the page: </label>
                                                <div class="input">
                                                        <input name="page" id="lang" type="text" size="30" required="required">
                                                        <span class="help-inline">Naime of the mainpage of the book in Wikisource</span>
                                                </div>
                                        </div>
                                        <div class="clearfix">
                                                <label for="format">File format: </label>
                                                <div class="input">
                                                        <select id="format" name="format" required="required">
                                                                <option>epub</option>
                                                                <option>xhtml</option>
                                                                <option>odt</option>
                                                        </select>
                                                        <span class="help-inline"></span>
                                                </div>
                                        </div>
                                        <div class="actions">
                                                <input class="btn primary" type="submit" value="Export" />
                                        </div>
                                </fieldset>
                        </form>
                </div>
                <footer>
                        <p>Tool under <a rel="licence" href="http://www.gnu.org/licenses/gpl.html">GNU GPLv2+ licence.</a></p>
                </footer>
        </div>
</body>
</html>
<?php exit(); ?>
