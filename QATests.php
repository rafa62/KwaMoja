<?php
/* $Id: QATests.php 1 2014-09-08 10:42:50Z agaluski $*/

include('includes/session.php');
$Title = _('QA Tests Maintenance');
$ViewTopic= 'QualityAssurance';// Filename in ManualContents.php's TOC.
$BookMark = 'QA_Tests';// Anchor's id in the manual's html document.
include('includes/header.php');

if (isset($_GET['SelectedQATest'])) {
	$SelectedQATest = mb_strtoupper($_GET['SelectedQATest']);
} elseif (isset($_POST['SelectedQATest'])) {
	$SelectedQATest = mb_strtoupper($_POST['SelectedQATest']);
}

if (isset($Errors)) {
	unset($Errors);
}

$Errors = array();

echo '<p class="page_title_text"><img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/maintenance.png" title="', _('Search'), '" alt="" />', $Title, '</p>';

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */
	$i = 1;

	//first off validate inputs sensible

	if (mb_strlen($_POST['QATestName']) > 50) {
		$InputError = 1;
		prnMsg(_('The QA Test name must be fifty characters or less long'), 'error');
		$Errors[$i] = 'QATestName';
		$i++;
	}

	if (mb_strlen($_POST['Type']) == '') {
		$InputError = 1;
		prnMsg(_('The Type must not be blank'), 'error');
		$Errors[$i] = 'Type';
		$i++;
	}
	$SQL = "SELECT COUNT(*) FROM qatests WHERE qatests.name='" . $_POST['QATestName'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0 and $_POST['submit'] != _('Update Information')) {
		$InputError = 1;
		prnMsg(_('The QA Test name already exists'), 'error');
		$Errors[$i] = 'QATestName';
		$i++;
	}

	if ($_POST['submit'] == _('Update Information') and $InputError != 1) {

		/*SelectedQATest could also exist if submit had not been clicked this code would not run in this case cos submit is false of course  see the delete code below*/

		$SQL = "UPDATE qatests SET name='" . $_POST['QATestName'] . "',
									method='" . $_POST['Method'] . "',
									groupby='" . $_POST['GroupBy'] . "',
									units='" . $_POST['Units'] . "',
									type='" . $_POST['Type'] . "',
									defaultvalue='" . $_POST['DefaultValue'] . "',
									numericvalue='" . $_POST['NumericValue'] . "',
									showoncert='" . $_POST['ShowOnCert'] . "',
									showonspec='" . $_POST['ShowOnSpec'] . "',
									showontestplan='" . $_POST['ShowOnTestPlan'] . "',
									active='" . $_POST['Active'] . "'
				WHERE qatests.testid = '" . $SelectedQATest . "'";

		$Msg = _('QA Test record for') . ' ' . $_POST['QATestName'] . ' ' . _('has been updated');
	} elseif ($InputError != 1) {

		/*Selected group is null cos no item selected on first time round so must be adding a record must be submitting new entries in the new QA Test form */

		$SQL = "INSERT INTO qatests (name,
						method,
						groupby,
						units,
						type,
						defaultvalue,
						numericvalue,
						showoncert,
						showonspec,
						showontestplan,
						active)
				VALUES ('" . $_POST['QATestName'] . "',
					'" . $_POST['Method'] . "',
					'" . $_POST['GroupBy'] . "',
					'" . $_POST['Units'] . "',
					'" . $_POST['Type'] . "',
					'" . $_POST['DefaultValue'] . "',
					'" . $_POST['NumericValue'] . "',
					'" . $_POST['ShowOnCert'] . "',
					'" . $_POST['ShowOnSpec'] . "',
					'" . $_POST['ShowOnTestPlan'] . "',
					'" . $_POST['Active'] . "'
					)";

		$Msg = _('A new QA Test record has been added for') . ' ' . $_POST['QATestName'];
	}
	if ($InputError != 1) {
		//run the SQL from either of the above possibilites
		$ErrMsg = _('The insert or update of the QA Test failed because');
		$DbgMsg = _('The SQL that was used and failed was');
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg);

		prnMsg($Msg, 'success');

		unset($SelectedQATest);
		unset($_POST['QATestName']);
		//unset($_POST['Method']);
		//unset($_POST['GroupBy']);
		//unset($_POST['Units']);
		//unset($_POST['Type']);
		unset($_POST['DefaultValue']);
		unset($_POST['NumericValue']);
		//unset($_POST['ShowOnCert']);
		//unset($_POST['ShowOnSpec']);
		//unset($_POST['ShowOnTestPlan']);
		//unset($_POST['Active']);
	}

} elseif (isset($_GET['delete'])) {
	//the link to delete a selected record was clicked instead of the submit button

	// PREVENT DELETES IF DEPENDENT RECORDS

	$SQL = "SELECT COUNT(*) FROM prodspec WHERE  prodspec.testid='" . $SelectedQATest . "'";
	//$Result = DB_query($SQL);
	//$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0) {
		prnMsg(_('Cannot delete this QA Test because Product Specs are using it'), 'error');
	} else {
		$SQL = "DELETE FROM qatests WHERE testid='" . $SelectedQATest . "'";
		$ErrMsg = _('The QA Test could not be deleted because');
		$Result = DB_query($SQL, $ErrMsg);

		prnMsg(_('QA Test') . ' ' . $SelectedQATest . ' ' . _('has been deleted from the database'), 'success');
		unset($SelectedQATest);
		unset($_GET['delete']);
	}
}

