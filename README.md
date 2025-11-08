# Query Monitor CLI

**WP-CLI commands and REST API endpoints for Query Monitor debugging**

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](LICENSE)

---

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [WP-CLI Commands](#wp-cli-commands)
- [REST API Endpoints](#rest-api-endpoints)
- [Usage Examples](#usage-examples)
- [Command Reference](#command-reference)
- [Use Cases](#use-cases)
- [Integration Examples](#integration-examples)
- [Architecture](#architecture)
- [Troubleshooting](#troubleshooting)
- [Testing](#testing)
- [Contributing](#contributing)
- [Changelog](#changelog)
- [License](#license)

---

## 🎯 Overview

Query Monitor CLI extends the popular [Query Monitor](https://wordpress.org/plugins/query-monitor/) plugin by providing command-line and REST API access to its powerful debugging features. Monitor database queries, profile performance, track HTTP requests, and more - all from the command line or via REST API.

### Why Query Monitor CLI?

**Problem**: Query Monitor provides debugging information only through browser-based interfaces, making it unavailable for:
- CLI-based development workflows
- Automated testing scripts
- Headless WordPress applications
- CI/CD pipelines
- Remote debugging scenarios

**Solution**: Query Monitor CLI bridges this gap by exposing all Query Monitor collectors through WP-CLI commands and REST API endpoints, enabling:
- ✅ Command-line debugging during development
- ✅ Automated performance testing
- ✅ CI/CD integration for quality gates
- ✅ Remote monitoring via REST API
- ✅ Headless WordPress debugging

---

## ✨ Features

### WP-CLI Commands

Access Query Monitor data directly from the command line:

| Command | Description | Output Formats |
|---------|-------------|----------------|
| `wp qm inspect` | **🔍 Complete analysis of a specific post/page/URL** | table, json |
| `wp qm env` | Environment information (PHP, WordPress, Database) | table, json, yaml, csv |
| `wp qm db` | Database query monitoring with slow query detection | table, json, csv |
| `wp qm profile` | Performance profiling (time, memory, queries) | table, json |
| `wp qm http` | HTTP request monitoring | table, json |
| `wp qm hooks` | WordPress hooks tracking | table, json |
| `wp qm errors` | PHP error monitoring | table, json |

### REST API Endpoints

Access Query Monitor data programmatically:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/wp-json/query-monitor/v1/inspect` | GET | **🔍 Complete post/page analysis** |
| `/wp-json/query-monitor/v1/environment` | GET | Environment information |
| `/wp-json/query-monitor/v1/database` | POST | Database queries |
| `/wp-json/query-monitor/v1/profile` | POST | Performance profile |
| `/wp-json/query-monitor/v1/http` | POST | HTTP requests |
| `/wp-json/query-monitor/v1/hooks` | POST | Hooks information |
| `/wp-json/query-monitor/v1/errors` | POST | PHP errors |

### Key Capabilities

- 🔍 **No Code Duplication** - Leverages existing Query Monitor collectors
- 🚀 **Multiple Output Formats** - Table, JSON, CSV, YAML
- 🎯 **Slow Query Detection** - Configurable thresholds
- 📊 **Performance Metrics** - Time, memory, database stats
- 🔐 **Secure REST API** - WordPress Application Password authentication
- 🧪 **CI/CD Ready** - JSON output for automation
- 📝 **Comprehensive Logging** - Track all WordPress operations

---

## 📦 Requirements

### ⚠️ Required Dependencies

- **Query Monitor Plugin**: 3.16 or higher
  - **This plugin will NOT work without Query Monitor installed and activated**
  - Install from: https://wordpress.org/plugins/query-monitor/
  - Or via WP-CLI: `wp plugin install query-monitor --activate`

### System Requirements

- **WordPress**: 6.0 or higher
- **PHP**: 7.4 or higher
- **WP-CLI**: 2.5 or higher (for CLI commands only)

### Dependency Check

The plugin automatically checks for Query Monitor on activation:
- ✅ If Query Monitor is active: Plugin works normally
- ❌ If Query Monitor is missing: Admin notice displayed, features disabled

---

## 🚀 Installation

### Method 1: Via Git Clone

```bash
# Navigate to WordPress plugins directory
cd wp-content/plugins

# Clone the repository
git clone https://github.com/MervinPraison/query-monitor-cli.git

# Install Query Monitor (if not already installed)
wp plugin install query-monitor --activate

# Activate Query Monitor CLI
wp plugin activate query-monitor-cli
```

### Method 2: Manual Installation

1. Download the latest release from [GitHub](https://github.com/MervinPraison/query-monitor-cli/releases)
2. Extract to `wp-content/plugins/query-monitor-cli`
3. Install and activate Query Monitor: `wp plugin install query-monitor --activate`
4. Activate Query Monitor CLI: `wp plugin activate query-monitor-cli`

### Verification

```bash
# Verify installation
wp plugin list | grep query-monitor

# Test the plugin
wp qm env
```

---

## ⚡ Quick Start

### 1. Basic Commands

```bash
# Check environment
wp qm env

# Monitor database queries
wp qm db post list

# Profile performance
wp qm profile post list
```

### 2. JSON Output for Automation

```bash
# Get JSON output
wp qm profile post list --format=json

# Save to file
wp qm db post list --format=json > queries.json
```

### 3. Slow Query Detection

```bash
# Find queries slower than 0.1 seconds
wp qm db post list --slow-only --threshold=0.1
```

### 4. REST API Access

```bash
# Create application password
wp user application-password create 1 "QM CLI" --porcelain

# Test REST API
curl -u "username:password" \
  "https://yoursite.com/wp-json/query-monitor/v1/environment"
```

---

## 💻 WP-CLI Commands

### `wp qm inspect` - Complete Post/Page Analysis 🔍

**The most powerful command** - Inspect a specific post, page, or URL with complete Query Monitor analysis showing ALL available data.

**Syntax:**
```bash
wp qm inspect [--post_id=<id>] [--slug=<slug>] [--url=<url>] [--format=<format>] [--collectors=<collectors>]
```

**Options:**
- `--post_id=<id>` - Post ID to inspect
- `--slug=<slug>` - Post slug to inspect  
- `--url=<url>` - URL path to inspect (e.g., /sample-page/)
- `--format=<format>` - Output format: table (default), json
- `--collectors=<collectors>` - Comma-separated list of specific collectors to show (default: all)

**What It Shows:**
- ✅ **Database Queries** - All queries with timing and callers
- ✅ **Performance Metrics** - Execution time, memory usage
- ✅ **HTTP Requests** - External API calls
- ✅ **Hooks Fired** - WordPress actions and filters
- ✅ **Assets Loaded** - Scripts and styles
- ✅ **Cache Operations** - Hits, misses, and operations
- ✅ **Conditionals** - WordPress conditional tags (is_single, is_page, etc.)
- ✅ **Request Details** - Matched query, query vars
- ✅ **Theme Information** - Template file, template hierarchy
- ✅ **Block Editor** - Block data and timing
- ✅ **Transients** - Transient operations
- ✅ **PHP Errors** - Errors, warnings, notices
- ✅ **Languages** - Translation files loaded
- ✅ **Timing** - Detailed timing breakdown
- ✅ And more...

**Examples:**
```bash
# Inspect by post ID
wp qm inspect --post_id=123

# Inspect by slug
wp qm inspect --slug=sample-page

# Inspect by URL
wp qm inspect --url=/about/

# Get JSON output for automation
wp qm inspect --post_id=123 --format=json

# Show only specific collectors
wp qm inspect --post_id=123 --collectors=db_queries,http,hooks

# Inspect and save to file
wp qm inspect --slug=sample-page --format=json > page-analysis.json
```

**Output Example:**
```
Inspecting: https://yoursite.com/sample-page/

=== Post Information ===
ID: 2
Title: Sample Page
Type: page
Status: publish

=== DB QUERIES ===
Total Queries: 15
Total Time: 0.0234s

Top 10 Queries:
1. [0.0045s] SELECT wp_posts.* FROM wp_posts WHERE ID = 2...
   Caller: WP_Query->get_posts | Component: WordPress Core
2. [0.0023s] SELECT t.*, tt.* FROM wp_terms AS t...
   Caller: _prime_term_caches | Component: WordPress Core
...

=== HTTP ===
Total HTTP Requests: 2
1. [GET] https://api.wordpress.org/plugins/info/1.0/ - Status: 200 (0.1234s)
2. [GET] https://api.wordpress.org/themes/info/1.0/ - Status: 200 (0.0987s)

=== HOOKS ===
Total Hooks Fired: 156
Sample Hooks (first 20):
init, wp_loaded, parse_request, send_headers, parse_query, pre_get_posts...

=== THEME ===
Theme: twentytwentyfour
Template: twentytwentyfour
Template File: /path/to/themes/twentytwentyfour/page.php
Template Hierarchy: page-2.php, page.php, singular.php, index.php

=== CONDITIONALS ===
True Conditionals: is_page, is_singular, is_front_page

=== CACHE ===
Hits: 45 | Misses: 12 | Total: 57

=== BLOCK EDITOR ===
Total Blocks: 5
Has Block Context: Yes
Post Has Blocks: Yes

Success: Inspection complete!
```

**Use Cases:**
- 🔍 **Debug specific pages** - See exactly what's happening on a particular page
- 📊 **Performance analysis** - Identify slow queries or heavy operations
- 🐛 **Troubleshooting** - Find errors or issues on specific posts
- 📈 **Optimization** - Analyze and optimize page load performance
- 🧪 **Testing** - Validate changes to specific pages
- 📝 **Documentation** - Generate detailed reports for pages

---

### `wp qm env` - Environment Information

Display PHP, WordPress, and database environment information.

**Syntax:**
```bash
wp qm env [--format=<format>]
```

**Options:**
- `--format=<format>` - Output format: table (default), json, yaml, csv

**Examples:**
```bash
# Table format (default)
wp qm env

# JSON format
wp qm env --format=json

# YAML format
wp qm env --format=yaml
```

**Output:**
```
=== PHP Information ===
Version: 8.4.6
Memory Limit: 256M
Max Execution Time: 30

=== WordPress Information ===
Version: 6.8.3
Multisite: No

=== Database Information ===
Extension: mysqli
Server: MySQL
Version: 8.0.35
```

---

### `wp qm db` - Database Query Monitoring

Monitor database queries with detailed metrics and slow query detection.

**Syntax:**
```bash
wp qm db [<command>...] [--format=<format>] [--slow-only] [--threshold=<seconds>]
```

**Options:**
- `[<command>...]` - WP-CLI command to monitor (optional)
- `--format=<format>` - Output format: table (default), json, csv
- `--slow-only` - Show only slow queries
- `--threshold=<seconds>` - Slow query threshold in seconds (default: 0.05)

**Examples:**
```bash
# Monitor current queries
wp qm db

# Monitor specific command
wp qm db post list

# Find slow queries
wp qm db post list --slow-only --threshold=0.1

# JSON output for automation
wp qm db post list --format=json

# Monitor complex operations
wp qm db post create --post_title="Test" --post_content="Content"
```

**Output:**
```
Total Queries: 4 | Total Time: 0.0272s

#   Time      Type    Caller                    SQL
1   0.0167s   SELECT  WP_Query->get_posts      SELECT wp_posts.* FROM...
2   0.0027s   SELECT  WP_Term_Query->get_terms SELECT DISTINCT t.term_id...
3   0.0005s   SELECT  _prime_term_caches       SELECT t.*, tt.* FROM...
4   0.0072s   SELECT  update_meta_cache        SELECT post_id, meta_key...
```

---

### `wp qm profile` - Performance Profiling

Profile command performance including execution time, memory usage, and database metrics.

**Syntax:**
```bash
wp qm profile [<command>...] [--format=<format>]
```

**Options:**
- `[<command>...]` - WP-CLI command to profile (optional)
- `--format=<format>` - Output format: table (default), json

**Examples:**
```bash
# Profile current state
wp qm profile

# Profile specific command
wp qm profile post list

# Profile cache operations
wp qm profile cache flush

# JSON output
wp qm profile post list --format=json
```

**Output:**
```
=== Performance Profile ===
Command: post list
Execution Time: 0.0203s
Peak Memory: 64 MB
Memory Used: 3 MB
Database Queries: 4
Database Time: 0.0070s
```

---

### `wp qm http` - HTTP Request Monitoring

Monitor HTTP requests made during command execution.

**Syntax:**
```bash
wp qm http [<command>...] [--format=<format>]
```

**Options:**
- `[<command>...]` - WP-CLI command to monitor (optional)
- `--format=<format>` - Output format: table (default), json

**Examples:**
```bash
# Monitor current HTTP requests
wp qm http

# Monitor plugin updates
wp qm http plugin update --all --dry-run

# JSON output
wp qm http --format=json
```

**Output:**
```
Total HTTP Requests: 2

URL                              Method  Status  Time
https://api.wordpress.org/...    GET     200     0.1234s
https://downloads.wordpress...   GET     200     0.5678s
```

---

### `wp qm hooks` - WordPress Hooks Tracking

Track WordPress action and filter hooks.

**Syntax:**
```bash
wp qm hooks [<command>...] [--format=<format>]
```

**Options:**
- `[<command>...]` - WP-CLI command to monitor (optional)
- `--format=<format>` - Output format: table (default), json

**Examples:**
```bash
# Track current hooks
wp qm hooks

# Track hooks during post creation
wp qm hooks post create --post_title="Test"

# JSON output for analysis
wp qm hooks --format=json
```

---

### `wp qm errors` - PHP Error Monitoring

Monitor PHP errors, warnings, and notices.

**Syntax:**
```bash
wp qm errors [<command>...] [--format=<format>]
```

**Options:**
- `[<command>...]` - WP-CLI command to monitor (optional)
- `--format=<format>` - Output format: table (default), json

**Examples:**
```bash
# Check for errors
wp qm errors

# Monitor plugin activation
wp qm errors plugin activate my-plugin

# JSON output
wp qm errors --format=json
```

**Output:**
```
Total PHP Errors: 0
Success: No PHP errors found!
```

---

## 🌐 REST API Endpoints

### Authentication

All REST API endpoints require authentication using WordPress Application Passwords.

**Create Application Password:**
```bash
wp user application-password create 1 "QM CLI API" --porcelain
```

**Use in Requests:**
```bash
curl -u "username:app_password" "https://site.com/wp-json/query-monitor/v1/..."
```

---

### GET /wp-json/query-monitor/v1/inspect 🔍

**Complete post/page analysis** - Get ALL Query Monitor data for a specific post, page, or URL.

**Parameters:**
- `post_id` (integer, optional) - Post ID to inspect
- `slug` (string, optional) - Post slug to inspect
- `url` (string, optional) - URL path to inspect
- `collectors` (string, optional) - Comma-separated list of collectors to include

**Request Examples:**
```bash
# Inspect by post ID
curl -u "username:password" \
  "https://yoursite.com/wp-json/query-monitor/v1/inspect?post_id=123"

# Inspect by slug
curl -u "username:password" \
  "https://yoursite.com/wp-json/query-monitor/v1/inspect?slug=sample-page"

# Inspect with specific collectors only
curl -u "username:password" \
  "https://yoursite.com/wp-json/query-monitor/v1/inspect?post_id=123&collectors=db_queries,http,hooks"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "url": "https://yoursite.com/sample-page/",
    "post_id": 2,
    "post": {
      "ID": 2,
      "title": "Sample Page",
      "type": "page",
      "status": "publish",
      "slug": "sample-page"
    },
    "collectors": {
      "db_queries": {
        "collector": "db_queries",
        "data": {
          "total_queries": 15,
          "total_time": 0.0234,
          "queries": [...]
        }
      },
      "http": {
        "collector": "http",
        "data": {
          "total_requests": 2,
          "requests": [...]
        }
      },
      "hooks": {
        "collector": "hooks",
        "data": {
          "total_hooks": 156,
          "hooks": ["init", "wp_loaded", ...]
        }
      },
      "conditionals": {
        "collector": "conditionals",
        "data": {
          "is_page": true,
          "is_singular": true,
          "is_front_page": true
        }
      },
      "theme": {
        "collector": "theme",
        "data": {
          "theme": "twentytwentyfour",
          "template": "twentytwentyfour",
          "template_file": "/path/to/page.php",
          "template_hierarchy": ["page-2.php", "page.php", "singular.php"]
        }
      },
      "cache": {
        "collector": "cache",
        "data": {
          "hits": 45,
          "misses": 12,
          "total": 57
        }
      }
      // ... and 15+ more collectors
    }
  }
}
```

**Available Collectors:**
- `db_queries` - Database queries
- `http` - HTTP requests
- `hooks` - WordPress hooks
- `conditionals` - Conditional tags
- `theme` - Theme information
- `cache` - Object cache stats
- `php_errors` - PHP errors
- `block_editor` - Block editor data
- `transients` - Transient operations
- `timing` - Performance timing
- `overview` - Performance overview
- `assets_scripts` - Loaded scripts
- `assets_styles` - Loaded styles
- `request` - Request details
- `environment` - Environment info
- And more...

---

### GET /wp-json/query-monitor/v1/environment

Get environment information.

**Request:**
```bash
curl -u "username:password" \
  "https://yoursite.com/wp-json/query-monitor/v1/environment"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "php": {
      "version": "8.4.6",
      "memory_limit": "256M",
      "max_execution_time": "30",
      "extensions": ["mysqli", "curl", "gd", ...]
    },
    "wordpress": {
      "version": "6.8.3",
      "multisite": false,
      "debug_mode": false
    },
    "database": {
      "extension": "mysqli",
      "server": "MySQL",
      "version": "8.0.35",
      "database": "wordpress"
    },
    "server": {
      "software": "nginx",
      "version": "1.21.0"
    }
  }
}
```

---

### POST /wp-json/query-monitor/v1/database

Get database query information.

**Request:**
```bash
curl -X POST -u "username:password" \
  "https://yoursite.com/wp-json/query-monitor/v1/database"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_queries": 4,
    "total_time": 0.0272,
    "queries": [
      {
        "index": 1,
        "time": 0.0167,
        "type": "SELECT",
        "caller": "WP_Query->get_posts",
        "sql": "SELECT wp_posts.* FROM...",
        "component": "WordPress Core"
      }
    ]
  }
}
```

---

### POST /wp-json/query-monitor/v1/profile

Get performance profile.

**Request:**
```bash
curl -X POST -u "username:password" \
  "https://yoursite.com/wp-json/query-monitor/v1/profile"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "execution_time": 0.0203,
    "memory_peak": 67108864,
    "memory_used": 3145728,
    "memory_limit": 268435456,
    "db_queries": 4,
    "db_time": 0.0070
  }
}
```

---

### POST /wp-json/query-monitor/v1/http

Get HTTP request information.

**Request:**
```bash
curl -X POST -u "username:password" \
  "https://yoursite.com/wp-json/query-monitor/v1/http"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_requests": 2,
    "total_time": 0.6912,
    "requests": [
      {
        "url": "https://api.wordpress.org/...",
        "method": "GET",
        "status": "200",
        "time": 0.1234,
        "component": "WordPress Core"
      }
    ]
  }
}
```

---

### POST /wp-json/query-monitor/v1/hooks

Get WordPress hooks information.

**Request:**
```bash
curl -X POST -u "username:password" \
  "https://yoursite.com/wp-json/query-monitor/v1/hooks"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_hooks": 150,
    "hooks": {
      "init": [...],
      "wp_loaded": [...],
      ...
    }
  }
}
```

---

### POST /wp-json/query-monitor/v1/errors

Get PHP error information.

**Request:**
```bash
curl -X POST -u "username:password" \
  "https://yoursite.com/wp-json/query-monitor/v1/errors"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_errors": 0,
    "errors": []
  }
}
```

---

## 📚 Use Cases

### Development

**Debug Slow Queries**
```bash
# Find queries taking more than 100ms
wp qm db post list --slow-only --threshold=0.1
```

**Profile Custom Code**
```bash
# Profile your custom WP-CLI command
wp qm profile my-custom-command --arg1=value
```

**Monitor HTTP Requests**
```bash
# Track external API calls
wp qm http my-api-sync-command
```

**Track Hook Execution**
```bash
# See which hooks fire during operation
wp qm hooks post create --post_title="Test"
```

---

### Testing & QA

**Automated Performance Testing**
```bash
#!/bin/bash
# performance-test.sh

