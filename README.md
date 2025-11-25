# NutriNexus - E-Commerce Platform

A comprehensive e-commerce platform for premium supplements and health products, built with PHP and modern web technologies.

## Features

### 🛍️ E-Commerce Core
- **Product Management**
  - Advanced product catalog with categories and subcategories
  - Product variants (size, color, digital products)
  - Inventory tracking and stock management
  - Product reviews and ratings system
  - Image galleries with multiple product images
  - Product search and filtering

- **Shopping Experience**
  - Shopping cart with persistent storage
  - Wishlist functionality
  - Coupon and discount system
  - Multiple payment gateways (eSewa, Khalti, COD)
  - Secure checkout process
  - Order tracking and status updates

- **Order Management**
  - Complete order lifecycle management
  - Order history and invoices
  - Order cancellation and refund processing
  - Email notifications for order updates
  - Order analytics and reporting

### 👥 Multi-User System
- **Customer Features**
  - User registration and authentication
  - Profile management
  - Order history and tracking
  - Address book management
  - Wishlist and saved items
  - Review and rating system

- **Seller Dashboard**
  - Seller registration and approval workflow
  - Product listing and management
  - Inventory control
  - Order processing
  - Wallet system with balance tracking
  - Withdrawal requests and payout management
  - Sales analytics and reports
  - Performance metrics

- **Admin Panel**
  - Comprehensive system administration
  - User management (customers, sellers, staff)
  - Product approval and moderation
  - Order oversight and management
  - Payment gateway configuration
  - System settings and configuration
  - Analytics and reporting dashboard

- **Courier/Delivery Management**
  - Courier account management
  - Order assignment system
  - Delivery tracking
  - COD collection management
  - Delivery proof upload
  - Performance monitoring

### 🔒 Security Features (Enterprise-Grade)
- **Authentication & Authorization**
  - Secure password hashing (bcrypt)
  - Multi-factor authentication support
  - Session management with secure cookies
  - Remember me functionality
  - Role-based access control (RBAC)
  - API key authentication
  - Token-based authentication

- **Protection Mechanisms**
  - CSRF (Cross-Site Request Forgery) protection
  - XSS (Cross-Site Scripting) prevention
  - SQL injection prevention (prepared statements)
  - Input validation and sanitization
  - Output escaping
  - File upload security with type validation
  - Request size limiting

- **Advanced Security**
  - Rate limiting and brute force protection
  - Fraud detection system with scoring
  - Suspicious pattern detection
  - Unusual activity monitoring
  - Idempotency key support for API requests
  - Replay attack prevention (timestamp validation)
  - Security event logging

- **Security Headers**
  - Content Security Policy (CSP)
  - X-Content-Type-Options
  - X-Frame-Options (clickjacking protection)
  - X-XSS-Protection
  - Strict-Transport-Security (HSTS)
  - Referrer-Policy
  - Permissions-Policy

- **Data Protection**
  - AES-256-CBC encryption for sensitive data
  - Secure session storage
  - Encrypted API communications
  - Payment data protection
  - Secure cookie configuration (HttpOnly, Secure, SameSite)

- **Monitoring & Logging**
  - Security event logging
  - Fraud attempt tracking
  - Rate limit monitoring
  - Security statistics dashboard
  - Automated log rotation
  - Configurable retention policies

### 💰 Financial Features
- **Payment Processing**
  - Multiple payment gateways integration
  - Secure payment processing
  - Transaction logging and audit trail
  - Payment verification and validation
  - Duplicate transaction prevention
  - Payment retry mechanism

- **Seller Wallet System**
  - Automatic wallet crediting on order completion
  - Withdrawal request system
  - Transaction history
  - Balance tracking
  - Commission and fee calculation
  - Payout management

### 🚚 Delivery & Logistics
- **Courier Management**
  - Courier registration and approval
  - Order assignment system
  - Delivery route optimization
  - Real-time tracking
  - COD collection tracking
  - Delivery proof management
  - Performance analytics

### 🎨 User Interface
- **Design & UX**
  - Responsive design (mobile, tablet, desktop)
  - Modern UI with Tailwind CSS
  - Theme customization support
  - Dark mode support
  - Accessibility features
  - Fast page loading
  - Smooth animations and transitions

- **SEO & Performance**
  - SEO optimized pages
  - Dynamic meta tags
  - Open Graph tags for social sharing
  - Twitter Card support
  - Schema.org structured data
  - XML sitemaps
  - Robots.txt configuration
  - Image optimization

### 📊 Analytics & Reporting
- **Business Intelligence**
  - Sales analytics
  - Product performance metrics
  - User behavior tracking
  - Revenue reports
  - Inventory reports
  - Security statistics
  - Custom report generation

## Requirements

- PHP >= 7.4
- MySQL/MariaDB
- Composer
- Node.js & npm (for CSS building)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/prasangapokharel/Eccomerce-Nutrinexas.git
cd Eccomerce-Nutrinexas
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install Node.js dependencies:
```bash
npm install
```

4. Build CSS:
```bash
npm run build:css
```

5. Configure environment:
   - Copy `.env.development` to `.env`
   - Update database credentials
   - Configure payment gateway settings

6. Start development server:
```bash
php start.php
```

The application will be available at `http://localhost:8000`

## Project Structure

```
├── App/              # Application core
│   ├── Controllers/  # Request handlers
│   ├── Models/       # Data models
│   ├── Views/        # View templates
│   ├── Core/         # Framework core
│   └── Helpers/      # Helper functions
├── public/           # Public assets
├── Database/         # Database migrations
├── resources/        # Resource files
└── start.php         # Development server starter
```

## Key Technologies

- **Backend**: PHP (MVC Architecture)
- **Frontend**: HTML, CSS (Tailwind), JavaScript
- **Database**: MySQL
- **Payment**: eSewa, Khalti, COD
- **Image Processing**: ImageKit, Intervention Image
- **Email**: PHPMailer

## Development

### Build CSS
```bash
npm run build:css
```

### Watch CSS changes
```bash
npm run watch:css
```

## License

Private - All rights reserved

## Support

For support and inquiries, contact the development team.

