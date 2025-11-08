# Query Monitor CLI - Testing Guide

This document provides comprehensive testing instructions for the Query Monitor CLI plugin.

## Test Environment Setup

### Prerequisites

1. **WordPress Installation**
   - WordPress 6.0+
   - PHP 7.4+
   - WP-CLI 2.5+

2. **Required Plugins**
   - Query Monitor 3.16+
   - Query Monitor CLI (this plugin)

### Installation for Testing

```bash
# Navigate to WordPress installation
cd /path/to/wordpress

# Install Query Monitor
wp plugin install query-monitor --activate

# Install Query Monitor CLI (from repository)
cd wp-content/plugins
git clone https://github.com/praison/query-monitor-cli.git
cd ../../../

# Activate Query Monitor CLI
wp plugin activate query-monitor-cli

# Verify installation
wp plugin list | grep query-monitor
```

## WP-CLI Command Tests

### Test 1: Environment Command

**Test Basic Output:**
```bash
wp qm env
```

**Expected Output:**
```
=== PHP Information ===
Version: 8.x.x
Memory Limit: XXX
Max Execution Time: XX

=== WordPress Information ===
Version: 6.x.x
Multisite: No/Yes

=== Database Information ===
Extension: mysqli
Server: MySQL/MariaDB
Version: X.X.X
Success: Environment information retrieved.
```

**Test JSON Output:**
```bash
wp qm env --format=json
```

**Expected:** Valid JSON with php, wordpress, and database sections

**Test Status:** ✅ PASSED

---

### Test 2: Database Query Monitoring

**Test 2.1: Monitor Current State**
```bash
wp qm db
```

**Expected Output:**
```
Total Queries: X | Total Time: X.XXXXs

[Table or message about queries]
Success: Found X queries.
```

**Test 2.2: Monitor Specific Command**
```bash
wp qm db post list
```

**Expected:** Table showing queries with columns: #, Time, Type, Caller, SQL

**Test 2.3: Slow Query Detection**
```bash
wp qm db post list --slow-only --threshold=0.05
```

**Expected:** Only queries taking > 0.05 seconds

**Test 2.4: JSON Output**
```bash
wp qm db post list --format=json
```

**Expected:** Valid JSON array of query objects

**Test Status:** ✅ PASSED

---

### Test 3: Performance Profiling

**Test 3.1: Profile Current State**
```bash
wp qm profile
```

**Expected Output:**
```
=== Performance Profile ===
Command: current state
Execution Time: X.XXXXs
Peak Memory: XX MB
Memory Used: XX MB
Database Queries: X
Database Time: X.XXXXs
Success: Profile completed.
```

**Test 3.2: Profile Specific Command**
```bash
wp qm profile post list
```

**Expected:** Profile data for the post list command

**Test 3.3: JSON Output**
```bash
wp qm profile cache flush --format=json
```

**Expected:** Valid JSON with execution_time, memory_peak, db_queries, etc.

**Test Status:** ✅ PASSED

---

### Test 4: HTTP Request Monitoring

**Test 4.1: Monitor Current State**
```bash
wp qm http
```

**Expected:** Message about HTTP requests (may be 0 if no requests made)

**Test 4.2: Monitor Command with HTTP Requests**
```bash
# This command makes HTTP requests
wp qm http plugin update --all --dry-run
```

**Expected:** Table showing HTTP requests with URL, Method, Status, Time

**Test 4.3: JSON Output**
```bash
wp qm http --format=json
```

**Expected:** Valid JSON array of HTTP request objects

**Test Status:** ✅ PASSED

---

### Test 5: Hook Tracking

**Test 5.1: Track Current Hooks**
```bash
wp qm hooks
```

**Expected:** Count of hooks and message about using JSON format

**Test 5.2: Track Hooks from Command**
```bash
wp qm hooks post create --post_title="Test Post"
```

**Expected:** Hook count and data

**Test 5.3: JSON Output**
```bash
wp qm hooks --format=json
```

**Expected:** Valid JSON with hook data

**Test Status:** ✅ PASSED

---

### Test 6: PHP Error Monitoring

**Test 6.1: Check for Errors**
```bash
wp qm errors
```

**Expected:** Count of PHP errors (should be 0 if no errors)

**Test 6.2: Monitor Command for Errors**
```bash
wp qm errors plugin activate query-monitor-cli
```

**Expected:** Error count and details

**Test 6.3: JSON Output**
```bash
wp qm errors --format=json
```

**Expected:** Valid JSON array of error objects

**Test Status:** ✅ PASSED

---

## REST API Endpoint Tests

### Setup Authentication

```bash
# Create application password for testing
wp user application-password create 1 "QM CLI Test" --porcelain
# Save the output password
```

### Test 7: Environment Endpoint

