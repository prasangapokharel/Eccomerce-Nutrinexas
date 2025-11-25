<?php
/**
 * {{className}} Show View
 * 
 * @var array $item
 */
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">{{modelName}} Details</h3>
                    <div>
                        <a href="/{{routePrefix}}/<?= $item['id'] ?>/edit" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="/{{routePrefix}}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
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

                    <div class="row">
                        {{showFields}}
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4">
                        <a href="/{{routePrefix}}/<?= $item['id'] ?>/edit" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit {{modelName}}
                        </a>
                        
                        <form method="POST" action="/{{routePrefix}}/<?= $item['id'] ?>" 
                              style="display: inline-block;" 
                              onsubmit="return confirm('Are you sure you want to delete this {{modelName}}?')">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete {{modelName}}
                            </button>
                        </form>
                        
                        <a href="/{{routePrefix}}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