PROFILE=$(wp qm profile post list --format=json)
TIME=$(echo $PROFILE | jq -r '.execution_time')

if (( $(echo "$TIME > 0.5" | bc -l) )); then
  echo "FAIL: Execution time $TIME exceeds 0.5s threshold"
  exit 1
fi

echo "PASS: Performance acceptable ($TIME seconds)"
```

**Query Optimization Validation**
```bash
#!/bin/bash
# check-slow-queries.sh

SLOW=$(wp qm db post list --slow-only --threshold=0.1 --format=json | jq 'length')

if [ "$SLOW" -gt 0 ]; then
  echo "WARNING: Found $SLOW slow queries"
  wp qm db post list --slow-only --threshold=0.1
  exit 1
fi

echo "PASS: No slow queries detected"
```

**Error Detection**
```bash
# Check for PHP errors after deployment
wp qm errors plugin activate my-plugin --format=json
```

---

### CI/CD Integration

**GitHub Actions Example**
```yaml
name: WordPress Performance Tests

on: [push, pull_request]

jobs:
  performance:
    runs-on: ubuntu-latest
    
    steps:
    - name: Setup WordPress
      run: |
        # Setup WordPress environment
        
    - name: Install Query Monitor CLI
      run: |
        cd wp-content/plugins
        git clone https://github.com/MervinPraison/query-monitor-cli.git
        wp plugin activate query-monitor-cli
        
    - name: Run Performance Tests
      run: |
        # Profile critical operations
        wp qm profile post list --format=json > profile.json
        
        # Check for slow queries
        SLOW=$(wp qm db post list --slow-only --threshold=0.1 --format=json | jq 'length')
        if [ "$SLOW" -gt 0 ]; then
          echo "::error::Found $SLOW slow queries"
          exit 1
        fi
        
    - name: Upload Results
      uses: actions/upload-artifact@v2
      with:
        name: performance-results
        path: profile.json
