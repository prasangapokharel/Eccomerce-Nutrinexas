<?php
/**
 * Premium Layout Component - Enhanced shadcn/ui Design
 * Usage: include 'Components/Layout.php';
 */

class Layout {
    public static function render($content, $title = 'NutriNexus', $attributes = []) {
        $html = "<div class=\"layout\">";
        $html .= "<header class=\"layout-header\">";
        $html .= "<nav class=\"layout-nav\">";
        $html .= "<div class=\"layout-brand\">";
        $html .= "<a href=\"#\" class=\"layout-logo\">$title</a>";
        $html .= "</div>";
        $html .= "<div class=\"layout-menu\">";
        $html .= "<a href=\"#\" class=\"layout-link\">Dashboard</a>";
        $html .= "<a href=\"#\" class=\"layout-link\">Products</a>";
        $html .= "<a href=\"#\" class=\"layout-link\">Orders</a>";
        $html .= "</div>";
        $html .= "</nav>";
        $html .= "</header>";
        $html .= "<main class=\"layout-main\">";
        $html .= $content;
        $html .= "</main>";
        $html .= "<footer class=\"layout-footer\">";
        $html .= "<p class=\"layout-footer-text\">© 2024 NutriNexus. All rights reserved.</p>";
        $html .= "</footer>";
        $html .= "</div>";
        
        return $html;
    }
    
    public static function dashboard($content, $title = 'Dashboard', $attributes = []) {
        $html = "<div class=\"dashboard-layout\">";
        $html .= "<div class=\"dashboard-header\">";
        $html .= "<h1 class=\"dashboard-title\">$title</h1>";
        $html .= "</div>";
        $html .= "<div class=\"dashboard-content\">";
        $html .= $content;
        $html .= "</div>";
        $html .= "</div>";
        
        return $html;
    }
    
    public static function card($content, $title = '', $footer = '', $attributes = []) {
        $html = "<div class=\"layout-card\">";
        if ($title) {
            $html .= "<div class=\"layout-card-header\">";
            $html .= "<h3 class=\"layout-card-title\">$title</h3>";
            $html .= "</div>";
        }
        $html .= "<div class=\"layout-card-content\">";
        $html .= $content;
        $html .= "</div>";
        if ($footer) {
            $html .= "<div class=\"layout-card-footer\">";
            $html .= $footer;
            $html .= "</div>";
        }
        $html .= "</div>";
        
        return $html;
    }
}
?>
<!-- Layout Component - Clean HTML with Custom CSS Classes Only -->
<!-- Header Layout -->
<header class="bg-white shadow">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900">
            <slot name="title"></slot>
        </h1>
    </div>
</header>

<!-- Main Layout -->
<main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <slot></slot>
    </div>
</main>

<!-- Footer Layout -->
<footer class="bg-white">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <p class="text-base text-gray-400">
                <slot name="footer-text"></slot>
            </p>
        </div>
    </div>
</footer>

<!-- Card Layout -->
<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
    <div class="card">
        <div class="card-content">
            <slot name="grid-item-1"></slot>
        </div>
    </div>
    <div class="card">
        <div class="card-content">
            <slot name="grid-item-2"></slot>
        </div>
    </div>
    <div class="card">
        <div class="card-content">
            <slot name="grid-item-3"></slot>
        </div>
    </div>
</div>

<!-- Dashboard Layout -->
<div class="min-h-screen bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        <div class="hidden md:flex md:w-64 md:flex-col">
            <div class="flex flex-col flex-grow pt-5 bg-white overflow-y-auto">
                <div class="flex items-center flex-shrink-0 px-4">
                    <slot name="logo"></slot>
                </div>
                <div class="mt-5 flex-grow flex flex-col">
                    <nav class="flex-1 px-2 pb-4 space-y-1">
                        <slot name="navigation"></slot>
                    </nav>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="flex flex-col w-0 flex-1 overflow-hidden">
            <div class="relative z-10 flex-shrink-0 flex h-16 bg-white shadow">
                <div class="flex-1 px-4 flex justify-between">
                    <div class="flex-1 flex">
                        <slot name="search"></slot>
                    </div>
                    <div class="ml-4 flex items-center md:ml-6">
                        <slot name="user-menu"></slot>
                    </div>
                </div>
            </div>
            
            <main class="flex-1 relative overflow-y-auto focus:outline-none">
                <div class="py-6">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                        <slot></slot>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>