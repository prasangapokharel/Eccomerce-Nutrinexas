# Cancel Request Flow - Comprehensive Test Scenarios

## Overview
This document tests the complete cancel request flow when a buyer cancels an order from a seller product, ensuring seller_id is saved, visibility in seller/admin menus, and proper status updates.

---

## Test Scenario 1: Customer Cancels Seller Product Order

### Prerequisites:
- Seller account created and approved
- Seller product added and approved
- Customer account created
- Order placed with seller product

### Steps:
1. **Customer Places Order**
   - Customer adds seller product to cart
   - Proceeds to checkout
   - Completes payment
   - **Expected**: Order created with order_items containing seller_id

2. **Customer Cancels Order**
   - Customer navigates to Orders
   - Views order details
   - Clicks "Cancel Order"
   - Enters cancellation reason: "Changed my mind"
   - Submits cancellation
   - **Expected**: 
     - Cancel log entry created in `order_cancel_log` table
     - `seller_id` field populated with correct seller ID
     - `order_id` linked correctly
     - `reason` saved
     - `status` = 'processing'
     - Order status changed to 'cancelled'
     - Product stock restored

3. **Verify Database Entry**
   - Check `order_cancel_log` table
   - **Expected**: 
     - Row exists with correct order_id
     - seller_id matches the product's seller
     - reason = "Changed my mind"
     - status = 'processing'
     - created_at timestamp set

---

## Test Scenario 2: Seller Views Cancellation Requests

### Steps:
1. **Seller Logs In**
   - Seller logs into dashboard
   - **Expected**: Login successful

2. **Seller Navigates to Cancellations**
   - Seller clicks "Cancellations" in menu
   - Navigates to `/seller/cancellations`
   - **Expected**: 
     - Page loads successfully
     - Shows all cancellation requests for this seller only
     - Does NOT show cancellations from other sellers

3. **Seller Views Cancellation List**
   - **Expected**: Table shows:
     - Order ID
     - Invoice number
     - Customer name and email
     - Order amount
     - Cancellation reason
     - Status (with color coding)
     - Created date
     - View details link

4. **Seller Filters by Status**
   - Click "Processing" filter
   - **Expected**: Only processing cancellations shown
   - Click "Refunded" filter
   - **Expected**: Only refunded cancellations shown
   - Click "Failed" filter
   - **Expected**: Only failed cancellations shown
   - Click "All"
   - **Expected**: All cancellations shown

5. **Seller Views Cancellation Details**
   - Click "View Details" on a cancellation
   - Navigate to `/seller/cancellations/detail/{id}`
   - **Expected**: 
     - Full cancellation details visible
     - Order information displayed
     - Customer information displayed
     - Status update form visible
     - Only cancellations belonging to this seller accessible

---

## Test Scenario 3: Seller Updates Cancellation Status

### Steps:
1. **Seller Views Cancellation**
   - Seller opens cancellation detail page
   - **Expected**: Current status visible

2. **Seller Updates Status**
   - Seller changes status dropdown (e.g., from "Processing" to "Refunded")
   - Clicks "Update Status"
   - **Expected**: 
     - Status updated in database
     - Success message displayed
     - Page refreshed with new status
     - Status badge color changes accordingly

3. **Verify Status Update**
   - Check database `order_cancel_log` table
   - **Expected**: 
     - Status field updated
     - updated_at timestamp changed
     - Status change reflected in seller's cancellation list

4. **Test All Status Options**
   - Update to "Processing" → **Expected**: Yellow badge
   - Update to "Refunded" → **Expected**: Green badge
   - Update to "Failed" → **Expected**: Red badge

---

## Test Scenario 4: Admin Views All Cancellations

### Steps:
1. **Admin Logs In**
   - Admin logs into dashboard
   - **Expected**: Login successful

2. **Admin Navigates to Cancellations**
   - Admin clicks "Cancellations" in menu
   - Navigates to `/admin/cancels`
   - **Expected**: 
     - Page loads successfully
     - Shows ALL cancellation requests from all sellers

3. **Admin Views Cancellation List**
   - **Expected**: Table shows:
     - Order ID
     - Invoice number
     - Customer name and email
     - **Seller Name and Company** (VERIFIED)
     - Order amount
     - Cancellation reason
     - Status (with color coding)
     - Created date
     - Status dropdown for quick update
     - View details link

4. **Verify Seller Information Display**
   - Check table column "Seller"
   - **Expected**: 
     - Seller name displayed
     - Company name displayed below seller name (if available)
     - "N/A" shown if no seller (admin products)

---

## Test Scenario 5: Admin Views Cancellation Details

