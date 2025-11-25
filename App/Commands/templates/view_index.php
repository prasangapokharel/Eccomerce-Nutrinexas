<?php
/**
 * {{className}} Index View
 * 
 * @var array $data
 * @var array $pagination
 * @var string $search
 */
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">{{className}} Management</h3>
                    <a href="/{{routePrefix}}/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New {{modelName}}
                    </a>
                </div>
                
                <div class="card-body">
                    <!-- Search Form -->
                    <form method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" 
                                           name="search" 
                                           class="form-control" 
                                           placeholder="Search {{modelName}}..." 
                                           value="<?= htmlspecialchars($search ?? '') ?>">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-outline-secondary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Flash Messages -->
                    <?php if (\App\Core\Session::hasFlash()): ?>
                        <?php $flash = \App\Core\Session::getFlash(); ?>
                        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
                            <?= htmlspecialchars($flash['message']) ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Data Table -->
                    <?php if (!empty($data)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        {{tableHeaders}}
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data as $item): ?>
                                        <tr>
                                            {{tableRows}}
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php if ($pagination['current_page'] > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?>&search=<?= urlencode($search ?? '') ?>">
                                                Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                        <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?>&search=<?= urlencode($search ?? '') ?>">
                                                Next
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No {{modelName}} found</h5>
                            <p class="text-muted">
                                <?php if ($search): ?>
                                    No results found for "<?= htmlspecialchars($search) ?>"
                                <?php else: ?>
                                    Get started by creating your first {{modelName}}
                                <?php endif; ?>
                            </p>
                            <?php if (!$search): ?>
                                <a href="/{{routePrefix}}/create" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add New {{modelName}}
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
