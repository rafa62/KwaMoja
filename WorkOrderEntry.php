<?php
include ('includes/DefineWOClass.php');
include ('includes/session.php');
$Title = _('Work Order Entry');
include ('includes/header.php');
include ('includes/SQL_CommonFunctions.php');

echo '<p class="page_title_text">
		<img src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/transactions.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '
	</p>';

/*unique session identifier to ensure that there is no conflict with other order entry sessions on the same machine  */
if (isset($_GET['identifier'])) {
	$Identifier = $_GET['identifier'];
} elseif (isset($_POST['identifier'])) {
	$Identifier = $_POST['identifier'];
} else {
	$Identifier = date('U');
	$_SESSION['WorkOrder' . $Identifier] = new WorkOrder();
}

if (isset($_GET['WO'])) {
	$_POST['WO'] = $_GET['WO'];
}

if (isset($_POST['RequiredBy'])) {
	$_SESSION['WorkOrder' . $Identifier]->RequiredBy = $_POST['RequiredBy'];
} else {
	$_SESSION['WorkOrder' . $Identifier]->RequiredBy = Date($_SESSION['DefaultDateFormat']);
}

if (isset($_POST['StartDate'])) {
	$_SESSION['WorkOrder' . $Identifier]->StartDate = $_POST['StartDate'];
} else {
	$_SESSION['WorkOrder' . $Identifier]->StartDate = Date($_SESSION['DefaultDateFormat']);
}

if (isset($_POST['StockLocation'])) {
	$_SESSION['WorkOrder' . $Identifier]->LocationCode = $_POST['StockLocation'];
}

if (isset($_GET['WO'])) {
	$_SESSION['WorkOrder' . $Identifier]->Load($_GET['WO']);
}

if (isset($_POST['Reference'])) {
	$_SESSION['WorkOrder' . $Identifier]->Reference = $_POST['Reference'];
}

if (isset($_POST['Remark'])) {
	$_SESSION['WorkOrder' . $Identifier]->Remark = $_POST['Remark'];
}

