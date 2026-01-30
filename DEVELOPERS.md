# Apparix Developer Documentation

## Architecture Overview

Apparix is a custom PHP MVC e-commerce platform built for performance and extensibility.

## Directory Structure

```
apparix/
├── app/
│   ├── Controllers/       # Route handlers
│   │   ├── Admin/         # Admin panel controllers
│   │   └── *.php          # Frontend controllers
│   ├── Models/            # Database models
│   ├── Views/             # PHP templates
│   │   ├── layouts/       # Main layout templates
│   │   ├── admin/         # Admin panel views
│   │   └── */             # Frontend views by section
│   ├── Core/              # Framework core classes
│   └── Helpers/           # Helper functions
├── content/
│   ├── plugins/           # Installed plugins
│   └── themes/            # Installed themes
├── public/                # Web root (document root)
│   ├── index.php          # Front controller / router
│   ├── assets/
│   │   ├── css/           # Stylesheets
│   │   ├── js/            # JavaScript
│   │   └── images/        # Static images
│   └── uploads/           # User uploads
├── storage/
│   ├── logs/              # Application logs
│   └── sessions/          # PHP sessions
├── database/
│   └── migrations/        # SQL migration files
├── cron/                  # Cron job scripts
├── install/               # Installation wizard
└── .env                   # Environment configuration (not in repo)
```

## Key Concepts

### Routing

All routes are defined in `/public/index.php`. The router maps URLs to controller methods.

```php
$router->get('/products', 'ProductController', 'index');
$router->get('/products/{slug}', 'ProductController', 'show');
$router->post('/cart/add', 'CartController', 'add');
```

### Controllers

Controllers extend `App\Core\Controller` and handle HTTP requests:

```php
class ProductController extends Controller
{
    public function index(): void
    {
        $products = $this->productModel->getActive();
        $this->render('products/index', ['products' => $products]);
    }
}
```

### Models

Models extend `App\Core\Model` for database operations:

```php
class Product extends Model
{
    protected string $table = 'products';

    public function getActive(): array
    {
        return $this->query(
            "SELECT * FROM {$this->table} WHERE is_active = 1"
        );
    }
}
```

### Views

Views are PHP templates in `/app/Views/`. Use the `escape()` helper for XSS protection:

```php
<h1><?php echo escape($product['name']); ?></h1>
```

### Helpers

Global helper functions in `/app/Helpers/`:

- `escape($str)` - HTML entity encoding
- `formatPrice($amount)` - Currency formatting
- `csrfField()` - CSRF token input
- `csrfToken()` - Raw CSRF token
- `auth()` - Current authenticated user
- `setFlash($type, $message)` - Flash messages
- `appName()` - Application name from settings
- `appUrl()` - Application URL

## Database

### Key Tables

- `products`, `product_variants`, `product_images` - Product data
- `categories`, `product_categories` - Categorization
- `orders`, `order_items` - Order data
- `users`, `admin_users` - User accounts
- `cart_items` - Shopping cart
- `shipping_origins`, `shipping_zones`, `shipping_rates` - Shipping
- `discount_codes`, `coupon_usage` - Coupons
- `settings` - Application settings

### Migrations

SQL migrations are in `/database/migrations/`. Run them in order:

```bash
mysql -u user -p database < database/migrations/001_initial.sql
```

## Security Features

- **CSRF Protection**: All forms must include `<?php echo csrfField(); ?>`
- **Password Hashing**: Argon2id via `password_hash()`
- **XSS Prevention**: Always use `escape()` for output
- **SQL Injection Prevention**: Use parameterized queries
- **Session Security**: Secure, HTTP-only cookies

## Plugin System

Plugins live in `/content/plugins/{plugin-slug}/`:

```
my-plugin/
├── plugin.json         # Manifest
├── MyPlugin.php        # Main class
├── assets/             # CSS/JS
└── views/              # Templates
```

See `/app/Core/Plugins/` for the plugin API.

## Theme System

Themes can override templates and add custom CSS:

```
my-theme/
├── theme.json          # Manifest
├── screenshot.png      # Preview image
├── assets/css/         # Theme styles
└── layouts/            # Template overrides
```

## Development Tips

### CSS/JS Cache Busting

Update version numbers in `/app/Views/layouts/main.php`:

```php
<link rel="stylesheet" href="/assets/css/main.css?v=42">
<script src="/assets/js/main.js?v=42"></script>
```

### Debug Mode

Enable in `.env`:

```
APP_DEBUG=true
```

### Logging

```php
error_log("Debug message: " . print_r($data, true));
```

Logs are in `/storage/logs/`.

## Testing

```bash
# Run PHP syntax check
find app -name "*.php" -exec php -l {} \;

# Test database connection
php -r "require 'vendor/autoload.php'; new App\Core\Database();"
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

Please follow PSR-12 coding standards.
