<?php
include ('includes/session.php');

$Title = _('Item Prices');

include ('includes/header.php');

$ViewTopic = 'Prices';
/*$BookMark = '';// Anchor's id in the manual's html document.*/

include ('includes/SQL_CommonFunctions.php');

echo '<div class="toplink">
		<a href="' . $RootPath . '/SelectProduct.php">' . _('Back to Items') . '</a>
	</div>';

echo '<p class="page_title_text"><img alt="" src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/money_add.png" title="' . _('Search') . '" />' . ' ' . $Title . '</p>';

//initialise no input errors assumed initially before we test
$InputError = 0;

if (isset($_GET['Item'])) {
	$Item = trim(mb_strtoupper($_GET['Item']));
} elseif (isset($_POST['Item'])) {
	$Item = trim(mb_strtoupper($_POST['Item']));
}

if (!isset($_POST['TypeAbbrev']) or $_POST['TypeAbbrev'] == '') {
	$_POST['TypeAbbrev'] = $_SESSION['DefaultPriceList'];
}

if (!isset($_POST['CurrAbrev'])) {
	$_POST['CurrAbrev'] = $_SESSION['CompanyRecord']['currencydefault'];
}

$Result = DB_query("SELECT stockmaster.description,
							stockmaster.mbflag
					FROM stockmaster
					WHERE stockmaster.stockid='" . $Item . "'");
$MyRow = DB_fetch_row($Result);

if (DB_num_rows($Result) == 0) {
	prnMsg(_('The part code entered does not exist in the database') . '. ' . _('Only valid parts can have prices entered against them'), 'error');
	$InputError = 1;
}

if (!isset($Item)) {
	echo '<p>';
	prnMsg(_('An item must first be selected before this page is called') . '. ' . _('The product selection page should call this page with a valid product code'), 'error');
	include ('includes/footer.php');
	exit;
}

$PartDescription = $MyRow[0];

if ($MyRow[1] == 'K') {
	prnMsg(_('The part selected is a kit set item') . ', ' . _('these items explode into their components when selected on an order') . ', ' . _('prices must be set up for the components and no price can be set for the whole kit'), 'error');
	exit;
}

if (isset($_POST['submit'])) {

	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	// This gives some date in 1999?? $ZeroDate = Date($_SESSION['DefaultDateFormat'],Mktime(0,0,0,0,0,0));
	if (!is_numeric(filter_number_format($_POST['Price'])) or $_POST['Price'] == '') {
		$InputError = 1;
		prnMsg(_('The price entered must be numeric'), 'error');
	}
	if (!is_date($_POST['StartDate'])) {
		$InputError = 1;
		prnMsg(_('The date this price is to take effect from must be entered in the format') . ' ' . $_SESSION['DefaultDateFormat'], 'error');
	}
	if ($_POST['EndDate'] != '') {
		if (FormatDateForSQL($_POST['EndDate']) != '9999-12-31') {
			if (!is_date($_POST['EndDate']) and $_POST['EndDate'] != '') {
				$InputError = 1;
				prnMsg(_('The date this price is be in effect to must be entered in the format') . ' ' . $_SESSION['DefaultDateFormat'], 'error');
			}
			if (Date1GreaterThanDate2($_POST['StartDate'], $_POST['EndDate']) and $_POST['EndDate'] != '' and FormatDateForSQL($_POST['EndDate']) != '9999-12-31') {
				$InputError = 1;
				prnMsg(_('The end date is expected to be after the start date, enter an end date after the start date for this price'), 'error');
			}
			if (Date1GreaterThanDate2(Date($_SESSION['DefaultDateFormat']), $_POST['EndDate']) and $_POST['EndDate'] != '' and FormatDateForSQL($_POST['EndDate']) != '9999-12-31') {
				$InputError = 1;
				prnMsg(_('The end date is expected to be after today. There is no point entering a new price where the effective date is before today!'), 'error');
			}
		}
	}
	if (is_date($_POST['EndDate'])) {
		$SQLEndDate = FormatDateForSQL($_POST['EndDate']);
	} else {
		$SQLEndDate = '9999-12-31';
	}

	$SQL = "SELECT COUNT(typeabbrev)
				FROM prices
			WHERE prices.stockid='" . $Item . "'
			AND startdate='" . FormatDateForSQL($_POST['StartDate']) . "'
			AND enddate ='" . $SQLEndDate . "'
			AND prices.typeabbrev='" . $_POST['TypeAbbrev'] . "'
			AND prices.currabrev='" . $_POST['CurrAbrev'] . "'";

	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);

	if ($MyRow[0] != 0 and !isset($_POST['OldTypeAbbrev']) and !isset($_POST['OldCurrAbrev'])) {
		prnMsg(_('This price has already been entered. To change it you should edit it'), 'warn');
		$InputError = 1;
	}

	if (isset($_POST['OldTypeAbbrev']) and isset($_POST['OldCurrAbrev']) and mb_strlen($Item) > 1 and $InputError != 1) {

		/* Need to see if there is also a price entered that has an end date after the start date of this price and if so we will need to update it so there is no ambiguity as to which price will be used*/

		//editing an existing price
		$SQL = "UPDATE prices SET
					typeabbrev='" . $_POST['TypeAbbrev'] . "',
					currabrev='" . $_POST['CurrAbrev'] . "',
					price='" . filter_number_format($_POST['Price']) . "',
					startdate='" . FormatDateForSQL($_POST['StartDate']) . "',
					enddate='" . $SQLEndDate . "'
				WHERE prices.stockid='" . $Item . "'
				AND startdate='" . $_POST['OldStartDate'] . "'
				AND enddate ='" . $_POST['OldEndDate'] . "'
				AND prices.typeabbrev='" . $_POST['OldTypeAbbrev'] . "'
				AND prices.currabrev='" . $_POST['OldCurrAbrev'] . "'
				AND prices.debtorno=''";

		$ErrMsg = _('Could not be update the existing prices');
		$Result = DB_query($SQL, $ErrMsg);

		ReSequenceEffectiveDates($Item, $_POST['TypeAbbrev'], $_POST['CurrAbrev']);

		prnMsg(_('The price has been updated'), 'success');

	} elseif ($InputError != 1) {

		/*Selected price is null cos no item selected on first time round so must be adding a	record must be submitting new entries in the new price form */

		$SQL = "INSERT INTO prices (stockid,
									typeabbrev,
									currabrev,
									startdate,
									enddate,
									price)
							VALUES ('" . $Item . "',
								'" . $_POST['TypeAbbrev'] . "',
								'" . $_POST['CurrAbrev'] . "',
								'" . FormatDateForSQL($_POST['StartDate']) . "',
								'" . $SQLEndDate . "',
								'" . filter_number_format($_POST['Price']) . "')";
		$ErrMsg = _('The new price could not be added');
		$Result = DB_query($SQL, $ErrMsg);

		ReSequenceEffectiveDates($Item, $_POST['TypeAbbrev'], $_POST['CurrAbrev']);
		prnMsg(_('The new price has been inserted'), 'success');
	}

	unset($_POST['Price']);
	unset($_POST['StartDate']);
	unset($_POST['EndDate']);

} elseif (isset($_GET['delete'])) {
	//the link to delete a selected record was clicked instead of the submit button
	$SQL = "DELETE FROM prices
			WHERE prices.stockid = '" . $Item . "'
			AND prices.typeabbrev='" . $_GET['TypeAbbrev'] . "'
			AND prices.currabrev ='" . $_GET['CurrAbrev'] . "'
			AND  prices.startdate = '" . $_GET['StartDate'] . "'
			AND  prices.enddate = '" . $_GET['EndDate'] . "'
			AND prices.debtorno=''";
	$ErrMsg = _('Could not delete this price');
	$Result = DB_query($SQL, $ErrMsg);
	prnMsg(_('The selected price has been deleted'), 'success');

}

