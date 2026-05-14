# GUVI Developer Internship Project

**🌐 Live Website:** [https://guviproject.infinityfreeapp.com](https://guviproject.infinityfreeapp.com)

This project is a complete user authentication and profile management system built with HTML, CSS, JavaScript, and PHP.

It delivers a full `Register → Login → Profile` flow with:
- a separate frontend for each page
- jQuery AJAX communication only
- Bootstrap style responsive forms
- secure MySQL prepared statements
- Redis session storage for login tokens
- optional MongoDB support for extended profile fields

## What the project contains

- `register.html` — user signup form
- `login.html` — login form with session handling
- `profile.html` — protected profile page and update form
- `assets/css/styles.css` — custom UI styling
- `assets/js/register.js` — register API calls
- `assets/js/login.js` — login API calls and localStorage session token
- `assets/js/profile.js` — profile fetch/update API calls
- `php/` — backend APIs, auth helpers, and database connectors
- `db/` — MySQL and MongoDB initialization files

## Folder Structure

```text
assets/
  css/styles.css
  js/register.js
  js/login.js
  js/profile.js
db/
  mysql_schema.sql
  seed_data.sql
  mongo_schema.js
php/
  auth.php
  config.php
  db_mysql.php
  db_mongo.php
  redis_client.php
  register.php
  login.php
  profile.php
  logout.php
index.html
login.html
profile.html
register.html
README.md
```

## Features

- user registration with password hashing
- login using email and password
- Redis-backed session token storage
- authenticated profile retrieval and update
- optional MongoDB storage for age, dob, contact
- frontend uses AJAX without page reloads

## Prerequisites

- PHP 8.1 or newer
- Composer
- MySQL
- Redis (for session handling)
- MongoDB (optional for extended profile data)

## Deployment

### Live Deployment (InfinityFree)

This project is currently deployed on **InfinityFree**.

**To access the live application:**
- Visit: [https://guviproject.infinityfreeapp.com](https://guviproject.infinityfreeapp.com)

**Key Configuration for InfinityFree:**
- All credentials are stored in `.env` file on the server
- MySQL: Uses InfinityFree's MySQL service
- Redis: Uses Upstash Redis REST API
- MongoDB: Connected via a separate Node.js API on Render

### Local Development Setup

1. Install dependencies:

```bash
composer install
```

2. Copy environment example:

```powershell
Copy-Item .env.example .env
```

3. Configure your environment values in `.env`:
   - Set `MYSQL_HOST`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_DATABASE`
   - Set `UPSTASH_REDIS_REST_URL` and `UPSTASH_REDIS_REST_TOKEN`
   - Set `MONGO_API_BASE_URL` (if using MongoDB)

4. Initialize MySQL:

```bash
mysql -u root -p < db/mysql_schema.sql
mysql -u root -p < db/seed_data.sql
```

5. (Optional) Initialize MongoDB:

```bash
mongosh < db/mongo_schema.js
```

6. Place the project in XAMPP `htdocs` and start Apache, MySQL, and Redis.

7. Open the app in your browser:

```text
http://localhost/guvi/register.html
```

## API Endpoints

- `POST /php/register.php` — register a new user
- `POST /php/login.php` — login and receive session token
- `GET /php/profile.php?action=get` — get current profile
- `PUT /php/profile.php?action=update` — update profile
- `POST /php/logout.php` — logout session

## Notes

- **Security:** Do not share the `.env` file publicly. Use `.env.example` as the template for configuration.
- The app works with MySQL + Redis even if MongoDB is not installed.
- All sensitive credentials (passwords, API tokens) are stored in `.env` and excluded from Git via `.gitignore`.
- For production deployments, ensure all environment variables are properly configured on your hosting server.