if (isset($SelectedQATest)) {
	echo '<div class="centre">
			<a href="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '">', _('Show All QA Tests'), '</a>
		</div>';
}

if (!isset($_GET['delete'])) {

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	if (isset($SelectedQATest)) {
		//editing an existing Sales-person

		$SQL = "SELECT testid,
						name,
						method,
						groupby,
						units,
						type,
						defaultvalue,
						numericvalue,
						showoncert,
						showonspec,
						showontestplan,
						active
					FROM qatests
					WHERE testid='" . $SelectedQATest . "'";

		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);

		$_POST['SelectedQATest'] = $MyRow['testid'];
		$_POST['QATestName'] = $MyRow['name'];
		$_POST['Method'] = $MyRow['method'];
		$_POST['GroupBy'] = $MyRow['groupby'];
		$_POST['Type'] = $MyRow['type'];
		$_POST['Units'] = $MyRow['units'];
		$_POST['DefaultValue'] = $MyRow['defaultvalue'];
		$_POST['NumericValue'] = $MyRow['numericvalue'];
		$_POST['ShowOnCert'] = $MyRow['showoncert'];
		$_POST['ShowOnSpec'] = $MyRow['showonspec'];
		$_POST['ShowOnTestPlan'] = $MyRow['showontestplan'];
		$_POST['Active'] = $MyRow['active'];


		echo '<input type="hidden" name="SelectedQATest" value="' . $SelectedQATest . '" />';
		echo '<input type="hidden" name="TestID" value="' . $_POST['SelectedQATest'] . '" />';
		echo '<table class="selection">
				<tr>
					<td>' . _('QA Test ID') . ':</td>
					<td>' . $_POST['SelectedQATest'] . '</td>
				</tr>';

	} else { //end of if $SelectedQATest only do the else when a new record is being entered
		$_POST['QATestName'] = '';
		$_POST['Method'] = '';
		$_POST['GroupBy'] = '';
		$_POST['Units'] = '';
		$_POST['Type'] = 4;
		$_POST['Active'] = 1;
		$_POST['NumericValue'] = 1;
		$_POST['DefaultValue'] = '';
		$_POST['ShowOnCert'] = 1;
		$_POST['ShowOnSpec'] = 1;
		$_POST['ShowOnTestPlan'] = 1;

		echo '<table class="selection">';

	}

	echo '<tr>
			<td>' . _('QA Test Name') . ':</td>
			<td><input type="text" ' . (in_array('QATestName', $Errors) ? 'class="inputerror"' : '') . ' name="QATestName"  size="30" maxlength="50" value="' . $_POST['QATestName'] . '" /></td>
		</tr>';
	echo '<tr>
			<td>' . _('Method') . ':</td>
			<td><input type="text" name="Method" size="20" maxlength="20" value="' . $_POST['Method'] . '" /></td>
		</tr>';
	echo '<tr>
			<td>' . _('Group By') . ':</td>
			<td><input type="text" name="GroupBy" size="20" maxlength="20" value="' . $_POST['GroupBy'] . '" /></td>
		</tr>';
	echo '<tr>
			<td>' . _('Units') . ':</td>
			<td><input type="text" name="Units" size="20" maxlength="20" value="' . $_POST['Units'] . '" /></td>
		</tr>';
	echo '<tr>
			<td>' . _('Type') . ':</td>
			<td><select name="Type">';
	if ($_POST['Type'] == 0) {
		echo '<option selected="selected" value="0">' . _('Text Box') . '</option>';
	} else {
		echo '<option value="0">' . _('Text Box') . '</option>';
	}
	if ($_POST['Type'] == 1) {
		echo '<option selected="selected" value="1">' . _('Select Box') . '</option>';
	} else {
		echo '<option value="1">' . _('Select Box') . '</option>';
	}
	if ($_POST['Type'] == 2) {
		echo '<option selected="selected" value="2">' . _('Check Box') . '</option>';
	} else {
		echo '<option value="2">' . _('Check Box') . '</option>';
	}
	if ($_POST['Type'] == 3) {
		echo '<option selected="selected" value="3">' . _('Date Box') . '</option>';
	} else {
		echo '<option value="3">' . _('Date Box') . '</option>';
	}
	if ($_POST['Type'] == 4) {
		echo '<option selected="selected" value="4">' . _('Range') . '</option>';
	} else {
		echo '<option value="4">' . _('Range') . '</option>';
	}
	echo '</select>
			</td>
		</tr>';
	echo '<tr>
			<td>' . _('Possible Values') . ':</td>
			<td><input type="text" name="DefaultValue" size="50" maxlength="150" value="' . $_POST['DefaultValue'] . '" /></td>
		</tr>';

	echo '<tr>
			<td>' . _('Numeric Value?') . ':</td>
			<td><select name="NumericValue">';
	if ($_POST['NumericValue'] == 1) {
		echo '<option selected="selected" value="1">' . _('Yes') . '</option>';
	} else {
		echo '<option value="1">' . _('Yes') . '</option>';
	}
	if ($_POST['NumericValue'] == 0) {
		echo '<option selected="selected" value="0">' . _('No') . '</option>';
	} else {
		echo '<option value="0">' . _('No') . '</option>';
	}
	echo '</select></td></tr><tr>
			<td>' . _('Show On Cert?') . ':</td>
			<td><select name="ShowOnCert">';
	if ($_POST['ShowOnCert'] == 1) {
		echo '<option selected="selected" value="1">' . _('Yes') . '</option>';
	} else {
		echo '<option value="1">' . _('Yes') . '</option>';
	}
	if ($_POST['ShowOnCert'] == 0) {
		echo '<option selected="selected" value="0">' . _('No') . '</option>';
	} else {
		echo '<option value="0">' . _('No') . '</option>';
	}
	echo '</select>
				</td>
			</tr>';

	echo '<tr>
			<td>' . _('Show On Spec?') . ':</td>
			<td><select name="ShowOnSpec">';
	if ($_POST['ShowOnSpec'] == 1) {
		echo '<option selected="selected" value="1">' . _('Yes') . '</option>';
	} else {
		echo '<option value="1">' . _('Yes') . '</option>';
	}
	if ($_POST['ShowOnSpec'] == 0) {
		echo '<option selected="selected" value="0">' . _('No') . '</option>';
	} else {
		echo '<option value="0">' . _('No') . '</option>';
	}
	echo '</select>
				</td>
			</tr>';

	echo '<tr>
			<td>' . _('Show On Test Plan?') . ':</td>
			<td><select name="ShowOnTestPlan">';
	if ($_POST['ShowOnTestPlan'] == 1) {
		echo '<option selected="selected" value="1">' . _('Yes') . '</option>';
	} else {
		echo '<option value="1">' . _('Yes') . '</option>';
	}
	if ($_POST['ShowOnTestPlan'] == 0) {
		echo '<option selected="selected" value="0">' . _('No') . '</option>';
	} else {
		echo '<option value="0">' . _('No') . '</option>';
	}
	echo '</select>
				</td>
			</tr>';

	echo '<tr>
			<td>' . _('Active?') . ':</td>
			<td><select name="Active">';
	if ($_POST['Active'] == 1) {
		echo '<option selected="selected" value="1">' . _('Yes') . '</option>';
	} else {
		echo '<option value="1">' . _('Yes') . '</option>';
	}
	if ($_POST['Active'] == 0) {
		echo '<option selected="selected" value="0">' . _('No') . '</option>';
	} else {
		echo '<option value="0">' . _('No') . '</option>';
	}
	echo '</select>
				</td>
			</tr>
		</table>';

	if (isset($_GET['SelectedQATest'])) {
		echo '<div class="centre">
				<input type="submit" name="submit" value="' . _('Update Information') . '" />
			</div>';
	} else {
		echo '<div class="centre">
				<input type="submit" name="submit" value="' . _('Enter Information') . '" />
			</div>';
	}
	echo '</form>';

} //end if record deleted no point displaying form to add record