```

**GitLab CI Example**
```yaml
performance_test:
  stage: test
  script:
    - wp plugin activate query-monitor-cli
    - wp qm profile post list --format=json > profile.json
    - |
      SLOW=$(wp qm db post list --slow-only --threshold=0.1 --format=json | jq 'length')
      if [ "$SLOW" -gt 0 ]; then
        echo "Found $SLOW slow queries"
        exit 1
      fi
  artifacts:
    paths:
      - profile.json
```

---

### DevOps & Monitoring

**Health Check Script**
```bash
#!/bin/bash
# health-check.sh

# Get performance metrics
PROFILE=$(curl -s -u "$WP_USER:$WP_PASS" \
  "https://yoursite.com/wp-json/query-monitor/v1/profile")

# Extract metrics
DB_QUERIES=$(echo $PROFILE | jq -r '.data.db_queries')
EXEC_TIME=$(echo $PROFILE | jq -r '.data.execution_time')

# Alert if thresholds exceeded
if [ "$DB_QUERIES" -gt 50 ]; then
  echo "ALERT: Too many database queries ($DB_QUERIES)"
fi

if (( $(echo "$EXEC_TIME > 1.0" | bc -l) )); then
  echo "ALERT: Slow response time ($EXEC_TIME seconds)"
fi
```

**Performance Baseline**
```bash
#!/bin/bash
# create-baseline.sh

