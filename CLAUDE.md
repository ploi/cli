# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Ploi CLI is a command-line interface for the [Ploi.io](https://ploi.io) server management platform. Built on Laravel Zero (a minimal CLI-focused Laravel framework), it provides 60+ commands for managing servers, sites, databases, SSL, daemons, and more.

## Common Commands

```bash
# Install dependencies
composer install

# Run tests (Pest)
./vendor/bin/pest

# Run a single test file
./vendor/bin/pest tests/Feature/ExampleTest.php

# Lint code (Laravel Pint)
./vendor/bin/pint

# Build standalone executable
php ploi app:build ploi --build-version=<VERSION>
```

## Architecture

### Entry Point
- `/ploi` - Main executable that bootstraps Laravel Zero
- `/bootstrap/app.php` - Application initialization

### Core Services
- `app/Services/PloiAPI.php` - HTTP client for Ploi API (all API communication goes through here)
- `app/Support/Configuration.php` - Manages `.ploi/settings.yml` and `.ploi/provision.yml` project configs

### Command Structure
All commands extend `app/Commands/Command.php` which provides:
- `$this->ploi` - PloiAPI instance
- `$this->configuration` - Configuration instance
- Output helpers: `info()`, `error()`, `success()`, `warn()`

Commands are organized hierarchically:
- `app/Commands/` - Top-level commands (init, deploy, provision, token, ssh:login)
- `app/Commands/Server/` - Server management (create, list, restart, services, etc.)
- `app/Commands/Site/` - Site management (create, list, repos, SSL, env, etc.)

### Command Concerns (Reusable Traits)
Located in `app/Commands/Concerns/`:
- `InteractWithServer` - Server selection and polling logic
- `InteractWithSite` - Site selection and interaction
- `InteractWithDatabase` - Database operations
- `InteractWithUser` - User interaction patterns

### Traits
Located in `app/Traits/`:
- `EnsureHasToken` - Validates API token exists
- `HasPloiConfiguration` - Checks for project config files
- `HasRepo` - Repository-related operations

### Configuration Files
- Global token: `~/.ploi/config.php`
- Project settings: `.ploi/settings.yml` (server ID, site ID, domain)
- Project provisioning: `.ploi/provision.yml` (commands, services)

## Tech Stack

- **PHP 8.2+** with strict typing
- **Laravel Zero 11** - CLI framework
- **Laravel Prompts** - Interactive CLI prompts (select, text, confirm, spin)
- **Guzzle** - HTTP client (via Illuminate HTTP facade)
- **Pest** - Testing framework
- **Laravel Pint** - Code formatter
- **Box** - PHAR builder for standalone executable

## Build Process

Builds are automated via GitHub Actions (`.github/workflows/build.yml`). On push to `main`:
1. Auto-increments version from latest git tag
2. Runs `php ploi app:build ploi --build-version=X.Y`
3. Commits built binary to `builds/ploi`
4. Creates new git tag
