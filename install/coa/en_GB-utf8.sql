INSERT INTO `accountsection` VALUES (1,'en_GB.utf8','EQUITY, PROVISIONS AND FINANCIAL LIABILITIES ACCOUNTS ');
INSERT INTO `accountsection` VALUES (2,'en_GB.utf8','FORMATION EXPENSES AND FIXED ASSETS ACCOUNTS ');
INSERT INTO `accountsection` VALUES (5,'en_GB.utf8','FINANCIAL ACCOUNTS ');
INSERT INTO `accountsection` VALUES (10,'en_GB.utf8','Capital or branches’ assigned capital and owner’s accounts');
INSERT INTO `accountsection` VALUES (20,'en_GB.utf8','Formation expenses and similar expenses ');
INSERT INTO `accountsection` VALUES (30,'en_GB.utf8','Raw materials and consumables ');
INSERT INTO `accountsection` VALUES (50,'en_GB.utf8','Transferable securities');

INSERT INTO `accountgroups` VALUES ('Cost of Goods Sold','10','en_GB.utf8',2,1,5000,'','0');
INSERT INTO `accountgroups` VALUES ('Promotions','100','en_GB.utf8',5,1,6000,'','0');
INSERT INTO `accountgroups` VALUES ('Revenue','110','en_GB.utf8',1,1,4000,'','0');
INSERT INTO `accountgroups` VALUES ('Sales','120','en_GB.utf8',1,1,10,'','0');
INSERT INTO `accountgroups` VALUES ('Outward Freight','130','en_GB.utf8',2,1,5000,'Cost of Goods Sold','10');
INSERT INTO `accountgroups` VALUES ('BBQs','140','en_GB.utf8',5,1,6000,'Promotions','100');
INSERT INTO `accountgroups` VALUES ('Giveaways','150','en_GB.utf8',5,1,6000,'Promotions','100');
INSERT INTO `accountgroups` VALUES ('Current Assets','20','en_GB.utf8',20,0,1000,'','0');
INSERT INTO `accountgroups` VALUES ('Equity','30','en_GB.utf8',50,0,3000,'','0');
INSERT INTO `accountgroups` VALUES ('Fixed Assets','40','en_GB.utf8',10,0,500,'','0');
INSERT INTO `accountgroups` VALUES ('Income Tax','50','en_GB.utf8',5,1,9000,'','0');
INSERT INTO `accountgroups` VALUES ('Liabilities','60','en_GB.utf8',30,0,2000,'','0');
INSERT INTO `accountgroups` VALUES ('Marketing Expenses','70','en_GB.utf8',5,1,6000,'','0');
INSERT INTO `accountgroups` VALUES ('Operating Expenses','80','en_GB.utf8',5,1,7000,'','0');
INSERT INTO `accountgroups` VALUES ('Other Revenue and Expenses','90','en_GB.utf8',5,1,8000,'','0');