# Create performance baseline
wp qm profile post list --format=json > baseline-posts.json
wp qm profile cache flush --format=json > baseline-cache.json
wp qm db post list --format=json > baseline-queries.json

echo "Baseline created successfully"
```

**Deployment Validation**
```bash
#!/bin/bash
# post-deploy-check.sh

echo "Running post-deployment checks..."

# Check for PHP errors
ERRORS=$(wp qm errors --format=json | jq -r '.data.total_errors')
if [ "$ERRORS" -gt 0 ]; then
  echo "ERROR: Found $ERRORS PHP errors"
  wp qm errors
  exit 1
fi

# Validate performance
PROFILE=$(wp qm profile post list --format=json)
TIME=$(echo $PROFILE | jq -r '.execution_time')

if (( $(echo "$TIME > 1.0" | bc -l) )); then
  echo "WARNING: Performance degradation detected"
  echo "Execution time: $TIME seconds"
fi

echo "Deployment validation complete"
```

---

## 🏗️ Architecture

### How It Works

Query Monitor CLI integrates with Query Monitor's existing architecture:

1. **Initialization**
   - Defines `QM_TESTS` constant to bypass CLI check
   - Manually loads Query Monitor collector and data files
   - Applies `qm/collectors` filter to register collectors

2. **Data Collection**
   - Uses existing Query Monitor collectors (no duplication)
   - Processes all collectors to ensure dependencies are met
   - Extracts data from collector data objects

3. **Output Formatting**
   - Formats data for CLI display (tables, JSON, CSV)
   - Provides machine-readable output for automation
   - Maintains consistent structure across commands

### Key Components

**Main Plugin File** (`query-monitor-cli.php`)
- Plugin initialization
- Dependency checking
- Class loading

**Base CLI Class** (`includes/class-qm-cli-base.php`)
- Query Monitor initialization
- Collector management
- Shared utilities

**CLI Commands** (`includes/class-qm-cli-commands.php`)
- WP-CLI command implementations
- Output formatting
- Error handling

**REST API** (`includes/class-qm-rest-api.php`)
- REST endpoint registration
- Authentication
- JSON responses

### Integration Points

```php
// Initialize Query Monitor in CLI context
define( 'QM_TESTS', true );

