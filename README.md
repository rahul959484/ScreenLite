# ScreenLite

## Project overview

ScreenLite is a small digital-signage screen-monitoring assignment. An admin adds and manages screens in a plain PHP page. Each screen receives a unique device code; a device uses that code to send heartbeats to a Node.js API. The PHP page shows a screen as **Online** when its last heartbeat was within 120 seconds, and **Offline** otherwise. A second API endpoint returns that screen's playlist as JSON.

The PHP page and Node.js API share a MariaDB database. No login, physical device software, media player, or AWS deployment is included because these were not required by the assignment.

## Technologies

- PHP 8+ with PDO
- Node.js 18+ with Express
- MariaDB/MySQL with `mysql2/promise`
- dotenv for Node.js configuration
- Jest and Supertest for automated API tests

## Project structure

```text
database/schema.sql              Database and the two shared tables
php-admin/config/database.php    PHP PDO connection
php-admin/index.php              Add/list/delete screens and display status
node-api/config/database.js      MariaDB connection pool
node-api/routes/deviceRoutes.js  Heartbeat and playlist endpoints
node-api/app.js                  Express app, exported for tests
node-api/server.js               API server startup
node-api/seed.js                 Repeat-safe sample screen and playlist
node-api/tests/heartbeat.test.js Automated heartbeat input tests
node-api/package.json            Node.js scripts and dependencies
node-api/package-lock.json       Locked Node.js dependency versions
node-api/.env.example            Safe environment-variable template
ANSWERS.md                       Answers to the four AWS questions
.gitignore                       Excludes local secrets and generated files
```

## Requirements

- XAMPP with Apache and MariaDB/MySQL
- PHP 8+
- Node.js 18+ and npm

## Database setup

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Open phpMyAdmin at `http://localhost/phpmyadmin`.
3. Select **Import**, choose `database/schema.sql`, and run the import. This creates the `screenlite` database and its `screens` and `playlist_items` tables.

For a typical local XAMPP installation, the database host is `127.0.0.1`, the username is `root`, and the password is empty. Use these defaults only if they match your local configuration.

PHP reads `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` from its environment. Node.js reads the same database settings plus `DB_PORT` from `node-api/.env`. The `.env.example` file contains placeholders/default local settings; copy it to `.env` if needed and edit it to match your MariaDB configuration. Never put real passwords or AWS keys in submitted files.

The schema uses InnoDB. `playlist_items.screen_id` references `screens.id` with `ON DELETE CASCADE`, so deleting a screen also deletes its playlist items. The `screens.id` and `playlist_items.id` columns are auto-incrementing primary keys, and `device_code` is unique.

## Run the PHP admin page

With XAMPP Apache and MySQL running, visit:

`http://localhost/rahul.web/ScreenLite/php-admin/`

Enter a screen name and select **Add Screen**. PHP validates the name, generates a secure unique device code, and inserts the screen. The page lists screens, their last-seen time in **India Standard Time (IST)**, status, and a POST-only Delete action. After an add/delete submission, the page redirects before displaying the list, so refreshing does not repeat that action.

### Heartbeat time and status

The `last_seen_at` MariaDB `DATETIME` value is stored as IST (`Asia/Kolkata`, UTC+05:30). The API writes the current UTC time plus 330 minutes; PHP interprets and compares the stored value as IST. A NULL time displays as `Never` and is Offline. A heartbeat no older than 120 seconds is Online.

## Run the Node.js API

Open a PowerShell terminal in the project directory and run:

```powershell
Set-Location .\node-api
Copy-Item .env.example .env
npm.cmd install
npm.cmd run seed
npm.cmd start
```

If `.env` already exists, do not overwrite it; check its database settings instead. Keep the API terminal open while using the API. By default, it listens on port `3000`.

The seed script creates the sample screen with device code `SCR-SAMPLE000001` and two example playlist items. It does not duplicate those items when run again. The media URLs use `example.com` and are placeholders, not actual media.

## API endpoints

### Send a heartbeat

**POST** `http://localhost:3000/v1/devices/heartbeat`

JSON body:

```json
{
  "device_code": "SCR-SAMPLE000001"
}
```

For a known device, the API updates `last_seen_at` and returns HTTP `200`:

```json
{
  "success": true,
  "message": "Heartbeat received successfully"
}
```

Invalid/missing input returns `400`; an unknown device code returns `404`. The manual request simulates a device. A real device would send heartbeats automatically using its own code.

### Get a screen's playlist

**GET** `http://localhost:3000/v1/devices/SCR-SAMPLE000001/playlist`

The API returns playlist items sorted by ascending `position`:

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

An existing screen with no playlist returns HTTP `200` and `"data": []`. An unknown device returns `404`. Playlist data is returned by the API as JSON; this project does not include a video player or a PHP playlist-management page.

## Automated tests

Open a second PowerShell terminal and run:

```powershell
Set-Location .\node-api
npm.cmd test
```

The Jest/Supertest tests verify that missing and non-string `device_code` inputs return HTTP `400` with `success: false`. These invalid-input tests do not need a database connection. The Express app is separate from the server startup so the tests do not open a network port.

## Security and assumptions

- SQL values use prepared/parameterized statements; displayed PHP values are HTML-escaped.
- The database connection settings are configurable. The local `.env` file is ignored by Git; only `.env.example` belongs in the submission.
- API errors use JSON and do not expose SQL credentials or stack traces.
- Admin authentication, CSRF protection, real media hosting, physical device integration, and AWS deployment are outside the assignment scope.

## Completion and next steps

The required PHP screen page, shared database schema, heartbeat and playlist endpoints, sample seed data, automated test, and AWS answers are included. The sample media URLs are illustrative. With more time, I would add authenticated admin access, real media storage, and integration tests for database-backed API cases.

## Submission checklist

- GitHub repository: https://github.com/rahul959484/ScreenLite
- Actual start time: **[fill in your start time]**
- Actual finish time: **[fill in your finish time]**
- Email subject: `ScreenLite Assignment: Your Name`
- Recipient email: the assignment document leaves this as `[add email address]`; obtain it from the assignment contact rather than guessing.

Before sharing the repository, confirm that `.env` and `node_modules/` are not included. Do not commit credentials. No repository has been created, committed, or pushed as part of this work.