if (!isset($SelectedQATest)) {

	/* It could still be the second time the page has been run and a record has been selected for modification - SelectedQATest will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters
	then none of the above are true and the list of QA Test will be displayed with
	links to delete or edit each. These will call the same page again and allow update/input
	or deletion of the records*/

	$SQL = "SELECT testid,
				name,
				method,
				groupby,
				units,
				type,
				defaultvalue,
				numericvalue,
				showoncert,
				showonspec,
				showontestplan,
				active
			FROM qatests
			ORDER BY name";
	$Result = DB_query($SQL);

	echo '<table class="selection">
			<thead>
				<tr>
					<th class="SortedColumn">', _('Test ID'), '</th>
					<th class="SortedColumn">', _('Name'), '</th>
					<th class="SortedColumn">', _('Method'), '</th>
					<th class="SortedColumn">', _('Group By'), '</th>
					<th class="SortedColumn">', _('Units'), '</th>
					<th class="SortedColumn">', _('Type'), '</th>
					<th>', _('Possible Values'), '</th>
					<th class="SortedColumn">', _('Numeric Value'), '</th>
					<th class="SortedColumn">', _('Show on Cert'), '</th>
					<th class="SortedColumn">', _('Show on Spec'), '</th>
					<th class="SortedColumn">', _('Show on Test Plan'), '</th>
					<th class="SortedColumn">', _('Active'), '</th>
				</tr>
			</thead>';
	$k = 0;
	echo '<tbody>';
	while ($MyRow = DB_fetch_array($Result)) {

		if ($k == 1) {
			echo '<tr class="EvenTableRows">';
			$k = 0;
		} else {
			echo '<tr class="OddTableRows">';
			$k++;
		}
		if ($MyRow['active'] == 1) {
			$ActiveText = _('Yes');
		} else {
			$ActiveText = _('No');
		}
		if ($MyRow['numericvalue'] == 1) {
			$IsNumeric = _('Yes');
		} else {
			$IsNumeric = _('No');
		}
		if ($MyRow['showoncert'] == 1) {
			$ShowOnCertText = _('Yes');
		} else {
			$ShowOnCertText = _('No');
		}
		if ($MyRow['showonspec'] == 1) {
			$ShowOnSpecText = _('Yes');
		} else {
			$ShowOnSpecText = _('No');
		}
		if ($MyRow['showontestplan'] == 1) {
			$ShowOnTestPlanText = _('Yes');
		} else {
			$ShowOnTestPlanText = _('No');
		}
		switch ($MyRow['type']) {
			case 0; //textbox
				$TypeDisp = 'Text Box';
				break;
			case 1; //select box
				$TypeDisp = 'Select Box';
				break;
			case 2; //checkbox
				$TypeDisp = 'Check Box';
				break;
			case 3; //datebox
				$TypeDisp = 'Date Box';
				break;
			case 4; //range
				$TypeDisp = 'Range';
				break;
		} //end switch
		echo '<td class="number">', $MyRow['testid'], '</td>
			<td>', $MyRow['name'], '</td>
			<td>', $MyRow['method'], '</td>
			<td>', $MyRow['groupby'], '</td>
			<td>', $MyRow['units'], '</td>
			<td>', $TypeDisp, '</td>
			<td>', $MyRow['defaultvalue'], '</td>
			<td>', $IsNumeric, '</td>
			<td>', $ShowOnCertText, '</td>
			<td>', $ShowOnSpecText, '</td>
			<td>', $ShowOnTestPlanText, '</td>
			<td>', $ActiveText, '</td>
			<td><a href="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '?SelectedQATest=', urlencode($MyRow['testid']), '">' . _('Edit') . '</a></td>
			<td><a href="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '?SelectedQATest=', urlencode($MyRow['testid']), '&amp;delete=1" onclick="return confirm(\'' . _('Are you sure you wish to delete this QA Test ?') . '\');">' . _('Delete') . '</a></td>
		</tr>';

	} //END WHILE LIST LOOP
	echo '</tbody>';
	echo '</table>';
} //end of ifs and buts!

include('includes/footer.php');
?>