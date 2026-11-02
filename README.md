# ScreenLite

## Project Overview

ScreenLite is a digital-signage screen-monitoring assignment built with PHP, Node.js, and MariaDB.

An administrator can add, view, and delete screens in a plain PHP page. Each screen receives a unique device code. A device sends heartbeat requests to the Node.js API to update its last-seen timestamp. The PHP page shows a screen as:

- **Online:** A heartbeat was received within the last 120 seconds.
- **Offline:** No heartbeat has been received, or the most recent heartbeat is older than 120 seconds.

A separate API endpoint returns that screen's playlist as JSON. The PHP page and Node.js API use the same MariaDB database.

## Technologies

- PHP 8+ with PDO
- Node.js 18+ with Express.js
- MariaDB / MySQL
- `mysql2/promise` and `dotenv`
- Jest and Supertest
- HTML and minimal CSS

## Project Structure

```text
ScreenLite/
├── database/
│   └── schema.sql
├── php-admin/
│   ├── config/
│   │   └── database.php
│   └── index.php
├── node-api/
│   ├── config/
│   │   └── database.js
│   ├── routes/
│   │   └── deviceRoutes.js
│   ├── tests/
│   │   └── heartbeat.test.js
│   ├── app.js
│   ├── server.js
│   ├── seed.js
│   ├── package.json
│   ├── package-lock.json
│   └── .env.example
├── ANSWERS.md
├── .gitignore
└── README.md
```

## Prerequisites

- XAMPP with Apache and MariaDB/MySQL
- PHP 8 or later
- Node.js 18 or later and npm
- Postman (optional, for manually testing the API)

## Database Setup

1. Open the XAMPP Control Panel and start **Apache** and **MySQL**.
2. Open [phpMyAdmin](http://localhost/phpmyadmin).
3. Select **Import**, choose `database/schema.sql`, and run the import.

This creates the `screenlite` database and its `screens` and `playlist_items` tables. Both tables use auto-increment primary keys. `device_code` is unique. `playlist_items.screen_id` references `screens.id` with `ON DELETE CASCADE`, so deleting a screen also deletes its playlist items.

### Database Configuration

The common local XAMPP settings are below, if they match your MariaDB installation:

```text
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=screenlite
DB_USER=root
DB_PASSWORD=
```

PHP reads `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` from its environment. Node.js reads the database settings from `node-api/.env`.

If `node-api/.env` does not exist, create it from the example in PowerShell:

```powershell
Copy-Item .env.example .env
```

Run this command from the `node-api` folder. If `.env` already exists, do not overwrite it; check its values instead. Update the settings to match your local MariaDB. Never commit `.env`, passwords, or AWS credentials. Only `.env.example` belongs in the repository.

## Run the PHP Application

1. Make sure the project is under XAMPP's `htdocs` directory.
2. Start Apache and MySQL in XAMPP.
3. Open:

   [http://localhost/rahul.web/ScreenLite/php-admin/](http://localhost/rahul.web/ScreenLite/php-admin/)

The page can add a screen by name, generate its unique device code, list all screens and their last-seen time/status, and delete a screen. Add and delete use POST. After either operation the page redirects, so refreshing does not repeat the operation.

The page uses prepared statements for SQL and `htmlspecialchars()` to escape displayed values.

### Heartbeat and Timezone

The `last_seen_at` MariaDB `DATETIME` value is stored as India Standard Time (IST, `Asia/Kolkata`, UTC+05:30). The API writes UTC plus 330 minutes, and PHP interprets and compares the stored value as IST. The database column is nullable: `NULL` displays as **Never** and is **Offline**.

## Run the Node.js API

Open a PowerShell terminal in the project directory and run:

```powershell
Set-Location .\node-api
npm.cmd install
npm.cmd run seed
npm.cmd start
```

Keep the API terminal open while using the API. By default, it listens on `http://localhost:3000`.

The seed script creates a sample screen with device code `SCR-SAMPLE000001` and two playlist items. It is safe to run again without duplicating those sample items. The media URLs use `example.com` and are placeholders, not actual hosted media.

## API Endpoints

### 1. Device Heartbeat

**Method:** `POST`
**URL:** `http://localhost:3000/v1/devices/heartbeat`

JSON body:

```json
{
  "device_code": "SCR-SAMPLE000001"
}
```

Successful response (**HTTP 200**):

```json
{
  "success": true,
  "message": "Heartbeat received successfully"
}
```

The API verifies that the device code exists and updates `last_seen_at`. Missing or invalid input returns **400**; an unknown code returns **404**. A manual request can simulate a device heartbeat; a real device would send requests automatically using its own code.

### 2. Get a Screen's Playlist

**Method:** `GET`
**Example URL:** `http://localhost:3000/v1/devices/SCR-SAMPLE000001/playlist`

Successful response (**HTTP 200**):

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "media_url": "https://example.com/media/welcome-video.mp4",
      "duration_seconds": 30,
      "position": 1
    },
    {
      "id": 2,
      "media_url": "https://example.com/media/store-hours.jpg",
      "duration_seconds": 15,
      "position": 2
    }
  ]
}
```

Items are sorted by ascending `position`. An existing screen with no playlist returns **200** with `"data": []`; an unknown code returns **404**. The API returns playlist JSON; this project does not include a media player or a PHP playlist-management page.

## Automated Tests

From a second PowerShell terminal, run:

```powershell
Set-Location .\node-api
npm.cmd test
```

The Jest/Supertest suite contains two heartbeat input tests:

1. Missing `device_code` returns **400**.
2. A non-string `device_code` returns **400**.

Both verify `success: false`. These invalid-input tests do not require a live database connection because the Express app is separated from server startup.

## Security and Assumptions

- PHP and Node.js use prepared/parameterized SQL statements.
- PHP validates input and HTML-escapes dynamic output.
- `.env` and `node_modules/` are excluded by `.gitignore`; do not commit credentials.
- API errors use JSON and do not expose SQL credentials or stack traces.
- Admin login, CSRF protection, physical device integration, actual media hosting/playback, and AWS deployment are outside the assignment scope.
- AWS architecture answers are provided separately in `ANSWERS.md`.

## Completion and Future Improvements

The required PHP screen-management page, shared database schema, heartbeat and playlist endpoints, sample seed data, automated API tests, and AWS answers are included. The sample media URLs are illustrative.

If more time were available, I would add authenticated admin access, real media hosting, database-backed API integration tests, and software integration with physical signage devices.

## Submission Details

- **GitHub repository:** [https://github.com/rahul959484/ScreenLite](https://github.com/rahul959484/ScreenLite)
- **Start time:** 02 November 2026, 2:30 PM IST
- **Finish time:** 02 November 2026, 5:18 PM IST
- **Email subject:** `ScreenLite Assignment: Rahul Gupta`

