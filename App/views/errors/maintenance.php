<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Under Maintenance</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#f48525",
                        "background-light": "#f8f7f5",
                        "background-dark": "#221810",
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "1rem",
                        "lg": "2rem",
                        "xl": "3rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
<style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark">
<div class="relative flex min-h-screen w-full flex-col items-center justify-center p-4">
<div class="absolute top-8 left-1/2 -translate-x-1/2">
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-4xl text-primary">storefront</span>
<span class="text-2xl font-bold text-[#1c140d] dark:text-background-light">BrandCommerce</span>
</div>
</div>
<main class="flex w-full max-w-2xl flex-col items-center justify-center text-center">
<span class="material-symbols-outlined text-6xl text-primary mb-4" data-icon="build_circle">build_circle</span>
<h1 class="text-[#1c140d] dark:text-[#f8f7f5] text-4xl font-bold leading-tight tracking-tight md:text-5xl">We'll be back soon</h1>
<p class="mt-4 max-w-lg text-base font-normal leading-normal text-[#1c140d]/80 dark:text-[#f8f7f5]/80">Our website is currently undergoing scheduled maintenance. We're working hard to make our site better for you and apologize for the inconvenience.</p>
<div class="mt-8 w-full max-w-md px-4">
<div class="flex flex-col gap-2">
<div class="flex justify-between">
<p class="text-sm font-medium leading-normal text-[#1c140d] dark:text-[#f8f7f5]">Back online by 3:00 PM EST</p>
</div>
<div class="h-2 rounded-full bg-primary/20">
<div class="h-2 rounded-full bg-primary" style="width: 75%;"></div>
</div>
</div>
</div>
<div class="mt-12 w-full max-w-lg rounded-lg bg-background-light dark:bg-[#2c2016] p-6 md:p-8 border border-black/5 dark:border-white/5 shadow-sm">
<h2 class="text-[#1c140d] dark:text-[#f8f7f5] text-lg font-bold leading-tight tracking-[-0.015em]">Get notified when we're back online</h2>
<p class="mt-2 text-sm font-normal leading-normal text-[#1c140d]/80 dark:text-[#f8f7f5]/80">Enter your email to receive a notification as soon as we're back.</p>
<div class="mt-6 flex flex-col items-center gap-4 sm:flex-row">
<label class="flex h-12 w-full flex-1">
<div class="flex w-full flex-1 items-stretch rounded-full">
<input class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-full border border-black/10 dark:border-white/10 bg-background-light dark:bg-background-dark px-4 text-sm font-normal leading-normal text-[#1c140d] dark:text-[#f8f7f5] placeholder:text-[#9c7049] focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/50" placeholder="Enter your email address" type="email" value=""/>
</div>
</label>
<button class="flex h-12 w-full min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-full bg-primary px-5 text-sm font-bold leading-normal tracking-[0.015em] text-white transition-colors hover:bg-primary/90 sm:w-auto">
<span class="truncate">Notify Me</span>
</button>
</div>
</div>
<div class="mt-12">
<p class="text-sm font-medium text-[#1c140d] dark:text-[#f8f7f5]">Follow us for the latest news</p>
<div class="mt-4 flex items-center justify-center gap-4">
<a aria-label="Twitter" class="text-[#1c140d]/70 dark:text-[#f8f7f5]/70 transition-colors hover:text-primary dark:hover:text-primary" href="#">
<svg class="h-6 w-6" fill="currentColor" viewbox="0 0 24 24">
<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"></path>
</svg>
</a>
<a aria-label="Facebook" class="text-[#1c140d]/70 dark:text-[#f8f7f5]/70 transition-colors hover:text-primary dark:hover:text-primary" href="#">
<svg class="h-6 w-6" fill="currentColor" viewbox="0 0 24 24">
<path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"></path>
</svg>
</a>
<a aria-label="Instagram" class="text-[#1c140d]/70 dark:text-[#f8f7f5]/70 transition-colors hover:text-primary dark:hover:text-primary" href="#">
<svg class="h-6 w-6" fill="currentColor" viewbox="0 0 24 24">
<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.85s-.011 3.584-.069 4.85c-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07s-3.584-.012-4.85-.07c-3.252-.148-4.771-1.691-4.919-4.919-.058-1.265-.069-1.645-.069-4.85s.011-3.584.069-4.85c.149-3.225 1.664-4.771 4.919-4.919 1.266-.058 1.644-.07 4.85-.07zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948s.014 3.667.072 4.947c.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072s3.667-.014 4.947-.072c4.358-.2 6.78-2.618 6.98-6.98.059-1.281.073-1.689.073-4.948s-.014-3.667-.072-4.947c-.2-4.358-2.618-6.78-6.98-6.98C15.667.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.88 1.44 1.44 0 000-2.88z"></path>
</svg>
</a>
</div>
</div>
</main>
<footer class="absolute bottom-8 text-center text-sm text-[#1c140d]/70 dark:text-[#f8f7f5]/70">
<p>Need help? <a class="font-semibold text-primary underline-offset-4 hover:underline" href="#">Contact Support</a></p>
</footer>
</div>
</body></html>