if (isset($_POST['AddToOrder'])) {
	$LocSQL = "SELECT locations.loccode
					FROM locations
					INNER JOIN locationusers
						ON locationusers.loccode=locations.loccode
						AND locationusers.userid='" . $_SESSION['UserID'] . "'
						AND locationusers.canupd=1
					WHERE locations.loccode='" . $_SESSION['WorkOrder' . $Identifier]->LocationCode . "'";
	$LocResult = DB_query($LocSQL);
	$LocRow = DB_fetch_array($LocResult);

	if (is_null($LocRow['loccode']) or $LocRow['loccode'] == '') {
		prnMsg(_('Your security settings do not allow you to create or update new Work Order at this location') . ' ' . $_SESSION['WorkOrder' . $Identifier]->LocationCode, 'error');
		echo '<br /><a href="' . $RootPath . '/SelectWorkOrder.php">' . _('Select an existing work order') . '</a>';
		include ('includes/footer.php');
		exit;
	}
	foreach ($_POST as $Key => $Value) {
		if (substr($Key, 0, 7) == 'StockID') {
			$Index = substr($Key, -1);
			if ($_POST['Quantity' . $Index] > 0) {
				$CheckItemResult = DB_query("SELECT mbflag,
													eoq,
													controlled
												FROM stockmaster
												WHERE stockid='" . $Value . "'");
				if (DB_num_rows($CheckItemResult) == 1) {
					$CheckItemRow = DB_fetch_array($CheckItemResult);
					if ($CheckItemRow['mbflag'] != 'M') {
						prnMsg(_('The item selected cannot be added to a work order because it is not a manufactured item'), 'warn');
						$InputError = true;
					}
				} else {
					prnMsg(_('The item selected cannot be found in the database'), 'error');
					$InputError = true;
				}
				$AlreadyOnOrder = 0;
				foreach ($_SESSION['WorkOrder' . $Identifier]->Items as $WorkOrderItem) {
					if ($WorkOrderItem->StockId == $Value) {
						++$AlreadyOnOrder;
					}
				}
				if ($AlreadyOnOrder > 0) {
					prnMsg(_('This item is already on the work order and cannot be added again'), 'warn');
					$InputError = true;
				}
				if (!$InputError) {
					$_SESSION['WorkOrder' . $Identifier]->AddItemToOrder($Value, '', $_POST['Quantity' . $Index], 0, '');
					if ($CheckItemRow['controlled'] == 1 and $_SESSION['DefineControlledOnWOEntry'] == 1) { //need to add serial nos or batches to determine quantity
						$_SESSION['WorkOrder' . $Identifier]->QuantityRequired = 0;
						$_SESSION['WorkOrder' . $Identifier]->Controlled = 1;
					}
				}
			}
		}
	}
}

if (isset($_POST['Save'])) {
	foreach ($_POST as $Key => $Value) {
		if (substr($Key, 0, 13) == 'OutputStockId') {
			$Index = substr($Key, -1);
			$_SESSION['WorkOrder' . $Identifier]->UpdateItem($Value, '', $_POST['OutputQty' . $Index], '');
		}
	}
	$InputError = false;

	if ($InputError == false) {
		if (!isset($EOQ)) {
			$EOQ = 1;
		}

		$Result = DB_Txn_Begin();

		$CheckSQL = "SELECT wo
						FROM workorders
						WHERE wo='" . $_SESSION['WorkOrder' . $Identifier]->OrderNumber . "'";
		$CheckResult = DB_query($CheckSQL);

		if (DB_num_rows($CheckResult) == 0) {
			// new
			$_SESSION['WorkOrder' . $Identifier]->OrderNumber = GetNextTransNo(40);
			$SQL = "INSERT INTO workorders (wo,
											loccode,
											requiredby,
											startdate,
											reference,
											remark)
										VALUES (
											'" . $_SESSION['WorkOrder' . $Identifier]->OrderNumber . "',
											'" . $_SESSION['WorkOrder' . $Identifier]->LocationCode . "',
											'" . FormatDateForSQL($_SESSION['WorkOrder' . $Identifier]->RequiredBy) . "',
											'" . FormatDateForSQL($_SESSION['WorkOrder' . $Identifier]->StartDate) . "',
											'" . $_SESSION['WorkOrder' . $Identifier]->Reference . "',
											'" . $_SESSION['WorkOrder' . $Identifier]->Remark . "')";
			$InsWOResult = DB_query($SQL);
		} else {
			$SQL = "UPDATE workorders SET loccode='" . $_SESSION['WorkOrder' . $Identifier]->LocationCode . "',
											requiredby='" . FormatDateForSQL($_SESSION['WorkOrder' . $Identifier]->RequiredBy) . "',
											startdate='" . FormatDateForSQL($_SESSION['WorkOrder' . $Identifier]->StartDate) . "',
											reference='" . $_SESSION['WorkOrder' . $Identifier]->Reference . "',
											remark='" . $_SESSION['WorkOrder' . $Identifier]->Remark . "'
										WHERE wo='" . $_SESSION['WorkOrder' . $Identifier]->OrderNumber . "'";
			$UpdWOResult = DB_query($SQL);
		}

		// insert parent item info
		foreach ($_SESSION['WorkOrder' . $Identifier]->Items as $Item) {
			$CostResult = DB_query("SELECT SUM((materialcost+labourcost+overheadcost)*bom.quantity) AS cost,
											bom.loccode
										FROM stockcosts
										INNER JOIN bom
											ON stockcosts.stockid=bom.component
										WHERE bom.parent='" . $Item->StockId . "'
											AND bom.loccode=(SELECT loccode FROM workorders WHERE wo='" . $_SESSION['WorkOrder' . $Identifier]->OrderNumber . "')
											AND bom.effectiveafter<=CURRENT_DATE
											AND bom.effectiveto>=CURRENT_DATE");
			$CostRow = DB_fetch_array($CostResult);
			if (is_null($CostRow['cost']) or $CostRow['cost'] == 0) {
				$Cost = 0;
				prnMsg(_('The cost of this item as accumulated from the sum of the component costs is nil. This could be because there is no bill of material set up ... you may wish to double check this'), 'warn');
			} else {
				$Cost = $CostRow['cost'];
			}

			$CheckSQL = "SELECT wo
							FROM woitems
							WHERE wo='" . $_SESSION['WorkOrder' . $Identifier]->OrderNumber . "'
								AND stockid='" . $Item->StockId . "'";
			$CheckResult = DB_query($CheckSQL);

			if (DB_num_rows($CheckResult) == 0) {
				$SQL = "INSERT INTO woitems (wo,
											stockid,
											qtyreqd,
											stdcost)
										VALUES (
											'" . $_SESSION['WorkOrder' . $Identifier]->OrderNumber . "',
											'" . $Item->StockId . "',
											'" . $Item->QuantityRequired . "',
											'" . $Cost . "'
										)";
				$ErrMsg = _('The work order item could not be added');
			} else {
				$SQL = "UPDATE woitems SET qtyreqd='" . $Item->QuantityRequired . "'
								WHERE wo='" . $_SESSION['WorkOrder' . $Identifier]->OrderNumber . "'
									AND stockid='" . $Item->StockId . "'";
				$ErrMsg = _('The work order item could not be updated');
			}
			$Result = DB_query($SQL, $ErrMsg);
			//Recursively insert real component requirements - see includes/SQL_CommonFunctions.in for function WoRealRequirements
			WoRealRequirements($_SESSION['WorkOrder' . $Identifier]->OrderNumber, $_SESSION['WorkOrder' . $Identifier]->LocationCode, $Item->StockId);

		}

		$Result = DB_Txn_Commit();
		prnMsg(_('The work order has been saved correctly'), 'success');

		unset($NewItem);
	} //end if there were no input errors
	
} //adding a new item to the work order
if (isset($_POST['delete'])) {
	//the link to delete a selected record was clicked instead of the submit button
	$CancelDelete = false; //always assume the best
	// can't delete it there are open work issues
	$HasTransResult = DB_query("SELECT transno
									FROM stockmoves
								WHERE (stockmoves.type= 26 OR stockmoves.type=28)
								AND reference " . LIKE . " '%" . $_POST['WO'] . "%'");
	if (DB_num_rows($HasTransResult) > 0) {
		prnMsg(_('This work order cannot be deleted because it has issues or receipts related to it'), 'error');
		$CancelDelete = true;
	}

	if ($CancelDelete == false) { //ie all tests proved ok to delete
		DB_Txn_Begin();
		$ErrMsg = _('The work order could not be deleted');
		$DbgMsg = _('The SQL used to delete the work order was');
		//delete the worequirements
		$SQL = "DELETE FROM worequirements WHERE wo='" . $_POST['WO'] . "'";
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
		//delete the items on the work order
		$SQL = "DELETE FROM woitems WHERE wo='" . $_POST['WO'] . "'";
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
		//delete the controlled items defined in wip
		$SQL = "DELETE FROM woserialnos WHERE wo='" . $_POST['WO'] . "'";
		$ErrMsg = _('The work order serial numbers could not be deleted');
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);
		// delete the actual work order
		$SQL = "DELETE FROM workorders WHERE wo='" . $_POST['WO'] . "'";
		$ErrMsg = _('The work order could not be deleted');
		$Result = DB_query($SQL, $ErrMsg, $DbgMsg, true);

		DB_Txn_Commit();
		prnMsg(_('The work order has been cancelled'), 'success');

		echo '<p><a href="' . $RootPath . '/SelectWorkOrder.php">' . _('Select an existing outstanding work order') . '</a></p>';
		unset($_POST['WO']);
		for ($i = 1;$i <= $_POST['NumberOfOutputs'];$i++) {
			unset($_POST['OutputItem' . $i]);
			unset($_POST['OutputQty' . $i]);
			unset($_POST['QtyRecd' . $i]);
			unset($_POST['NetLotSNRef' . $i]);
			unset($_POST['HasWOSerialNos' . $i]);
			unset($_POST['WOComments' . $i]);
		}
		include ('includes/footer.php');
		exit;
	}
}

