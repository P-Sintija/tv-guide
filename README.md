# TV Guide
A Laravel application powered by Laravel Sail.

---

## Setup

### Clone the Repository
```bash
git clone https://github.com/P-Sintija/tv-guide.git
cd tv-guide
```

### Environment Setup
Copy the environment file:
```bash
cp .env.example .env
```
Update the database configuration in `.env`:
```bash
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```
### Install Dependencies
```bash
composer install
```
### Generate Application Key
```bash
php artisan key:generate
```
### Start the Application
Make sure Docker is running, then start Laravel Sail:
```bash
./vendor/bin/sail up -d
```
Run database migrations
```bash
./vendor/bin/sail artisan migrate
```

### Stop the Application
```bash
./vendor/bin/sail down -v
```
