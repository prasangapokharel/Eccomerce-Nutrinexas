# Seller Management System - Comprehensive Test Scenarios

## Overview
This document outlines 12 comprehensive test scenarios covering the complete seller management system, including admin controls, seller operations, withdrawals, and business workflows.

---

## Test Scenario 1: Seller Registration & Approval Flow

### Steps:
1. **New Seller Registration**
   - Seller registers with company details
   - Uploads required documents (citizenship, PAN/VAT, logo)
   - Submits registration form
   - **Expected**: Seller account created with status 'inactive' and 'is_approved' = 0

2. **Admin Reviews Seller**
   - Admin navigates to `/admin/seller`
   - Views pending sellers
   - Clicks on seller to view details at `/admin/seller/details/{id}`
   - Reviews all documents
   - **Expected**: All seller information and documents visible

3. **Admin Approves Seller**
   - Admin clicks "Approve" button
   - **Expected**: 
     - Seller status changes to 'active'
     - is_approved = 1
     - approved_at timestamp set
     - Seller can now login

4. **Seller Login After Approval**
   - Seller attempts to login
   - **Expected**: Login successful, redirected to seller dashboard

### Test Data:
- Seller Name: "Test Seller 1"
- Email: "testseller1@example.com"
- Company: "Test Company Ltd"

---

## Test Scenario 2: Seller Rejection & Re-application

### Steps:
1. **Admin Rejects Seller**
   - Admin views pending seller
   - Clicks "Reject" button
   - Enters rejection reason: "Incomplete documentation"
   - Submits rejection
   - **Expected**: 
     - Seller status remains 'inactive'
     - is_approved = 0
     - rejection_reason saved
     - Seller cannot login

2. **Seller Views Rejection**
   - Seller attempts login
   - **Expected**: Error message showing rejection reason

3. **Seller Updates Documents**
   - Seller updates missing documents
   - Resubmits for approval
   - **Expected**: New approval request created

---

## Test Scenario 3: Product Submission & Approval

### Steps:
1. **Seller Adds Product**
   - Seller logs in
   - Navigates to Products > Create
   - Fills product details (name, price, description, images)
   - Submits product
   - **Expected**: 
     - Product created with approval_status = 'pending'
     - Product not visible on public site

2. **Admin Reviews Product**
   - Admin navigates to `/admin/seller/products`
   - Views pending products
   - Clicks product to view details
   - Reviews product information
   - **Expected**: Full product details visible

3. **Admin Approves Product**
   - Admin clicks "Approve"
   - Adds approval notes (optional)
   - **Expected**: 
     - Product approval_status = 'approved'
     - Product status = 'active'
     - Product visible on public site

4. **Customer Views Product**
   - Customer browses products
   - **Expected**: Approved product appears in listings

---

## Test Scenario 4: Product Rejection & Edit

### Steps:
1. **Admin Rejects Product**
   - Admin reviews product
   - Clicks "Reject"
   - Enters rejection reason: "Inappropriate content"
   - **Expected**: 
     - Product approval_status = 'rejected'
     - Product status = 'inactive'
     - Product not visible on public site

2. **Seller Views Rejection**
   - Seller checks product status
   - **Expected**: Rejection reason visible

3. **Seller Edits Product**
   - Seller updates product content
   - Resubmits for approval
   - **Expected**: New approval request created

---

## Test Scenario 5: Order Processing Flow

### Steps:
1. **Customer Places Order**
   - Customer adds seller product to cart
   - Proceeds to checkout
   - Completes payment
   - **Expected**: 
     - Order created with status 'pending'
     - Order item linked to seller
     - Seller notified

2. **Seller Views Order**
   - Seller logs in
   - Navigates to Orders
   - **Expected**: New order visible in pending orders

3. **Seller Accepts Order**
   - Seller reviews order details
   - Clicks "Accept Order"
   - **Expected**: 
     - Order status changes to 'processing'
     - Customer notified

4. **Seller Updates Order Status**
   - Seller marks order as "Shipped"
   - Adds tracking number
   - **Expected**: 
     - Order status updated
     - Customer can track order

5. **Order Completion**
   - Order delivered
   - Seller marks as "Delivered"
   - **Expected**: 
     - Order status = 'completed'
     - Seller earnings calculated
     - Commission deducted

---

## Test Scenario 6: Withdrawal Request Flow

### Steps:
1. **Seller Checks Wallet**
   - Seller navigates to Wallet
   - **Expected**: Current balance displayed
   - Transaction history visible

2. **Seller Adds Bank Account**
   - Seller navigates to Bank Account
   - Adds bank details (account holder, bank name, account number)
   - Sets as default
   - **Expected**: Bank account saved

3. **Seller Requests Withdrawal**
   - Seller navigates to Withdraw Requests
   - Clicks "Create Withdrawal"
   - Selects bank account
   - Enters amount (within available balance)
   - Submits request
   - **Expected**: 
     - Withdrawal request created with status 'pending'
     - Wallet balance remains unchanged (frozen)
     - Request visible in seller's withdrawal list

4. **Admin Reviews Withdrawal**
   - Admin navigates to `/admin/seller/withdraws`
   - Views pending withdrawals
   - Clicks on withdrawal or seller
   - Reviews bank account details
   - **Expected**: Full withdrawal details visible

5. **Admin Approves Withdrawal**
   - Admin clicks "Approve"
   - Adds admin notes (optional)
   - **Expected**: 
     - Withdrawal status = 'approved'
     - Seller wallet balance deducted
     - Transaction recorded
     - Seller notified

6. **Admin Marks as Completed**
   - After payment processed
   - Admin clicks "Mark Complete"
   - Adds payment notes
   - **Expected**: 
     - Withdrawal status = 'completed'
     - completed_at timestamp set

