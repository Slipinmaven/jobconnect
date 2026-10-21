# JobConnect

JobConnect is a PHP job-search and recruitment application for job seekers and employers. It includes job listings, applications, employer and job-seeker profiles, interviews, saved jobs, notifications, and optional Google sign-in.

## Requirements

- PHP 8.1 or newer with PDO MySQL, cURL, OpenSSL, and the extensions required by Composer packages
- Composer
- MySQL or a compatible MySQL server
- Apache with `mod_rewrite` for an Apache deployment

## Setup

1. Clone the repository and install the locked PHP dependencies:

   ```sh
   composer install
   ```

2. Create a local environment file:

   ```sh
   cp .env.example .env
   ```

   On PowerShell, use `Copy-Item .env.example .env`.

3. Edit `.env` with your local MySQL, SMTP, and optional Google OAuth settings. Set a unique `ENCRYPTION_KEY`; do not commit `.env` or production credentials.

4. Create/configure the MySQL database and make sure it contains the tables expected by the application. This repository does not currently include a database schema or migrations.

5. Start the PHP development server from the project root:

   ```sh
   php -S 127.0.0.1:8000 -t public public/index.php
   ```

   Open <http://127.0.0.1:8000>. The development server is for local use; for Apache, point the document root to the project's `public/` directory and enable `mod_rewrite`.

## Configuration

See [`.env.example`](.env.example) for all supported settings:

- `DB_*` configures the MySQL connection.
- `APP_ENV`, `APP_URL`, and `BASE_PATH` configure the environment and URL generation.
- `ENCRYPTION_KEY` should be a unique secret for each deployment.
- `SMTP_*` configures email delivery.
- `GOOGLE_*` is optional and enables Google OAuth when configured.

## Repository notes

- `composer.lock` is committed so installs use the dependency versions selected for this project.
- `.env`, runtime logs/cache, and user-uploaded CVs, profile photos, and employer logos are excluded from Git.
- Public landing-page images in `public/uploads/landing_images/` are application assets and remain eligible for version control.