echo '<form method="post" action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '?identifier=' . $Identifier . '" name="form1">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<table>';

if (isset($_POST['WO']) and $_POST['WO'] != _('Not yet allocated')) {
	/* It's anexisting work order so read it in from DB */
	$SQL = "SELECT workorders.loccode,
					requiredby,
					startdate,
					costissued,
					closed,
					reference,
					remark
				FROM workorders
				INNER JOIN locations
					ON workorders.loccode=locations.loccode
				INNER JOIN locationusers
					ON locationusers.loccode=workorders.loccode
					AND locationusers.userid='" . $_SESSION['UserID'] . "'
					AND locationusers.canupd=1
				WHERE workorders.wo='" . $_POST['WO'] . "'";

	$WOResult = DB_query($SQL);
	if (DB_num_rows($WOResult) == 1) {

		$MyRow = DB_fetch_array($WOResult);
		$_SESSION['WorkOrder' . $Identifier]->StartDate = ConvertSQLDate($MyRow['startdate']);
		$_POST['CostIssued'] = $MyRow['costissued'];
		$_POST['Closed'] = $MyRow['closed'];
		$_SESSION['WorkOrder' . $Identifier]->RequiredBy = ConvertSQLDate($MyRow['requiredby']);
		$_SESSION['WorkOrder' . $Identifier]->Reference = $MyRow['reference'];
		$_SESSION['WorkOrder' . $Identifier]->Remark = $MyRow['remark'];
		$_POST['StockLocation'] = $MyRow['loccode'];
		$ErrMsg = _('Could not get the work order items');
		$WOItemsSQL = "SELECT woitems.stockid,
							stockmaster.description,
							qtyreqd,
							qtyrecd,
							stdcost,
							nextlotsnref,
							controlled,
							serialised,
							stockmaster.decimalplaces,
							nextserialno,
							woitems.comments
						FROM woitems
						INNER JOIN stockmaster
							ON woitems.stockid=stockmaster.stockid
						WHERE wo='" . $_POST['WO'] . "'";
		$WOItemsResult = DB_query($WOItemsSQL, $ErrMsg);
		$NumberOfOutputs = DB_num_rows($WOItemsResult);
		$i = 1;
		while ($WOItem = DB_fetch_array($WOItemsResult)) {
			$_POST['OutputItem' . $i] = $WOItem['stockid'];
			$_POST['OutputItemDesc' . $i] = $WOItem['description'];
			$_POST['OutputQty' . $i] = $WOItem['qtyreqd'];
			$_POST['RecdQty' . $i] = $WOItem['qtyrecd'];
			$_POST['WOComments' . $i] = $WOItem['comments'];
			$_POST['DecimalPlaces' . $i] = $WOItem['decimalplaces'];
			if ($WOItem['serialised'] == 1 and $WOItem['nextserialno'] > 0) {
				$_POST['NextLotSNRef' . $i] = $WOItem['nextserialno'];
			} else {
				$_POST['NextLotSNRef' . $i] = $WOItem['nextlotsnref'];
			}
			$_POST['Controlled' . $i] = $WOItem['controlled'];
			$_POST['Serialised' . $i] = $WOItem['serialised'];
			$HasWOSerialNosResult = DB_query("SELECT wo FROM woserialnos WHERE wo='" . $_POST['WO'] . "'");
			if (DB_num_rows($HasWOSerialNosResult) > 0) {
				$_POST['HasWOSerialNos'] = true;
			} else {
				$_POST['HasWOSerialNos'] = false;
			}
			$i++;
		}
	} else {
		if ($EditingExisting == true) {
			prnMsg(_('Your location security settings do not allow you to Update this Work Order'), 'error');
			echo '<br /><a href="' . $RootPath . '/SelectWorkOrder.php">' . _('Select an existing work order') . '</a>';
			include ('includes/footer.php');
			exit;
		}
	}
}
echo '<input type="hidden" name="WO" value="' . $_SESSION['WorkOrder' . $Identifier]->OrderNumber . '" />';