### Steps:
1. **Admin Clicks View Details**
   - Admin clicks eye icon on a cancellation
   - Navigates to `/admin/cancels/view/{id}`
   - **Expected**: 
     - Full cancellation details page loads

2. **Admin Reviews Information**
   - **Expected**: Page shows:
     - Cancellation Request section:
       - Request ID
       - Order ID
       - Invoice Number
       - Cancellation Reason
       - Status
       - Created/Updated timestamps
     - Order Information section:
       - Order Amount
       - Order Status
     - Customer Information section:
       - Customer Name
       - Customer Email
     - **Seller Information section** (VERIFIED):
       - Seller Name
       - Company Name (if available)
       - Seller Email (if available)
       - Link to "View Seller Details"

3. **Admin Clicks Seller Link**
   - Admin clicks "View Seller Details" link
   - **Expected**: 
     - Redirects to `/admin/seller/details/{seller_id}`
     - Seller details page loads
     - Shows full seller information

---

## Test Scenario 6: Admin Updates Cancellation Status

### Steps:
1. **Admin Views Cancellation**
   - Admin opens cancellation detail page
   - **Expected**: Status update form visible

2. **Admin Updates Status via Detail Page**
   - Admin changes status dropdown
   - Clicks "Update Status"
   - **Expected**: 
     - Status updated in database
     - Success message displayed
     - Page refreshed with new status

3. **Admin Updates Status via List Page**
   - Admin goes back to cancellation list
   - Changes status in dropdown (quick update)
   - Confirms update
   - **Expected**: 
     - Status updated via AJAX
     - Page refreshed
     - New status reflected

4. **Verify Status Persistence**
   - Check both admin and seller views
   - **Expected**: 
     - Status consistent in both views
     - Updated timestamp reflects change

---

## Test Scenario 7: Multiple Sellers - Isolation Test

### Steps:
1. **Setup Multiple Sellers**
   - Create Seller A with products
   - Create Seller B with products
   - Both sellers approved

2. **Create Orders**
   - Customer 1 orders from Seller A
   - Customer 2 orders from Seller B
   - Both customers cancel their orders

