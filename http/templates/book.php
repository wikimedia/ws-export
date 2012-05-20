<?php include 'header.php'; ?>
<form method="get" action="book.php" class="form-horizontal">
    <fieldset>
        <legend>Export a file</legend>
        <div class="control-group">
            <label for="lang" class="control-label">Lang: </label>
            <div class="controls">
                <input name="lang" id="lang" type="text" size="3" maxlength="20" required="required" value="<?php echo Api::getHttpLang(); ?>" class="input-mini" />
                <span class="help-inline">Language code of Wikisource domain, like en or fr</span>
            </div>
        </div>
        <div class="control-group">
            <label for="page" class="control-label">Title of the page: </label>
            <div class="controls">
                <input name="page" id="page" type="text" size="30" required="required" />
                <span class="help-inline">Name of the mainpage of the book in Wikisource</span>
            </div>
        </div>
        <div class="control-group">
            <label for="format" class="control-label">File format: </label>
            <div class="controls">
                <select id="format" name="format">
                    <option>epub</option>
                    <option>xhtml</option>
                    <option>odt</option>
                </select>
                <span class="help-inline"></span>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">Options: </label>
            <div class="controls">
                <label class="checkbox">
                    <input type="checkbox" value="false" <?php if(!$options['fonts']) echo 'checked="checked"'; ?> name="fonts" />
                    Do not include fonts
                </label>
                <label class="checkbox">
                    <input type="checkbox" value="false" <?php if(!$options['images']) echo 'checked="checked"'; ?> name="images" />
                    Do not include images
                </label>
            </div>
        </div>
        <div class="form-actions">
            <input class="btn btn-primary" type="submit" value="Export" />
        </div>
    </fieldset>
</form>
<?php include 'footer.php';
exit(); ?>
