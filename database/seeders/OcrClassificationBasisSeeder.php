<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OcrClassificationBasisSeeder extends Seeder
{
    public function run(): void
    {
        // Only seed if training data table is empty
        if (DB::table('document_training_data')->count() > 0) {
            return;
        }

        $trainingData = $this->getTrainingData();

        foreach ($trainingData as $typeKey => $samples) {
            $type = DB::table('document_types')->where('key', $typeKey)->first();

            if (!$type) {
                continue;
            }

            foreach ($samples as $sample) {
                DB::table('document_training_data')->insert([
                    'document_type_id' => $type->id,
                    'sample_text'      => $sample['text'],
                    'keywords'         => $sample['keywords'],
                    'status'           => 'active',
                    'created_by'       => 1,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        }
    }

    private function getTrainingData(): array
    {
        return [
            // ─── Sales Invoice ────────────────────────────────────────
            'invoice' => [
                [
                    'text' => 'TAX INVOICE Invoice No INV-2025-0042 Date 15-Jan-2025 Due Date 14-Feb-2025 Bill To M/s Sharma Enterprises GSTIN 07AABCS1234H1Z5 Address 45 Nehru Place New Delhi 110019 Ship To Same as above HSN Code Description Qty Rate Amount 8471 Laptop Dell Inspiron 15 2 45000.00 90000.00 8443 HP LaserJet Printer 1 18500.00 18500.00 Sub Total 108500.00 CGST 9% 9765.00 SGST 9% 9765.00 Total 128030.00 Amount in Words Rupees One Lakh Twenty Eight Thousand Thirty Only Bank Details Account Name ScanOCR Pvt Ltd Account No 50200012345678 IFSC HDFC0001234 Terms Payment due within 30 days Authorized Signatory',
                    'keywords' => 'tax invoice,invoice no,inv,bill to,gstin,hsn,cgst,sgst,sub total,due date,amount in words,authorized signatory',
                ],
                [
                    'text' => 'INVOICE Invoice Number WB-INV-0089 Invoice Date 22/03/2025 Customer Name Rajesh Trading Co Customer GSTIN 27AADCR5678M1ZP Billing Address Shop No 12 Crawford Market Mumbai 400001 Particulars Qty Unit Price Taxable Value Office Chair Ergonomic 10 8500 85000 Standing Desk Adjustable 5 22000 110000 Monitor Arm Dual 10 3500 35000 Subtotal 230000 Discount 5% 11500 Net Amount 218500 IGST 18% 39330 Grand Total 257830 Payment Terms Net 15 days E&OE',
                    'keywords' => 'invoice,invoice number,customer gstin,billing address,particulars,taxable value,subtotal,discount,igst,grand total,payment terms',
                ],
                [
                    'text' => 'SALES INVOICE Seller ScanOCR Private Limited CIN U72200DL2020PTC123456 GSTIN 07AABCW1234H1Z5 Invoice Ref SI/2025/00156 Date of Issue 05-Apr-2025 Buyer ABC Corporation Ltd GSTIN 29AABCA5678K1Z2 Place of Supply Karnataka Item Description SAC HSN Qty Rate Per Amount Annual Software License 997331 1 250000 Nos 250000 Implementation Service 998314 1 75000 Nos 75000 Total Before Tax 325000 IGST at 18% 58500 Total After Tax 383500 Total Invoice Value INR 3,83,500 Due Date 05-May-2025',
                    'keywords' => 'sales invoice,seller,cin,gstin,invoice ref,buyer,place of supply,sac,implementation,total before tax,total after tax,total invoice value',
                ],
            ],

            // ─── Proforma Invoice ─────────────────────────────────────
            'proforma' => [
                [
                    'text' => 'PROFORMA INVOICE Proforma No PRO-2025-0034 Date 10-Feb-2025 Valid Until 10-Mar-2025 To Mehta Industries Pvt Ltd Address Plot 78 MIDC Pune 411018 GSTIN 27AABCM9876K1Z3 Description Qty Unit Rate Amount Industrial Conveyor Belt 2 Nos 185000 370000 Motor Assembly 3HP 4 Nos 42000 168000 Control Panel PLC 1 Set 95000 95000 Total 633000 GST 18% 113940 Grand Total 746940 Note This is not a tax invoice Delivery 4-6 weeks from order confirmation Payment 50% advance balance before dispatch',
                    'keywords' => 'proforma invoice,proforma no,valid until,not a tax invoice,delivery,advance,order confirmation,dispatch',
                ],
                [
                    'text' => 'PROFORMA INVOICE Reference PRO/WB/2025/067 Date 18-Mar-2025 Validity 30 days Client Global Tech Solutions GSTIN 06AABCG4567L1Z8 Contact Mr Vikram Singh Email vikram@globaltech.in Sr Description HSN Qty Rate Amount 1 ERP Software License 997331 1 500000 500000 2 Customization Charges 998314 1 200000 200000 3 Training 5 days 998393 5 15000 75000 4 Annual Maintenance 997331 1 100000 100000 Subtotal 875000 CGST 9% 78750 SGST 9% 78750 Total 1032500 Terms Quotation valid for 30 days 40% advance on confirmation Balance on delivery Taxes extra as applicable',
                    'keywords' => 'proforma,reference,validity,client,customization,training,annual maintenance,quotation valid,advance on confirmation,taxes extra',
                ],
                [
                    'text' => 'PRO-FORMA INVOICE No WB-PF-2025-112 Issued 01-May-2025 Expires 31-May-2025 Prepared For Sunrise Exports Ltd Attn Mr Anil Kapoor Address SEZ Unit 5 Kandla Gujarat GSTIN 24AABCS3456N1Z1 Item Code Description UOM Qty Unit Price Total WB-SRV-01 Cloud Hosting Setup Nos 1 45000 45000 WB-SRV-02 Data Migration Service Nos 1 80000 80000 WB-SRV-03 API Integration Nos 3 25000 75000 Net Amount 200000 GST 18% IGST 36000 Total Payable 236000 Validity This proforma is valid for 30 days Payment 100% advance for new clients Delivery Within 15 working days',
                    'keywords' => 'pro-forma,issued,expires,prepared for,cloud hosting,data migration,api integration,net amount,total payable,validity,working days',
                ],
            ],

            // ─── Credit Note ──────────────────────────────────────────
            'credit_note' => [
                [
                    'text' => 'CREDIT NOTE Credit Note No CN-2025-0018 Date 20-Feb-2025 Against Invoice INV-2025-0042 dated 15-Jan-2025 Customer M/s Sharma Enterprises GSTIN 07AABCS1234H1Z5 Reason Goods returned damaged in transit Item Description Qty Rate Amount Laptop Dell Inspiron 15 1 45000.00 45000.00 Less CGST 9% 4050.00 Less SGST 9% 4050.00 Total Credit Amount 53100.00 Amount Credited to Customer Account Remarks Replacement will be shipped within 7 days Authorized Signatory',
                    'keywords' => 'credit note,cn,against invoice,goods returned,damaged,credit amount,credited to customer,replacement,reason',
                ],
                [
                    'text' => 'CREDIT MEMO No WB-CN-2025-045 Date 12-Apr-2025 Original Invoice WB-INV-0089 dated 22/03/2025 Issued To Rajesh Trading Co GSTIN 27AADCR5678M1ZP Reason for Credit Pricing error on original invoice overcharged Description Original Price Correct Price Difference Office Chair Ergonomic x10 8500 7800 7000 Credit Subtotal 7000 Less IGST 18% 1260 Total Credit 8260 This credit note reduces the outstanding balance on your account Approved By Finance Manager',
                    'keywords' => 'credit memo,original invoice,issued to,reason for credit,pricing error,overcharged,credit subtotal,outstanding balance,approved by',
                ],
                [
                    'text' => 'CREDIT NOTE Ref CN/2025/WB/078 Issue Date 28-Mar-2025 Linked Invoice SI/2025/00156 Customer ABC Corporation Ltd GSTIN 29AABCA5678K1Z2 Nature of Credit Service not rendered as per scope Particulars Amount Annual Software License partial 3 months unused 62500 IGST 18% on above 11250 Net Credit Value 73750 Adjustment This amount will be adjusted against next invoice Note Original invoice value was 383500 Credit percentage 19.2% Issued By Accounts Department ScanOCR Pvt Ltd',
                    'keywords' => 'credit note,linked invoice,nature of credit,service not rendered,net credit value,adjustment,adjusted against,credit percentage,accounts department',
                ],
            ],

            // ─── Delivery Note / Challan ──────────────────────────────
            'delivery_note' => [
                [
                    'text' => 'DELIVERY CHALLAN DC No DN-2025-0091 Date 18-Jan-2025 Dispatch From ScanOCR Warehouse Sector 62 Noida UP 201301 Deliver To M/s Sharma Enterprises 45 Nehru Place New Delhi 110019 Contact Person Mr Rahul Sharma Phone 9876543210 Vehicle No DL-01-AB-1234 Transporter BlueDart Logistics LR No BD2025001234 Sr Item Description Qty UOM 1 Laptop Dell Inspiron 15 2 Nos 2 HP LaserJet Printer 1 Nos Total Packages 2 Boxes Weight 12.5 Kg Remarks Handle with care fragile items Dispatched By Warehouse Manager Received By signature date',
                    'keywords' => 'delivery challan,dc no,dispatch from,deliver to,vehicle no,transporter,lr no,total packages,weight,handle with care,dispatched by,received by',
                ],
                [
                    'text' => 'DELIVERY NOTE No WB-DN-2025-156 Delivery Date 25-Mar-2025 Reference Invoice WB-INV-0089 Customer Rajesh Trading Co Delivery Address Shop No 12 Crawford Market Mumbai 400001 Mode of Transport Road Courier Delhivery AWB No 1234567890 Items Delivered Sr Description Ordered Delivered Pending 1 Office Chair Ergonomic 10 10 0 2 Standing Desk Adjustable 5 3 2 3 Monitor Arm Dual 10 10 0 Note 2 Standing Desks pending due to stock shortage will deliver by 30-Mar-2025 Receiver Name Stamp Date',
                    'keywords' => 'delivery note,delivery date,reference invoice,delivery address,mode of transport,awb no,items delivered,ordered,pending,stock shortage,receiver name',
                ],
                [
                    'text' => 'DISPATCH CHALLAN Challan No DC/WB/2025/234 Date of Dispatch 02-May-2025 Consignor ScanOCR Pvt Ltd GSTIN 07AABCW1234H1Z5 Consignee Sunrise Exports Ltd SEZ Unit 5 Kandla Gujarat GSTIN 24AABCS3456N1Z1 Purpose of Dispatch Supply against PO No PO-SUN-2025-045 Eway Bill No 3312 4567 8901 Valid Until 04-May-2025 Description of Goods Qty Weight Server Hardware 2 45kg Network Equipment 1 8kg Cables and Accessories 1 lot 3kg Total Weight 56 Kg Packages 4 Driver Name Suresh Kumar DL No DL-0420110012345 Vehicle DL-01-CD-5678',
                    'keywords' => 'dispatch challan,consignor,consignee,purpose of dispatch,eway bill,valid until,description of goods,driver name,vehicle,packages',
                ],
            ],

            // ─── Receipt ──────────────────────────────────────────────
            'receipt' => [
                [
                    'text' => 'PAYMENT RECEIPT Receipt No RCP-2025-0067 Date 14-Feb-2025 Received From M/s Sharma Enterprises Amount Received Rs 128030.00 Rupees One Lakh Twenty Eight Thousand Thirty Only Payment Mode NEFT Reference No HDFC2025021400123 Against Invoice INV-2025-0042 dated 15-Jan-2025 Balance Due Nil Payment Status Full and Final Settlement Received By Accounts Department ScanOCR Pvt Ltd',
                    'keywords' => 'payment receipt,receipt no,received from,amount received,payment mode,neft,reference no,against invoice,balance due,full and final,received by',
                ],
                [
                    'text' => 'RECEIPT No WB-RCP-2025-134 Date 28-Mar-2025 Received with thanks from Rajesh Trading Co the sum of Rupees Two Lakh Fifty Seven Thousand Eight Hundred Thirty Only Rs 257830 by Cheque No 456789 dated 25-Mar-2025 drawn on ICICI Bank Andheri Branch towards payment of Invoice WB-INV-0089 Previous Balance 257830 Amount Received 257830 Current Balance 0.00 For ScanOCR Pvt Ltd Authorized Signatory Cashier Stamp',
                    'keywords' => 'receipt,received with thanks,cheque no,drawn on,towards payment,previous balance,current balance,authorized signatory,cashier',
                ],
                [
                    'text' => 'MONEY RECEIPT Receipt Number RCP/WB/2025/201 Receipt Date 10-May-2025 Customer Name ABC Corporation Ltd Customer ID CUST-0045 Amount Rs 200000.00 Two Lakhs Only Mode of Payment Bank Transfer UTR No UTIB2025051000456 Bank AXIS Bank Payment Against Advance for PO WB-PO-2025-089 Narration Advance payment 50% as per proforma PRO/WB/2025/067 Outstanding After Payment Rs 832500.00 Acknowledgement This receipt confirms payment received in our bank account Issued By Finance Team ScanOCR',
                    'keywords' => 'money receipt,receipt number,customer name,amount,mode of payment,utr no,payment against,advance,narration,outstanding,acknowledgement',
                ],
            ],

            // ─── Purchase Order ───────────────────────────────────────
            'purchase_order' => [
                [
                    'text' => 'PURCHASE ORDER PO Number PO-WB-2025-0078 Date 05-Jan-2025 Delivery Date 20-Jan-2025 Vendor TechSupply India Pvt Ltd Vendor GSTIN 27AABCT4567M1Z9 Address Unit 34 Electronic City Bangalore 560100 Ship To ScanOCR Warehouse Sector 62 Noida UP 201301 Sr Item Code Description UOM Qty Unit Price Total 1 DELL-INS15 Laptop Dell Inspiron 15 Nos 5 42000 210000 2 HP-LJ-PRO HP LaserJet Pro Nos 3 17500 52500 Subtotal 262500 GST 18% 47250 Grand Total 309750 Terms Net 30 days Delivery FOB destination Warranty 1 year standard Approved By Purchase Manager',
                    'keywords' => 'purchase order,po number,delivery date,vendor,ship to,item code,uom,unit price,fob,warranty,approved by,purchase manager',
                ],
                [
                    'text' => 'PO PURCHASE ORDER No WB/PO/2025/112 Order Date 15-Mar-2025 Required By 30-Mar-2025 Supplier Furniture World GSTIN 07AABCF2345K1Z6 Supplier Address 23 Kirti Nagar Delhi 110015 Deliver To Our Office 4th Floor Tower B Cyber City Gurgaon Billing Address Same as registered office Item Description Specification Qty Rate Amount Ergonomic Office Chair High back mesh 20 7800 156000 Adjustable Standing Desk Electric 120x60cm 10 21000 210000 Dual Monitor Arm Clamp mount 20 3200 64000 Total Before Tax 430000 IGST 18% 77400 Total Order Value 507400 Special Instructions Assembly required at site Delivery in single lot preferred Contact warehouse manager before delivery Payment Terms 50% advance 50% on delivery',
                    'keywords' => 'purchase order,order date,required by,supplier,deliver to,specification,total before tax,total order value,special instructions,assembly,payment terms',
                ],
                [
                    'text' => 'PURCHASE ORDER Ref PO-2025-SRV-034 Date 22-Apr-2025 Vendor Global Cloud Services Inc Address 100 Tech Park Hyderabad 500081 GSTIN 36AABCG7890P1Z4 Category Service Purchase Description of Services Sr Service Description SAC Period Rate Amount 1 Cloud Server Hosting 998315 12 months 25000/mo 300000 2 SSL Certificate Wildcard 998316 1 year 8000 8000 3 CDN Service 998315 12 months 5000/mo 60000 4 Email Hosting 50 users 998319 12 months 3000/mo 36000 Net Value 404000 GST 18% 72720 Total PO Value 476720 Validity This PO is valid for 15 days Authorized Signatory CTO ScanOCR',
                    'keywords' => 'purchase order,service purchase,sac,cloud server,ssl certificate,cdn,email hosting,net value,total po value,validity,authorized,cto',
                ],
            ],

            // ─── Purchase Bill ────────────────────────────────────────
            'bill' => [
                [
                    'text' => 'TAX INVOICE BILL Supplier TechSupply India Pvt Ltd GSTIN 27AABCT4567M1Z9 CIN U51909KA2015PTC078901 Invoice No TS-INV-2025-3456 Date 20-Jan-2025 Bill To ScanOCR Pvt Ltd GSTIN 07AABCW1234H1Z5 Address Sector 62 Noida UP 201301 Against PO PO-WB-2025-0078 HSN Description Qty Rate Amount 8471 Laptop Dell Inspiron 15 5 42000 210000 8443 HP LaserJet Pro 3 17500 52500 Taxable Amount 262500 IGST 18% 47250 Round Off 0.00 Total 309750 Amount in Words Three Lakhs Nine Thousand Seven Hundred Fifty Only Bank Details A/C 12345678901234 IFSC SBIN0001234 Payment Due 20-Feb-2025',
                    'keywords' => 'tax invoice,bill,supplier,cin,against po,taxable amount,igst,round off,amount in words,bank details,payment due',
                ],
                [
                    'text' => 'VENDOR INVOICE Bill No FW-2025-7890 Bill Date 30-Mar-2025 Vendor Furniture World GSTIN 07AABCF2345K1Z6 PAN AABCF2345K Billed To ScanOCR Pvt Ltd Our PO Ref WB/PO/2025/112 Delivery Challan Ref FW-DC-2025-456 dated 28-Mar-2025 Particulars HSN Qty Rate Taxable Ergonomic Office Chair 9401 20 7800 156000 Adjustable Standing Desk 9403 10 21000 210000 Dual Monitor Arm 8302 20 3200 64000 Total Taxable Value 430000 CGST 9% 38700 SGST 9% 38700 Invoice Total 507400 TDS Applicable under 194C at 1% Deduct TDS 4300 Net Payable 503100 E-Invoice IRN abcdef1234567890 QR Code Generated',
                    'keywords' => 'vendor invoice,bill no,bill date,pan,our po ref,delivery challan ref,taxable,tds applicable,deduct tds,net payable,e-invoice,irn,qr code',
                ],
                [
                    'text' => 'PURCHASE BILL From Global Cloud Services Inc GSTIN 36AABCG7890P1Z4 Bill Reference GCS-INV-2025-0567 Date 01-May-2025 Service Period May 2025 to Apr 2026 Bill To ScanOCR Private Limited GSTIN 07AABCW1234H1Z5 PO Reference PO-2025-SRV-034 SAC Description Period Amount 998315 Cloud Server Hosting 12 months 300000 998316 SSL Certificate Wildcard 1 year 8000 998315 CDN Service 12 months 60000 998319 Email Hosting 50 users 12 months 36000 Subtotal 404000 IGST 18% 72720 Total Bill Amount 476720 Due Date 31-May-2025 Late Payment Interest 1.5% per month',
                    'keywords' => 'purchase bill,bill reference,service period,bill to,po reference,sac,subtotal,total bill amount,due date,late payment,interest',
                ],
            ],

            // ─── Debit Note ───────────────────────────────────────────
            'debit_note' => [
                [
                    'text' => 'DEBIT NOTE Debit Note No DBN-WB-2025-0012 Date 25-Jan-2025 To TechSupply India Pvt Ltd GSTIN 27AABCT4567M1Z9 Against Bill TS-INV-2025-3456 dated 20-Jan-2025 Reason for Debit 1 unit Laptop Dell Inspiron received with damaged screen returned to vendor Description Qty Rate Amount Laptop Dell Inspiron 15 damaged 1 42000 42000 IGST 18% 7560 Total Debit Amount 49560 Deduction This amount will be deducted from next payment Goods returned via courier AWB DL2025012500789 Prepared By Purchase Department Approved By Finance Head',
                    'keywords' => 'debit note,debit note no,against bill,reason for debit,damaged,returned to vendor,total debit amount,deduction,deducted from,goods returned,prepared by',
                ],
                [
                    'text' => 'DEBIT NOTE No WB-DBN-2025-034 Issue Date 05-Apr-2025 Vendor Furniture World GSTIN 07AABCF2345K1Z6 Reference Bill FW-2025-7890 dated 30-Mar-2025 Nature of Claim Rate difference as per agreed PO price vs billed price Item PO Rate Billed Rate Qty Difference Ergonomic Office Chair 7500 7800 20 6000 Total Claim Before Tax 6000 CGST 9% 540 SGST 9% 540 Net Debit Value 7080 Request Please adjust this amount in your next invoice or issue a credit note within 7 days Raised By Accounts Payable ScanOCR',
                    'keywords' => 'debit note,vendor,reference bill,nature of claim,rate difference,po rate,billed rate,net debit value,adjust,credit note,accounts payable',
                ],
                [
                    'text' => 'DEBIT MEMO Ref DBN/WB/2025/056 Date 15-May-2025 Supplier Global Cloud Services Inc GSTIN 36AABCG7890P1Z4 Against Invoice GCS-INV-2025-0567 Reason Service downtime exceeding SLA 99.9% uptime guarantee Actual uptime 97.2% in May 2025 Penalty as per contract clause 8.3 Calculation Monthly hosting charge 25000 SLA breach penalty 10% 2500 Days affected 8 out of 31 Pro-rata deduction 6452 Service Credit Amount 6452 IGST 18% 1161 Total Debit 7613 Action Required Credit to be applied to June 2025 invoice Authorized By CTO ScanOCR',
                    'keywords' => 'debit memo,supplier,against invoice,service downtime,sla,uptime,penalty,contract clause,pro-rata,service credit,action required',
                ],
            ],

            // ─── Payment Voucher ──────────────────────────────────────
            'payment' => [
                [
                    'text' => 'PAYMENT VOUCHER Voucher No PAY-WB-2025-0089 Date 20-Feb-2025 Pay To TechSupply India Pvt Ltd Amount Rs 309750.00 Three Lakhs Nine Thousand Seven Hundred Fifty Only Against Bill TS-INV-2025-3456 dated 20-Jan-2025 Less TDS u/s 194C at 1% 2625.00 Net Payment 307125.00 Mode of Payment NEFT Bank HDFC Bank Noida Branch UTR No HDFC20250220001234 Debit Account Vendor Payments A/C Credit Account HDFC Current A/C Narration Payment against purchase bill for laptops and printers Approved By Finance Manager Prepared By Accounts Payable',
                    'keywords' => 'payment voucher,pay to,against bill,less tds,net payment,mode of payment,neft,utr no,debit account,credit account,narration,approved by',
                ],
                [
                    'text' => 'PAYMENT ADVICE Payment Ref PAY/WB/2025/145 Payment Date 02-Apr-2025 Beneficiary Furniture World Account No 9876543210123 IFSC ICIC0001234 Bank ICICI Bank Kirti Nagar Amount Paid Rs 503100.00 Breakup Bill Amount 507400.00 Less TDS 1% 4300.00 Net Paid 503100.00 Bills Covered Bill No FW-2025-7890 dated 30-Mar-2025 Amount 507400 TDS Deducted 4300 TDS Certificate will be issued quarterly Remittance sent via RTGS Ref ICICR2025040200567 For ScanOCR Pvt Ltd Accounts Department',
                    'keywords' => 'payment advice,payment ref,beneficiary,account no,ifsc,amount paid,less tds,net paid,bills covered,tds certificate,remittance,rtgs',
                ],
                [
                    'text' => 'VENDOR PAYMENT Voucher PAY-2025-SRV-067 Date 30-May-2025 Payee Global Cloud Services Inc Payment Amount 476720.00 Payment Method Bank Transfer NEFT UTR UTIB2025053000890 Invoice Reference GCS-INV-2025-0567 Invoice Amount 476720 TDS Deducted Nil Reason Exempt service no TDS applicable Ledger Entry Dr Cloud Services Expense 404000 Dr Input IGST 72720 Cr Bank Account HDFC 476720 Remarks Annual cloud services payment as per PO-2025-SRV-034 Processed By AP Team Authorized By CFO',
                    'keywords' => 'vendor payment,payee,payment amount,payment method,invoice reference,tds deducted,exempt,ledger entry,cloud services,processed by,authorized by,cfo',
                ],
            ],

            // ─── Journal Voucher ──────────────────────────────────────
            'journal' => [
                [
                    'text' => 'JOURNAL VOUCHER Voucher No JV-WB-2025-0034 Date 31-Jan-2025 Narration Depreciation entry for January 2025 as per schedule Sr Account Head Debit Credit 1 Depreciation Expense Computer Equipment 15000 2 Depreciation Expense Furniture 8000 3 Depreciation Expense Vehicles 12000 4 Accumulated Depreciation Computer 15000 5 Accumulated Depreciation Furniture 8000 6 Accumulated Depreciation Vehicles 12000 Total 35000 35000 Reference Fixed Asset Register Jan-2025 Prepared By Accountant Checked By Senior Accountant Approved By Finance Manager',
                    'keywords' => 'journal voucher,voucher no,narration,depreciation,account head,debit,credit,accumulated,fixed asset register,prepared by,checked by',
                ],
                [
                    'text' => 'JOURNAL ENTRY JV No JV/WB/2025/078 Entry Date 31-Mar-2025 Type Adjusting Entry Period End Q4 FY 2024-25 Description Provision for doubtful debts and prepaid expense adjustment Account Particulars Dr Amount Cr Amount Bad Debts Expense 45000 Provision for Doubtful Debts 45000 Prepaid Insurance 12000 Insurance Expense 12000 Rent Receivable 30000 Rent Income 30000 Total Debits 87000 Total Credits 87000 Supporting Documents Ageing report Insurance policy Rent agreement Entered By Junior Accountant Verified By Audit Team',
                    'keywords' => 'journal entry,adjusting entry,period end,provision,doubtful debts,prepaid,account particulars,total debits,total credits,supporting documents,verified by',
                ],
                [
                    'text' => 'JOURNAL VOUCHER No JV-2025-ADJ-012 Date 30-Apr-2025 Category Inter-company Transfer Narration Transfer of shared service costs from HO to Branch Office Particulars Ledger Debit Rs Credit Rs Branch Office Expense A/C 125000 Head Office Shared Services A/C 125000 Being allocation of IT infrastructure costs for Apr-2025 Basis of Allocation Headcount ratio HO 60% Branch 40% Total shared cost 312500 Branch share 40% 125000 Cost Center Branch-Mumbai-01 Approved By Group CFO Document Ref Internal memo ITC/2025/04-15',
                    'keywords' => 'journal voucher,inter-company,transfer,shared service,allocation,head office,branch,headcount ratio,cost center,group cfo,internal memo',
                ],
            ],

            // ─── Contra Voucher ───────────────────────────────────────
            'contra' => [
                [
                    'text' => 'CONTRA VOUCHER Voucher No CV-WB-2025-0015 Date 10-Jan-2025 Type Cash Deposit to Bank Narration Depositing excess cash from petty cash to bank account Debit HDFC Bank Current Account 50000 Credit Cash in Hand Petty Cash 50000 Amount Rs 50000.00 Fifty Thousand Only Deposit Slip No DEP2025011000345 Bank HDFC Bank Noida Sector 62 Branch Prepared By Cashier Approved By Accounts Manager',
                    'keywords' => 'contra voucher,cash deposit,bank,petty cash,deposit slip,debit,credit,cash in hand,cashier,accounts manager',
                ],
                [
                    'text' => 'CONTRA ENTRY Voucher CV/WB/2025/034 Date 15-Mar-2025 Nature Cash Withdrawal from Bank Description Withdrawal of cash for office expenses and petty cash replenishment Account Debit Credit Cash in Hand 100000 HDFC Bank Current A/C 100000 Cheque No Self 789012 dated 15-Mar-2025 Purpose Petty cash replenishment 60000 Office supplies purchase 25000 Courier and postage 15000 Cash Balance Before 5000 Cash Balance After 105000 Authorized By Finance Head Counter Signed Admin Manager',
                    'keywords' => 'contra entry,cash withdrawal,replenishment,cheque no,self,purpose,cash balance,authorized by,counter signed',
                ],
                [
                    'text' => 'CONTRA VOUCHER No CV-2025-BT-008 Date 28-Apr-2025 Transaction Type Inter-Bank Transfer Narration Transfer from HDFC to ICICI for vendor payment funding From Account HDFC Bank Current A/C No 50200012345678 To Account ICICI Bank Current A/C No 123456789012 Transfer Amount Rs 500000.00 Five Lakhs Only Mode NEFT UTR Reference HDFC2025042800456 Reason ICICI account to be used for vendor payments in May 2025 Ledger Dr ICICI Bank A/C 500000 Cr HDFC Bank A/C 500000 Processed By Treasury Team Approved By CFO',
                    'keywords' => 'contra voucher,inter-bank transfer,from account,to account,transfer amount,neft,utr,treasury,dr,cr,processed by',
                ],
            ],

            // ─── GRN (Goods Receipt Note) ─────────────────────────────
            'grn' => [
                [
                    'text' => 'GOODS RECEIPT NOTE GRN No GRN-WB-2025-0045 Date 20-Jan-2025 Received From TechSupply India Pvt Ltd Against PO PO-WB-2025-0078 DC/Invoice Ref TS-DC-2025-890 Received At ScanOCR Warehouse Sector 62 Noida Inspected By Mr Amit Kumar Sr Item Description PO Qty Received Accepted Rejected 1 Laptop Dell Inspiron 15 5 5 5 0 2 HP LaserJet Pro 3 3 3 0 Total Items 8 Condition Good no visible damage Remarks All items received in original sealed packaging Serial numbers verified and recorded Quality Check Passed Received By Store Keeper Approved By Warehouse Manager',
                    'keywords' => 'goods receipt note,grn,received from,against po,inspected by,po qty,received,accepted,rejected,condition,serial numbers,quality check,store keeper',
                ],
                [
                    'text' => 'GRN GOODS RECEIVED NOTE No WB-GRN-2025-089 Receipt Date 28-Mar-2025 Supplier Furniture World Challan Ref FW-DC-2025-456 PO Reference WB/PO/2025/112 Location Office 4th Floor Tower B Cyber City Gurgaon Item Ordered Recd Short Excess Remarks Office Chair 20 20 0 0 OK Assembled Standing Desk 10 8 2 0 2 units pending Monitor Arm 20 20 0 0 OK Summary Total Ordered 50 Total Received 48 Short Supply 2 Pending Delivery Expected by 30-Mar-2025 Inspection Status All received items inspected and accepted Discrepancy Report Raised for 2 short desks GRN Prepared By Stores Incharge Verified By Purchase Officer',
                    'keywords' => 'grn,goods received,receipt date,supplier,challan ref,short,excess,pending delivery,inspection status,discrepancy report,stores incharge,purchase officer',
                ],
                [
                    'text' => 'MATERIAL RECEIPT REPORT GRN Reference GRN/WB/2025/112 Date of Receipt 02-May-2025 Vendor Global Cloud Services Inc Delivery Mode Courier Delhivery AWB 9876543210 PO No PO-2025-SRV-034 Items Received Description Qty Condition Server Hardware Dell PowerEdge R740 2 Good sealed box Network Switch Cisco 48-port 1 Good sealed box Patch Cables Cat6 50 Good bundled Rack Mount Kit 2 Good Physical Verification Done by IT Team Asset Tags Generated TAG-2025-0456 to TAG-2025-0460 Storage Location Server Room B2 Temperature Humidity Check OK Installation Scheduled 05-May-2025 Received By IT Infrastructure Lead Acknowledged By Vendor Representative',
                    'keywords' => 'material receipt,grn reference,vendor,delivery mode,awb,items received,condition,physical verification,asset tags,storage location,installation scheduled',
                ],
            ],
        ];
    }
}