INSERT INTO `chartmaster` VALUES ('1','en_GB.utf8','Default Sales/Discounts','Sales','120');
INSERT INTO `chartmaster` VALUES ('1010','en_GB.utf8','Petty Cash','Current Assets','20');
INSERT INTO `chartmaster` VALUES ('1020','en_GB.utf8','Cash on Hand','Current Assets','20');
INSERT INTO `chartmaster` VALUES ('1030','en_GB.utf8','Cheque Accounts','Current Assets','20');
INSERT INTO `chartmaster` VALUES ('1040','en_GB.utf8','Savings Accounts','Current Assets','20');
INSERT INTO `chartmaster` VALUES ('1050','en_GB.utf8','Payroll Accounts','Current Assets','20');
INSERT INTO `chartmaster` VALUES ('1060','en_GB.utf8','Special Accounts','Current Assets','20');
INSERT INTO `chartmaster` VALUES ('1070','en_GB.utf8','Money Market Investments','Current Assets','20');
INSERT INTO `chartmaster` VALUES ('1080','en_GB.utf8','Short-Term Investments (< 90 days)','Current Assets','20');
INSERT INTO `chartmaster` VALUES ('1090','en_GB.utf8','Interest Receivable','Current Assets','20');
INSERT INTO `chartmaster` VALUES ('1100','en_GB.utf8','Accounts Receivable','Current Assets','20');
INSERT INTO `chartmaster` VALUES ('1150','en_GB.utf8','Allowance for Doubtful Accounts','Current Assets','20');
INSERT INTO `chartmaster` VALUES ('1200','en_GB.utf8','Notes Receivable','Current Assets','20');
INSERT INTO `chartmaster` VALUES ('1250','en_GB.utf8','Income Tax Receivable','Current Assets','20');
INSERT INTO `chartmaster` VALUES ('1300','en_GB.utf8','Prepaid Expenses','Current Assets','20');
INSERT INTO `chartmaster` VALUES ('1350','en_GB.utf8','Advances','Current Assets','20');
INSERT INTO `chartmaster` VALUES ('1400','en_GB.utf8','Supplies Inventory','Current Assets','20');
INSERT INTO `chartmaster` VALUES ('1420','en_GB.utf8','Raw Material Inventory','Current Assets','20');
INSERT INTO `chartmaster` VALUES ('1440','en_GB.utf8','Work in Progress Inventory','Current Assets','20');
INSERT INTO `chartmaster` VALUES ('1460','en_GB.utf8','Finished Goods Inventory','Current Assets','20');
INSERT INTO `chartmaster` VALUES ('1500','en_GB.utf8','Land','Fixed Assets','40');
INSERT INTO `chartmaster` VALUES ('1550','en_GB.utf8','Bonds','Fixed Assets','40');
INSERT INTO `chartmaster` VALUES ('1600','en_GB.utf8','Buildings','Fixed Assets','40');
INSERT INTO `chartmaster` VALUES ('1620','en_GB.utf8','Accumulated Depreciation of Buildings','Fixed Assets','40');
INSERT INTO `chartmaster` VALUES ('1650','en_GB.utf8','Equipment','Fixed Assets','40');
INSERT INTO `chartmaster` VALUES ('1670','en_GB.utf8','Accumulated Depreciation of Equipment','Fixed Assets','40');
INSERT INTO `chartmaster` VALUES ('1700','en_GB.utf8','Furniture & Fixtures','Fixed Assets','40');
INSERT INTO `chartmaster` VALUES ('1710','en_GB.utf8','Accumulated Depreciation of Furniture & Fixtures','Fixed Assets','40');
INSERT INTO `chartmaster` VALUES ('1720','en_GB.utf8','Office Equipment','Fixed Assets','40');
INSERT INTO `chartmaster` VALUES ('1730','en_GB.utf8','Accumulated Depreciation of Office Equipment','Fixed Assets','40');
INSERT INTO `chartmaster` VALUES ('1740','en_GB.utf8','Software','Fixed Assets','40');
INSERT INTO `chartmaster` VALUES ('1750','en_GB.utf8','Accumulated Depreciation of Software','Fixed Assets','40');
INSERT INTO `chartmaster` VALUES ('1760','en_GB.utf8','Vehicles','Fixed Assets','40');
INSERT INTO `chartmaster` VALUES ('1770','en_GB.utf8','Accumulated Depreciation Vehicles','Fixed Assets','40');
INSERT INTO `chartmaster` VALUES ('1780','en_GB.utf8','Other Depreciable Property','Fixed Assets','40');
INSERT INTO `chartmaster` VALUES ('1790','en_GB.utf8','Accumulated Depreciation of Other Depreciable Prop','Fixed Assets','40');
INSERT INTO `chartmaster` VALUES ('1800','en_GB.utf8','Patents','Fixed Assets','40');
INSERT INTO `chartmaster` VALUES ('1850','en_GB.utf8','Goodwill','Fixed Assets','40');
INSERT INTO `chartmaster` VALUES ('2','en_GB.utf8','test','Sales','120');
INSERT INTO `chartmaster` VALUES ('2010','en_GB.utf8','Bank Indedebtedness (overdraft)','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2020','en_GB.utf8','Retainers or Advances on Work','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2050','en_GB.utf8','Interest Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2100','en_GB.utf8','Accounts Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2150','en_GB.utf8','Goods Received Suspense','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2200','en_GB.utf8','Short-Term Loan Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2230','en_GB.utf8','Current Portion of Long-Term Debt Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2250','en_GB.utf8','Income Tax Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2300','en_GB.utf8','GST Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2310','en_GB.utf8','GST Recoverable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2320','en_GB.utf8','PST Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2330','en_GB.utf8','PST Recoverable (commission)','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2340','en_GB.utf8','Payroll Tax Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2350','en_GB.utf8','Withholding Income Tax Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2360','en_GB.utf8','Other Taxes Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2400','en_GB.utf8','Employee Salaries Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2410','en_GB.utf8','Management Salaries Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2420','en_GB.utf8','Director / Partner Fees Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2450','en_GB.utf8','Health Benefits Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2460','en_GB.utf8','Pension Benefits Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2470','en_GB.utf8','Canada Pension Plan Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2480','en_GB.utf8','Employment Insurance Premiums Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2500','en_GB.utf8','Land Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2550','en_GB.utf8','Long-Term Bank Loan','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2560','en_GB.utf8','Notes Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2600','en_GB.utf8','Building & Equipment Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2700','en_GB.utf8','Furnishing & Fixture Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2720','en_GB.utf8','Office Equipment Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2740','en_GB.utf8','Vehicle Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2760','en_GB.utf8','Other Property Payable','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2800','en_GB.utf8','Shareholder Loans','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('2900','en_GB.utf8','Suspense','Liabilities','60');
INSERT INTO `chartmaster` VALUES ('3100','en_GB.utf8','Capital Stock','Equity','30');
INSERT INTO `chartmaster` VALUES ('3200','en_GB.utf8','Capital Surplus / Dividends','Equity','30');
INSERT INTO `chartmaster` VALUES ('3300','en_GB.utf8','Dividend Taxes Payable','Equity','30');
INSERT INTO `chartmaster` VALUES ('3400','en_GB.utf8','Dividend Taxes Refundable','Equity','30');
INSERT INTO `chartmaster` VALUES ('3500','en_GB.utf8','Retained Earnings','Equity','30');
INSERT INTO `chartmaster` VALUES ('4100','en_GB.utf8','Product / Service Sales','Revenue','110');
INSERT INTO `chartmaster` VALUES ('4200','en_GB.utf8','Sales Exchange Gains/Losses','Revenue','110');
INSERT INTO `chartmaster` VALUES ('4500','en_GB.utf8','Consulting Services','Revenue','110');
INSERT INTO `chartmaster` VALUES ('4600','en_GB.utf8','Rentals','Revenue','110');
INSERT INTO `chartmaster` VALUES ('4700','en_GB.utf8','Finance Charge Income','Revenue','110');
INSERT INTO `chartmaster` VALUES ('4800','en_GB.utf8','Sales Returns & Allowances','Revenue','110');
INSERT INTO `chartmaster` VALUES ('4900','en_GB.utf8','Sales Discounts','Revenue','110');
INSERT INTO `chartmaster` VALUES ('5000','en_GB.utf8','Cost of Sales','Cost of Goods Sold','10');
INSERT INTO `chartmaster` VALUES ('5100','en_GB.utf8','Production Expenses','Cost of Goods Sold','10');
INSERT INTO `chartmaster` VALUES ('5200','en_GB.utf8','Purchases Exchange Gains/Losses','Cost of Goods Sold','10');
INSERT INTO `chartmaster` VALUES ('5500','en_GB.utf8','Direct Labour Costs','Cost of Goods Sold','10');
INSERT INTO `chartmaster` VALUES ('5600','en_GB.utf8','Freight Charges','Outward Freight','130');
INSERT INTO `chartmaster` VALUES ('5700','en_GB.utf8','Inventory Adjustment','Cost of Goods Sold','10');
INSERT INTO `chartmaster` VALUES ('5800','en_GB.utf8','Purchase Returns & Allowances','Cost of Goods Sold','10');
INSERT INTO `chartmaster` VALUES ('5900','en_GB.utf8','Purchase Discounts','Cost of Goods Sold','10');
INSERT INTO `chartmaster` VALUES ('6100','en_GB.utf8','Advertising','Marketing Expenses','70');
INSERT INTO `chartmaster` VALUES ('6150','en_GB.utf8','Promotion','Promotions','100');
INSERT INTO `chartmaster` VALUES ('6200','en_GB.utf8','Communications','Marketing Expenses','70');
INSERT INTO `chartmaster` VALUES ('6250','en_GB.utf8','Meeting Expenses','Marketing Expenses','70');
INSERT INTO `chartmaster` VALUES ('6300','en_GB.utf8','Travelling Expenses','Marketing Expenses','70');
INSERT INTO `chartmaster` VALUES ('6400','en_GB.utf8','Delivery Expenses','Marketing Expenses','70');
INSERT INTO `chartmaster` VALUES ('6500','en_GB.utf8','Sales Salaries & Commission','Marketing Expenses','70');
INSERT INTO `chartmaster` VALUES ('6550','en_GB.utf8','Sales Salaries & Commission Deductions','Marketing Expenses','70');
INSERT INTO `chartmaster` VALUES ('6590','en_GB.utf8','Benefits','Marketing Expenses','70');
INSERT INTO `chartmaster` VALUES ('6600','en_GB.utf8','Other Selling Expenses','Marketing Expenses','70');
INSERT INTO `chartmaster` VALUES ('6700','en_GB.utf8','Permits, Licenses & License Fees','Marketing Expenses','70');
INSERT INTO `chartmaster` VALUES ('6800','en_GB.utf8','Research & Development','Marketing Expenses','70');
INSERT INTO `chartmaster` VALUES ('6900','en_GB.utf8','Professional Services','Marketing Expenses','70');
INSERT INTO `chartmaster` VALUES ('7020','en_GB.utf8','Support Salaries & Wages','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7030','en_GB.utf8','Support Salary & Wage Deductions','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7040','en_GB.utf8','Management Salaries','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7050','en_GB.utf8','Management Salary deductions','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7060','en_GB.utf8','Director / Partner Fees','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7070','en_GB.utf8','Director / Partner Deductions','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7080','en_GB.utf8','Payroll Tax','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7090','en_GB.utf8','Benefits','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7100','en_GB.utf8','Training & Education Expenses','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7150','en_GB.utf8','Dues & Subscriptions','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7200','en_GB.utf8','Accounting Fees','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7210','en_GB.utf8','Audit Fees','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7220','en_GB.utf8','Banking Fees','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7230','en_GB.utf8','Credit Card Fees','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7240','en_GB.utf8','Consulting Fees','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7260','en_GB.utf8','Legal Fees','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7280','en_GB.utf8','Other Professional Fees','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7300','en_GB.utf8','Business Tax','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7350','en_GB.utf8','Property Tax','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7390','en_GB.utf8','Corporation Capital Tax','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7400','en_GB.utf8','Office Rent','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7450','en_GB.utf8','Equipment Rental','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7500','en_GB.utf8','Office Supplies','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7550','en_GB.utf8','Office Repair & Maintenance','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7600','en_GB.utf8','Automotive Expenses','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7610','en_GB.utf8','Communication Expenses','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7620','en_GB.utf8','Insurance Expenses','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7630','en_GB.utf8','Postage & Courier Expenses','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7640','en_GB.utf8','Miscellaneous Expenses','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7650','en_GB.utf8','Travel Expenses','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7660','en_GB.utf8','Utilities','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7700','en_GB.utf8','Ammortization Expenses','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7750','en_GB.utf8','Depreciation Expenses','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7800','en_GB.utf8','Interest Expense','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('7900','en_GB.utf8','Bad Debt Expense','Operating Expenses','80');
INSERT INTO `chartmaster` VALUES ('8100','en_GB.utf8','Gain on Sale of Assets','Other Revenue and Expenses','90');
INSERT INTO `chartmaster` VALUES ('8200','en_GB.utf8','Interest Income','Other Revenue and Expenses','90');
INSERT INTO `chartmaster` VALUES ('8300','en_GB.utf8','Recovery on Bad Debt','Other Revenue and Expenses','90');
INSERT INTO `chartmaster` VALUES ('8400','en_GB.utf8','Other Revenue','Other Revenue and Expenses','90');
INSERT INTO `chartmaster` VALUES ('8500','en_GB.utf8','Loss on Sale of Assets','Other Revenue and Expenses','90');
INSERT INTO `chartmaster` VALUES ('8600','en_GB.utf8','Charitable Contributions','Other Revenue and Expenses','90');
INSERT INTO `chartmaster` VALUES ('8900','en_GB.utf8','Other Expenses','Other Revenue and Expenses','90');
INSERT INTO `chartmaster` VALUES ('9100','en_GB.utf8','Income Tax Provision','Income Tax','50');