//Always do this stuff
$SQL = "SELECT currencies.currency,
			salestypes.sales_type,
		prices.price,
		prices.stockid,
		prices.typeabbrev,
		prices.currabrev,
		prices.startdate,
		prices.enddate,
		currencies.decimalplaces AS currdecimalplaces
	FROM prices
	INNER JOIN salestypes
		ON prices.typeabbrev = salestypes.typeabbrev
	INNER JOIN currencies
		ON prices.currabrev=currencies.currabrev
	WHERE prices.stockid='" . $Item . "'
	AND prices.debtorno=''
	ORDER BY prices.currabrev,
		prices.typeabbrev,
		prices.startdate";

$Result = DB_query($SQL);

if (DB_num_rows($Result) > 0) {
	echo '<form method="post" action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '">';
	echo '<table>
			<thead>
				<tr>
					<th colspan="7">
						<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />' . _('Pricing for part') . ':
						<input type="text" autofocus="autofocus" name="Item" size="22" value="' . $Item . '" maxlength="20" />
						<input type="submit" name="NewPart" value="' . _('Review Prices') . '" />
					</th>
			</tr>
			<tr>
				<th class="SortedColumn">' . _('Currency') . '</th>
				<th class="SortedColumn">' . _('Sales Type') . '</th>
				<th>' . _('Price') . '</th>
				<th class="SortedColumn">' . _('Start Date') . ' </th>
				<th>' . _('End Date') . '</th>';
	if (in_array(1000, $_SESSION['AllowedPageSecurityTokens'])) { // If is allow to modify prices.
		echo '<th colspan="2">' . _('Maintenance') . '</th>';
	}
	echo '</tr>
		</thead>';

	echo '<tbody>';
	while ($MyRow = DB_fetch_array($Result)) {
		if ($MyRow['enddate'] == '9999-12-31') {
			$EndDateDisplay = _('No End Date');
		} else {
			$EndDateDisplay = ConvertSQLDate($MyRow['enddate']);
		}

		echo '<tr class="striped_row">
				<td>' . $MyRow['currency'] . '</td>
				<td>' . $MyRow['sales_type'] . '</td>
				<td class="number">' . locale_number_format($MyRow['price'], $MyRow['currdecimalplaces'] + 2) . '</td>
				<td>' . ConvertSQLDate($MyRow['startdate']) . '</td>
				<td>' . $EndDateDisplay . '</td>';
		/*Only allow access to modify prices if securiy token 1000 is allowed */
		if (in_array(5, $_SESSION['AllowedPageSecurityTokens'])) {
			echo '<td><a href="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '?Item=' . $MyRow['stockid'] . '&amp;TypeAbbrev=' . $MyRow['typeabbrev'] . '&amp;CurrAbrev=' . $MyRow['currabrev'] . '&amp;Price=' . locale_number_format($MyRow['price'], $MyRow['currdecimalplaces']) . '&amp;StartDate=' . $MyRow['startdate'] . '&amp;EndDate=' . $MyRow['enddate'] . '&amp;Edit=1">' . _('Edit') . '</a></td>
				<td><a href="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '?Item=' . $MyRow['stockid'] . '&amp;TypeAbbrev=' . $MyRow['typeabbrev'] . '&amp;CurrAbrev=' . $MyRow['currabrev'] . '&amp;StartDate=' . $MyRow['startdate'] . '&amp;EndDate=' . $MyRow['enddate'] . '&amp;delete=yes" onclick="return MakeConfirm(\'' . _('Are you sure you wish to delete this price?') . '\', \'Confirm Delete\', this);">' . _('Delete') . '</a></td>';
		}
		echo '</tr>';
	}
	//END WHILE LIST LOOP
	echo '</tbody>
		</table>
	</form>';
} else {
	prnMsg(_('There are no prices set up for this part'), 'warn');
}