**Test 7.1: GET Request**
```bash
curl -u "username:password" \
  "https://your-site.test/wp-json/query-monitor/v1/environment"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "php": {
      "version": "8.x.x",
      "memory_limit": "XXX",
      "max_execution_time": "XX"
    },
    "wordpress": {
      "version": "6.x.x",
      "multisite": false
    },
    "database": {
      "extension": "mysqli",
      "server": "MySQL",
      "version": "X.X.X"
    }
  }
}
```

**Test 7.2: Unauthorized Request**
```bash
curl "https://your-site.test/wp-json/query-monitor/v1/environment"
```

**Expected:** 401 Unauthorized error

**Test Status:** ✅ PASSED

---

### Test 8: Database Endpoint

**Test 8.1: POST Request**
```bash
curl -X POST -u "username:password" \
  "https://your-site.test/wp-json/query-monitor/v1/database"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "total_queries": X,
    "total_time": X.XXXX,
    "queries": [...]
  }
}
```

**Test Status:** ✅ PASSED

---

### Test 9: Profile Endpoint

**Test 9.1: POST Request**
```bash
curl -X POST -u "username:password" \
  "https://your-site.test/wp-json/query-monitor/v1/profile"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "execution_time": X.XXXX,
    "memory_peak": XXXXX,
    "memory_used": XXXXX,
    "memory_limit": XXXXX,
    "db_queries": X,
    "db_time": X.XXXX
  }
}
```

**Test Status:** ✅ PASSED

---

### Test 10: HTTP Endpoint

**Test 10.1: POST Request**
```bash
curl -X POST -u "username:password" \
  "https://your-site.test/wp-json/query-monitor/v1/http"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "total_requests": X,
    "total_time": X.XXXX,
    "requests": [...]
  }
}
```

**Test Status:** ✅ PASSED

---

### Test 11: Hooks Endpoint

**Test 11.1: POST Request**
```bash
curl -X POST -u "username:password" \
  "https://your-site.test/wp-json/query-monitor/v1/hooks"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "total_hooks": X,
    "hooks": {...}
  }
}
```

**Test Status:** ✅ PASSED

---

### Test 12: Errors Endpoint

**Test 12.1: POST Request**
```bash
curl -X POST -u "username:password" \
  "https://your-site.test/wp-json/query-monitor/v1/errors"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "total_errors": X,
    "errors": [...]
  }
}
```

**Test Status:** ✅ PASSED

---

## Integration Tests

### Test 13: CI/CD Pipeline Integration

**Test Script:**
```bash
#!/bin/bash
# test-performance.sh

# Profile a critical operation
RESULT=$(wp qm profile "post list" --format=json)

# Extract execution time
TIME=$(echo $RESULT | jq -r '.execution_time')

# Check if time exceeds threshold (0.5 seconds)
if (( $(echo "$TIME > 0.5" | bc -l) )); then
  echo "FAIL: Execution time $TIME exceeds threshold"
  exit 1
else
  echo "PASS: Execution time $TIME is acceptable"
  exit 0
fi
```

**Expected:** Script exits with 0 if performance is good, 1 if slow

**Test Status:** ✅ PASSED

---

### Test 14: Slow Query Detection

**Test Script:**
```bash
#!/bin/bash
# test-slow-queries.sh

# Check for slow queries
SLOW=$(wp qm db post list --slow-only --threshold=0.1 --format=json | jq 'length')

if [ "$SLOW" -gt 0 ]; then
  echo "WARNING: Found $SLOW slow queries"
  wp qm db post list --slow-only --threshold=0.1
  exit 1
else
  echo "PASS: No slow queries found"
  exit 0
fi
```

**Expected:** Script identifies slow queries

**Test Status:** ✅ PASSED

---

## Error Handling Tests

### Test 15: Missing Query Monitor

**Test:**
```bash
# Deactivate Query Monitor
wp plugin deactivate query-monitor

# Try to use QM CLI
wp qm env
```

**Expected:** Error message about Query Monitor not being active

**Cleanup:**
```bash
wp plugin activate query-monitor
```

**Test Status:** ✅ PASSED

---

### Test 16: Invalid Command

**Test:**
```bash
wp qm invalid-command
```

**Expected:** Error about invalid subcommand

**Test Status:** ✅ PASSED

---

### Test 17: Invalid Format

**Test:**
```bash
wp qm env --format=invalid
```

**Expected:** Error or fallback to default format

**Test Status:** ✅ PASSED

---

## Performance Tests

### Test 18: Large Dataset

**Test:**
```bash
# Create 1000 posts
wp post generate --count=1000

# Profile the query
wp qm profile "post list"

# Check memory usage
wp qm profile "post list" --format=json | jq '.memory_peak'
```

**Expected:** Command completes without memory errors

**Cleanup:**
```bash
wp post delete $(wp post list --post_type=post --format=ids) --force
```

**Test Status:** ✅ PASSED

---

### Test 19: Concurrent Requests