// Load collectors
$qm = QueryMonitor::init( $qm_file );
foreach ( glob( $qm_dir . '/collectors/*.php' ) as $file ) {
    include_once $file;
}

// Register collectors
$collectors = apply_filters( 'qm/collectors', array(), $qm );
foreach ( $collectors as $collector ) {
    QM_Collectors::add( $collector );
}

// Process and get data
QM_Collectors::init()->process();
$collector = QM_Collectors::get( 'environment' );
$data = $collector->get_data();
```

---

## 🔧 Troubleshooting

### Common Issues

**"Query Monitor plugin is not active"**

Query Monitor must be installed and activated first.

```bash
# Install Query Monitor
wp plugin install query-monitor --activate

# Verify installation
wp plugin list | grep query-monitor
```

---

**"Environment collector not found"**

This means Query Monitor collectors aren't loading properly.

**Solutions:**
1. Deactivate and reactivate both plugins:
   ```bash
   wp plugin deactivate query-monitor query-monitor-cli
   wp plugin activate query-monitor query-monitor-cli
   ```

2. Check for PHP errors:
   ```bash
   tail -f wp-content/debug.log
   ```

3. Verify Query Monitor version:
   ```bash
   wp plugin list | grep query-monitor
   # Should be 3.16 or higher
   ```

---

**REST API returns 401 Unauthorized**

Authentication is required for all REST API endpoints.

**Solution:**
```bash
# Create application password
wp user application-password create 1 "QM CLI API" --porcelain