echo '<form method="post" action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
if (isset($_GET['Edit'])) {
	echo '<input type="hidden" name="OldTypeAbbrev" value="' . $_GET['TypeAbbrev'] . '" />';
	echo '<input type="hidden" name="OldCurrAbrev" value="' . $_GET['CurrAbrev'] . '" />';
	echo '<input type="hidden" name="OldStartDate" value="' . $_GET['StartDate'] . '" />';
	echo '<input type="hidden" name="OldEndDate" value="' . $_GET['EndDate'] . '" />';
	$_POST['CurrAbrev'] = $_GET['CurrAbrev'];
	$_POST['TypeAbbrev'] = $_GET['TypeAbbrev'];
	/*the price sent with the get is sql format price so no need to filter */
	$_POST['Price'] = $_GET['Price'];
	$_POST['StartDate'] = ConvertSQLDate($_GET['StartDate']);
	if ($_GET['EndDate'] == '' or $_GET['EndDate'] == '9999-12-31') {
		$_POST['EndDate'] = '';
	} else {
		$_POST['EndDate'] = ConvertSQLDate($_GET['EndDate']);
	}
}

$SQL = "SELECT currabrev,
				currency
		FROM currencies";
$Result = DB_query($SQL);

echo '<table>
		<tr>
			<th colspan="5"><h3>' . $Item . ' - ' . $PartDescription . '</h3></th>
		</tr>';
