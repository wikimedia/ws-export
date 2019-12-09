<?php
use App\CreationLog;

include 'header.php';
?>
<ol class="breadcrumb">
	<li><a href="book.php">Home</a></li>
	<li class="active">Statistics</li>
</ol>
<div class="row">
	<aside class="col-md-3">
		<h2>Recently Popular</h2>
		<ol>
			<?php try {
				foreach ( CreationLog::singleton()->getRecentPopular() as $book ) { ?>
					<li value="<?php echo $book['total'] ?>">
						<a href="book.php?lang=<?php echo $book['lang'] ?>&page=<?php echo urlencode( $book['title'] ) ?>" title="Download epub">
							<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/d/d5/EPUB_silk_icon.svg/20px-EPUB_silk_icon.svg.png" alt="The epub logo."/>
						</a>
						<a href="https://<?php echo $book['lang'] ?>.wikisource.org/wiki/<?php echo urlencode( $book['title'] ) ?>" title="View on Wikisource">
							<?php echo str_replace( '_', ' ', $book['title'] ) ?>
						</a>
					</li>
				<?php }
			} catch ( Exception $e ) {
				echo '<div class="alert alert-danger">Internal error: ' . htmlspecialchars( $e->getMessage() ) . '</div>' . "\n";
			} ?>
		</ol>
	</aside>
	<div class="col-md-9">
		<table class="table table-striped">
			<caption>Stats for <?php echo $month, '/', $year ?></caption>
			<thead>
			<tr>
				<th scope="col">Lang</th>
				<?php
				foreach ( $total as $format => $value ) {
					echo '<th scope="col">' . $format . '</th>';
				}
				?>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $val as $lang => $temp ) {
				echo '<tr><th scope="row">' . $lang . '</th>';
				foreach ( $total as $format => $value ) {
					echo '<td>' . ( array_key_exists( $format, $temp ) ? $temp[$format] : 0 ) . '</td>';
				}
				echo "</tr>\n";
			} ?>
			</tbody>
			<tfoot>
			<tr>
				<th scope="row">Total</th>
				<?php
				foreach ( $total as $format => $value ) {
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
	</div>
</div>
<?php include 'footer.html';
