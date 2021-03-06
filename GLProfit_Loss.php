<?php
include ('includes/session.php');
$Title = _('Profit and Loss'); // Screen identification.
$ViewTopic = 'GeneralLedger'; // Filename's id in ManualContents.php's TOC.
$BookMark = 'ProfitAndLoss'; // Anchor's id in the manual's html document.
include ('includes/SQL_CommonFunctions.php');
include ('includes/AccountSectionsDef.php'); // This loads the $Sections variable
if (isset($_POST['FromPeriod']) and ($_POST['FromPeriod'] > $_POST['ToPeriod'])) {
	prnMsg(_('The selected period from is actually after the period to') . '! ' . _('Please reselect the reporting period'), 'error');
	$_POST['SelectADifferentPeriod'] = 'Select A Different Period';
}

if (isset($_POST['Period']) and $_POST['Period'] != '') {
	$_POST['FromPeriod'] = ReportPeriod($_POST['Period'], 'From');
	$_POST['ToPeriod'] = ReportPeriod($_POST['Period'], 'To');
}

if ((!isset($_POST['FromPeriod']) and !isset($_POST['ToPeriod'])) or isset($_POST['SelectADifferentPeriod'])) {

	include ('includes/header.php');

	echo '<p class="page_title_text">
			<img alt="" src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/printer.png" title="', _('Print'), '" />', _('Print Profit and Loss Report'), '
		</p>'; // Page title.
	echo '<div class="page_help_text">', _('Profit and loss statement, also called an Income Statement, or Statement of Operations, this is the statement that indicates how the revenue (money received from the sale of products and services before expenses are taken out, also known as the top line) is transformed into the net income (the result after all revenues and expenses have been accounted for, also known as the bottom line).'), '<br />', _('The purpose of the income statement is to show whether the company made or lost money during the period being reported.'), '<br />', _('The Profit and Loss statement represents a period of time. This contrasts with the Balance Sheet, which represents a single moment in time.'), '<br />', $ProjectName, _(' is an accrual based system (not a cash based system).  Accrual systems include items when they are invoiced to the customer, and when expenses are owed based on the supplier invoice date.'), '</div>';

	echo '<form method="post" action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	if (Date('m') > $_SESSION['YearEnd']) {
		/*Dates in SQL format */
		$DefaultFromDate = Date('Y-m-d', Mktime(0, 0, 0, $_SESSION['YearEnd'] + 2, 0, Date('Y')));
		$FromDate = Date($_SESSION['DefaultDateFormat'], Mktime(0, 0, 0, $_SESSION['YearEnd'] + 2, 0, Date('Y')));
	} else {
		$DefaultFromDate = Date('Y-m-d', Mktime(0, 0, 0, $_SESSION['YearEnd'] + 2, 0, Date('Y') - 1));
		$FromDate = Date($_SESSION['DefaultDateFormat'], Mktime(0, 0, 0, $_SESSION['YearEnd'] + 2, 0, Date('Y') - 1));
	}

	/*Show a form to allow input of criteria for profit and loss to show */
	echo '<fieldset>
			<legend>', _('Criteria for report'), '</legend>
			<field>
				<label for="FromPeriod">', _('Select Period From'), ':</label>
				<select name="FromPeriod" autofocus="autofocus">';

	$SQL = "SELECT periodno,
					lastdate_in_period
			FROM periods
			ORDER BY periodno DESC";
	$Periods = DB_query($SQL);

	while ($MyRow = DB_fetch_array($Periods)) {
		if (isset($_POST['FromPeriod']) and $_POST['FromPeriod'] != '') {
			if ($_POST['FromPeriod'] == $MyRow['periodno']) {
				echo '<option selected="selected" value="', $MyRow['periodno'], '">', MonthAndYearFromSQLDate($MyRow['lastdate_in_period']), '</option>';
			} else {
				echo '<option value="', $MyRow['periodno'], '">', MonthAndYearFromSQLDate($MyRow['lastdate_in_period']), '</option>';
			}
		} else {
			if ($MyRow['lastdate_in_period'] == $DefaultFromDate) {
				echo '<option selected="selected" value="' . $MyRow['periodno'] . '">' . MonthAndYearFromSQLDate($MyRow['lastdate_in_period']) . '</option>';
			} else {
				echo '<option value="', $MyRow['periodno'], '">', MonthAndYearFromSQLDate($MyRow['lastdate_in_period']), '</option>';
			}
		}
	}
	echo '</select>
		<fieldhelp>', _('Select the starting period for this report'), '</fieldhelp>
	</field>';

	if (!isset($_POST['ToPeriod']) or $_POST['ToPeriod'] == '') {
		$LastDate = date('Y-m-d', mktime(0, 0, 0, Date('m') + 1, 0, Date('Y')));
		$SQL = "SELECT periodno FROM periods where lastdate_in_period = '" . $LastDate . "'";
		$MaxPrd = DB_query($SQL);
		$MaxPrdrow = DB_fetch_row($MaxPrd);
		$DefaultToPeriod = (int)($MaxPrdrow[0]);

	} else {
		$DefaultToPeriod = $_POST['ToPeriod'];
	}

	echo '<field>
			<label for="ToPeriod">', _('Select Period To'), ':</label>
			<select name="ToPeriod">';

	$RetResult = DB_data_seek($Periods, 0);

	while ($MyRow = DB_fetch_array($Periods)) {

		if ($MyRow['periodno'] == $DefaultToPeriod) {
			echo '<option selected="selected" value="' . $MyRow['periodno'] . '">' . MonthAndYearFromSQLDate($MyRow['lastdate_in_period']) . '</option>';
		} else {
			echo '<option value="' . $MyRow['periodno'] . '">' . MonthAndYearFromSQLDate($MyRow['lastdate_in_period']) . '</option>';
		}
	}
	echo '</select>
		<fieldhelp>', _('Select the end period for this report'), '</fieldhelp>
	</field>';

	if (!isset($_POST['Period'])) {
		$_POST['Period'] = '';
	}

	echo '<h3>', _('OR'), '</h3>';

	echo '<field>
			<label for="Period">', _('Select Period'), ':</label>
			', ReportPeriodList($_POST['Period'], array('l', 't')), '
			<fieldhelp>', _('Select a predefined period from this list. If a selection is made here it will override anything selected in the From and To options above.'), '</fieldhelp>
		</field>';

	echo '<field>
			<label for="Detail">', _('Detail Or Summary'), ':</label>
			<select name="Detail">
				<option value="Summary">', _('Summary'), '</option>
				<option selected="selected" value="Detailed">', _('All Accounts'), '</option>
			</select>
			<fieldhelp>', _('Show report for all accounts, or just show summary report.'), '</fieldhelp>
		</field>
		<field>
			<label for="ShowZeroBalances">', _('Show all Accounts including zero balances'), '</label>
			<input type="checkbox" checked="checked" title="', _('Check this box to display all accounts including those accounts with no balance'), '" name="ShowZeroBalances">
			<fieldhelp>', _('Show all accounts, or just accounts with balances.'), '</fieldhelp>
 		</field>
	</fieldset>
	<div class="centre">
		<input type="submit" name="ShowPL" value="', _('Show on Screen (HTML)'), '" />
	</div>
	<div class="centre">
		<input type="submit" name="PrintPDF" value="', _('Produce PDF Report'), '" />
	</div>
