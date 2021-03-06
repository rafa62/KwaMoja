<?php
include ('includes/session.php');

$Title = _('Maintain ABC ranking methods');

include ('includes/header.php');

echo '<p class="page_title_text" >
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', $Title, '" alt="', $Title, '" />', ' ', $Title, '
	</p>';

if (isset($_GET['Delete'])) {
	$CheckSQL = "SELECT methodid FROM abcgroups WHERE methodid='" . $_GET['SelectedMethodID'] . "'";
	$CheckResult = DB_query($CheckSQL);
	if (DB_num_rows($CheckResult) == 0) {
		$SQL = "DELETE FROM abcmethods WHERE methodid='" . $_GET['SelectedMethodID'] . "'";
		$Result = DB_query($SQL);
		prnMsg(_('ABC Ranking method number') . ' ' . $_GET['SelectedMethodID'] . ' ' . _('has been deleted'), 'success');
		echo '<div class="centre">
				<a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">', _('View all the ranking methods'), '</a>
			</div>';
		include ('includes/footer.php');
		exit;
	} else {
		prnMsg(_('ABC Ranking method number') . ' ' . $_GET['SelectedMethodID'] . ' ' . _('cannot be deleted as it is used ABC groups'), 'error');
	}
}

if (isset($_POST['Submit'])) {
	if ($_POST['Mode'] == 'New') {
		$SQL = "INSERT INTO abcmethods (methodid,
										methodname
									) VALUES (
										'" . $_POST['MethodID'] . "',
										'" . $_POST['MethodName'] . "'
									)";
		$InsertResult = DB_query($SQL);
	} else {
		$SQL = "UPDATE abcmethods SET methodname='" . $_POST['MethodName'] . "'
							WHERE methodid='" . $_POST['MethodID'] . "'";
		$UpdateResult = DB_query($SQL);
	}
	prnMsg(_('The ranking method has been successfully saved to the database'), 'success');
	echo '<div class="centre">
			<a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">', _('View all the ranking methods'), '</a>
		</div>';
	include ('includes/footer.php');
	exit;
} else {
	$SQL = "SELECT methodid,
					methodname
				FROM abcmethods";
	$Result = DB_query($SQL);
	echo '<table summary="', _('List of ABC Ranking Methods'), '">
			<tr>
				<th colspan="10">
					<h3>', _('List of ABC Ranking Methods'), '
						<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/printer.png" class="PrintIcon" title="', _('Print'), '" alt="', _('Print'), '" onclick="window.print();" />
					</h3>
				</th>
			</tr>
			<tr>
				<th>', _('ID'), '</th>
				<th>', _('Method name'), '</th>
				<th colspan="2"></th>
			</tr>';

	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr class="striped_row">
				<td>', $MyRow['methodid'], '</td>
				<td>', $MyRow['methodname'], '</td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedMethodID=', $MyRow['methodid'], '">', _('Edit'), '</a></td>
				<td><a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedMethodID=', $MyRow['methodid'], '&amp;Delete=1" onclick="return MakeConfirm(\'' . _('Are you sure you wish to delete this ranking method?') . '\', \'Confirm Delete\', this);">', _('Delete'), '</a></td>
			</tr>';
	}
	echo '</table>';

	echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post" id="ABCMethods">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	if (isset($_GET['SelectedMethodID'])) {
		$SQL = "SELECT methodid,
						methodname
					FROM abcmethods
					WHERE methodid='" . $_GET['SelectedMethodID'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		echo '<input type="hidden" name="Mode" value="Edit" />';
		$IDInput = '<input type="hidden" name="MethodID" value="' . $MyRow['methodid'] . '" />' . $MyRow['methodid'];
		$Description = $MyRow['methodname'];
	} else {
		echo '<input type="hidden" name="Mode" value="New" />';
		$IDInput = '<input type="text" required="required" autofocus="autofocus" size="3" class="number" name="MethodID" />';
		$Description = '';
	}

	echo '<fieldset>
			<legend>', _('Ranking Method Details'), '</legend>
			<field>
				<label for="MethodID">', _('Method ID'), '</label>
				', $IDInput, '
				<fieldhelp>', _('The ID by which this ranking method will be known.'), '</fieldhelp>
			</field>
			<field>
				<label for="MethodName">', _('Method Description'), '</label>
				<input type="text" size="30" required="required" autofocus="autofocus" maxlength="40" name="MethodName" value="', $Description, '" />
				<fieldhelp>', _('The name by which this ranking method will be known.'), '</fieldhelp>
			</field>
		</fieldset>';

	echo '<div class="centre">
			<input type="submit" name="Submit" value="Save" />
		</div>';
	echo '</form>';
}

include ('includes/footer.php');

?>