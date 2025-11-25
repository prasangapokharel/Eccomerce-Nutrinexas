# Seller Wallet Withdrawal Test - Nepal Bank Transfer

## Overview
This document tests the complete seller wallet withdrawal flow for Nepal bank transfers, including bank account management, withdrawal requests, wallet balance deduction, admin approval, and transaction history.

---

## Test Scenario 1: Seller Adds Bank Account Information

### Prerequisites:
- Seller account created and approved
- Seller logged into dashboard

### Steps:
1. **Navigate to Bank Account Management**
   - Seller clicks "Bank Account" in menu
   - Navigates to `/seller/bank-account`
   - **Expected**: Bank account management page loads

2. **Add Bank Account**
   - Fill in bank details:
     - Bank Name: "Nabil Bank"
     - Account Holder Name: "Yoga Bar Pvt. Ltd."
     - Account Number: "1234567890123"
     - Branch Name: "Kathmandu Branch"
     - SWIFT Code: "NABLNPKT" (optional)
   - Check "Set as default bank account"
   - Click "Add Account"
   - **Expected**: 
     - Success message displayed
     - Bank account saved in database
     - Account appears in "Saved Bank Accounts" list
     - Default badge shown if set as default

3. **Verify Database Entry**
   - Check `seller_bank_accounts` table
   - **Expected**: 
     - Row created with seller_id
     - All fields saved correctly
     - is_default = 1 if checked

4. **Add Multiple Bank Accounts**
   - Add second bank account (e.g., "Nepal Investment Bank")
   - Set as default
   - **Expected**: 
     - Previous default account unset
     - New account set as default
     - Both accounts visible in list

5. **Update Bank Account**
   - Click edit on existing account
   - Update account number
   - Save
   - **Expected**: 
     - Account updated successfully
     - Changes reflected in list

6. **Delete Bank Account**
   - Click delete on non-default account
   - Confirm deletion
   - **Expected**: 
     - Account deleted
     - Cannot delete if it's the only account (or can delete if allowed)

---

## Test Scenario 2: Seller Requests Withdrawal

### Prerequisites:
- Seller has bank account added
- Seller has wallet balance > रु 100

### Steps:
1. **Navigate to Withdrawal Request**
   - Seller clicks "Wallet" in menu
   - Clicks "Request Withdrawal" button
   - OR navigates to `/seller/withdraw-requests/create`
   - **Expected**: Withdrawal request form loads

2. **View Wallet Balance**
   - **Expected**: 
     - Available balance displayed prominently
     - Balance shows correct amount

3. **Fill Withdrawal Form**
   - Enter amount: रु 500.00
   - Payment Method: "Bank Transfer" (only option for Nepal)
   - Select bank account from dropdown
   - Add optional account details if needed
   - Click "Submit Request"
   - **Expected**: 
     - Form validates amount (minimum रु 100)
     - Validates bank account is selected
     - Validates amount doesn't exceed balance
     - Success message displayed
     - Redirected to withdrawal request detail page

4. **Verify Withdrawal Request Created**
   - Check `seller_withdraw_requests` table
   - **Expected**: 
     - Row created with:
       - seller_id = current seller
       - amount = 500.00
       - payment_method = 'bank_transfer'
       - bank_account_id = selected account ID
       - status = 'pending'
       - requested_at timestamp set

5. **Verify Wallet Updated**
   - Check `seller_wallet` table
   - **Expected**: 
     - balance unchanged (not deducted yet)
     - pending_withdrawals increased by रु 500.00
     - total_withdrawals unchanged

6. **Test Validation**
   - Try to withdraw more than balance
   - **Expected**: Error "Insufficient balance"
   - Try to withdraw less than रु 100
   - **Expected**: Error "Minimum withdrawal: रु 100"
   - Try to submit without selecting bank account
   - **Expected**: Error "Please select a bank account"

---

## Test Scenario 3: Wallet Balance Deduction Verification

### Steps:
1. **Check Initial Balance**
   - Note seller's wallet balance before withdrawal request
   - Example: Balance = रु 5,000.00

