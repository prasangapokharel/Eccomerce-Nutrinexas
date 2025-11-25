<?php
/**
 * Premium Success Component
 * White theme with clean, minimal design
 */
?>

<style>
    .success-container {
        text-align: center;
        padding: 3rem 2rem;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        max-width: 400px;
        margin: 0 auto;
    }

    .success-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 1.5rem;
        position: relative;
    }

    .success-circle {
        fill: none;
        stroke: #10b981;
        stroke-width: 3;
        stroke-linecap: round;
        stroke-dasharray: 251;
        stroke-dashoffset: 251;
        animation: circle-draw 0.8s ease-out forwards;
        transform-origin: center;
    }

    .success-tick {
        fill: none;
        stroke: #10b981;
        stroke-width: 4;
        stroke-linecap: round;
        stroke-linejoin: round;
        stroke-dasharray: 40;
        stroke-dashoffset: 40;
        animation: tick-draw 0.6s ease-out 0.8s forwards;
    }

    .success-text {
        color: #1f2937;
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0 0 0.5rem 0;
        opacity: 0;
        animation: text-fade 0.5s ease-out 1.2s forwards;
    }

    .success-subtext {
        color: #6b7280;
        font-size: 0.875rem;
        margin: 0 0 1.5rem 0;
        opacity: 0;
        animation: text-fade 0.5s ease-out 1.4s forwards;
    }

    .success-actions {
        opacity: 0;
        animation: text-fade 0.5s ease-out 1.6s forwards;
    }

    .btn-primary {
        background: #0A3167;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        display: inline-block;
        margin: 0 0.5rem;
        transition: background-color 0.2s;
    }

    .btn-primary:hover {
        background: #082A5A;
        color: white;
        text-decoration: none;
    }

    .btn-secondary {
        background: #f3f4f6;
        color: #374151;
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        display: inline-block;
        margin: 0 0.5rem;
        transition: background-color 0.2s;
    }

    .btn-secondary:hover {
        background: #e5e7eb;
        color: #374151;
        text-decoration: none;
    }

    @keyframes circle-draw {
        to {
            stroke-dashoffset: 0;
        }
    }

    @keyframes tick-draw {
        to {
            stroke-dashoffset: 0;
        }
    }

    @keyframes text-fade {
        to {
            opacity: 1;
        }
    }

    /* Pulse effect for completed state */
    .success-icon::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 80px;
        height: 80px;
        border: 2px solid #10b981;
        border-radius: 50%;
        transform: translate(-50%, -50%) scale(0);
        opacity: 0;
        animation: pulse-ring 2s ease-out 1.6s infinite;
    }

    @keyframes pulse-ring {
        0% {
            transform: translate(-50%, -50%) scale(0.8);
            opacity: 0.8;
        }
        100% {
            transform: translate(-50%, -50%) scale(1.4);
            opacity: 0;
        }
    }
</style>

<div class="success-container">
    <div class="success-icon">
        <svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
            <!-- Circle -->
            <circle 
                class="success-circle" 
                cx="40" 
                cy="40" 
                r="38"
            />
            <!-- Checkmark -->
            <path 
                class="success-tick"
                d="M25 40 L35 50 L55 30"
            />
        </svg>
    </div>
    
    <h2 class="success-text"><?= $title ?? 'Payment Successful' ?></h2>
    <p class="success-subtext"><?= $message ?? 'Your transaction has been completed successfully' ?></p>
    
    <div class="success-actions">
        <?php if (isset($actions) && is_array($actions)): ?>
            <?php foreach ($actions as $action): ?>
                <a href="<?= $action['url'] ?>" class="<?= $action['class'] ?? 'btn-primary' ?>">
                    <?= $action['text'] ?>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <a href="<?= \App\Core\View::url('orders') ?>" class="btn-primary">View Orders</a>
            <a href="<?= \App\Core\View::url('') ?>" class="btn-secondary">Continue Shopping</a>
        <?php endif; ?>
    </div>
</div>
