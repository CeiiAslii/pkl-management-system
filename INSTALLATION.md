# E-PKL Installation

## Requirements

The requirements below are derived from `composer.json`, `composer.lock`, the Laravel database configuration, and the image-processing code in this repository.

- PHP 8.3 or newer
- Composer
- Node.js and npm
- MariaDB or MySQL
- Git
- ImageMagick with the `magick` CLI for private attendance and daily-report image processing
- PHP extensions used by the locked dependencies and application: Ctype, DOM, Fileinfo, Filter, Hash, JSON, Libxml, Mbstring, OpenSSL, PCRE, PDO, PDO MySQL, Phar, Session, Tokenizer, XML, XMLReader, XMLWriter, and Zip

Some PHP distributions provide Ctype, Filter, Hash, JSON, PCRE, PDO, Session, and Tokenizer as core modules. Confirm the effective environment after installing dependencies:

```bash
composer check-platform-reqs
```

## Setup

1. Clone the repository and enter its directory:

   ```bash
   git clone https://github.com/CeiiAslii/pkl-management-system.git
   cd pkl-management-system
   ```

2. Install PHP dependencies:

   ```bash
   composer install
   ```

3. Create a local environment file and application key:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Create an empty MariaDB/MySQL database and configure these local values in `.env`:

   ```dotenv
   APP_URL=http://localhost:8000
   APP_TIMEZONE=Asia/Makassar

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=pkl_management
   DB_USERNAME=pkl_user
   DB_PASSWORD=your-local-password

   SESSION_DRIVER=database
   QUEUE_CONNECTION=database
   ```

5. Create the schema. The optional seed step adds generic PKL reference majors and does not create user accounts:

   ```bash
   php artisan migrate
   # Optional reference data:
   php artisan db:seed
   ```

6. Install and compile frontend dependencies:

   ```bash
   npm install
   npm run build
   ```

7. Start the application:

   ```bash
   php artisan serve
   ```

## Queue Worker

Telegram notifications are queued so an external API failure does not invalidate saved attendance or report data. Run a queue worker in a separate process:

```bash
php artisan queue:work
```

Use a supervised long-running worker in a deployed environment.

## Scheduler

Run Laravel's scheduler locally with:

```bash
php artisan schedule:work
```

In deployment, invoke `php artisan schedule:run` once per minute using the platform scheduler or cron.

## Optional Telegram Integration

Telegram notifications remain disabled until the blank `TELEGRAM_*` placeholders in `.env.example` are configured in the local or deployment environment. Use a bot token and destination identifiers issued for your own environment. Never commit those values.

## Camera and GPS Access

Browsers require a secure context for camera and geolocation APIs. `localhost` is accepted for development on the same device. When accessing E-PKL from a phone or another computer, serve it through HTTPS with a trusted certificate or HTTPS-capable development tunnel. Keep forwarded-proxy headers configured correctly rather than hardcoding a temporary tunnel hostname.

## Storage and Production Preparation

Ensure the web server and queue worker can write to `storage/` and `bootstrap/cache/`. Before deployment, use production environment values, disable debug mode, build assets, run migrations, and start a supervised queue worker. Do not reuse the sample database credentials.