echo '<tr>
		<td>' . _('Currency') . ':</td>
		<td><select required="required" name="CurrAbrev">';
while ($MyRow = DB_fetch_array($Result)) {
	if ($MyRow['currabrev'] == $_POST['CurrAbrev']) {
		echo '<option selected="selected" value="';
	} else {
		echo '<option value="';
	}
	echo $MyRow['currabrev'] . '">' . $MyRow['currency'] . '</option>';
} //end while loop
DB_free_result($Result);

echo '</select>
			</td>
		</tr>
		<tr>
			<td>' . _('Sales Type Price List') . ':</td>
			<td><select required="required" name="TypeAbbrev">';

$SQL = "SELECT typeabbrev, sales_type FROM salestypes";
$Result = DB_query($SQL);

while ($MyRow = DB_fetch_array($Result)) {
	if ($MyRow['typeabbrev'] == $_POST['TypeAbbrev']) {
		echo '<option selected="selected" value="';
	} else {
		echo '<option value="';
	}
	echo $MyRow['typeabbrev'] . '">' . $MyRow['sales_type'] . '</option>';

} //end while loop
echo '</select></td></tr>';

DB_free_result($Result);

if (!isset($_POST['StartDate'])) {
	$_POST['StartDate'] = Date($_SESSION['DefaultDateFormat']);
}
if (!isset($_POST['EndDate'])) {
	$_POST['EndDate'] = DateAdd(Date($_SESSION['DefaultDateFormat']), 'y', 1);
}
echo '<tr>
		<td>' . _('Price Effective From Date') . ':</td>
		<td><input type="text" class="date" name="StartDate" size="10" required="required" maxlength="10" value="' . $_POST['StartDate'] . '" /></td>
	</tr>';
echo '<tr>
		<td>' . _('Price Effective To Date') . ':</td>
		<td><input type="text" class="date" name="EndDate" size="10" maxlength="10" value="' . $_POST['EndDate'] . '" /></td>
	</tr>';
echo '<input type="hidden" name="Item" value="' . $Item . '" />';
if (!isset($_POST['Price'])) {
	$_POST['Price'] = 0;
}
echo '<tr>
		<td>' . _('Price') . ':</td>
		<td>
			<input type="text" class="number" name="Price" size="12" required="required" maxlength="11" value="' . $_POST['Price'] . '" />
		</td>
	</tr>
</table>
<div class="centre">
	<input type="submit" name="submit" value="' . _('Enter') . '/' . _('Amend Price') . '" />
</div>';

echo '</form>';
include ('includes/footer.php');