# Use in requests
curl -u "username:APP_PASSWORD_HERE" \
  "https://yoursite.com/wp-json/query-monitor/v1/environment"
```

---

**No queries recorded**

If monitoring a command shows no queries:

1. Ensure the command actually runs queries:
   ```bash
   # This should show queries
   wp qm db post list
   ```

2. Check if SAVEQUERIES is defined as false in wp-config.php

3. Try a simple command first to verify it's working

---

**Command execution fails**

If a monitored command fails:

1. Test the command without monitoring:
   ```bash
   wp post list
   ```

2. Check command syntax

3. Use `--debug` flag:
   ```bash
   wp qm db post list --debug
   ```

---

### Debug Mode

Enable WordPress debug mode for detailed error information:

```php
// wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

Then check the log:
```bash
tail -f wp-content/debug.log
```

---

## 🧪 Testing

### Manual Testing

**Test Environment Command:**
```bash
wp qm env
# Should display PHP, WordPress, and database info
```

**Test Database Monitoring:**
```bash
wp qm db post list
# Should show queries with timing information
```

**Test Performance Profiling:**
```bash
wp qm profile post list
# Should show execution time and memory usage
```

**Test REST API:**
```bash
# Create app password first
APP_PASS=$(wp user application-password create 1 "Test" --porcelain)

# Test endpoint
curl -u "admin:$APP_PASS" \
  "https://yoursite.test/wp-json/query-monitor/v1/environment"
```

