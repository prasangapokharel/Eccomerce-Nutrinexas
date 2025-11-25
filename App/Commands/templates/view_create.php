<?php
/**
 * {{className}} Create View
 */
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Create New {{modelName}}</h3>
                    <a href="/{{routePrefix}}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
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

                    <form method="POST" action="/{{routePrefix}}" enctype="multipart/form-data">
                        {{formFields}}
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create {{modelName}}
                            </button>
                            <a href="/{{routePrefix}}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
