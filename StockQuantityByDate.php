<?php
include ('includes/session.php');
$Title = _('Stock On Hand By Date');
include ('includes/header.php');

echo '<p class="page_title_text" >
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/inventory.png" title="', _('Inventory'), '" alt="" /><b>', $Title, '</b>
	</p>';

echo '<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

$SQL = "SELECT categoryid, categorydescription FROM stockcategory";
$ResultStkLocs = DB_query($SQL);

echo '<fieldset>
		<legend>', _('Report Criteria'), '</legend>
		<field>
			<label for="StockCategory">', _('For Stock Category'), ':</label>
			<select required="required" name="StockCategory" autofocus="autofocus">
				<option value="All">', _('All'), '</option>';

while ($MyRow = DB_fetch_array($ResultStkLocs)) {
	if (isset($_POST['StockCategory']) and $_POST['StockCategory'] != 'All') {
		if ($MyRow['categoryid'] == $_POST['StockCategory']) {
			echo '<option selected="selected" value="', $MyRow['categoryid'], '">', $MyRow['categorydescription'], '</option>';
		} else {
			echo '<option value="', $MyRow['categoryid'], '">', $MyRow['categorydescription'], '</option>';
		}
	} else {
		echo '<option value="', $MyRow['categoryid'], '">', $MyRow['categorydescription'], '</option>';
	}
}
echo '</select>
	<fieldhelp>', _('Select the stock category to filter by, or select All, to view over all categories'), '</fieldhelp>
</field>';

$SQL = "SELECT locationname,
				locations.loccode
			FROM locations
			INNER JOIN locationusers
				ON locationusers.loccode=locations.loccode
				AND locationusers.userid='" . $_SESSION['UserID'] . "'
				AND locationusers.canview=1";

$ResultStkLocs = DB_query($SQL);

echo '<field>
		<label for="StockLocation">', _('For Stock Location'), ':</label>
		<select required="required" name="StockLocation"> ';

while ($MyRow = DB_fetch_array($ResultStkLocs)) {
	if (isset($_POST['StockLocation']) and $_POST['StockLocation'] != 'All') {
		if ($MyRow['loccode'] == $_POST['StockLocation']) {
			echo '<option selected="selected" value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
		} else {
			echo '<option value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
		}
	} elseif ($MyRow['loccode'] == $_SESSION['UserStockLocation']) {
		echo '<option selected="selected" value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
		$_POST['StockLocation'] = $MyRow['loccode'];
	} else {
		echo '<option value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
	}
}
echo '</select>
	<fieldhelp>', _('Select the location to report on'), '</fieldhelp>
</field>';

if (!isset($_POST['OnHandDate'])) {
	$_POST['OnHandDate'] = Date($_SESSION['DefaultDateFormat'], Mktime(0, 0, 0, Date('m'), 0, Date('y')));
}

echo '<field>
		<label for="OnHandDate">', _('On-Hand On Date'), ':</label>
		<input type="text" class="date" name="OnHandDate" size="12" required="required" maxlength="10" value="', $_POST['OnHandDate'], '" />
		<fieldhelp>', _('The date as of which you wish to report the stock balances on'), '</fieldhelp>
	</field>';

if (isset($_POST['ShowZeroStocks'])) {
	$Checked = 'checked="checked"';
} else {
	$Checked = '';
}

echo '<field>
		<label for="ShowZeroStocks">', ('Include zero stocks'), '</label>
		<input type="checkbox" name="ShowZeroStocks" value="" ', $Checked, '  />
		<fieldhelp>', _('Tick this box to include items with a zero balance at the report date'), '</fieldhelp>
	</field>';

echo '</fieldset>';

echo '<div class="centre">
		<input type="submit" name="ShowStatus" value="' . _('Show Stock Status') . '" />
	</div>';

echo '</form>';

$TotalQuantity = 0;

