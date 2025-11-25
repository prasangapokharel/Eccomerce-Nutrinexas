<?php ob_start(); ?>
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mt-4">Manage Referrals</h1>
        <div>
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#fixReferralsModal">
                <i class="fas fa-wrench me-1"></i> Fix Missing Referrals
            </button>
        </div>
    </div>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= \App\Core\View::url('admin') ?>">Dashboard</a></li>
        <li class="breadcrumb-item active">Referrals</li>
    </ol>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['flash_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Referral Earnings
        </div>
        <div class="card-body">
            <?php if (empty($referralEarnings)): ?>
                <div class="alert alert-info">No referral earnings found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Order</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($referralEarnings as $earning): ?>
                                <tr>
                                    <td><?= $earning['id'] ?></td>
                                    <td>
                                        <?= htmlspecialchars($earning['first_name'] . ' ' . $earning['last_name']) ?>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($earning['email']) ?></small>
                                    </td>
                                    <td>
                                        <a href="<?= \App\Core\View::url('admin/viewOrder/' . $earning['order_id']) ?>">
                                            <?= htmlspecialchars($earning['invoice']) ?>
                                        </a>
                                        <br>
                                        <small class="text-muted">₹<?= number_format($earning['total_amount'], 2) ?></small>
                                    </td>
                                    <td class="text-end">₹<?= number_format($earning['amount'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $earning['status'] === 'pending' ? 'warning' : ($earning['status'] === 'paid' ? 'success' : 'danger') ?>">
                                            <?= ucfirst($earning['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y g:i A', strtotime($earning['created_at'])) ?></td>
                                    <td>
            <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?= $earning['id'] ?>">
                                            Update Status
                                        </button>
                                        
                                        <!-- Status Update Modal -->
                                        <div class="modal fade" id="updateStatusModal<?= $earning['id'] ?>" tabindex="-1" aria-labelledby="updateStatusModalLabel<?= $earning['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="updateStatusModalLabel<?= $earning['id'] ?>">Update Referral Status</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="<?= \App\Core\View::url('admin/updateReferralStatus/' . $earning['id']) ?>" method="post">
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label for="status<?= $earning['id'] ?>" class="form-label">Status</label>
                                                                <select class="form-select" id="status<?= $earning['id'] ?>" name="status" required>
                                                                    <option value="pending" <?= $earning['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                                    <option value="paid" <?= $earning['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                                                    <option value="cancelled" <?= $earning['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                                </select>
                                                            </div>
                                                            <div class="alert alert-warning">
                                                                <strong>Note:</strong> Changing status to "Cancelled" will deduct the amount from the user's balance if it was previously "Pending".
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
            <button type="button" class="btn" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn">Update Status</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Fix Referrals Modal -->
    <div class="modal fade" id="fixReferralsModal" tabindex="-1" aria-labelledby="fixReferralsModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fixReferralsModalLabel">Fix Missing Referral Commissions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This will process all paid orders that don't have referral earnings yet.</p>
                    
                    <div class="alert alert-info">
                        <strong>Note:</strong> This process will:
                        <ul>
                            <li>Find all paid orders without referral earnings</li>
                            <li>Check if the order was placed by a referred user</li>
                            <li>Calculate and add the 5% commission</li>
                            <li>Update the referrer's balance</li>
                        </ul>
                    </div>
                    
                    <form id="fixAllReferralsForm" action="<?= \App\Core\View::url('admin/processMissingReferrals') ?>" method="post">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning">
                                Process All Missing Referrals
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <h6>Fix Referrals for Specific User</h6>
                    <form id="fixUserReferralsForm" action="<?= \App\Core\View::url('admin/processMissingReferrals') ?>" method="post">
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Select User</label>
                            <select name="user_id" id="user_id" class="form-select" required>
                                <option value="">Select User</option>
                                <?php 
                                $userModel = new \App\Models\User();
                                $users = $userModel->all();
                                foreach ($users as $user): 
                                ?>
                                    <option value="<?= $user['id'] ?>">
                                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-grid gap-2">
            <button type="submit" class="btn">
                                Process User's Referrals
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
            <button type="button" class="btn" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); ?>

<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>