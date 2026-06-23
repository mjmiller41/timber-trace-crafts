# Timber & Trace Crafts: Project Memory & Rules

These guidelines document project-specific requirements, database behaviors, and environment guidelines to ensure consistency for future tasks.

---

## 🔑 Database Credentials & Environment Setup
- **Accessing Environment Variables:** Always use `getenv()` instead of the `$_ENV` superglobal in PHP files. By default, the PHP CLI has `variables_order` set to ignore `$_ENV` mapping, which breaks local environment configuration.
- **Local Credentials:** Local development is configured for the MySQL user `admin` with password `admin` on the database `timber_trace_crafts`.
- **First Admin Auto-elevation:** Do not pre-seed admin credentials. The codebase automatically elevates the first registered user to `admin` role ([AuthController.php](file:///app/Controllers/AuthController.php#L69-L74)).

---

## ⚡ Architecture & Dual-Mode Lifecycles
- **Request / Response Handlers:** The application runs in FPM mode (LiteSpeed/Apache for Hostinger shared server) and async daemon mode (Open Swoole HTTP server for VPS/CLI).
- **Core Abstractions:**
  - Standard global arrays (`$_GET`, `$_POST`, `$_COOKIE`) are wrapped in `App\Core\Request`.
  - Content flushings are wrapped in `App\Core\Response`.
  - Dynamic parameters are managed via a custom router (`config/routes.php`).
- **Connection Isolation (Swoole compatibility):** In Swoole mode, multiple coroutines run concurrently. Connections are isolated by mapping standard PDO instances to Swoole Coroutine IDs (`Swoole\Coroutine::getCid()`) and deferred to auto-close. Never share a single static PDO instance across requests.
- **Session Engines:** Use `PHPSession` for standard PHP FPM session management, and `FileSession` for coroutine-safe, cookie-driven file sessions under Swoole.

---

## 🚀 Commands
- **Launch Local PHP Server (FPM mode):**
  ```bash
  php -S localhost:8000 -t public
  ```
- **Launch Swoole Server (Async mode):**
  ```bash
  php bin/server.php
  ```
- **Seeding Database:**
  ```bash
  php bin/seed.php
  ```