### Automated Testing

**Performance Test Script:**
```bash
#!/bin/bash
# tests/performance-test.sh

echo "Running performance tests..."

# Test 1: Execution time
PROFILE=$(wp qm profile post list --format=json)
TIME=$(echo $PROFILE | jq -r '.execution_time')

if (( $(echo "$TIME > 0.5" | bc -l) )); then
  echo "❌ FAIL: Execution time too slow ($TIME s)"
  exit 1
else
  echo "✅ PASS: Execution time acceptable ($TIME s)"
fi

# Test 2: Slow queries
SLOW=$(wp qm db post list --slow-only --threshold=0.1 --format=json | jq 'length')

if [ "$SLOW" -gt 0 ]; then
  echo "❌ FAIL: Found $SLOW slow queries"
  exit 1
else
  echo "✅ PASS: No slow queries"
fi

# Test 3: PHP errors
ERRORS=$(wp qm errors --format=json | jq -r '.data.total_errors')

if [ "$ERRORS" -gt 0 ]; then
  echo "❌ FAIL: Found $ERRORS PHP errors"
  exit 1
else
  echo "✅ PASS: No PHP errors"
fi

echo "All tests passed!"
```

**Run Tests:**
```bash
chmod +x tests/performance-test.sh
./tests/performance-test.sh
```