function ReSequenceEffectiveDates($Item, $PriceList, $CurrAbbrev) {

	/*This is quite complicated - the idea is that prices set up should be unique and there is no way two prices could be returned as valid - when getting a price in includes/GetPrice.php the logic is to first look for a price of the salestype/currency within the effective start and end dates - then if not get the price with a start date prior but a blank end date (the default price). We would not want two prices where one price falls inside another effective date range except in the case of a blank end date - ie no end date - the default price for the currency/salestype.
	I first thought that we would need to update the previous default price (blank end date), when a new default price is entered, to have an end date of the startdate of this new default price less 1 day - but this is  converting a default price into a special price which could result in having two special prices over the same date range - best to leave it unchanged and use logic in the GetPrice.php to ensure the correct default price is returned
	*
	* After further discussion (Ricard) if the new price has a blank end date - i.e. no end then the pre-existing price with no end date should be changed to have an end date just prior to the new default (no end date) price commencing
	*/
	//this is just the case where debtorno='' - see the Prices_Customer.php script for customer special prices
	$SQL = "SELECT price,
						startdate,
						enddate
				FROM prices
				WHERE debtorno=''
				AND stockid='" . $Item . "'
				AND currabrev='" . $CurrAbbrev . "'
				AND typeabbrev='" . $PriceList . "'
				AND enddate <>'9999-12-31'
				ORDER BY startdate, enddate";
	$Result = DB_query($SQL);

	while ($MyRow = DB_fetch_array($Result)) {
		if (isset($NextStartDate)) {
			if (Date1GreaterThanDate2(ConvertSQLDate($MyRow['startdate']), $NextStartDate)) {
				$NextStartDate = ConvertSQLDate($MyRow['startdate']);
				//Only if the previous enddate is after the new start date do we need to look at updates
				if (Date1GreaterThanDate2(ConvertSQLDate($EndDate), ConvertSQLDate($MyRow['startdate']))) {
					/*Need to make the end date the new start date less 1 day */
					$SQL = "UPDATE prices SET enddate = '" . FormatDateForSQL(DateAdd($NextStartDate, 'd', -1)) . "'
										WHERE stockid ='" . $Item . "'
										AND currabrev='" . $CurrAbbrev . "'
										AND typeabbrev='" . $PriceList . "'
										AND startdate ='" . $StartDate . "'
										AND enddate = '" . $EndDate . "'
										AND debtorno =''";
					$UpdateResult = DB_query($SQL);
				}
			} //end of if startdate  after NextStartDate - we have a new NextStartDate
			
		} //end of if set NextStartDate
		else {
			$NextStartDate = ConvertSQLDate($MyRow['startdate']);
		}
		$StartDate = $MyRow['startdate'];
		$EndDate = $MyRow['enddate'];
	} // end of loop around all prices
	//Now look for duplicate prices with no end
	$SQL = "SELECT price,
						startdate,
						enddate
					FROM prices
					WHERE debtorno=''
					AND stockid='" . $Item . "'
					AND currabrev='" . $CurrAbbrev . "'
					AND typeabbrev='" . $PriceList . "'
					AND enddate ='9999-12-31'
					ORDER BY startdate";
	$Result = DB_query($SQL);

	while ($MyRow = DB_fetch_array($Result)) {
		if (isset($OldStartDate)) {
			/*Need to make the end date the new start date less 1 day */
			$NewEndDate = FormatDateForSQL(DateAdd(ConvertSQLDate($MyRow['startdate']), 'd', -1));
			$SQL = "UPDATE prices SET enddate = '" . $NewEndDate . "'
							WHERE stockid ='" . $Item . "'
							AND currabrev='" . $CurrAbbrev . "'
							AND typeabbrev='" . $PriceList . "'
							AND startdate ='" . $OldStartDate . "'
							AND enddate = '9999-12-31'
							AND debtorno =''";
			$UpdateResult = DB_query($SQL);
		}
		$OldStartDate = $MyRow['startdate'];
	} // end of loop around duplicate no end date prices
	
} // end function ReSequenceEffectiveDates

?>