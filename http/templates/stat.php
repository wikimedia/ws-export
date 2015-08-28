<?php include 'header.php'; ?>
<table class="table table-striped">
	<caption>Stats for <?php echo $month, '/', $year ?></caption>
	<thead>
	<tr>
		<th scope="col">Lang</th>
		<?php
		foreach($total as $format => $value) {
			echo '<th scope="col">' . $format . '</th>';
		}
		?>
	</tr>
	</thead>
	<tbody>
	<?php
	foreach( $val as $lang => $temp ) {
		echo '<tr><th scope="row">' . $lang . '</th>';
		foreach($total as $format => $value) {
			echo '<td>' . (array_key_exists($format, $temp) ? $temp[$format] : 0) . '</td>';
		}
		echo "</tr>\n";
	} ?>
	</tbody>
	<tfoot>
	<tr>
		<th scope="row">Total</th>
		<?php
		foreach($total as $format => $value) {
			echo '<td>' . $value . '</td>';
		}
		?>
	</tr>
	</tfoot>
</table>
<form class="form-inline" role="form">
	<label>Change month:</label>

	<div class="form-group">
		<input name="month" id="month" type="number" placeholder="month" size="2" maxlength="2" min="1" max="12"
		       required="required" value="<?php echo $month ?>" class="form-control"/>
	</div>
	<div class="form-group">
		<input name="year" id="year" type="number" placeholder="year" size="4" maxlength="4" min="2012"
		       max="<?php echo $date['year'] ?>" required="required" value="<?php echo $year ?>" class="form-control"/>
	</div>
	<button class="btn" type="btn btn-default">Go</button>
</form>
<?php include 'footer.php';
exit(); ?>