3. **Seller A Views Cancellations**
   - Seller A logs in
   - Views cancellations
   - **Expected**: 
     - Only sees cancellation from Customer 1 (Seller A's order)
     - Does NOT see cancellation from Customer 2 (Seller B's order)

4. **Seller B Views Cancellations**
   - Seller B logs in
   - Views cancellations
   - **Expected**: 
     - Only sees cancellation from Customer 2 (Seller B's order)
     - Does NOT see cancellation from Customer 1 (Seller A's order)

5. **Admin Views All Cancellations**
   - Admin views cancellation list
   - **Expected**: 
     - Sees both cancellations
     - Seller A's cancellation shows "Seller A" name
     - Seller B's cancellation shows "Seller B" name
     - Can distinguish between sellers

---

## Test Scenario 8: Order with Multiple Sellers

### Steps:
1. **Create Order with Multiple Seller Products**
   - Customer adds product from Seller A
   - Customer adds product from Seller B
   - Places single order
   - **Expected**: Order created with multiple order_items

2. **Customer Cancels Order**
   - Customer cancels the order
   - **Expected**: 
     - Cancel log created
     - seller_id set to first seller (from first order item)
     - OR multiple cancel logs created (one per seller)

3. **Verify Seller Visibility**
   - Seller A checks cancellations
   - Seller B checks cancellations
   - **Expected**: 
     - Each seller sees cancellation if their product was in order
     - OR only first seller sees it (depending on implementation)

---

## Test Scenario 9: Status Workflow Validation

### Steps:
1. **Initial Status**
   - Customer cancels order
   - **Expected**: Status = 'processing'

2. **Seller Updates to Refunded**
   - Seller updates status to 'refunded'
   - **Expected**: 
     - Status changed
     - Admin sees updated status
     - Customer can see status (if implemented)

3. **Admin Updates to Failed**
   - Admin changes status to 'failed'
   - **Expected**: 
     - Status changed
     - Seller sees updated status
     - Status persists correctly

4. **Status Change History**
   - Check updated_at timestamps
   - **Expected**: 
     - updated_at changes with each status update
     - Timestamps accurate

---

## Test Scenario 10: Edge Cases

### Steps:
1. **Cancel Order with No Seller (Admin Product)**
   - Customer orders admin product (no seller_id)
   - Customer cancels
   - **Expected**: 
     - Cancel log created with seller_id = NULL
     - Admin sees cancellation
     - Seller list shows "N/A" for seller
     - No errors in system

2. **Seller Tries to Access Other Seller's Cancellation**
   - Seller A tries to access Seller B's cancellation ID
   - Direct URL access: `/seller/cancellations/detail/{seller_b_cancel_id}`
   - **Expected**: 
     - Access denied
     - Error message: "Cancellation request not found"
     - Redirected to cancellations list

3. **Invalid Status Update**
   - Seller tries to set invalid status
   - **Expected**: 
     - Validation error
     - Status not changed
     - Error message displayed

4. **Cancel Non-Existent Order**
   - Try to cancel order that doesn't exist
   - **Expected**: 
     - Error handled gracefully
     - Appropriate error message

---

## Test Scenario 11: Database Integrity

### Steps:
1. **Verify Foreign Keys**
   - Check order_cancel_log.order_id references orders.id
   - Check order_cancel_log.seller_id references sellers.id (if not null)
   - **Expected**: 
     - Foreign key constraints work
     - Cannot delete order if cancel log exists (or cascade works)
     - Cannot delete seller if cancel logs exist (or set null)

2. **Verify Data Consistency**
   - Create cancellation
   - Check order_cancel_log.seller_id matches order_items.seller_id
   - **Expected**: 
     - seller_id consistent across tables
     - No orphaned records

3. **Verify Indexes**
   - Check query performance with seller_id filter
   - **Expected**: 
     - Fast queries when filtering by seller_id
     - Index on seller_id exists

---

## Test Scenario 12: UI/UX Validation

### Steps:
1. **Seller Cancellation List**
   - Check responsive design
   - Check status color coding
   - Check filter functionality
   - **Expected**: 
     - Mobile-friendly layout
     - Clear status indicators
     - Filters work smoothly

2. **Admin Cancellation List**
   - Check seller name display
   - Check quick status update
   - Check view details link
   - **Expected**: 
     - Seller information clearly visible
     - Status updates work without page reload
     - Navigation intuitive

3. **Detail Pages**
   - Check information layout
   - Check status update form
   - Check navigation links
   - **Expected**: 
     - Information well-organized
     - Forms functional
     - Links work correctly

---

## Test Checklist

### Database:
- [ ] order_cancel_log table has seller_id column
- [ ] seller_id column has index
- [ ] seller_id can be NULL (for admin products)
- [ ] Foreign key constraints work correctly
- [ ] Data saves correctly when order cancelled

### Seller Functions:
- [ ] Seller can view their cancellations
- [ ] Seller sees only their cancellations
- [ ] Seller can filter by status
- [ ] Seller can view cancellation details
- [ ] Seller can update cancellation status
- [ ] Seller cannot access other sellers' cancellations

### Admin Functions:
- [ ] Admin can view all cancellations
- [ ] Admin sees seller name in list
- [ ] Admin sees seller company name
- [ ] Admin can view cancellation details
- [ ] Admin sees seller information in details
- [ ] Admin can click to view seller details
- [ ] Admin can update cancellation status
- [ ] Admin can update status from list or detail page

### System Validation:
- [ ] seller_id saved correctly when order cancelled
- [ ] Status updates persist correctly
- [ ] Status visible to both seller and admin
- [ ] Status changes reflected immediately
- [ ] No data leakage between sellers
- [ ] Error handling works correctly

---

## Expected Database Schema

```sql
order_cancel_log:
- id (INT, PRIMARY KEY)
- order_id (INT, FOREIGN KEY -> orders.id)
- seller_id (INT, NULLABLE, FOREIGN KEY -> sellers.id, INDEXED)
- reason (TEXT)
- status (ENUM: 'processing', 'refunded', 'failed')
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

---

## Test Data Examples

### Test Order:
- Order ID: 123
- Seller ID: 5 (Yoga Bar)
- Customer: John Doe (john@example.com)
- Amount: रु 1,500.00
- Products: 2 items from seller

### Test Cancellation:
- Cancel ID: 45
- Order ID: 123
- Seller ID: 5
- Reason: "Product not needed anymore"
- Status: processing
- Created: 2025-11-21 10:30:00

---

## Success Criteria

✅ **All tests pass** when:
1. seller_id is correctly saved in order_cancel_log
2. Seller sees only their cancellations
3. Admin sees all cancellations with seller names
4. Both seller and admin can update status
5. Status updates are consistent across views
6. No security issues (sellers can't access other sellers' data)
7. UI displays seller information correctly
8. All database operations work correctly

---

## Notes

- Test with various order scenarios (single seller, multiple sellers, admin products)
- Verify mobile responsiveness
- Check error messages are user-friendly
- Ensure proper logging of status changes
- Verify email notifications if implemented
- Test with large datasets for performance