if ($_SESSION['WorkOrder' . $Identifier]->OrderNumber === 0) {
	echo '<tr>
			<td class="label">' . _('Work Order Reference') . ':</td>
			<td>' . _('Not Yet Allocated') . '</td>
		</tr>';
} else {
	echo '<tr>
			<td class="label">' . _('Work Order Reference') . ':</td>
			<td>' . $_SESSION['WorkOrder' . $Identifier]->OrderNumber . '</td>
		</tr>';
}

echo '<tr>
		<td class="label">' . _('Factory Location') . ':</td>
		<td><select name="StockLocation" onChange="ReloadForm(form1.submit)">';
$LocResult = DB_query("SELECT locations.loccode,locationname
						FROM locations
						INNER JOIN locationusers
							ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "'
							AND locationusers.canupd=1
						WHERE locations.usedforwo = 1");
while ($LocRow = DB_fetch_array($LocResult)) {
	if ($_SESSION['WorkOrder' . $Identifier]->LocationCode == $LocRow['loccode']) {
		echo '<option selected="True" value="' . $LocRow['loccode'] . '">' . $LocRow['locationname'] . '</option>';
	} else {
		echo '<option value="' . $LocRow['loccode'] . '">' . $LocRow['locationname'] . '</option>';
	}
}
echo '</select></td></tr>';

echo '<tr>
		<td class="label">' . _('Start Date') . ':</td>
		<td><input type="text" name="StartDate" size="12" maxlength="12" value="' . $_SESSION['WorkOrder' . $Identifier]->StartDate . '" class="date" /></td>
	</tr>';

echo '<tr>
		<td class="label">' . _('Required By') . ':</td>
		<td><input type="text" name="RequiredBy" size="12" maxlength="12" value="' . $_SESSION['WorkOrder' . $Identifier]->RequiredBy . '" class="date" /></td>
	</tr>';

echo '<tr>
		<td class="label">' . _('Reference') . ':</td>
		<td><input type="text" name="Reference"  value="' . $_SESSION['WorkOrder' . $Identifier]->Reference . '" size="12" maxlength="40" /><td>
	</tr>';

echo '<tr>
		<td class="label">' . _('Remark') . ':</td>
		<td><textarea name="Remark" >' . $_SESSION['WorkOrder' . $Identifier]->Remark . '</textarea></td>
		</tr>';

if (isset($WOResult)) {
	echo '<tr><td class="label">' . _('Accumulated Costs') . ':</td>
			  <td class="number">' . locale_number_format($MyRow['costissued'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
}
echo '</table>';

echo '<table>
		<tr>
			<th>' . _('Output Item') . '</th>
			<th>' . _('Comments') . '</th>
			<th>' . _('Qty Required') . '</th>
			<th>' . _('Qty Received') . '</th>
			<th>' . _('Balance Remaining') . '</th>
			<th>' . _('Next Lot/SN Ref') . '</th>
		</tr>';

$j = 0;
if (isset($_SESSION['WorkOrder' . $Identifier]->NumberOfItems)) {
	$i = 0;
	foreach ($_SESSION['WorkOrder' . $Identifier]->Items as $WorkOrderItem) {
		$DescriptionSQL = "SELECT descriptiontranslation AS description
							FROM stockdescriptiontranslations
							WHERE stockid='" . $WorkOrderItem->StockId . "'
								AND language_id='" . $_SESSION['InventoryLanguage'] . "'";
		$DescriptionResult = DB_query($DescriptionSQL);
		$DescriptionRow = DB_fetch_array($DescriptionResult);
		echo '<tr class="striped_row">
				<td>' . $WorkOrderItem->StockId . ' - ' . $DescriptionRow['description'] . '</td>';
		echo '<input type="hidden" name="OutputStockId' . $i . '" value="' . $WorkOrderItem->StockId . '" />';
		echo '<td><textarea style="width:100%" rows="2" cols="50" name="WOComments' . $i . '" >' . $WorkOrderItem->Comments . '</textarea></td>';
		if ($WorkOrderItem->Controlled == 1 and $_SESSION['DefineControlledOnWOEntry'] == 1) {
			echo '<td class="number">' . locale_number_format($WorkOrderItem->QuantityRequired, $WorkOrderItem->DecimalPlaces) . '</td>';
		} else {
			echo '<td><input type="text" required="required" class="number" name="OutputQty' . $i . '" value="' . locale_number_format($WorkOrderItem->QuantityRequired, $WorkOrderItem->DecimalPlaces) . '" size="10" maxlength="10" title="' . _('The input format must be positive numeric') . '" /></td>';
		}
		echo '<td class="number">' . locale_number_format($WorkOrderItem->QuantityReceived, $WorkOrderItem->DecimalPlaces) . '
			</td>';
		echo '<td class="number">' . locale_number_format(($WorkOrderItem->QuantityRequired - $WorkOrderItem->QuantityReceived), $WorkOrderItem->DecimalPlaces) . '
			</td>';
		if ($_POST['Controlled' . $i] == 1) {
			echo '<td><input type="text" name="NextLotSNRef' . $i . '" value="' . $WorkOrderItem->NextLotSerialNumbers . '" /></td>';
			if ($_SESSION['DefineControlledOnWOEntry'] == 1) {
				if ($WorkOrderItem->Serialised == 1) {
					$LotOrSN = _('S/Ns');
				} else {
					$LotOrSN = _('Batches');
				}
				echo '<td><a href="' . $RootPath . '/WOSerialNos.php?WO=' . $_POST['WO'] . '&StockID=' . urlencode($WorkOrderItem->StockId) . '&Description=' . urlencode($DescriptionRow['description']) . '&Serialised=' . urlencode($WorkOrderItem->Serialised) . '&NextSerialNo=' . urlencode($WorkOrderItem->NextLotSerialNumbers) . '">' . $LotOrSN . '</a></td>';
			}
		}
		echo '<td>';
		if ($_SESSION['WikiApp'] != 0) {
			wikiLink('WorkOrder', $_POST['WO'] . $WorkOrderItem->StockId);
		}
		echo '</td>
		</tr>';
		++$i;
	}
}
echo '</table>';

echo '<div class="centre">
		<input type="submit" name="Save" value="' . _('Save') . '" />
		<input type="submit" name="delete" value="' . _('Cancel This Work Order') . '" />
	</div>';

if (isset($_POST['Search']) or isset($_POST['Prev']) or isset($_POST['Next'])) {

	if ($_POST['Keywords'] and $_POST['StockCode']) {
		prnMsg(_('Stock description keywords have been used in preference to the Stock code extract entered'), 'warn');
	}
	//insert wildcard characters in spaces
	$_POST['Keywords'] = mb_strtoupper($_POST['Keywords']);
	$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';
	$SearchCode = '%' . $_POST['StockCode'] . '%';

	if ($_POST['StockCat'] == 'All') {
		$_POST['StockCat'] = '%';
	}
	$SQL = "SELECT  stockmaster.stockid,
					stockdescriptiontranslations.descriptiontranslation AS description,
					stockmaster.units
				FROM stockmaster
				INNER JOIN stockcategory
					ON stockmaster.categoryid=stockcategory.categoryid
				INNER JOIN stockdescriptiontranslations
					ON stockmaster.stockid=stockdescriptiontranslations.stockid
					AND stockdescriptiontranslations.language_id='" . $_SESSION['InventoryLanguage'] . "'
				WHERE (stockcategory.stocktype='F' OR stockcategory.stocktype='M')
					AND stockmaster.description " . LIKE . " '" . $SearchString . "'
					AND stockmaster.categoryid " . LIKE . " '" . $_POST['StockCat'] . "'
					AND stockmaster.stockid " . LIKE . " '" . $SearchCode . "'
					AND stockmaster.discontinued=0
					AND mbflag='M'
				ORDER BY stockmaster.stockid";

	$ErrMsg = _('There was an error retrieving the stock item details');
	$SQLCount = substr($SQL, strpos($SQL, "FROM"));
	$SQLCount = substr($SQLCount, 0, strpos($SQLCount, "ORDER"));
	$SQLCount = 'SELECT COUNT(*) ' . $SQLCount;
	$SearchResult = DB_query($SQLCount, $ErrMsg);

	$MyRow = DB_fetch_array($SearchResult);
	DB_free_result($SearchResult);
	unset($SearchResult);
	$ListCount = $MyRow[0];
	if ($ListCount > 0) {
		$ListPageMax = ceil($ListCount / $_SESSION['DisplayRecordsMax']) - 1;
	} else {
		$ListPageMax = 1;
	}

	if (isset($_POST['Next'])) {
		$Offset = $_POST['CurrPage'] + 1;
	}
	if (isset($_POST['Prev'])) {
		$Offset = $_POST['CurrPage'] - 1;
	}
	if (!isset($Offset)) {
		$Offset = 0;
	}
	if ($Offset < 0) {
		$Offset = 0;
	}
	if ($Offset > $ListPageMax) {
		$Offset = $ListPageMax;
	}
	$SQL = $SQL . ' LIMIT ' . $_SESSION['DisplayRecordsMax'] . ' OFFSET ' . strval($_SESSION['DisplayRecordsMax'] * $Offset);

	$ErrMsg = _('There is a problem selecting the part records to display because');
	$DbgMsg = _('The SQL used to get the part selection was');
	$SearchResult = DB_query($SQL, $ErrMsg, $DbgMsg);

	if (DB_num_rows($SearchResult) == 0) {
		prnMsg(_('There are no products available meeting the criteria specified'), 'info');

		if ($Debug == 1) {
			prnMsg(_('The SQL statement used was') . ':<br />' . $SQL, 'info');
		}
	}

} //end of if search
$SQL = "SELECT categoryid,
			categorydescription
		FROM stockcategory
		WHERE stocktype='F' OR stocktype='M'
		ORDER BY categorydescription";
$Result1 = DB_query($SQL);

echo '<table><tr><td>' . _('Select a stock category') . ':<select name="StockCat">';

if (!isset($_POST['StockCat'])) {
	echo '<option selected="True" value="All">' . _('All') . '</option>';
	$_POST['StockCat'] = 'All';
} else {
	echo '<option value="All">' . _('All') . '</option>';
}

while ($MyRow1 = DB_fetch_array($Result1)) {

	if ($_POST['StockCat'] == $MyRow1['categoryid']) {
		echo '<option selected="True" value=' . $MyRow1['categoryid'] . '>' . $MyRow1['categorydescription'] . '</option>';
	} else {
		echo '<option value=' . $MyRow1['categoryid'] . '>' . $MyRow1['categorydescription'] . '</option>';
	}
}

if (!isset($_POST['Keywords'])) {
	$_POST['Keywords'] = '';
}

if (!isset($_POST['StockCode'])) {
	$_POST['StockCode'] = '';
}

echo '</select>
		<td>' . _('Enter text extracts in the') . ' <b>' . _('description') . '</b>:</td>
		<td><input type="text" name="Keywords" size="20" maxlength="25" value="' . $_POST['Keywords'] . '" /></td>
	</tr>
    <tr>
		<td>&nbsp;</td>
		<td><font size="3"><b>' . _('OR') . ' </b></font>' . _('Enter extract of the') . ' <b>' . _('Stock Code') . '</b>:</td>
		<td><input type="text" name="StockCode" autofocus="autofocus" size="15" maxlength="18" value="' . $_POST['StockCode'] . '" /></td>
	</tr>
	</table>
	<div class="centre">
		<input type="submit" name="Search" value="' . _('Search Now') . '" />
	</div>';

if (isset($SearchResult)) {

	if (DB_num_rows($SearchResult) > 0) {

		echo '<table cellpadding="2">';

		echo '<thead>
				<tr>
					<th class="SortedColumn">' . _('Code') . '</th>
					<th class="SortedColumn">' . _('Description') . '</th>
					<th>' . _('Units') . '</th>
					<th colspan="2"><input type="submit" name="AddToOrder" value="' . _('Add to Work Order') . '" /></th>
				</tr>
			</thead>';
		$j = 1;

		$ItemCodes = array();
		for ($i = 1;$i <= $NumberOfOutputs;$i++) {
			$ItemCodes[] = $_POST['OutputItem' . $i];
		}
		echo '<tbody>';
		while ($MyRow = DB_fetch_array($SearchResult)) {

			if (!in_array($MyRow['stockid'], $ItemCodes)) {

				$SupportedImgExt = array('png', 'jpg', 'jpeg');
				$ImageFileArray = glob($_SESSION['part_pics_dir'] . '/' . $MyRow['stockid'] . '.{' . implode(",", $SupportedImgExt) . '}', GLOB_BRACE);
				$ImageFile = reset($ImageFileArray);
				if (extension_loaded('gd') and function_exists('gd_info') and file_exists($ImageFile)) {
					$ImageSource = '<img src="GetStockImage.php?automake=1&textcolor=FFFFFF&bgcolor=CCCCCC&StockID=' . urlencode($MyRow['stockid']) . '&text=&width=64&height=64" alt="" />';
				} else if (file_exists($ImageFile)) {
					$ImageSource = '<img src="' . $ImageFile . '" height="64" width="64" />';
				} else {
					$ImageSource = _('No Image');
				}

				echo '<tr class="striped_row">
						<td>', $MyRow['stockid'], '</td>
						<td>', $MyRow['description'], '</td>
						<td>', $MyRow['units'], '</td>
						<td>', $ImageSource, '</td>
						<input type="hidden" value="' . $MyRow['stockid'] . '" name="StockID' . $j . '"" />', '
						<td><input type="text" size="10" class="number" value="0" name="Quantity' . $j . '" />', '</td>
					</tr>';

				++$j;
			} //end if not already on work order
			
		} //end of while loop
		
	} //end if more than 1 row to show
	echo '</tbody>
		</table>';

} //end if SearchResults to show
echo '</form>';
include ('includes/footer.php');
?>