2. **Create Withdrawal Request**
   - Request withdrawal of रु 1,000.00
   - **Expected**: 
     - Balance remains: रु 5,000.00
     - Pending withdrawals: रु 1,000.00
     - Available balance: रु 4,000.00 (balance - pending)

3. **Admin Approves Withdrawal**
   - Admin approves the withdrawal
   - **Expected**: 
     - Balance deducted: रु 4,000.00 (5,000 - 1,000)
     - Pending withdrawals: रु 0.00
     - Total withdrawals: रु 1,000.00
     - Wallet transaction created

4. **Verify Balance Calculation**
   - Available Balance = balance - pending_withdrawals
   - **Expected**: Calculation correct in UI

---

## Test Scenario 4: Admin Sees Withdrawal Request

### Prerequisites:
- Admin logged in
- Seller has created withdrawal request

### Steps:
1. **Navigate to Seller Withdrawals**
   - Admin clicks "Sellers" → "Withdrawals"
   - OR navigates to `/admin/seller/withdraws`
   - **Expected**: List of all seller withdrawal requests

2. **View Withdrawal List**
   - **Expected**: Table shows:
     - Request ID
     - Seller Name
     - Seller Company Name
     - Amount
     - Payment Method
     - Bank Account Details
     - Status (with color coding)
     - Requested Date
     - Actions (Approve/Reject/View)

3. **Filter by Status**
   - Filter by "Pending"
   - **Expected**: Only pending requests shown
   - Filter by "Approved"
   - **Expected**: Only approved requests shown

4. **View Specific Seller Withdrawals**
   - Navigate to seller details page
   - Click "View Withdrawals"
   - **Expected**: 
     - Only that seller's withdrawals shown
     - Seller information displayed

5. **View Withdrawal Details**
   - Click "View" on a withdrawal request
   - **Expected**: 
     - Full withdrawal details
     - Seller information
     - Bank account details
     - Request history
     - Approve/Reject buttons

---

## Test Scenario 5: Admin Updates Payout Status

### Steps:
1. **Admin Views Pending Withdrawal**
   - Admin opens withdrawal detail page
   - **Expected**: 
     - Status = "Pending"
     - Approve and Reject buttons visible

2. **Admin Approves Withdrawal**
   - Admin clicks "Approve"
   - Optionally adds admin notes
   - Confirms approval
   - **Expected**: 
     - Status updated to "approved" in database
     - approved_at timestamp set
     - approved_by = admin user ID
     - admin_notes saved
     - Success message displayed

3. **Verify Wallet Deduction**
   - Check seller wallet after approval
   - **Expected**: 
     - balance decreased by withdrawal amount
     - pending_withdrawals decreased by withdrawal amount
     - total_withdrawals increased by withdrawal amount

4. **Verify Transaction Created**
   - Check `seller_wallet_transactions` table
   - **Expected**: 
     - Transaction created with:
       - type = 'debit'
       - amount = withdrawal amount
       - description = 'Withdrawal approved - Bank Transfer'
       - withdraw_request_id = withdrawal request ID
       - balance_after = new balance
       - status = 'completed'

5. **Admin Rejects Withdrawal**
   - Admin clicks "Reject" on another request
   - Adds rejection reason
   - **Expected**: 
     - Status updated to "rejected"
     - Wallet balance NOT deducted
     - pending_withdrawals reduced (money back to available)
     - Rejection reason saved

6. **Test Status Updates**
   - Update status to "processing"
   - **Expected**: Status changes correctly
   - Update to "completed"
   - **Expected**: Status changes correctly

---

## Test Scenario 6: Seller Sees Withdrawal History in Wallet

### Prerequisites:
- Seller has made withdrawal requests
- Some requests approved/rejected

### Steps:
1. **Navigate to Wallet Page**
   - Seller clicks "Wallet" in menu
   - Navigates to `/seller/wallet`
   - **Expected**: Wallet dashboard loads

2. **View Wallet Summary**
   - **Expected**: Cards show:
     - Available Balance
     - Total Earnings
     - Total Withdrawals
     - Pending Withdrawals