---

## Test Scenario 7: Withdrawal Rejection

### Steps:
1. **Admin Rejects Withdrawal**
   - Admin reviews withdrawal request
   - Clicks "Reject"
   - Enters reason: "Insufficient documentation"
   - **Expected**: 
     - Withdrawal status = 'rejected'
     - Wallet balance unfrozen (available again)
     - Seller notified with reason

2. **Seller Views Rejection**
   - Seller checks withdrawal status
   - **Expected**: Rejection reason visible

3. **Seller Resubmits**
   - Seller updates bank account if needed
   - Creates new withdrawal request
   - **Expected**: New request created

---

## Test Scenario 8: Seller Status Management

### Steps:
1. **Admin Suspends Seller**
   - Admin views seller details
   - Edits seller
   - Changes status to 'suspended'
   - **Expected**: 
     - Seller status = 'suspended'
     - Seller cannot login
     - Seller products hidden from public

2. **Seller Attempts Login**
   - Seller tries to login
   - **Expected**: Error: "Account suspended"

3. **Admin Reactivates Seller**
   - Admin changes status back to 'active'
   - **Expected**: 
     - Seller can login again
     - Products visible again

---

## Test Scenario 9: Commission Management

### Steps:
1. **Admin Sets Commission Rate**
   - Admin edits seller
   - Sets commission rate to 15%
   - **Expected**: Commission rate saved

2. **Order Processing with Commission**
   - Customer places order for रु 1000
   - Order completed
   - **Expected**: 
     - Seller earns: रु 850 (85%)
     - Platform commission: रु 150 (15%)
     - Seller wallet credited with रु 850

3. **Admin Views Commission Report**
   - Admin checks seller statistics
   - **Expected**: Total commission earned visible

---

## Test Scenario 10: Multiple Sellers & Products

### Steps:
1. **Multiple Sellers Register**
   - 3 different sellers register
   - All get approved
   - **Expected**: All sellers active

2. **Each Seller Adds Products**
   - Seller 1 adds 5 products
   - Seller 2 adds 3 products
   - Seller 3 adds 7 products
   - **Expected**: All products pending approval

3. **Admin Approves Products**
   - Admin approves all products
   - **Expected**: 
     - All products visible on site
     - Products grouped by seller
     - Seller profiles accessible

4. **Customer Views Seller Stores**
   - Customer visits `/seller/{company_name}`
   - **Expected**: 
     - Seller profile visible
     - Only that seller's products shown
     - Logo and banner displayed

---

## Test Scenario 11: Bulk Operations

### Steps:
1. **Admin Views All Sellers**
   - Admin navigates to `/admin/seller`
   - **Expected**: 
     - All sellers listed
     - Statistics visible (total, approved, pending)
     - Filters working (status, approval)

2. **Admin Views All Withdrawals**
   - Admin navigates to `/admin/seller/withdraws`
   - **Expected**: 
     - All seller withdrawals listed
     - Statistics visible (total, pending, approved, completed)
     - Filter by status working

3. **Admin Views All Seller Products**
   - Admin navigates to `/admin/seller/products`
   - **Expected**: 
     - All seller products listed
     - Filter by approval status working
     - Seller information visible

---

## Test Scenario 12: Complete Business Cycle

### Steps:
1. **Seller Onboarding**
   - Seller registers → Admin approves → Seller active

2. **Product Listing**
   - Seller adds 10 products → Admin approves all → Products live

3. **Sales Generation**
   - 5 customers place orders → Seller accepts → Orders processed → Orders completed

4. **Earnings Accumulation**
   - Seller wallet shows earnings from completed orders
   - Commission deducted correctly

5. **Withdrawal Process**
   - Seller requests withdrawal → Admin approves → Admin marks complete

6. **Reporting**
   - Admin views seller statistics
   - Seller views own analytics
   - **Expected**: All data accurate and consistent

---

## Test Checklist

### Admin Functions:
- [ ] View all sellers
- [ ] Filter sellers by status/approval
- [ ] View seller details
- [ ] Approve seller
- [ ] Reject seller with reason
- [ ] Edit seller information
- [ ] Suspend/activate seller
- [ ] View seller statistics
- [ ] View all withdrawals
- [ ] Approve withdrawal
- [ ] Reject withdrawal
- [ ] Mark withdrawal as complete
- [ ] View seller products
- [ ] Approve/reject products

### Seller Functions:
- [ ] Register account
- [ ] Login after approval
- [ ] View dashboard
- [ ] Add/edit products
- [ ] View orders
- [ ] Accept/reject orders
- [ ] Update order status
- [ ] View wallet balance
- [ ] Add bank account
- [ ] Request withdrawal
- [ ] View withdrawal history
- [ ] View analytics

### System Validations:
- [ ] Unapproved sellers cannot login
- [ ] Suspended sellers cannot login
- [ ] Pending products not visible publicly
- [ ] Rejected products not visible publicly
- [ ] Withdrawal amount cannot exceed balance
- [ ] Commission calculated correctly
- [ ] Wallet balance updates correctly
- [ ] All transactions recorded

---

## Expected Outcomes Summary

1. **Full Admin Control**: Admin can manage all aspects of sellers, products, and withdrawals
2. **Proper Workflow**: All approval processes work correctly
3. **Financial Accuracy**: Commissions and withdrawals calculated correctly
4. **Security**: Only approved sellers can access system
5. **Transparency**: All actions logged and visible
6. **User Experience**: Clear status indicators and error messages

---

## Notes

- All monetary values in NPR (रु)
- All timestamps in Asia/Kathmandu timezone
- Test with various amounts and edge cases
- Verify database consistency after each operation
- Check email notifications if implemented
- Test on mobile and desktop views

