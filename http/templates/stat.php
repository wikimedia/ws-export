<?php include 'header.php'; ?>
<table class="table table-striped">
    <caption>Stats for <?php echo $month,'/',$year ?></caption>
    <thead>
        <tr><th scope="col">Lang</th><th scope="col">epub</th><th scope="col">xhtml</th><th scope="col">odt</th></tr>
    </thead>
    <tbody>
    <?php foreach($val as $lang => $temp) {
        echo '<tr><th scope="row">' . $lang . '</th><td>' . ($temp['epub-2'] + $temp['epub-3']) . '</td><td>' . $temp['xhtml'] . '</td><td>' . $temp['odt'] . '</td></tr>' . "\n";
    } ?>
    </tbody>
    <tfoot>
    <?php echo '<tr><th scope="row">Total</th><td>' . ($total['epub-2'] + $total['epub-3']) . '</td><td>' . $total['xhtml'] . '</td><td>' . $total['odt'] . '</td></tr>'; ?>
    </tfoot>
</table>
<form class="form-inline">
    <label>Change month:</label>
    <input name="month" id="month" type="number" placeholder="month" size="2" maxlength="2" min="1" max="12" required="required" value="<?php echo $month ?>" class="input-small" />
    <input name="year" id="year" type="number" placeholder="year" size="4" maxlength="4" min="2012" max="<?php echo $date['year'] ?>" required="required" value="<?php echo $year ?>" class="input-small" />
    <button class="btn" type="submit">Go</button>
</form>
<?php include 'footer.php'; 
exit(); ?>