3. **View Recent Transactions**
   - Scroll to "Recent Transactions" section
   - **Expected**: 
     - Shows last 10 transactions
     - Includes withdrawal transactions
     - Shows:
       - Date/Time
       - Type (Credit/Debit)
       - Description
       - Amount (with +/-)
       - Balance After
       - Status

4. **View Withdrawal in Transactions**
   - Look for withdrawal transaction
   - **Expected**: 
     - Type = "Debit"
     - Description = "Withdrawal approved - Bank Transfer"
     - Amount = negative (e.g., -रु 1,000.00)
     - Balance After = balance after withdrawal
     - Status = "Completed"

5. **View All Transactions**
   - Click "View All Transactions"
   - Navigates to `/seller/wallet/transactions`
   - **Expected**: 
     - Full transaction history
     - Pagination if many transactions
     - All withdrawal transactions visible

6. **Filter Transactions**
   - Check transaction types
   - **Expected**: 
     - Credit transactions (earnings from orders)
     - Debit transactions (withdrawals)
     - Both visible in history

---

## Test Scenario 7: Complete Withdrawal Flow

### End-to-End Test:

1. **Setup**
   - Seller has रु 10,000.00 in wallet
   - Seller has bank account added

2. **Request Withdrawal**
   - Seller requests रु 3,000.00
   - **Expected**: 
     - Request created (status: pending)
     - Wallet: balance = रु 10,000.00, pending = रु 3,000.00
     - Available = रु 7,000.00

3. **Admin Reviews**
   - Admin sees request in list
   - Admin views details
   - **Expected**: 
     - All information correct
     - Bank account details visible

4. **Admin Approves**
   - Admin approves request
   - **Expected**: 
     - Status = approved
     - Wallet: balance = रु 7,000.00, pending = रु 0.00
     - Transaction created

5. **Seller Checks Wallet**
   - Seller views wallet page
   - **Expected**: 
     - Balance = रु 7,000.00
     - Pending = रु 0.00
     - Transaction visible in history
     - Withdrawal request shows "Approved"

6. **Seller Views Request Detail**
   - Seller clicks on withdrawal request
   - **Expected**: 
     - Status = "Approved"
     - Approved date/time shown
     - Bank account details shown

---

## Test Scenario 8: Multiple Withdrawal Requests

### Steps:
1. **Create Multiple Requests**
   - Request 1: रु 1,000.00 (pending)
   - Request 2: रु 2,000.00 (pending)
   - **Expected**: 
     - Both requests created
     - Pending withdrawals = रु 3,000.00
     - Available balance = balance - 3,000

2. **Approve First Request**
   - Admin approves request 1
   - **Expected**: 
     - Request 1 status = approved
     - Balance deducted by रु 1,000.00
     - Pending = रु 2,000.00 (only request 2)

3. **Approve Second Request**
   - Admin approves request 2
   - **Expected**: 
     - Request 2 status = approved
     - Balance deducted by रु 2,000.00
     - Pending = रु 0.00

4. **Verify All Transactions**
   - Check wallet transactions
   - **Expected**: 
     - Two debit transactions
     - Both linked to correct withdrawal requests

---

## Test Scenario 9: Edge Cases

### Steps:
1. **Insufficient Balance After Request**
   - Seller has रु 1,000.00
   - Request रु 500.00 (pending)
   - Try to request another रु 600.00
   - **Expected**: 
     - Error: "Insufficient balance"
     - Available = रु 500.00 (1,000 - 500 pending)

2. **Reject Pending Withdrawal**
   - Admin rejects pending withdrawal
   - **Expected**: 
     - Status = rejected
     - Balance NOT deducted
     - Pending withdrawals reduced
     - Money back to available balance

3. **Delete Bank Account with Pending Withdrawal**
   - Seller has pending withdrawal
   - Try to delete bank account used in withdrawal
   - **Expected**: 
     - Cannot delete (or can delete but withdrawal still references it)
     - Appropriate error/handling

