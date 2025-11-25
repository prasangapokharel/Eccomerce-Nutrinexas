# NutriNexus - E-Commerce Platform

A comprehensive e-commerce platform for premium supplements and health products, built with PHP and modern web technologies.

## Features

- 🛍️ **Full E-Commerce Functionality**
  - Product catalog with categories and subcategories
  - Shopping cart and checkout system
  - Multiple payment gateways (eSewa, Khalti, COD)
  - Order management and tracking

- 👥 **Multi-User System**
  - Customer accounts and profiles
  - Seller dashboard and product management
  - Admin panel for system management
  - Courier/delivery management

- 💰 **Seller Features**
  - Seller registration and approval
  - Product listing and inventory management
  - Wallet system with withdrawal requests
  - Sales analytics and reports

- 🚚 **Delivery System**
  - Courier assignment and tracking
  - Real-time order status updates
  - COD collection management
  - Delivery proof upload

- 🎨 **Modern UI**
  - Responsive design with Tailwind CSS
  - Theme customization support
  - Product image galleries
  - SEO optimized pages

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