### Test Coverage

For detailed testing documentation, see [tests/TESTING.md](tests/TESTING.md).

---

## 🤝 Contributing

Contributions are welcome! Here's how you can help:

### Reporting Issues

1. Check existing issues first
2. Provide detailed information:
   - WordPress version
   - PHP version
   - Query Monitor version
   - Steps to reproduce
   - Expected vs actual behavior
   - Error messages or logs

### Submitting Pull Requests

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Make your changes
4. Add tests if applicable
5. Commit with clear messages: `git commit -m "Add feature X"`
6. Push to your fork: `git push origin feature/my-feature`
7. Submit a pull request

### Development Setup

```bash
# Clone the repository
git clone https://github.com/MervinPraison/query-monitor-cli.git
cd query-monitor-cli

# Create symlink to WordPress
ln -s $(pwd) /path/to/wordpress/wp-content/plugins/query-monitor-cli

# Install dependencies
cd /path/to/wordpress
wp plugin install query-monitor --activate
wp plugin activate query-monitor-cli

# Test your changes
wp qm env
```

### Coding Standards

- Follow WordPress Coding Standards
- Use meaningful variable and function names
- Add PHPDoc comments
- Test your changes thoroughly

---

## 📝 Changelog

### Version 0.1.0 (2025-11-08)

**Initial Release**

- ✅ WP-CLI commands for all major Query Monitor collectors
- ✅ REST API endpoints for programmatic access
- ✅ Multiple output formats (table, JSON, CSV, YAML)
- ✅ Database query monitoring with slow query detection
- ✅ Performance profiling (time, memory, queries)
- ✅ HTTP request monitoring
- ✅ WordPress hooks tracking
- ✅ PHP error monitoring
- ✅ Comprehensive documentation
- ✅ Testing guide
- ✅ CI/CD integration examples

---

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

See [LICENSE](LICENSE) file for full license text.

---

## 👏 Credits

- **Built on top of**: [Query Monitor](https://querymonitor.com/) by [John Blackbourn](https://johnblackbourn.com/)
- **Developed by**: [Praison](https://praison.com)
- **Inspired by**: The WordPress developer community

---

## 🔗 Links

- **GitHub Repository**: https://github.com/MervinPraison/query-monitor-cli
- **Issue Tracker**: https://github.com/MervinPraison/query-monitor-cli/issues
- **Query Monitor**: https://wordpress.org/plugins/query-monitor/
- **WP-CLI**: https://wp-cli.org/

---

## 🌟 Support

If you find this plugin helpful, please:

- ⭐ Star the repository on GitHub
- 🐛 Report bugs and request features
- 📖 Improve documentation
- 💻 Contribute code
- 📢 Share with other WordPress developers

---

**Made with ❤️ for WordPress developers**