4. **Withdrawal Amount Validation**
   - Try to withdraw रु 0.00
   - **Expected**: Error
   - Try to withdraw negative amount
   - **Expected**: Error
   - Try to withdraw रु 50.00 (below minimum)
   - **Expected**: Error "Minimum withdrawal: रु 100"

---

## Test Scenario 10: Bank Account Management

### Steps:
1. **Add Bank Account from Withdrawal Form**
   - In withdrawal form, click "Add New Bank Account"
   - **Expected**: 
     - Redirects to bank account page
     - Or modal opens to add account

2. **Select Default Account**
   - Multiple accounts exist
   - Select default account in withdrawal form
   - **Expected**: 
     - Default account pre-selected
     - Can change to other account

3. **Bank Account Required**
   - Try to submit withdrawal without account
   - **Expected**: 
     - Error: "Please select a bank account"
     - Form validation prevents submission

---

## Test Checklist

### Bank Account Management:
- [ ] Seller can add bank account
- [ ] Seller can update bank account
- [ ] Seller can delete bank account
- [ ] Seller can set default account
- [ ] Multiple accounts supported
- [ ] Bank account fields validated

### Withdrawal Request:
- [ ] Seller can create withdrawal request
- [ ] Amount validation (min रु 100)
- [ ] Balance validation (cannot exceed balance)
- [ ] Bank account selection required
- [ ] Request saved with correct seller_id
- [ ] Pending withdrawals updated
- [ ] Balance NOT deducted on request (only on approval)

### Admin Management:
- [ ] Admin sees all withdrawal requests
- [ ] Admin sees seller name and company
- [ ] Admin sees bank account details
- [ ] Admin can filter by status
- [ ] Admin can view request details
- [ ] Admin can approve withdrawal
- [ ] Admin can reject withdrawal
- [ ] Admin can add notes

### Wallet Balance:
- [ ] Balance deducted when admin approves
- [ ] Pending withdrawals reduced on approval
- [ ] Total withdrawals updated
- [ ] Balance NOT deducted on rejection
- [ ] Available balance = balance - pending

### Transaction History:
- [ ] Withdrawal transaction created on approval
- [ ] Transaction type = 'debit'
- [ ] Transaction linked to withdrawal request
- [ ] Transaction shows in wallet page
- [ ] Transaction shows in transactions list
- [ ] Balance after calculated correctly

### UI/UX:
- [ ] Wallet page shows correct balances
- [ ] Withdrawal requests list shows all requests
- [ ] Status badges color-coded correctly
- [ ] Bank account dropdown works
- [ ] Forms validate correctly
- [ ] Success/error messages displayed

---

## Expected Database State

### After Withdrawal Request:
```sql
seller_withdraw_requests:
- id, seller_id, amount, payment_method='bank_transfer'
- bank_account_id, status='pending'
- requested_at = NOW()

seller_wallet:
- balance = unchanged
- pending_withdrawals = increased by amount
- total_withdrawals = unchanged
```

### After Admin Approval:
```sql
seller_withdraw_requests:
- status = 'approved'
- approved_at = NOW()
- approved_by = admin_id

seller_wallet:
- balance = decreased by amount
- pending_withdrawals = decreased by amount
- total_withdrawals = increased by amount

seller_wallet_transactions:
- type = 'debit'
- amount = withdrawal amount
- withdraw_request_id = request id
- balance_after = new balance
- status = 'completed'
```

---

## Success Criteria

✅ **All tests pass** when:
1. Seller can add/manage bank accounts
2. Seller can request withdrawal
3. Amount validation works correctly
4. Wallet balance NOT deducted on request
5. Admin sees withdrawal requests with seller info
6. Admin can approve/reject withdrawals
7. Wallet balance deducted ONLY on approval
8. Transaction created on approval
9. Seller sees withdrawal in wallet history
10. All balances calculated correctly

---

## Notes

- Test with various amounts (minimum, maximum, edge cases)
- Test with multiple bank accounts
- Test concurrent withdrawal requests
- Verify database integrity
- Check transaction isolation
- Test error handling
- Verify UI updates correctly
- Test mobile responsiveness

