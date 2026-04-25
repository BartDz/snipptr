# Snipptr

Paste code → get a public URL with syntax highlighting, one-click copy, and optional expiration. Like GitHub Gist, but self-hosted and login-free.

## Features

- Syntax highlighting for 17 languages (PHP, JS, Python, SQL, Go, Rust, and more)
- CodeMirror 5 editor with line numbers and auto-language detection
- Auto-detect language on paste
- Expiration: 1h / 24h / 7 days / never
- Optional password protection (bcrypt hashed)
- Raw view (`/p/{slug}/raw`) - works with `curl`
- View counter
- CSRF protection on all state-changing operations
- Rate limiting (max 10 snippets/hour per IP)
- JSON API for programmatic access
- Input validation (XSS protection via `htmlspecialchars()`)
- Responsive glassmorphism UI with modern CSS

## Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3 (no framework) |
| Database | PostgreSQL 16 |
| Server | Nginx 1.25 + PHP-FPM |
| Deploy | Docker Compose |
| Syntax HL | Prism.js (CDN) |
| Editor | CodeMirror 5 (CDN) |
| Tests | PHPUnit 11 |

## Getting Started

**Requirements:** Docker + Docker Compose

```bash
git clone repo_url
cd snipptr
docker-compose up -d --build
docker-compose exec app bash -c "curl -sS https://getcomposer.org/installer | php && php composer.phar install"
```

Open [http://localhost:8080](http://localhost:8080)

## API

```bash
# Create a snippet
curl -X POST http://localhost:8080/api/paste \
  -H "Content-Type: application/json" \
  -d '{"content":"echo hello","language":"bash","expires":"24h"}'

# Response
{
  "url": "http://localhost:8080/p/abc1234",
  "raw": "http://localhost:8080/p/abc1234/raw",
  "slug": "abc1234",
  "expires_at": "2026-04-25 12:00:00"
}

# Fetch raw content
curl http://localhost:8080/p/abc1234/raw
```

### Parameters

| Field | Type | Description |
|---|---|---|
| `content` | string (required) | Code to paste |
| `language` | string | Language (default: `plaintext`) |
| `expires` | string | `1h` / `24h` / `7d` / `never` (default: `never`) |
| `password` | string | Optional password |

## Tests

PHPUnit test suite with coverage for core functionality:

```bash
docker-compose exec app ./vendor/bin/phpunit --testdox
```

**Test Coverage:**
- `DatabaseTest` - Connection, migrations, schema validation
- `PasteTest` - CRUD operations, rate limiting, expiration
- `PasteInputTest` - Validation, sanitization, language detection
- `SlugTest` - Uniqueness, format validation

## Project Structure

```
snipptr/
├── docker/
│   ├── nginx/default.conf
│   └── php/Dockerfile
├── public/
│   ├── assets/
│   │   ├── style.css      # Glassmorphism UI + responsive design
│   │   ├── editor.js      # CodeMirror setup + form handlers
│   │   ├── view.js        # Copy button, expiration countdown
│   │   └── favicon.png
│   ├── index.php          # create form
│   ├── view.php           # snippet view + password form
│   ├── raw.php            # plain text output
│   └── api.php            # JSON API
├── src/
│   ├── Database.php       # PDO singleton + auto-migrations
│   ├── Paste.php          # CRUD model + rate limiting
│   ├── PasteInput.php     # input validation & sanitization
│   ├── Slug.php           # 7-char slug generator
│   ├── Request.php        # request parsing (POST/GET)
│   ├── Response.php       # JSON response helper
│   ├── Csrf.php           # CSRF token generation & validation
│   └── Constants/
│       ├── Constants.php   # app-level constants
│       ├── Expire.php      # expiration options (1h, 24h, 7d, never)
│       └── Lang.php        # supported languages
└── tests/
    ├── DatabaseTest.php
    ├── PasteTest.php
    ├── PasteInputTest.php
    └── SlugTest.php
```
