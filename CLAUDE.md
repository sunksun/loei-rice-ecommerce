# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP-based e-commerce website for selling traditional rice varieties from Loei province, Thailand. The application uses vanilla PHP with MySQL/MariaDB database and follows a traditional MVC-like structure without a formal framework.

## Development Environment Setup

### Prerequisites
- XAMPP or similar LAMP/WAMP stack
- PHP 8.0+
- MySQL/MariaDB 10.4+
- Web browser for testing

### Database Setup
1. Import the database schema: `database/loei_rice_ecommerce.sql`
2. Update database credentials in `config/database.php` if needed
3. Default database name: `loei_rice_ecommerce`
4. Default credentials: host=localhost, user=root, password='' (empty)

### Running the Application
1. Place project in XAMPP htdocs directory
2. Start Apache and MySQL services
3. Access via: `http://localhost/loei-rice-ecommerce/`
4. Admin panel: `http://localhost/loei-rice-ecommerce/admin/`

## Code Architecture

### Directory Structure
- `config/` - Configuration files (database, settings)
- `admin/` - Admin panel pages and functionality
- `assets/` - Static assets (CSS, JS, images)
- `uploads/` - User uploaded files (product images, etc.)
- `database/` - SQL files and database backups
- `includes/` - Shared components (header, footer, navigation)
- Root level - Main application pages (index.php, products.php, etc.)

### Core Components

#### Database Connection
- Uses PDO with singleton pattern in `config/database.php`
- Helper function `getDB()` for easy connection access
- Comprehensive error handling and logging
- Automatic cleanup and maintenance functions

#### Configuration System
- Main config in `config/config.php` with extensive settings
- Environment-specific configurations (development/production)
- Dynamic settings loaded from database `site_settings` table
- Helper functions for URLs, pricing, dates, security

#### Key Features
- User authentication and registration
- Product catalog with categories
- Shopping cart functionality
- Order management system
- Admin panel for content management
- Review and rating system
- Thai language support (UTF-8)

### Database Schema
Key tables include:
- `users` - Customer accounts
- `admins` - Admin users
- `categories` - Product categories
- `products` - Product catalog
- `orders` & `order_items` - Order management
- `cart` - Shopping cart items
- `reviews` - Product reviews
- `addresses` - Customer addresses
- `site_settings` - Dynamic configuration

## Development Commands

### Database Operations
```bash
# Import database
mysql -u root -p loei_rice_ecommerce < database/loei_rice_ecommerce.sql

# Backup database
mysqldump -u root -p loei_rice_ecommerce > backup.sql
```

### Testing
- No automated testing framework configured
- Manual testing through web interface
- Use `seeder.php` for sample data generation
- Debug tools: `debug_login.php`, `test_password.php`, `hash_password.php`

### File Permissions
Ensure write permissions for:
- `uploads/` directory and subdirectories
- `logs/` directory (if exists)

## Important Conventions

### PHP Standards
- Use UTF-8 encoding for Thai language support
- Session management with CSRF protection
- Password hashing with PHP's `password_hash()`
- PDO prepared statements for SQL queries
- Error logging enabled in development mode

### Security Features
- CSRF token validation
- SQL injection prevention via PDO
- Login attempt limiting
- Session timeout management
- Input sanitization functions
- File upload restrictions

### Thai Language Support
- Database charset: `utf8mb4`
- All text content in Thai language
- Thai date/time formatting
- Currency formatting in Thai Baht (฿)

## Key Files to Understand

- `config/config.php` - Master configuration with all constants and helper functions
- `config/database.php` - Database connection class with maintenance functions
- `index.php` - Homepage with product displays and user authentication
- `admin/index.php` - Admin dashboard
- `products.php` - Product catalog page
- `cart.php` - Shopping cart functionality
- `checkout.php` - Order processing