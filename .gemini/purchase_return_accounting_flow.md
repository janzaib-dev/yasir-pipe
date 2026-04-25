# Purchase Return Accounting Flow

## Overview
When a purchase return (partial or full) is processed, the system automatically creates comprehensive accounting entries to maintain accurate financial records.

## Accounting Entries Created

### 1. **Journal Voucher (Debit Note)**
**Purpose:** Records the return of goods and reduces vendor liability

**Entries:**
```
Dr. Accounts Payable (Vendor)    Rs. XXX
    Cr. Purchase Expense          Rs. XXX
```

**Created by:** `TransactionService::createPurchaseReturnVoucher()`
- **Voucher Type:** Journal (TYPE_JOURNAL)
- **Status:** Posted
- **Party:** Vendor
- **Narration:** "Purchase Return #[Invoice]"

---

### 2. **Payment Voucher (Receipt) - If Refund Received**
**Purpose:** Records cash/bank refund received from vendor

**Entries:**
```
Dr. Cash/Bank Account            Rs. XXX
    Cr. Accounts Payable          Rs. XXX
```

**Created by:** `PurchaseController::processPurchaseReturn()`
- **Voucher Type:** Receipt (TYPE_RECEIPT)
- **Status:** Posted
- **Party:** Vendor
- **Narration:** "Refund for Return #[Invoice]"

**Note:** This is created for EACH payment account if multiple refund methods are used.

---

### 3. **Stock Movements**
**Purpose:** Track inventory reduction

**Entry:**
```
Type: OUT
Ref: PURCHASE_RETURN
Qty: [Returned Pieces]
```

**Created by:** Bulk insert in `stock_movements` table

---

### 4. **Warehouse Stock Updates**
**Purpose:** Reduce physical inventory

**Logic:**
- Calculates current stock from `quantity (boxes) × pieces_per_box`
- Subtracts returned pieces
- Updates both `total_pieces` and `quantity` (boxes)

---

### 5. **Vendor Ledger Update**
**Purpose:** Update vendor balance for legacy reports

**Calculation:**
```
Balance Change = -(Net Amount - Paid Amount)
New Closing Balance = Current Balance + Balance Change
```

---

## Example Scenario

### Partial Return:
**Original Purchase:** Rs. 1,000 (5.1 boxes, 11 pieces @ Rs. 100/piece)
**Return:** 0.1 boxes (1 piece) = Rs. 100

**Accounting Entries:**

1. **Journal Entry (Debit Note):**
   ```
   Dr. Accounts Payable (Vendor)    100.00
       Cr. Purchase Expense          100.00
   ```

2. **If Refund Received (Rs. 100 cash):**
   ```
   Dr. Cash Account                 100.00
       Cr. Accounts Payable          100.00
   ```

3. **Stock Movement:**
   - Type: OUT
   - Qty: 1 piece
   - Ref: PURCHASE_RETURN #PR-001

4. **Warehouse Stock:**
   - Before: 11 pieces (5.1 boxes)
   - After: 10 pieces (5.0 boxes)

5. **Vendor Balance:**
   - Before: Rs. 1,000 (payable)
   - After: Rs. 900 (payable)

---

### Full Return:
**Original Purchase:** Rs. 1,000 (11 pieces)
**Return:** All 11 pieces = Rs. 1,000

**Result:**
- Purchase Status: "Returned"
- Vendor Balance: Rs. 0
- Stock: 0 pieces
- All accounting entries reversed

---

## Database Tables Updated

1. **purchase_returns** - Return header
2. **purchase_return_items** - Return line items
3. **voucher_masters** - Voucher headers (Journal + Receipt)
4. **voucher_details** - Voucher line items (Dr/Cr entries)
5. **stock_movements** - Inventory tracking
6. **warehouse_stocks** - Physical inventory
7. **vendor_ledgers** - Vendor balance snapshot
8. **purchases** - Status updated to "Returned" (full return only)

---

## Financial Impact Summary

### On Balance Sheet:
- **Assets (Inventory):** Decreases by return amount
- **Liabilities (Accounts Payable):** Decreases by return amount
- **Assets (Cash/Bank):** Increases by refund amount (if received)

### On Profit & Loss:
- **Purchase Expense:** Decreases (Credit entry)
- **Net Effect:** Improves profitability by return amount

---

## Verification Checklist

After processing a return, verify:

✅ Journal voucher created in `voucher_masters` (TYPE_JOURNAL)
✅ Receipt voucher created if refund received (TYPE_RECEIPT)
✅ Stock movements recorded (type = 'out')
✅ Warehouse stock reduced correctly
✅ Vendor balance updated
✅ Purchase status = "Returned" (if full return)
✅ Purchase index shows updated amounts

---

## Notes

- All entries are created within a **database transaction** for data integrity
- If any step fails, entire return is rolled back
- Partial returns do NOT change purchase status (remains "approved")
- Full returns change purchase status to "Returned"
- Multiple refund accounts supported (cash + bank, etc.)
