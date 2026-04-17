# File Retention Test Task

Laravel 13 application for uploading and storing PDF/DOCX files with:

- asynchronous upload via Bootstrap + jQuery,
- file metadata persistence in MySQL,
- manual deletion from a CRUD page,
- automatic deletion after 24 hours,
- RabbitMQ publication on every deletion event,
- no real email sending, only a RabbitMQ message plus readable application logs.

## Stack

- PHP 8.3 / Laravel 13
- MySQL 8
- RabbitMQ 3 with Management UI
- Bootstrap 5 + jQuery
- Docker Compose

## What is implemented

1. Upload only `pdf` and `docx` files.
2. Reject files larger than 10 MB.
3. Store metadata in the `stored_files` table.
4. Display uploaded files on a dedicated management page.
5. Allow manual deletion.
6. Automatically delete expired files with a scheduled Artisan command.
7. Publish a JSON payload to RabbitMQ when a file is deleted manually or automatically.
8. Log a human-readable message that clearly says which file notification is being sent to which email.

## Important note about email sending

The task requirement says to implement message delivery to RabbitMQ, but not the real SMTP email sending itself.

Because of that, this project does **not** send emails. Instead it:

- publishes a payload to RabbitMQ,
- writes a log entry similar to:

```text
Preparing RabbitMQ notification for "contract.pdf" to qa@example.com.
Publishing deletion message for "contract.pdf" to "qa@example.com" finished with status: published.
```

## Quick start

From the project root:

```bash
docker compose up --build
```

If port `8080` or another default port is busy, you can override it in PowerShell:

```powershell
$env:APP_PORT=8088
docker compose up --build
```

After startup:

- Application: [http://localhost:8088](http://localhost:8088) by default, or `http://localhost:$APP_PORT` if overridden
- RabbitMQ UI: [http://localhost:15672](http://localhost:15672)
- RabbitMQ credentials: `guest / guest`
- MySQL port on host: `33061`

Important: run `docker compose` from `D:\projects\file-retention-test`, not from the inner `app` directory.

The first container start will:

- copy `.env.example` to `.env` if needed,
- install Composer dependencies if `vendor` is missing,
- generate `APP_KEY`,
- run database migrations.

## Environment variables

Main settings are already prepared in `app/.env.example`.

The notification recipient is configured here:

```dotenv
FILE_DELETION_NOTIFICATION_EMAIL=qa@example.com
```

RabbitMQ settings:

```dotenv
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/
RABBITMQ_EXCHANGE=file-events
RABBITMQ_QUEUE=file-deletion-notifications
RABBITMQ_ROUTING_KEY=file.deleted
```

## How automatic deletion works

- Every uploaded file gets `expires_at = uploaded_at + 24 hours`.
- Scheduler service runs `php artisan schedule:work`.
- Laravel schedule triggers `php artisan files:purge-expired` every minute.
- The command deletes expired files and publishes a RabbitMQ message for each deleted file.

## Checking the RabbitMQ message

Open RabbitMQ Management UI:

1. Go to `Queues and Streams`.
2. Open `file-deletion-notifications`.
3. Use `Get messages`.

Expected payload example:

```json
{
  "event": "file.deleted",
  "reason": "manual",
  "recipient_email": "qa@example.com",
  "deleted_at": "2026-04-17T16:30:00+00:00",
  "file": {
    "id": 1,
    "original_name": "contract.pdf",
    "stored_name": "uuid.pdf",
    "path": "uploads/2026/04/17/uuid.pdf",
    "mime_type": "application/pdf",
    "size_bytes": 12345,
    "uploaded_at": "2026-04-17T15:30:00+00:00",
    "expires_at": "2026-04-18T15:30:00+00:00"
  }
}
```

## Local testing without Docker

If you want to run only tests locally:

```bash
cd app
php artisan test
```

## Implemented tests

- upload stores metadata and file,
- manual deletion removes the file and publishes a notification,
- scheduled cleanup removes expired files and publishes a notification.
