<?php include 'header.php';
$formats = [
	'epub-3' => 'epub 3',
	'epub-2' => 'epub 2 (deprecated, may be useful for some very old e-readers)',
	'mobi' => 'mobi (in beta)',
	'txt' => 'txt (in beta)',
	'rtf' => 'rtf (in beta)',
	'pdf-a4' => 'pdf A4 format (in beta)',
	'pdf-a5' => 'pdf A5 format (in beta)',
	'pdf-letter' => 'pdf US letter format (in beta)',
];
?>
<form method="get" action="book.php" role="form" class="form-horizontal">
	<fieldset>
		<legend>Export a file</legend>
		<div class="form-group">
			<label for="lang" class="col-lg-2 control-label">Language code</label>

			<div class="col-lg-10">
				<input name="lang" id="lang" type="text" size="3" maxlength="20" required="required"
				       value="<?php echo Api::getHttpLang(); ?>" class="form-control input-mini"/>
				<span class="help-block">Language code of Wikisource domain, like en or fr</span>
			</div>
		</div>
		<div class="form-group">
			<label for="page" class="col-lg-2 control-label">Title of the page</label>

			<div class="col-lg-10">
				<input name="page" id="page" type="text" size="30" required="required" class="form-control"
					   value="<?php echo $title; ?>"/>
				<span class="help-block">Name of the mainpage of the book in Wikisource</span>
			</div>
		</div>
		<div class="form-group">
			<label for="format" class="col-lg-2 control-label">File format</label>

			<div class="col-lg-10">
				<select id="format" name="format" class="form-control">
					<?php foreach ( $formats as $key => $label ) {
						echo '<option value="' . $key . '"';
						if ( $format === $key ) {
							echo ' selected="selected"';
						}
						echo '>' . $label . '</option>';
} ?>
				</select>
				<span class="help-inline"></span>
			</div>
		</div>
		<div class="form-group">
			<label for="fonts" class="col-lg-2 control-label">Include fonts</label>

			<div class="col-lg-10">
				<select id="fonts" name="fonts" class="form-control">
					<option value="">None</option><?php
					$list = FontProvider::getList();
					foreach ( $list as $key => $label ) {
						echo '<option value="' . $key . '"';
						if ( $options['fonts'] == $key ) {
							echo ' selected="selected"';
						}
						echo '>' . $label . '</option>' . "\n";
					}
					?></select>
			</div>
		</div>
		<div class="form-group">
			<label class="col-lg-2 control-label">Options</label>

			<div class="col-lg-10">
				<label class="checkbox-inline">
					<input type="checkbox" value="false" <?php if ( !$options['images'] ) {
						echo 'checked="checked"';
} ?> name="images"/>
					Do not include images
				</label>
			</div>
		</div>
		<div class="form-group">
			<div class="col-lg-offset-2 col-lg-10">
				<input class="btn btn-primary" type="submit" value="Export"/>
			</div>
		</div>
	</fieldset>
</form>
<?php include 'footer.html';
exit();
