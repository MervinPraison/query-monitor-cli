# Query Monitor CLI

WP-CLI commands and REST API endpoints for Query Monitor debugging.

## Description

Query Monitor CLI extends the popular [Query Monitor](https://wordpress.org/plugins/query-monitor/) plugin by providing command-line and REST API access to its powerful debugging features. Monitor database queries, profile performance, track HTTP requests, and more - all from the command line or via REST API.

## Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- WP-CLI 2.5 or higher (for CLI commands)
- Query Monitor 3.16 or higher (required dependency)

## Installation

### Via WP-CLI

```bash
# Install Query Monitor first
wp plugin install query-monitor --activate

# Clone this repository
cd wp-content/plugins
git clone https://github.com/praison/query-monitor-cli.git

# Activate the plugin
wp plugin activate query-monitor-cli
```

### Manual Installation

1. Install and activate [Query Monitor](https://wordpress.org/plugins/query-monitor/)
2. Download this plugin
3. Upload to `wp-content/plugins/query-monitor-cli`
4. Activate via WordPress admin or WP-CLI

## Features

### ✅ WP-CLI Commands

Access Query Monitor data directly from the command line:

- **`wp qm env`** - Display environment information (PHP, WordPress, Database)
- **`wp qm db`** - Monitor database queries
- **`wp qm profile`** - Profile command performance
- **`wp qm http`** - Monitor HTTP requests
- **`wp qm hooks`** - Track WordPress hooks
- **`wp qm errors`** - Monitor PHP errors

### ✅ REST API Endpoints

Access Query Monitor data via REST API:

- **`GET /wp-json/query-monitor/v1/environment`** - Environment information
- **`POST /wp-json/query-monitor/v1/database`** - Database queries
- **`POST /wp-json/query-monitor/v1/profile`** - Performance profile
- **`POST /wp-json/query-monitor/v1/http`** - HTTP requests
- **`POST /wp-json/query-monitor/v1/hooks`** - Hooks information
- **`POST /wp-json/query-monitor/v1/errors`** - PHP errors

## Usage Examples

### WP-CLI Commands

#### Environment Information

```bash
# Display environment info
wp qm env

# Output as JSON
wp qm env --format=json
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

#### Database Query Monitoring

```bash
# Monitor current database queries
wp qm db

# Monitor queries from a specific command
wp qm db post list

# Show only slow queries (over 0.1 seconds)
wp qm db post list --slow-only --threshold=0.1

# Output as JSON
wp qm db post list --format=json
```

**Output:**
```
Total Queries: 4 | Total Time: 0.0272s

#	Time	Type	Caller	SQL
1	0.0167s	SELECT	WP_Query->get_posts	SELECT   wp_posts.*...
2	0.0027s	SELECT	WP_Term_Query->get_terms	SELECT DISTINCT t.term_id...
3	0.0005s	SELECT	_prime_term_caches	SELECT t.*, tt.* FROM wp_terms...
4	0.0072s	SELECT	update_meta_cache	SELECT post_id, meta_key...
```

#### Performance Profiling

```bash
# Profile current state
wp qm profile

# Profile a specific command
wp qm profile post list

# Profile with JSON output
wp qm profile cache flush --format=json
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

#### HTTP Request Monitoring

```bash
# Monitor HTTP requests
wp qm http

# Monitor HTTP requests from a command
wp qm http plugin update --all

# JSON output
wp qm http --format=json
```

#### Hook Tracking

```bash
# Track hooks
wp qm hooks

# Track hooks from a command
wp qm hooks post create --post_title="Test"

# JSON output
wp qm hooks --format=json
```

#### PHP Error Monitoring

```bash
# Check for PHP errors
wp qm errors

# Monitor errors from a command
wp qm errors plugin activate my-plugin

# JSON output
wp qm errors --format=json
```

### REST API Usage

#### Authentication

REST API endpoints require authentication. Use WordPress Application Passwords:

```bash
# Create an application password
wp user application-password create 1 "API Access" --porcelain
```

#### Example Requests

**Environment Information:**
```bash
curl -u "username:app_password" \
  "https://example.com/wp-json/query-monitor/v1/environment"
```

**Database Queries:**
```bash
curl -X POST -u "username:app_password" \
  "https://example.com/wp-json/query-monitor/v1/database"
```

**Performance Profile:**
```bash
curl -X POST -u "username:app_password" \
  "https://example.com/wp-json/query-monitor/v1/profile"
```

**Response Format:**
```json
{
  "success": true,
  "data": {
    "php": {
      "version": "8.4.6",
      "memory_limit": "256M",
      "max_execution_time": "30"
    },
    "wordpress": {
      "version": "6.8.3",
      "multisite": false
    },
    "database": {
      "extension": "mysqli",
      "server": "MySQL",
      "version": "8.0.35"
    }
  }
}
```

## Command Reference

### `wp qm env`

Display environment information.

**Options:**
- `--format=<format>` - Output format (table, json, yaml, csv). Default: table

**Examples:**
```bash
wp qm env
wp qm env --format=json
```

### `wp qm db`

Monitor database queries.

**Options:**
- `[<command>...]` - WP-CLI command to monitor (optional)
- `--format=<format>` - Output format (table, json, csv). Default: table
- `--slow-only` - Show only slow queries
- `--threshold=<seconds>` - Slow query threshold. Default: 0.05

**Examples:**
```bash
wp qm db
wp qm db post list
wp qm db post list --slow-only --threshold=0.1
wp qm db post list --format=json
```

### `wp qm profile`

Profile command performance.

**Options:**
- `[<command>...]` - WP-CLI command to profile (optional)
- `--format=<format>` - Output format (table, json). Default: table

**Examples:**
```bash
wp qm profile
wp qm profile post list
wp qm profile cache flush --format=json
```

### `wp qm http`

Monitor HTTP requests.

**Options:**
- `[<command>...]` - WP-CLI command to monitor (optional)
- `--format=<format>` - Output format (table, json). Default: table

**Examples:**
```bash
wp qm http
wp qm http plugin update --all
wp qm http --format=json
```

### `wp qm hooks`

Monitor WordPress hooks.

**Options:**
- `[<command>...]` - WP-CLI command to monitor (optional)
- `--format=<format>` - Output format (table, json). Default: table

**Examples:**
```bash
wp qm hooks
wp qm hooks post create --post_title="Test"
wp qm hooks --format=json
```

### `wp qm errors`

Monitor PHP errors.

**Options:**
- `[<command>...]` - WP-CLI command to monitor (optional)
- `--format=<format>` - Output format (table, json). Default: table

**Examples:**
```bash
wp qm errors
wp qm errors plugin activate my-plugin
wp qm errors --format=json
```

## Use Cases

### Development

- **Debug slow queries** during development
- **Profile performance** of custom code
- **Monitor HTTP requests** to external APIs
- **Track hook execution** order

### Testing

- **Automated testing** with JSON output
- **Performance regression** detection
- **Error monitoring** in CI/CD pipelines
- **Query optimization** validation

### DevOps

- **Deployment scripts** with performance monitoring
- **Health checks** via REST API
- **Automated profiling** of critical operations
- **Performance baselines** for comparison

## Integration Examples

### CI/CD Pipeline

```bash
#!/bin/bash
# Check for slow queries in deployment
SLOW_QUERIES=$(wp qm db post list --slow-only --threshold=0.1 --format=json | jq 'length')

if [ "$SLOW_QUERIES" -gt 0 ]; then
  echo "Warning: Found $SLOW_QUERIES slow queries"
  exit 1
fi
```

### Performance Monitoring Script

```bash
#!/bin/bash
# Profile critical operations
wp qm profile "cache flush" --format=json > profile-cache.json
wp qm profile "post list" --format=json > profile-posts.json

# Compare with baseline
```

### REST API Monitoring

```javascript
// Node.js example
const axios = require('axios');

async function checkPerformance() {
  const response = await axios.post(
    'https://example.com/wp-json/query-monitor/v1/profile',
    {},
    {
      auth: {
        username: 'admin',
        password: 'app_password'
      }
    }
  );
  
  console.log('Execution Time:', response.data.data.execution_time);
  console.log('Database Queries:', response.data.data.db_queries);
}
```

## Troubleshooting

### "Query Monitor plugin is not active"

Make sure Query Monitor is installed and activated:
```bash
wp plugin install query-monitor --activate
```

### "Environment collector not found"

This usually means Query Monitor collectors aren't loading. Try:
1. Deactivate and reactivate both plugins
2. Check for PHP errors in debug.log
3. Verify Query Monitor version is 3.16+

### REST API returns 401

You need to authenticate with WordPress Application Passwords:
```bash
wp user application-password create 1 "API Access"
```

### No queries recorded

If monitoring a command and no queries show up:
1. Make sure the command actually runs queries
2. Try a simple command first: `wp qm db post list`
3. Check if SAVEQUERIES is defined as false

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## Testing

See [tests/TESTING.md](tests/TESTING.md) for detailed testing instructions.

## Changelog

### 0.1.0 (2025-11-08)
- Initial release
- WP-CLI commands for all major Query Monitor collectors
- REST API endpoints for programmatic access
- Support for JSON, table, CSV output formats
- Database query monitoring with slow query detection
- Performance profiling
- HTTP request monitoring
- Hook tracking
- PHP error monitoring

## License

GPL v2 or later

## Credits

- Built on top of [Query Monitor](https://querymonitor.com/) by John Blackbourn
- Developed by Praison

## Support

- [GitHub Issues](https://github.com/praison/query-monitor-cli/issues)
- [Documentation](https://github.com/praison/query-monitor-cli)

## Related Projects

- [Query Monitor](https://wordpress.org/plugins/query-monitor/) - The core debugging plugin
- [WP-CLI](https://wp-cli.org/) - WordPress command-line interface

---

**Made with ❤️ for WordPress developers**