</form>';

	/*Now do the posting while the user is thinking about the period to select */

	include ('includes/GLPostings.php');

} else if (isset($_POST['PrintPDF'])) {

	include ('includes/PDFStarter.php');
	$PDF->addInfo('Title', _('Profit and Loss'));
	$PDF->addInfo('Subject', _('Profit and Loss'));

	$PageNumber = 0;
	$FontSize = 8;
	$line_height = 12;

	$NumberOfMonths = $_POST['ToPeriod'] - $_POST['FromPeriod'] + 1;

	if ($NumberOfMonths > 12) {
		include ('includes/header.php');
		echo '<p>';
		prnMsg(_('A period up to 12 months in duration can be specified') . ' - ' . _('the system automatically shows a comparative for the same period from the previous year') . ' - ' . _('it cannot do this if a period of more than 12 months is specified') . '. ' . _('Please select an alternative period range'), 'error');
		include ('includes/footer.php');
		exit;
	}

	$SQL = "SELECT lastdate_in_period FROM periods WHERE periodno='" . $_POST['ToPeriod'] . "'";
	$PrdResult = DB_query($SQL);
	$MyRow = DB_fetch_row($PrdResult);
	$PeriodToDate = MonthAndYearFromSQLDate($MyRow[0]);

	$SQL = "SELECT accountgroups.sectioninaccounts,
					accountgroups.groupname,
					accountgroups.parentgroupname,
					chartdetails.accountcode ,
					chartmaster.accountname,
					Sum(CASE WHEN chartdetails.period='" . $_POST['FromPeriod'] . "' THEN chartdetails.bfwd ELSE 0 END) AS firstprdbfwd,
					Sum(CASE WHEN chartdetails.period='" . $_POST['FromPeriod'] . "' THEN chartdetails.bfwdbudget ELSE 0 END) AS firstprdbudgetbfwd,
					Sum(CASE WHEN chartdetails.period='" . $_POST['ToPeriod'] . "' THEN chartdetails.bfwd + chartdetails.actual ELSE 0 END) AS lastprdcfwd,
					Sum(CASE WHEN chartdetails.period='" . ($_POST['FromPeriod'] - 12) . "' THEN chartdetails.bfwd ELSE 0 END) AS lyfirstprdbfwd,
					Sum(CASE WHEN chartdetails.period='" . ($_POST['ToPeriod'] - 12) . "' THEN chartdetails.bfwd + chartdetails.actual ELSE 0 END) AS lylastprdcfwd,
					Sum(CASE WHEN chartdetails.period='" . $_POST['ToPeriod'] . "' THEN chartdetails.bfwdbudget + chartdetails.budget ELSE 0 END) AS lastprdbudgetcfwd
				FROM chartmaster
				INNER JOIN accountgroups
					ON chartmaster.groupcode = accountgroups.groupcode
					AND chartmaster.language = accountgroups.language
				INNER JOIN chartdetails ON chartmaster.accountcode= chartdetails.accountcode
				INNER JOIN glaccountusers ON glaccountusers.accountcode=chartmaster.accountcode AND glaccountusers.userid='" . $_SESSION['UserID'] . "' AND glaccountusers.canview=1
				WHERE accountgroups.pandl=1
					AND chartmaster.language='" . $_SESSION['ChartLanguage'] . "'
				GROUP BY accountgroups.sectioninaccounts,
					accountgroups.groupcode,
					accountgroups.parentgroupname,
					chartdetails.accountcode,
					chartmaster.accountname,
					accountgroups.sequenceintb
				ORDER BY accountgroups.sectioninaccounts,
					accountgroups.sequenceintb,
					accountgroups.groupcode,
					chartdetails.accountcode";

	$AccountsResult = DB_query($SQL);

	if (DB_error_no() != 0) {
		$title = _('Profit and Loss') . ' - ' . _('Problem Report') . '....';
		include ('includes/header.php');
		prnMsg(_('No general ledger accounts were returned by the SQL because') . ' - ' . DB_error_msg());
		echo '<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
		if ($Debug == 1) {
			echo '<br />' . $SQL;
		}
		include ('includes/footer.php');
		exit;
	}
	if (DB_num_rows($AccountsResult) == 0) {
		$title = _('Print Profit and Loss Error');
		include ('includes/header.php');
		echo '<br />';
		prnMsg(_('There were no entries to print out for the selections specified'), 'warn');
		echo '<br /><a href="' . $RootPath . '/index.php">' . _('Back to the menu') . '</a>';
		include ('includes/footer.php');
		exit;
	}

	include ('includes/PDFProfitAndLossPageHeader.php');

	$Section = '';
	$SectionPrdActual = 0;
	$SectionPrdLY = 0;
	$SectionPrdBudget = 0;

	$ActGrp = '';
	$ParentGroups = array();
	$Level = 0;
	$ParentGroups[$Level] = '';
	$GrpPrdActual = array(0);
	$GrpPrdLY = array(0);
	$GrpPrdBudget = array(0);
	$TotalIncome = 0;
	$TotalBudgetIncome = 0;
	$TotalLYIncome = 0;
	$PeriodProfitLoss = 0;
	$PeriodBudgetProfitLoss = 0;
	$PeriodLYProfitLoss = 0;

	while ($MyRow = DB_fetch_array($AccountsResult)) {

		// Print heading if at end of page
		if ($YPos < ($Bottom_Margin)) {
			include ('includes/PDFProfitAndLossPageHeader.php');
		}

		if ($MyRow['groupname'] != $ActGrp) {
			if ($ActGrp != '') {
				if ($MyRow['parentgroupname'] != $ActGrp) {
					while ($MyRow['groupname'] != $ParentGroups[$Level] and $Level > 0) {
						if ($_POST['Detail'] == 'Detailed') {
							$ActGrpLabel = $ParentGroups[$Level] . ' ' . _('total');
						} else {
							$ActGrpLabel = $ParentGroups[$Level];
						}
						if ($Section == 1) {
							/*Income */
							$LeftOvers = $PDF->addTextWrap($Left_Margin + ($Level * 10), $YPos, 200 - ($Level * 10), $FontSize, $ActGrpLabel);
							$LeftOvers = $PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format(-$GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
							$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format(-$GrpPrdBudget[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
							$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format(-$GrpPrdBudget[$Level] - $GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
							$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format(-$GrpPrdLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
							$YPos-= (2 * $line_height);
						} else {
							/*Costs */
							$LeftOvers = $PDF->addTextWrap($Left_Margin + ($Level * 10), $YPos, 200 - ($Level * 10), $FontSize, $ActGrpLabel);
							$LeftOvers = $PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format($GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
							$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format($GrpPrdBudget[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
							$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format($GrpPrdBudget[$Level] - $GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
							$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format($GrpPrdLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
							$YPos-= (2 * $line_height);
						}
						$GrpPrdLY[$Level] = 0;
						$GrpPrdActual[$Level] = 0;
						$GrpPrdBudget[$Level] = 0;
						$ParentGroups[$Level] = '';
						$Level--;
						// Print heading if at end of page
						if ($YPos < ($Bottom_Margin + (2 * $line_height))) {
							include ('includes/PDFProfitAndLossPageHeader.php');
						}
					} //end of loop
					//still need to print out the group total for the same level
					if ($_POST['Detail'] == 'Detailed') {
						$ActGrpLabel = $ParentGroups[$Level] . ' ' . _('total');
					} else {
						$ActGrpLabel = $ParentGroups[$Level];
					}
					if ($Section == 1) {
						/*Income */
						$LeftOvers = $PDF->addTextWrap($Left_Margin + ($Level * 10), $YPos, 200 - ($Level * 10), $FontSize, $ActGrpLabel);
						$PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format(-$GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
						$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format(-$GrpPrdBudget[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
						$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format($GrpPrdBudget[$Level] - $GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
						$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format(-$GrpPrdLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
						$YPos-= (2 * $line_height);
					} else {
						/*Costs */
						$LeftOvers = $PDF->addTextWrap($Left_Margin + ($Level * 10), $YPos, 200 - ($Level * 10), $FontSize, $ActGrpLabel);
						$LeftOvers = $PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format($GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
						$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format($GrpPrdBudget[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
						$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format($GrpPrdBudget[$Level] - $GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
						$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format($GrpPrdLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
						$YPos-= (2 * $line_height);
					}
					$GrpPrdLY[$Level] = 0;
					$GrpPrdActual[$Level] = 0;
					$GrpPrdBudget[$Level] = 0;
					$ParentGroups[$Level] = '';
				}
			}
		}

		// Print heading if at end of page
		if ($YPos < ($Bottom_Margin + (2 * $line_height))) {
			include ('includes/PDFProfitAndLossPageHeader.php');
		}

		if ($MyRow['sectioninaccounts'] != $Section) {
			//			$PDF->setFont('', 'B');
			$FontSize = 8;
			if ($Section != '') {
				$PDF->line($Left_Margin + 250, $YPos + $line_height, $Left_Margin + 500, $YPos + $line_height);
				$PDF->line($Left_Margin + 250, $YPos - 3, $Left_Margin + 500, $YPos - 3);
				if ($Section == 1) {
					/*Income*/

					$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 200, $FontSize, $Sections[$Section]);
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format(-$SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format(-$SectionPrdBudget, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format(-$SectionPrdBudget - $SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format(-$SectionPrdLY, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$YPos-= (2 * $line_height);

					$TotalIncome = - $SectionPrdActual;
					$TotalBudgetIncome = - $SectionPrdBudget;
					$TotalLYIncome = - $SectionPrdLY;
				} else {
					$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 200, $FontSize, $Sections[$Section]);
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format($SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format($SectionPrdBudget, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format($SectionPrdBudget - $SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format($SectionPrdLY, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$YPos-= (2 * $line_height);
				}
				if ($Section == 2) {
					/*Cost of Sales - need sub total for Gross Profit*/
					$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 200, $FontSize, _('Gross Profit'));
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format($TotalIncome - $SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format($TotalBudgetIncome - $SectionPrdBudget, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format(($TotalBudgetIncome - $SectionPrdBudget) - ($TotalIncome - $SectionPrdActual), $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format($TotalLYIncome - $SectionPrdLY, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$PDF->line($Left_Margin + 310, $YPos + $line_height, $Left_Margin + 500, $YPos + $line_height);
					$PDF->line($Left_Margin + 310, $YPos, $Left_Margin + 500, $YPos);
					$YPos-= (2 * $line_height);

					if ($TotalIncome != 0) {
						$PrdGPPercent = 100 * ($TotalIncome - $SectionPrdActual) / $TotalIncome;
					} else {
						$PrdGPPercent = 0;
					}
					if ($TotalBudgetIncome != 0) {
						$BudgetGPPercent = 100 * ($TotalBudgetIncome - $SectionPrdBudget) / $TotalBudgetIncome;
					} else {
						$BudgetGPPercent = 0;
					}
					if ($TotalLYIncome != 0) {
						$LYGPPercent = 100 * ($TotalLYIncome - $SectionPrdLY) / $TotalLYIncome;
					} else {
						$LYGPPercent = 0;
					}
					$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 200, $FontSize, _('Gross Profit Percent'));
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format($PrdGPPercent, 1) . '%', 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format($BudgetGPPercent, 1) . '%', 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format($BudgetGPPercent, 1) . '%', 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format($LYGPPercent, 1) . '%', 'right');
					$YPos-= (2 * $line_height);
				}
			}
			$SectionPrdLY = 0;
			$SectionPrdActual = 0;
			$SectionPrdBudget = 0;

			$Section = $MyRow['sectioninaccounts'];

			if ($_POST['Detail'] == 'Detailed') {
				$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 200, $FontSize, $Sections[$MyRow['sectioninaccounts']]);
				$YPos-= (2 * $line_height);
			}
			$FontSize = 8;
			$PDF->setFont('', ''); //sets to normal type in the default font
			
		}

		if ($MyRow['groupname'] != $ActGrp) {
			if ($MyRow['parentgroupname'] == $ActGrp and $ActGrp != '') { //adding another level of nesting
				$Level++;
			}
			$ActGrp = $MyRow['groupname'];
			$ParentGroups[$Level] = $ActGrp;
			if ($_POST['Detail'] == 'Detailed') {
				$FontSize = 8;
				//				$PDF->setFont('', 'B');
				$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 200, $FontSize, $MyRow['groupname']);
				$YPos-= (2 * $line_height);
				$FontSize = 8;
				$PDF->setFont('', '');
			}
		}

		$AccountPeriodActual = $MyRow['lastprdcfwd'] - $MyRow['firstprdbfwd'];
		$AccountPeriodLY = $MyRow['lylastprdcfwd'] - $MyRow['lyfirstprdbfwd'];
		$AccountPeriodBudget = $MyRow['lastprdbudgetcfwd'] - $MyRow['firstprdbudgetbfwd'];
		$PeriodProfitLoss+= $AccountPeriodActual;
		$PeriodBudgetProfitLoss+= $AccountPeriodBudget;
		$PeriodLYProfitLoss+= $AccountPeriodLY;

		for ($i = 0;$i <= $Level;$i++) {
			if (!isset($GrpPrdLY[$i])) {
				$GrpPrdLY[$i] = 0;
			}
			$GrpPrdLY[$i]+= $AccountPeriodLY;
			if (!isset($GrpPrdActual[$i])) {
				$GrpPrdActual[$i] = 0;
			}
			$GrpPrdActual[$i]+= $AccountPeriodActual;
			if (!isset($GrpPrdBudget[$i])) {
				$GrpPrdBudget[$i] = 0;
			}
			$GrpPrdBudget[$i]+= $AccountPeriodBudget;
		}

		$SectionPrdLY+= $AccountPeriodLY;
		$SectionPrdActual+= $AccountPeriodActual;
		$SectionPrdBudget+= $AccountPeriodBudget;

		if ($_POST['Detail'] == 'Detailed') {

			if (isset($_POST['ShowZeroBalances']) or (!isset($_POST['ShowZeroBalances']) and ($AccountPeriodActual <> 0 or $AccountPeriodBudget <> 0 or $AccountPeriodLY <> 0))) { //condition for pdf
				$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 60, $FontSize, $MyRow['accountcode']);
				$LeftOvers = $PDF->addTextWrap($Left_Margin + 60, $YPos, 190, $FontSize, $MyRow['accountname']);

				if ($Section == 1) {
					/*Income*/
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format(-$AccountPeriodActual, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format(-$AccountPeriodBudget, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format($AccountPeriodBudget - $AccountPeriodActual, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format(-$AccountPeriodLY, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
				} else {
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format($AccountPeriodActual, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format($AccountPeriodBudget, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format($AccountPeriodBudget - $AccountPeriodActual, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format($AccountPeriodLY, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
				}
				$YPos-= $line_height;
			}
		}
	}
	//end of loop
	if ($ActGrp != '') {

		if ($MyRow['parentgroupname'] != $ActGrp) {

			while ($MyRow['groupname'] != $ParentGroups[$Level] and $Level > 0) {
				if ($_POST['Detail'] == 'Detailed') {
					$ActGrpLabel = $ParentGroups[$Level] . ' ' . _('total');
				} else {
					$ActGrpLabel = $ParentGroups[$Level];
				}
				if ($Section == 1) {
					/*Income */
					$LeftOvers = $PDF->addTextWrap($Left_Margin + ($Level * 10), $YPos, 200 - ($Level * 10), $FontSize, $ActGrpLabel);
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format(-$GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format(-$GrpPrdBudget[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format($GrpPrdBudget[$Level] - $GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format(-$GrpPrdLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$YPos-= (2 * $line_height);
				} else {
					/*Costs */
					$LeftOvers = $PDF->addTextWrap($Left_Margin + ($Level * 10), $YPos, 200 - ($Level * 10), $FontSize, $ActGrpLabel);
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format($GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format($GrpPrdBudget[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format($GrpPrdBudget[$Level] - $GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format($GrpPrdLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
					$YPos-= (2 * $line_height);
				}
				$GrpPrdLY[$Level] = 0;
				$GrpPrdActual[$Level] = 0;
				$GrpPrdBudget[$Level] = 0;
				$ParentGroups[$Level] = '';
				$Level--;
				// Print heading if at end of page
				if ($YPos < ($Bottom_Margin + (2 * $line_height))) {
					include ('includes/PDFProfitAndLossPageHeader.php');
				}
			}
			//still need to print out the group total for the same level
			if ($_POST['Detail'] == 'Detailed') {
				$ActGrpLabel = $ParentGroups[$Level] . ' ' . _('total');
			} else {
				$ActGrpLabel = $ParentGroups[$Level];
			}
			if ($Section == 1) {
				/*Income */
				$LeftOvers = $PDF->addTextWrap($Left_Margin + ($Level * 10), $YPos, 200 - ($Level * 10), $FontSize, $ActGrpLabel);
				$PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format(-$GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
				$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format(-$GrpPrdBudget[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
				$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format($GrpPrdBudget[$Level] - $GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
				$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format(-$GrpPrdLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
				$YPos-= (2 * $line_height);
			} else {
				/*Costs */
				$LeftOvers = $PDF->addTextWrap($Left_Margin + ($Level * 10), $YPos, 200 - ($Level * 10), $FontSize, $ActGrpLabel);
				$LeftOvers = $PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format($GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
				$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format($GrpPrdBudget[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
				$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format($GrpPrdBudget[$Level] - $GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
				$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format($GrpPrdLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']), 'right');
				$YPos-= (2 * $line_height);
			}
			$GrpPrdLY[$Level] = 0;
			$GrpPrdActual[$Level] = 0;
			$GrpPrdBudget[$Level] = 0;
			$ParentGroups[$Level] = '';
		}
	}
	// Print heading if at end of page
	if ($YPos < ($Bottom_Margin + (2 * $line_height))) {
		include ('includes/PDFProfitAndLossPageHeader.php');
	}
	if ($Section != '') {

		//		$PDF->setFont('', 'B');
		$PDF->line($Left_Margin + 250, $YPos + 13, $Left_Margin + 500, $YPos + 13);
		$PDF->line($Left_Margin + 250, $YPos - 3, $Left_Margin + 500, $YPos - 3);

		if ($Section == 1) {
			/*Income*/
			$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 200, $FontSize, $Sections[$Section]);
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format(-$SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format(-$SectionPrdBudget, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format($SectionPrdBudget - $SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format(-$SectionPrdLY, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
			$YPos-= (2 * $line_height);

			$TotalIncome = - $SectionPrdActual;
			$TotalBudgetIncome = - $SectionPrdBudget;
			$TotalLYIncome = - $SectionPrdLY;
		} else {
			$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 60, $FontSize, $Sections[$Section]);
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format($SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format($SectionPrdBudget, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format($SectionPrdBudget - $SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format($SectionPrdLY, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
			$YPos-= (2 * $line_height);
		}
		if ($Section == 2) {
			/*Cost of Sales - need sub total for Gross Profit*/
			$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 60, $FontSize, _('Gross Profit'));
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format($TotalIncome - $SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format($TotalBudgetIncome - $SectionPrdBudget, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format(($TotalBudgetIncome - $SectionPrdBudget) - ($TotalIncome - $SectionPrdActual), $_SESSION['CompanyRecord']['decimalplaces']), 'right');
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format($TotalLYIncome - $SectionPrdLY, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
			$YPos-= (2 * $line_height);

			$LeftOvers = $PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format(100 * ($TotalIncome - $SectionPrdActual) / $TotalIncome, 1) . '%', 'right');
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format(100 * ($TotalBudgetIncome - $SectionPrdBudget) / $TotalBudgetIncome, 1) . '%', 'right');
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format(100 * ($TotalBudgetIncome - $SectionPrdBudget) / $TotalBudgetIncome, 1) . '%', 'right');
			$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format(100 * ($TotalLYIncome - $SectionPrdLY) / $TotalLYIncome, 1) . '%', 'right');
			$YPos-= (2 * $line_height);
		}
	}

	$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 60, $FontSize, _('Profit') . ' - ' . _('Loss'));
	$LeftOvers = $PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format(-$PeriodProfitLoss, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
	$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format(-$PeriodBudgetProfitLoss), 'right');
	$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format($PeriodBudgetProfitLoss - $PeriodProfitLoss), 'right');
	$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format(-$PeriodLYProfitLoss, $_SESSION['CompanyRecord']['decimalplaces']), 'right');
	$YPos-= (2 * $line_height);

	if ($TotalIncome != 0) {
		$PrdPLPercent = 100 * (-$PeriodProfitLoss) / $TotalIncome;
	} else {
		$PrdPLPercent = 0;
	}
	if ($TotalBudgetIncome != 0) {
		$BudgetPLPercent = 100 * (-$PeriodBudgetProfitLoss) / $TotalBudgetIncome;
	} else {
		$BudgetPLPercent = 0;
	}
	if ($TotalLYIncome != 0) {
		$LYPLPercent = 100 * (-$PeriodLYProfitLoss) / $TotalLYIncome;
	} else {
		$LYPLPercent = 0;
	}
	$LeftOvers = $PDF->addTextWrap($Left_Margin, $YPos, 200, $FontSize, _('Net Profit Percent'));
	$LeftOvers = $PDF->addTextWrap($Left_Margin + 250, $YPos, 70, $FontSize, locale_number_format($PrdPLPercent, 1) . '%', 'right');
	$LeftOvers = $PDF->addTextWrap($Left_Margin + 310, $YPos, 70, $FontSize, locale_number_format($BudgetPLPercent, 1) . '%', 'right');
	$LeftOvers = $PDF->addTextWrap($Left_Margin + 370, $YPos, 70, $FontSize, locale_number_format($BudgetPLPercent, 1) . '%', 'right');
	$LeftOvers = $PDF->addTextWrap($Left_Margin + 430, $YPos, 70, $FontSize, locale_number_format($LYPLPercent, 1) . '%', 'right');
	$YPos-= (2 * $line_height);

	$PDF->line($Left_Margin + 250, $YPos + $line_height, $Left_Margin + 500, $YPos + $line_height);

	$PDF->OutputD($_SESSION['DatabaseName'] . '_' . 'Income_Statement_' . date('Y-m-d') . '.pdf');
	$PDF->__destruct();
	exit;

} else {

	$ViewTopic = 'GeneralLedger';
	$BookMark = 'ProfitAndLoss';
	include ('includes/header.php');
	echo '<form method="post" action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			<input type="hidden" name="FromPeriod" value="' . $_POST['FromPeriod'] . '" />
			<input type="hidden" name="ToPeriod" value="' . $_POST['ToPeriod'] . '" />';

	$NumberOfMonths = $_POST['ToPeriod'] - $_POST['FromPeriod'] + 1;

	if ($NumberOfMonths > 12) {
		echo '<br />';
		prnMsg(_('A period up to 12 months in duration can be specified') . ' - ' . _('the system automatically shows a comparative for the same period from the previous year') . ' - ' . _('it cannot do this if a period of more than 12 months is specified') . '. ' . _('Please select an alternative period range'), 'error');
		include ('includes/footer.php');
		exit;
	}

	$SQL = "SELECT lastdate_in_period FROM periods WHERE periodno='" . $_POST['ToPeriod'] . "'";
	$PrdResult = DB_query($SQL);
	$MyRow = DB_fetch_row($PrdResult);
	$PeriodToDate = MonthAndYearFromSQLDate($MyRow[0]);

	$SQL = "SELECT accountgroups.sectioninaccounts,
					accountgroups.parentgroupname,
					accountgroups.groupname,
					chartdetails.accountcode,
					chartmaster.accountname,
					SUM(CASE WHEN chartdetails.period='" . $_POST['FromPeriod'] . "' THEN chartdetails.bfwd ELSE 0 END) AS firstprdbfwd,
					SUM(CASE WHEN chartdetails.period='" . $_POST['FromPeriod'] . "' THEN chartdetails.bfwdbudget ELSE 0 END) AS firstprdbudgetbfwd,
					SUM(CASE WHEN chartdetails.period='" . $_POST['ToPeriod'] . "' THEN chartdetails.bfwd + chartdetails.actual ELSE 0 END) AS lastprdcfwd,
					SUM(CASE WHEN chartdetails.period='" . ($_POST['FromPeriod'] - 12) . "' THEN chartdetails.bfwd ELSE 0 END) AS lyfirstprdbfwd,
					SUM(CASE WHEN chartdetails.period='" . ($_POST['ToPeriod'] - 12) . "' THEN chartdetails.bfwd + chartdetails.actual ELSE 0 END) AS lylastprdcfwd,
					SUM(CASE WHEN chartdetails.period='" . $_POST['ToPeriod'] . "' THEN chartdetails.bfwdbudget + chartdetails.budget ELSE 0 END) AS lastprdbudgetcfwd
			FROM chartmaster
			INNER JOIN accountgroups
				ON chartmaster.groupcode = accountgroups.groupcode
				AND chartmaster.language = accountgroups.language
			INNER JOIN chartdetails	ON chartmaster.accountcode= chartdetails.accountcode
			INNER JOIN glaccountusers ON glaccountusers.accountcode=chartmaster.accountcode AND glaccountusers.userid='" . $_SESSION['UserID'] . "' AND glaccountusers.canview=1
			WHERE accountgroups.pandl=1
				AND chartmaster.language='" . $_SESSION['ChartLanguage'] . "'
			GROUP BY accountgroups.sectioninaccounts,
					accountgroups.parentgroupname,
					accountgroups.groupname,
					chartdetails.accountcode,
					chartmaster.accountname
			ORDER BY accountgroups.sectioninaccounts,
					accountgroups.sequenceintb,
					accountgroups.groupname,
					chartdetails.accountcode";

	$AccountsResult = DB_query($SQL, _('No general ledger accounts were returned by the SQL because'), _('The SQL that failed was'));

	echo '<p class="page_title_text">
			<img alt="" src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/transactions.png" title="' . _('General Ledger Profit Loss Inquiry') . '" />
			' . _('Statement of Profit and Loss for the') . ' ' . $NumberOfMonths . ' ' . _('months to') . ' and including ' . $PeriodToDate . '
		</p>'; // Page title.
	/*show a table of the accounts info returned by the SQL
	 Account Code ,   Account Name , Month Actual, Month Budget, Period Actual, Period Budget */

	echo '<table summary="' . _('General Ledger Profit Loss Inquiry') . '">
			<tr>
				<th colspan="10">
					<b>' . _('General Ledger Profit Loss Inquiry') . '</b>
					<img src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/printer.png" class="PrintIcon" title="' . _('Print') . '" alt="' . _('Print') . '" onclick="window.print();" />
				</th>
			</tr>';

	if ($_POST['Detail'] == 'Detailed') {
		$TableHeader = '<tr>
							<th>' . _('Account') . '</th>
							<th>' . _('Account Name') . '</th>
							<th colspan="2">' . _('Period Actual') . '</th>
							<th colspan="2">' . _('Period Budget') . '</th>
							<th colspan="2">' . _('Period Variance') . '</th>
							<th colspan="2">' . _('Last Year') . '</th>
						</tr>';
	} else {
		/*summary */
		$TableHeader = '<tr>
							<th colspan="2"></th>
							<th colspan="2">' . _('Period Actual') . '</th>
							<th colspan="2">' . _('Period Budget') . '</th>
							<th colspan="2">' . _('Period Variance') . '</th>
							<th colspan="2">' . _('Last Year') . '</th>
						</tr>';
	}

	$j = 1;

	$Section = '';
	$SectionPrdActual = 0;
	$SectionPrdLY = 0;
	$SectionPrdBudget = 0;

	$PeriodProfitLoss = 0;
	$PeriodProfitLoss = 0;
	$PeriodLYProfitLoss = 0;
	$PeriodBudgetProfitLoss = 0;

	$ActGrp = '';
	$ParentGroups = array();
	$Level = 0;
	$ParentGroups[$Level] = '';
	$GrpPrdActual = array(0);
	$GrpPrdLY = array(0);
	$GrpPrdBudget = array(0);
	$TotalIncome = 0;
	$TotalBudgetIncome = 0;
	$TotalLYIncome = 0;

	while ($MyRow = DB_fetch_array($AccountsResult)) {
		if ($MyRow['groupname'] != $ActGrp) {
			if ($MyRow['parentgroupname'] != $ActGrp and $ActGrp != '') {
				while ($MyRow['groupname'] != $ParentGroups[$Level] and $Level > 0) {
					if ($_POST['Detail'] == 'Detailed') {
						echo '<tr>
								<td colspan="3"></td>
								<td colspan="8"><hr /></td>
							</tr>';
						$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level] . ' ' . _('total');
					} else {
						$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level];
					}
					if ($Section == 1) {
						/*Income */
						printf('<tr>
								<td colspan="2"><h4><i>%s</i></h4></td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
								</tr>', $ActGrpLabel, locale_number_format(-$GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$GrpPrdBudget[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$GrpPrdBudget[$Level] - $GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$GrpPrdLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']));
					} else {
						/*Costs */
						printf('<tr>
								<td colspan="3"><h4><i>%s </i></h4></td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								</tr>', $ActGrpLabel, locale_number_format($GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($GrpPrdBudget[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($GrpPrdBudget[$Level] - $GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($GrpPrdLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']));
					}

					$GrpPrdLY[$Level] = 0;
					$GrpPrdActual[$Level] = 0;
					$GrpPrdBudget[$Level] = 0;
					$ParentGroups[$Level] = '';
					$Level--;
				} //end while
				//still need to print out the old group totals
				if ($_POST['Detail'] == 'Detailed') {
					echo '<tr>
							<td colspan="3"></td>
							<td colspan="8"><hr /></td>
						</tr>';
					$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level] . ' ' . _('total');
				} else {
					$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level];
				}

				if ($Section == 1) {
					/*Income */
					printf('<tr>
							<td colspan="2"><h4><i>%s </i></h4></td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							</tr>', $ActGrpLabel, locale_number_format(-$GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$GrpPrdBudget[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($GrpPrdBudget[$Level] - $GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$GrpPrdLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']));
				} else {
					/*Costs */
					printf('<tr>
							<td colspan="2"><h4><i>%s </i></h4></td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							</tr>', $ActGrpLabel, locale_number_format($GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($GrpPrdBudget[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($GrpPrdBudget[$Level] - $GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($GrpPrdLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']));
				}
				$GrpPrdLY[$Level] = 0;
				$GrpPrdActual[$Level] = 0;
				$GrpPrdBudget[$Level] = 0;
				$ParentGroups[$Level] = '';
			}
			++$j;
		}

		if ($MyRow['sectioninaccounts'] != $Section) {

			if ($SectionPrdLY + $SectionPrdActual + $SectionPrdBudget != 0) {
				if ($Section == 1) {
					/*Income*/

					echo '<tr>
							<td colspan="3"></td>
	  						<td><hr /></td>
							<td></td>
							<td><hr /></td>
							<td></td>
							<td><hr /></td>
							<td></td>
							<td><hr /></td>
						</tr>';

					printf('<tr>
							<td colspan="2"><h2>%s</td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							</tr>', $Sections[$Section], locale_number_format(-$SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$SectionPrdBudget, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($SectionPrdBudget - $SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$SectionPrdLY, $_SESSION['CompanyRecord']['decimalplaces']));
					$TotalIncome = - $SectionPrdActual;
					$TotalBudgetIncome = - $SectionPrdBudget;
					$TotalLYIncome = - $SectionPrdLY;
				} else {
					echo '<tr>
							<td colspan="2"></td>
			  				<td><hr /></td>
							<td></td>
							<td><hr /></td>
							<td></td>
							<td><hr /></td>
							<td></td>
							<td><hr /></td>
							</tr>';
					printf('<tr>
							<td colspan="2"><h2>%s</h2></td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							</tr>', $Sections[$Section], locale_number_format($SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($SectionPrdBudget, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($SectionPrdBudget - $SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($SectionPrdLY, $_SESSION['CompanyRecord']['decimalplaces']));
				}
				if ($Section == 2) {
					/*Cost of Sales - need sub total for Gross Profit*/
					echo '<tr>
							<td colspan="2"></td>
							<td colspan="8"><hr /></td>
						</tr>';
					printf('<tr>
							<td colspan="2"><h2>' . _('Gross Profit') . '</h2></td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							</tr>', locale_number_format($TotalIncome - $SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($TotalBudgetIncome - $SectionPrdBudget, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(($TotalBudgetIncome - $SectionPrdBudget) - ($TotalIncome - $SectionPrdActual), $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($TotalLYIncome - $SectionPrdLY, $_SESSION['CompanyRecord']['decimalplaces']));

					if ($TotalIncome != 0) {
						$PrdGPPercent = 100 * ($TotalIncome - $SectionPrdActual) / $TotalIncome;
					} else {
						$PrdGPPercent = 0;
					}
					if ($TotalBudgetIncome != 0) {
						$BudgetGPPercent = 100 * ($TotalBudgetIncome - $SectionPrdBudget) / $TotalBudgetIncome;
					} else {
						$BudgetGPPercent = 0;
					}
					if ($TotalLYIncome != 0) {
						$LYGPPercent = 100 * ($TotalLYIncome - $SectionPrdLY) / $TotalLYIncome;
					} else {
						$LYGPPercent = 0;
					}
					echo '<tr>
							<td colspan="2"></td>
							<td colspan="8"><hr /></td>
						</tr>';
					printf('<tr>
							<td colspan="2"><h4><i>' . _('Gross Profit Percent') . '</i></h4></td>
							<td></td>
							<td class="number"><i>%s</i></td>
							<td></td>
							<td class="number"><i>%s</i></td>
							<td></td>
							<td class="number"><i>%s</i></td>
							<td></td>
							<td class="number"><i>%s</i></td>
							</tr><tr><td colspan="6"> </td></tr>', locale_number_format($PrdGPPercent, 1) . '%', locale_number_format($BudgetGPPercent, 1) . '%', locale_number_format($BudgetGPPercent, 1) . '%', locale_number_format($LYGPPercent, 1) . '%');
					++$j;
				}

				if (($Section != 1) and ($Section != 2)) {
					echo '<tr>
							<td colspan="2"></td>
							<td colspan="8"><hr /></td>
						</tr>';

					printf('<tr>
								<td colspan="2"><h4><b>' . _('Profit') . ' - ' . _('Loss') . ' ' . _('after') . ' ' . $Sections[$Section] . '</b></h2></td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
							</tr>', locale_number_format(-$PeriodProfitLoss, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$PeriodBudgetProfitLoss, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$PeriodBudgetProfitLoss - $PeriodProfitLoss, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$PeriodLYProfitLoss, $_SESSION['CompanyRecord']['decimalplaces']));

					if ($TotalIncome != 0) {
						$PrdNPPercent = 100 * (-$PeriodProfitLoss) / $TotalIncome;
					} else {
						$PrdNPPercent = 0;
					}
					if ($TotalBudgetIncome != 0) {
						$BudgetNPPercent = 100 * (-$PeriodBudgetProfitLoss) / $TotalBudgetIncome;
					} else {
						$BudgetNPPercent = 0;
					}
					if ($TotalLYIncome != 0) {
						$LYNPPercent = 100 * (-$PeriodLYProfitLoss) / $TotalLYIncome;
					} else {
						$LYNPPercent = 0;
					}
					printf('<tr>
								<td colspan="2"><h4><i>' . _('P/L Percent after') . ' ' . $Sections[$Section] . '</i></h4></td>
								<td></td>
								<td class="number"><i>%s</i></td>
								<td></td>
								<td class="number"><i>%s</i></td>
								<td></td>
								<td class="number"><i>%s</i></td>
								<td></td>
								<td class="number"><i>%s</i></td>
								</tr><tr><td colspan="6"> </td>
							</tr>', locale_number_format($PrdNPPercent, 1) . '%', locale_number_format($BudgetNPPercent, 1) . '%', locale_number_format($BudgetNPPercent, 1) . '%', locale_number_format($LYNPPercent, 1) . '%');

					echo '<tr>
							<td colspan="2"></td>
							<td colspan="8"><hr /></td>
						</tr>';
				}

			}
			$SectionPrdLY = 0;
			$SectionPrdActual = 0;
			$SectionPrdBudget = 0;

			$Section = $MyRow['sectioninaccounts'];

			if ($_POST['Detail'] == 'Detailed') {
				printf('<tr>
					<td colspan="8"><h2><b>%s</b></h2></td>
					</tr>', $Sections[$MyRow['sectioninaccounts']]);
			}
			++$j;

		}

		if ($MyRow['groupname'] != $ActGrp) {

			if ($MyRow['parentgroupname'] == $ActGrp and $ActGrp != '') { //adding another level of nesting
				$Level++;
			}

			$ParentGroups[$Level] = $MyRow['groupname'];
			$ActGrp = $MyRow['groupname'];
			if ($_POST['Detail'] == 'Detailed') {
				printf('<tr>
					<th colspan="11"><b>%s</b></th>
					</tr>', $MyRow['groupname']);
				echo $TableHeader;
			}
		}

		$AccountPeriodActual = $MyRow['lastprdcfwd'] - $MyRow['firstprdbfwd'];
		$AccountPeriodLY = $MyRow['lylastprdcfwd'] - $MyRow['lyfirstprdbfwd'];
		$AccountPeriodBudget = $MyRow['lastprdbudgetcfwd'] - $MyRow['firstprdbudgetbfwd'];
		$PeriodProfitLoss+= $AccountPeriodActual;
		$PeriodBudgetProfitLoss+= $AccountPeriodBudget;
		$PeriodLYProfitLoss+= $AccountPeriodLY;

		for ($i = 0;$i <= $Level;$i++) {
			if (!isset($GrpPrdLY[$i])) {
				$GrpPrdLY[$i] = 0;
			}
			$GrpPrdLY[$i]+= $AccountPeriodLY;
			if (!isset($GrpPrdActual[$i])) {
				$GrpPrdActual[$i] = 0;
			}
			$GrpPrdActual[$i]+= $AccountPeriodActual;
			if (!isset($GrpPrdBudget[$i])) {
				$GrpPrdBudget[$i] = 0;
			}
			$GrpPrdBudget[$i]+= $AccountPeriodBudget;
		}
		$SectionPrdLY+= $AccountPeriodLY;
		$SectionPrdActual+= $AccountPeriodActual;
		$SectionPrdBudget+= $AccountPeriodBudget;

		if ($_POST['Detail'] == 'Detailed') {

			if (isset($_POST['ShowZeroBalances']) or (!isset($_POST['ShowZeroBalances']) and ($AccountPeriodActual <> 0 or $AccountPeriodBudget <> 0 or $AccountPeriodLY <> 0))) {
				$ActEnquiryURL = '<a href="' . $RootPath . '/GLAccountInquiry.php?Period=' . $_POST['ToPeriod'] . '&amp;Account=' . $MyRow['accountcode'] . '&amp;Show=Yes">' . $MyRow['accountcode'] . '</a>';

				if ($Section == 1) {
					printf('<tr class="striped_row">
								<td>%s</td>
								<td>%s</td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
							</tr>', $ActEnquiryURL, htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false), locale_number_format(-$AccountPeriodActual, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$AccountPeriodBudget, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($AccountPeriodBudget - $AccountPeriodActual, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$AccountPeriodLY, $_SESSION['CompanyRecord']['decimalplaces']));
				} else {
					printf('<tr class="striped_row">
								<td>%s</td>
								<td>%s</td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
							</tr>', $ActEnquiryURL, htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false), locale_number_format($AccountPeriodActual, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($AccountPeriodBudget, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($AccountPeriodBudget - $AccountPeriodActual, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($AccountPeriodLY, $_SESSION['CompanyRecord']['decimalplaces']));
				}
				++$j;
			}
		}
	}
	//end of loop
	

	if ($MyRow['groupname'] != $ActGrp) {
		if ($MyRow['parentgroupname'] != $ActGrp and $ActGrp != '') {
			while ($MyRow['groupname'] != $ParentGroups[$Level] and $Level > 0) {
				if ($_POST['Detail'] == 'Detailed') {
					echo '<tr>
						<td colspan="2"></td>
						<td colspan="6"><hr /></td>
					</tr>';
					$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level] . ' ' . _('total');
				} else {
					$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level];
				}
				if ($Section == 1) {
					/*Income */
					printf('<tr>
								<td colspan="2"><h4><i>%s </i></h4></td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
							</tr>', $ActGrpLabel, locale_number_format(-$GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$GrpPrdBudget[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$GrpPrdBudget[$Level] - $GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$GrpPrdLY[$Level]), $_SESSION['CompanyRecord']['decimalplaces']);
				} else {
					/*Costs */
					printf('<tr>
								<td colspan="2"><h4><i>%s </i></h4></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
								<td class="number">%s</td>
								<td></td>
							</tr>', $ActGrpLabel, locale_number_format($GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($GrpPrdBudget[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($GrpPrdBudget[$Level] - $GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($GrpPrdLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']));
				}
				$GrpPrdLY[$Level] = 0;
				$GrpPrdActual[$Level] = 0;
				$GrpPrdBudget[$Level] = 0;
				$ParentGroups[$Level] = '';
				$Level--;
			} //end while
			//still need to print out the old group totals
			if ($_POST['Detail'] == 'Detailed') {
				echo '<tr>
							<td colspan="2"></td>
							<td colspan="8"><hr /></td>
						</tr>';
				$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level] . ' ' . _('total');
			} else {
				$ActGrpLabel = str_repeat('___', $Level) . $ParentGroups[$Level];
			}

			if ($Section == 1) {
				/*Income */
				printf('<tr>
							<td colspan="2"><h4><i>%s </i></h4></td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
						</tr>', $ActGrpLabel, locale_number_format(-$GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$GrpPrdBudget[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$GrpPrdBudget[$Level] - $GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$GrpPrdLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']));
			} else {
				/*Costs */
				printf('<tr>
							<td colspan="2"><h4><i>%s </i></h4></td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
							<td class="number">%s</td>
							<td></td>
						</tr>', $ActGrpLabel, locale_number_format($GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($GrpPrdBudget[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($GrpPrdBudget[$Level] - $GrpPrdActual[$Level], $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($GrpPrdLY[$Level], $_SESSION['CompanyRecord']['decimalplaces']));
			}
			$GrpPrdLY[$Level] = 0;
			$GrpPrdActual[$Level] = 0;
			$GrpPrdBudget[$Level] = 0;
			$ParentGroups[$Level] = '';
		}
		++$j;
	}

	if ($MyRow['sectioninaccounts'] != $Section) {

		if ($Section == 1) {
			/*Income*/

			echo '<tr>
					<td colspan="3"></td>
					<td><hr /></td>
					<td></td>
					<td><hr /></td>
					<td></td>
					<td><hr /></td>
					<td></td>
					<td><hr /></td>
				</tr>';

			printf('<tr>
						<td colspan="2"><h2>%s</h2></td>
						<td></td>
						<td class="number">%s</td>
						<td></td>
						<td class="number">%s</td>
						<td></td>
						<td class="number">%s</td>
					</tr>', $Sections[$Section], locale_number_format(-$SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$SectionPrdBudget, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$SectionPrdLY, $_SESSION['CompanyRecord']['decimalplaces']));
			$TotalIncome = - $SectionPrdActual;
			$TotalBudgetIncome = - $SectionPrdBudget;
			$TotalLYIncome = - $SectionPrdLY;
		} else {
			echo '<tr>
					<td colspan="2"></td>
					<td><hr /></td>
					<td></td>
					<td><hr /></td>
					<td></td>
					<td><hr /></td>
					<td></td>
					<td><hr /></td>
				</tr>';
			printf('<tr>
						<td colspan="2"><h2>%s</h2></td>
						<td></td>
						<td class="number">%s</td>
						<td></td>
						<td class="number">%s</td>
						<td></td>
						<td class="number">%s</td>
						<td></td>
						<td class="number">%s</td>
					</tr>', $Sections[$Section], locale_number_format($SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($SectionPrdBudget, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($SectionPrdBudget - $SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($SectionPrdLY, $_SESSION['CompanyRecord']['decimalplaces']));
		}
		if ($Section == 2) {
			/*Cost of Sales - need sub total for Gross Profit*/
			echo '<tr>
					<td colspan="2"></td>
					<td colspan="8"><hr /></td>
				</tr>';
			printf('<tr>
						<td colspan="2"><h2>' . _('Gross Profit') . '</h2></td>
						<td></td>
						<td class="number">%s</td>
						<td></td>
						<td class="number">%s</td>
						<td></td>
						<td class="number">%s</td>
					</tr>', locale_number_format($TotalIncome - $SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($TotalBudgetIncome - $SectionPrdBudget, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($TotalLYIncome - $SectionPrdLY, $_SESSION['CompanyRecord']['decimalplaces']));

			if ($TotalIncome != 0) {
				$PrdGPPercent = 100 * ($TotalIncome - $SectionPrdActual) / $TotalIncome;
			} else {
				$PrdGPPercent = 0;
			}
			if ($TotalBudgetIncome != 0) {
				$BudgetGPPercent = 100 * ($TotalBudgetIncome - $SectionPrdBudget) / $TotalBudgetIncome;
			} else {
				$BudgetGPPercent = 0;
			}
			if ($TotalLYIncome != 0) {
				$LYGPPercent = 100 * ($TotalLYIncome - $SectionPrdLY) / $TotalLYIncome;
			} else {
				$LYGPPercent = 0;
			}
			echo '<tr>
					<td colspan="2"></td>
					<td colspan="8"><hr /></td>
				</tr>';
			printf('<tr>
					<td colspan="2"><h4><i>' . _('Gross Profit Percent') . '</i></h4></td>
					<td></td>
					<td class="number"><i>%s</i></td>
					<td></td>
					<td class="number"><i>%s</i></td>
					<td></td>
					<td class="number"><i>%s</i></td>
					</tr><tr><td colspan="6"> </td></tr>', locale_number_format($PrdGPPercent, 1) . '%', locale_number_format($BudgetGPPercent, 1) . '%', locale_number_format($LYGPPercent, 1) . '%');
			++$j;
		}

		$SectionPrdLY = 0;
		$SectionPrdActual = 0;
		$SectionPrdBudget = 0;

		$Section = $MyRow['sectioninaccounts'];

		if ($_POST['Detail'] == 'Detailed' and isset($Sections[$MyRow['sectioninaccounts']])) {
			printf('<tr>
				<td colspan="6"><h2><b>%s</b></h2></td>
				</tr>', $Sections[$MyRow['sectioninaccounts']]);
		}

		++$j;

	}

	echo '<tr>
			<td colspan="2"></td>
			<td colspan="8"><hr /></td>
		</tr>';

	printf('<tr>
				<td colspan="2"><h2><b>' . _('Profit') . ' - ' . _('Loss') . '</b></h2></td>
				<td></td>
				<td class="number">%s</td>
				<td></td>
				<td class="number">%s</td>
				<td></td>
				<td class="number">%s</td>
				<td></td>
				<td class="number">%s</td>
			</tr>', locale_number_format(-$PeriodProfitLoss, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$PeriodBudgetProfitLoss, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format($PeriodBudgetProfitLoss - $PeriodProfitLoss, $_SESSION['CompanyRecord']['decimalplaces']), locale_number_format(-$PeriodLYProfitLoss, $_SESSION['CompanyRecord']['decimalplaces']));

	if ($TotalIncome != 0) {
		$PrdNPPercent = 100 * (-$PeriodProfitLoss) / $TotalIncome;
	} else {
		$PrdNPPercent = 0;
	}
	if ($TotalBudgetIncome != 0) {
		$BudgetNPPercent = 100 * (-$PeriodBudgetProfitLoss) / $TotalBudgetIncome;
	} else {
		$BudgetNPPercent = 0;
	}
	if ($TotalLYIncome != 0) {
		$LYNPPercent = 100 * (-$PeriodLYProfitLoss) / $TotalLYIncome;
	} else {
		$LYNPPercent = 0;
	}
	echo '<tr>
			<td colspan="2"></td>
			<td colspan="8"><hr /></td>
		</tr>';

	printf('<tr>
				<td colspan="2"><h4><i>' . _('Net Profit Percent') . '</i></h4></td>
				<td></td>
				<td class="number"><i>%s</i></td>
				<td></td>
				<td class="number"><i>%s</i></td>
				<td></td>
				<td class="number"><i>%s</i></td>
				<td></td>
				<td class="number"><i>%s</i></td>
				</tr><tr><td colspan="6"> </td>
			</tr>', locale_number_format($PrdNPPercent, 1) . '%', locale_number_format($BudgetNPPercent, 1) . '%', locale_number_format($BudgetNPPercent, 1) . '%', locale_number_format($LYNPPercent, 1) . '%');

	echo '<tr>
			<td colspan="2"></td>
			<td colspan="8"><hr /></td>
		</tr>';
	echo '</table>';
	echo '<br /><div class="centre"><input type="submit" name="SelectADifferentPeriod" value="' . _('Select A Different Period') . '" /></div>';
}
echo '</form>';
include ('includes/footer.php');

?>