if (isset($_POST['ShowStatus']) and is_date($_POST['OnHandDate'])) {
	if ($_POST['StockCategory'] == 'All') {
		$SQL = "SELECT stockid,
						 description,
						 decimalplaces
					 FROM stockmaster
					 WHERE (mbflag='M' OR mbflag='B')";
	} else {
		$SQL = "SELECT stockid,
						description,
						decimalplaces
					 FROM stockmaster
					 WHERE categoryid = '" . $_POST['StockCategory'] . "'
					 AND (mbflag='M' OR mbflag='B')";
	}

	$ErrMsg = _('The stock items in the category selected cannot be retrieved because');
	$DbgMsg = _('The SQL that failed was');

	$StockResult = DB_query($SQL, $ErrMsg, $DbgMsg);

	$SQLOnHandDate = FormatDateForSQL($_POST['OnHandDate']);

	echo '<table>
			<tr>
				<th>' . _('Item Code') . '</th>
				<th>' . _('Description') . '</th>
				<th>' . _('Quantity On Hand') . '</th>
				<th>' . _('Cost per Unit') . '</th>
				<th>' . _('Total Value') . '</th>
			</tr>';

	while ($MyRow = DB_fetch_array($StockResult)) {

		if (isset($_POST['ShowZeroStocks'])) {
			$SQL = "SELECT stockid,
							newqoh
						FROM stockmoves
						WHERE stockmoves.trandate <= '" . $SQLOnHandDate . "'
							AND stockid = '" . $MyRow['stockid'] . "'
							AND loccode = '" . $_POST['StockLocation'] . "'
						ORDER BY stkmoveno DESC LIMIT 1";
		} else {
			$SQL = "SELECT stockid,
							newqoh
						FROM stockmoves
						WHERE stockmoves.trandate <= '" . $SQLOnHandDate . "'
							AND stockid = '" . $MyRow['stockid'] . "'
							AND loccode = '" . $_POST['StockLocation'] . "'
							AND newqoh > 0
						ORDER BY stkmoveno DESC LIMIT 1";
		}

		$ErrMsg = _('The stock held as at') . ' ' . $_POST['OnHandDate'] . ' ' . _('could not be retrieved because');

		$LocStockResult = DB_query($SQL, $ErrMsg);

		$NumRows = DB_num_rows($LocStockResult);

		while ($LocQtyRow = DB_fetch_array($LocStockResult)) {

			$CostSQL = "SELECT stockcosts.materialcost+stockcosts.labourcost+stockcosts.overheadcost AS cost
							FROM stockcosts
							WHERE stockcosts.costfrom<='" . $SQLOnHandDate . "'
								AND stockid = '" . $MyRow['stockid'] . "'
							ORDER BY costfrom DESC
							LIMIT 1";
			$CostResult = DB_query($CostSQL);
			if (DB_num_rows($CostResult) == 0) {
				$CostSQL = "SELECT stockcosts.materialcost+stockcosts.labourcost+stockcosts.overheadcost AS cost
								FROM stockcosts
								WHERE stockid = '" . $MyRow['stockid'] . "'
								ORDER BY costfrom DESC
								LIMIT 1";
				$CostResult = DB_query($CostSQL);
				$CostRow = DB_fetch_array($CostResult);
			} else {
				$CostRow = DB_fetch_array($CostResult);
			}
			if ($NumRows == 0) {
				printf('<tr class="striped_row">
						<td><a target="_blank" href="' . $RootPath . '/StockStatus.php?%s">%s</a></td>
						<td>%s</td>
						<td class="number">%s</td>
						<td class="number">%s</td>
						<td class="number">%s</td></tr>', 'StockID=' . mb_strtoupper($MyRow['stockid']), mb_strtoupper($MyRow['stockid']), $MyRow['description'], 0, locale_number_format($CostRow['cost'], $_SESSION['CompanyRecord']['decimalplaces']), 0);
			} else {
				printf('<tr class="striped_row">
						<td><a target="_blank" href="' . $RootPath . '/StockStatus.php?%s">%s</a></td>
						<td>%s</td>
						<td class="number">%s</td>
						<td class="number">%s</td>
						<td class="number">%s</td></tr>', 'StockID=' . mb_strtoupper($MyRow['stockid']), mb_strtoupper($MyRow['stockid']), $MyRow['description'], locale_number_format($LocQtyRow['newqoh'], $MyRow['decimalplaces']), locale_number_format($CostRow['cost'], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(($CostRow['cost'] * $LocQtyRow['newqoh']), $_SESSION['CompanyRecord']['decimalplaces']));

				$TotalQuantity+= $LocQtyRow['newqoh'];
			}
			//end of page full new headings if
			
		}

	} //end of while loop
	echo '<tr>
			<td>' . _('Total Quantity') . ': ' . $TotalQuantity . '</td>
		</tr>
		</table>';
}

include ('includes/footer.php');
?>