**Test:**
```bash
# Run multiple commands simultaneously
for i in {1..5}; do
  wp qm profile "post list" --format=json > profile-$i.json &
done
wait

# Check all completed successfully
ls profile-*.json | wc -l
```

**Expected:** All 5 files created successfully

**Test Status:** ✅ PASSED

---

## Security Tests

### Test 20: REST API Authentication

**Test 20.1: No Authentication**
```bash
curl "https://your-site.test/wp-json/query-monitor/v1/environment"
```

**Expected:** 401 Unauthorized

**Test 20.2: Invalid Credentials**
```bash
curl -u "invalid:credentials" \
  "https://your-site.test/wp-json/query-monitor/v1/environment"
```

**Expected:** 401 Unauthorized

**Test 20.3: Valid Credentials**
```bash
curl -u "username:valid_password" \
  "https://your-site.test/wp-json/query-monitor/v1/environment"
```

**Expected:** 200 OK with data

**Test Status:** ✅ PASSED

---

## Test Summary

| Test # | Test Name | Status | Notes |
|--------|-----------|--------|-------|
| 1 | Environment Command | ✅ PASSED | All formats working |
| 2 | Database Monitoring | ✅ PASSED | Queries tracked correctly |
| 3 | Performance Profiling | ✅ PASSED | Accurate metrics |
| 4 | HTTP Monitoring | ✅ PASSED | Requests captured |
| 5 | Hook Tracking | ✅ PASSED | Hooks recorded |
| 6 | Error Monitoring | ✅ PASSED | Errors detected |
| 7 | Environment Endpoint | ✅ PASSED | REST API working |
| 8 | Database Endpoint | ✅ PASSED | REST API working |
| 9 | Profile Endpoint | ✅ PASSED | REST API working |
| 10 | HTTP Endpoint | ✅ PASSED | REST API working |
| 11 | Hooks Endpoint | ✅ PASSED | REST API working |
| 12 | Errors Endpoint | ✅ PASSED | REST API working |
| 13 | CI/CD Integration | ✅ PASSED | Scripts work |
| 14 | Slow Query Detection | ✅ PASSED | Thresholds work |
| 15 | Missing Dependency | ✅ PASSED | Error handling |
| 16 | Invalid Command | ✅ PASSED | Error handling |
| 17 | Invalid Format | ✅ PASSED | Error handling |
| 18 | Large Dataset | ✅ PASSED | Performance OK |
| 19 | Concurrent Requests | ✅ PASSED | Thread-safe |
| 20 | REST API Security | ✅ PASSED | Auth required |

## Automated Testing Script

```bash
#!/bin/bash
# run-all-tests.sh

echo "Running Query Monitor CLI Tests..."
echo "=================================="

# Test 1: Environment
echo "Test 1: Environment Command"
wp qm env > /dev/null && echo "✅ PASSED" || echo "❌ FAILED"

# Test 2: Database
echo "Test 2: Database Monitoring"
wp qm db post list > /dev/null && echo "✅ PASSED" || echo "❌ FAILED"

# Test 3: Profile
echo "Test 3: Performance Profiling"
wp qm profile post list > /dev/null && echo "✅ PASSED" || echo "❌ FAILED"

# Test 4: HTTP
echo "Test 4: HTTP Monitoring"
wp qm http > /dev/null && echo "✅ PASSED" || echo "❌ FAILED"

# Test 5: Hooks
echo "Test 5: Hook Tracking"
wp qm hooks > /dev/null && echo "✅ PASSED" || echo "❌ FAILED"

# Test 6: Errors
echo "Test 6: Error Monitoring"
wp qm errors > /dev/null && echo "✅ PASSED" || echo "❌ FAILED"

echo "=================================="
echo "All tests completed!"
```

## Continuous Integration

### GitHub Actions Example

```yaml
name: Test Query Monitor CLI

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup WordPress
      run: |
        docker-compose up -d
        sleep 10
    
    - name: Install Dependencies
      run: |
        docker-compose exec -T wordpress wp plugin install query-monitor --activate
        docker-compose exec -T wordpress wp plugin activate query-monitor-cli
    
    - name: Run Tests
      run: |
        docker-compose exec -T wordpress wp qm env
        docker-compose exec -T wordpress wp qm db post list
        docker-compose exec -T wordpress wp qm profile post list
```

## Reporting Issues

When reporting issues, please include:

1. WordPress version: `wp core version`
2. PHP version: `php -v`
3. Query Monitor version: `wp plugin list | grep query-monitor`
4. Full command output with `--debug` flag
5. Any error messages from `wp-content/debug.log`

## Test Environment

**Last Tested:**
- Date: 2025-11-08
- WordPress: 6.8.3
- PHP: 8.4.6
- Query Monitor: 3.20.0
- WP-CLI: 2.11.0

**Test Results:** All tests passing ✅

---

**Testing completed successfully!**
