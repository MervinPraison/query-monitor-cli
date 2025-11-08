# Product Requirements Document (PRD)
## Query Monitor CLI - WP-CLI Integration

---

## 1. Executive Summary

### Product Name
Query Monitor CLI

### Version
1.0.0

### Purpose
Extend the existing Query Monitor WordPress plugin to provide comprehensive debugging and monitoring capabilities through WP-CLI commands, enabling developers to access Query Monitor's powerful debugging features during CLI-based development, testing, and automation workflows.

### Target Users
- WordPress developers using WP-CLI for development
- DevOps engineers automating WordPress deployments
- QA engineers running automated tests
- Developers building headless WordPress applications
- Anyone needing debugging information without browser access

---

## 2. Problem Statement

### Current Limitations
Query Monitor currently provides debugging information only through browser-based interfaces:
- Admin toolbar panel (for logged-in users)
- HTTP headers (for Ajax/REST API requests)
- Browser console output (for JavaScript errors)

### Pain Points
1. **No CLI Access**: Developers using WP-CLI cannot access Query Monitor's debugging data
2. **Testing Bottleneck**: Automated testing scripts cannot capture performance metrics
3. **Headless Development**: Developers building headless WordPress apps lack debugging visibility
4. **CI/CD Integration**: No way to integrate Query Monitor data into continuous integration pipelines
5. **Script Debugging**: PHP scripts executed via WP-CLI cannot be profiled with Query Monitor

---

## 3. Solution Overview

Create a WP-CLI command suite that exposes Query Monitor's data collectors and provides formatted output suitable for CLI environments. The solution will:

- **Leverage existing Query Monitor collectors** (no code duplication)
- **Require Query Monitor plugin** to be installed and active
- **Provide CLI-formatted output** (tables, JSON, CSV)
- **Support all major Query Monitor features** accessible via CLI
- **Enable automation** through machine-readable output formats

---

## 4. Core Features

### 4.1 Database Query Monitoring

**Command**: `wp qm db`

**Capabilities**:
- Display all database queries executed during a WP-CLI command
- Show query execution time, affected rows, and query type
- Group queries by component (plugin/theme)
- Filter by query type (SELECT, INSERT, UPDATE, DELETE)
- Identify slow queries (configurable threshold)
- Detect duplicate queries
- Show query errors

**Output Options**:
- Table format (default)
- JSON format (for parsing)
- Summary statistics only

**Example Usage**:
```bash
wp qm db --command="post list" --format=table
wp qm db --command="post list" --slow-only --threshold=0.05
wp qm db --command="post list" --format=json > queries.json
```

### 4.2 Performance Profiling

**Command**: `wp qm profile`

**Capabilities**:
- Measure execution time of WP-CLI commands
- Track memory usage (peak and current)
- Show timing breakdown by component
- Display hook execution times
- Profile custom code sections

**Output Metrics**:
- Total execution time
- Peak memory usage
- Memory limit and usage percentage
- Time limit and usage percentage
- Query count and total query time

**Example Usage**:
```bash
wp qm profile "post list --post_type=page"
wp qm profile "cache flush" --format=json
```

### 4.3 HTTP API Request Monitoring

**Command**: `wp qm http`

**Capabilities**:
- Monitor all HTTP requests made during CLI execution
- Show request URL, method, response code
- Display request/response headers
- Track request duration
- Identify failed requests
- Show responsible component

**Example Usage**:
```bash
wp qm http --command="plugin update --all"
wp qm http --command="transient delete --all" --failed-only
```

### 4.4 Hook & Action Tracking

**Command**: `wp qm hooks`

**Capabilities**:
- List all hooks fired during command execution
- Show callbacks attached to each hook
- Display hook priorities
- Identify responsible components
- Filter by hook name or component

**Example Usage**:
```bash
wp qm hooks --command="post create --post_title='Test'"
wp qm hooks --filter="save_post" --command="post create"
```

### 4.5 PHP Error Monitoring

**Command**: `wp qm errors`

**Capabilities**:
- Capture PHP errors, warnings, notices
- Show deprecated function usage
- Display "Doing it Wrong" notices
- Include stack traces
- Group by severity level
- Show responsible component

**Example Usage**:
```bash
wp qm errors --command="plugin activate my-plugin"
wp qm errors --severity=warning --command="theme activate my-theme"
```

### 4.6 Environment Information

**Command**: `wp qm env`

**Capabilities**:
- Display PHP configuration (version, memory limit, extensions)
- Show MySQL/MariaDB information
- List WordPress constants
- Show server information
- Display loaded plugins and themes

**Example Usage**:
```bash
wp qm env
wp qm env --format=json
```

### 4.7 Capability Checks

**Command**: `wp qm caps`

**Capabilities**:
- Track user capability checks during command execution
- Show check results (allowed/denied)
- Display capability parameters
- Identify responsible component

**Example Usage**:
```bash
wp qm caps --command="post delete 123" --user=editor
```

### 4.8 Transient Monitoring

**Command**: `wp qm transients`

**Capabilities**:
- Track transient operations (set, get, delete)
- Show transient keys and values
- Display expiration times
- Identify responsible component

**Example Usage**:
```bash
wp qm transients --command="cache flush"
```

### 4.9 Comprehensive Monitoring

**Command**: `wp qm run`

**Capabilities**:
- Execute any WP-CLI command with full Query Monitor instrumentation
- Collect data from all collectors simultaneously
- Generate comprehensive report
- Support multiple output formats

**Example Usage**:
```bash
wp qm run "post list --post_type=page" --report=full
wp qm run "plugin update --all" --format=json --output=report.json
```

---

## 5. Technical Architecture

### 5.1 Plugin Structure

```
query-monitor-cli/
├── query-monitor-cli.php          # Main plugin file
├── composer.json                   # Dependencies
├── includes/
│   ├── class-qm-cli-command.php   # Base command class
│   ├── class-qm-cli-runner.php    # Command execution wrapper
│   ├── class-qm-cli-formatter.php # Output formatting
│   └── commands/
│       ├── class-qm-db-command.php
│       ├── class-qm-profile-command.php
│       ├── class-qm-http-command.php
│       ├── class-qm-hooks-command.php
│       ├── class-qm-errors-command.php
│       ├── class-qm-env-command.php
│       ├── class-qm-caps-command.php
│       ├── class-qm-transients-command.php
│       └── class-qm-run-command.php
└── tests/
    └── test-qm-cli-commands.php
```

### 5.2 Integration Approach

**Dependency on Query Monitor**:
- Check if Query Monitor is installed and active
- Use Query Monitor's existing collector classes
- Access data through Query Monitor's data storage
- No code duplication from Query Monitor

**Data Collection Flow**:
1. Register WP-CLI command
2. Verify Query Monitor is active
3. Initialize Query Monitor collectors
4. Execute target WP-CLI command in isolated context
5. Collect data from Query Monitor collectors
6. Format output based on user preference
7. Display results

**Key Classes to Leverage**:
- `QM_Collector` - Base collector class
- `QM_Collector_DB_Queries` - Database queries
- `QM_Collector_Overview` - Performance metrics
- `QM_Collector_HTTP` - HTTP requests
- `QM_Collector_Hooks` - Hook tracking
- `QM_Collector_PHP_Errors` - Error tracking
- `QM_Collector_Environment` - Environment info
- `QM_Collector_Caps` - Capability checks
- `QM_Collector_Transients` - Transient operations

### 5.3 Output Formats

**Table Format** (Default):
- Human-readable ASCII tables
- Color-coded for terminals supporting ANSI
- Suitable for direct viewing

**JSON Format**:
- Machine-readable structured data
- Suitable for parsing and automation
- Preserves all data fields

**CSV Format**:
- Spreadsheet-compatible
- Suitable for data analysis
- Simplified data structure

**Summary Format**:
- High-level statistics only
- Quick overview
- Minimal output

---

## 6. User Stories

### US-1: Database Query Analysis
**As a** WordPress developer  
**I want to** see all database queries executed by a WP-CLI command  
**So that** I can identify slow or duplicate queries in my scripts

**Acceptance Criteria**:
- All queries are captured and displayed
- Query execution time is shown
- Queries can be filtered by type
- Slow queries are highlighted
- Duplicate queries are identified

### US-2: Performance Profiling
**As a** DevOps engineer  
**I want to** measure the performance of WP-CLI commands  
**So that** I can optimize deployment scripts

**Acceptance Criteria**:
- Execution time is measured accurately
- Memory usage is tracked
- Results can be exported to JSON
- Historical comparison is possible

### US-3: HTTP Request Monitoring
**As a** developer  
**I want to** monitor external HTTP requests during CLI execution  
**So that** I can debug API integration issues

**Acceptance Criteria**:
- All HTTP requests are logged
- Request and response details are shown
- Failed requests are highlighted
- Responsible component is identified

### US-4: Error Detection
**As a** QA engineer  
**I want to** capture PHP errors during automated tests  
**So that** I can identify issues before deployment

**Acceptance Criteria**:
- All PHP errors are captured
- Stack traces are included
- Errors can be filtered by severity
- Output can be parsed by CI tools

### US-5: CI/CD Integration
**As a** DevOps engineer  
**I want to** integrate Query Monitor data into CI/CD pipelines  
**So that** I can fail builds on performance regressions

**Acceptance Criteria**:
- JSON output is available
- Exit codes indicate issues
- Thresholds can be configured
- Reports can be archived

---

## 7. Non-Functional Requirements

### 7.1 Performance
- Minimal overhead on command execution (< 5%)
- Efficient data collection
- Optimized output formatting

### 7.2 Compatibility
- WordPress 5.9+ (matches Query Monitor requirements)
- PHP 7.4+ (matches Query Monitor requirements)
- WP-CLI 2.5+
- Query Monitor 3.16+

### 7.3 Reliability
- Graceful degradation if Query Monitor is not available
- Error handling for invalid commands
- Safe execution in production environments

### 7.4 Usability
- Intuitive command structure
- Comprehensive help documentation
- Consistent output formatting
- Clear error messages

### 7.5 Security
- No sensitive data exposure
- Respect WordPress user capabilities
- Safe for production use (with warnings)

---

## 8. Dependencies

### Required
- **Query Monitor Plugin** (3.16+): Core functionality provider
- **WP-CLI** (2.5+): Command-line interface
- **WordPress** (5.9+): Platform
- **PHP** (7.4+): Runtime environment

### Optional
- **WP-CLI Table Package**: Enhanced table formatting
- **Symfony Console**: Advanced CLI features

---

## 9. Installation & Setup

### Installation Steps
1. Install Query Monitor plugin
2. Install Query Monitor CLI plugin
3. Activate both plugins
4. Verify WP-CLI can access commands

### Verification
```bash
wp qm --help
wp qm env
```

---

## 10. Success Metrics

### Adoption Metrics
- Number of installations
- Active users
- Command execution frequency

### Performance Metrics
- Command execution overhead
- Data collection accuracy
- Output generation speed

### Quality Metrics
- Bug reports
- Feature requests
- User satisfaction ratings

---

## 11. Future Enhancements

### Phase 2 Features
- Real-time monitoring with `--watch` flag
- Historical data storage and comparison
- Custom collector support
- Integration with external monitoring tools
- GraphQL query monitoring
- Block editor performance tracking

### Phase 3 Features
- Web-based dashboard for CLI data
- Automated performance regression detection
- Machine learning-based optimization suggestions
- Integration with popular CI/CD platforms

---

## 12. Risks & Mitigations

### Risk 1: Query Monitor API Changes
**Mitigation**: Version compatibility checks, comprehensive testing

### Risk 2: Performance Overhead
**Mitigation**: Efficient data collection, optional features, performance testing

### Risk 3: Output Format Complexity
**Mitigation**: Multiple format options, clear documentation, examples

### Risk 4: WP-CLI Command Conflicts
**Mitigation**: Unique command namespace (`qm`), conflict detection

---

## 13. Documentation Requirements

### User Documentation
- Installation guide
- Command reference
- Usage examples
- Best practices
- Troubleshooting guide

### Developer Documentation
- Architecture overview
- API reference
- Extension guide
- Contributing guidelines

---

## 14. Testing Strategy

### Unit Tests
- Individual command functionality
- Data collection accuracy
- Output formatting

### Integration Tests
- Query Monitor integration
- WP-CLI integration
- WordPress compatibility

### Performance Tests
- Execution overhead measurement
- Memory usage tracking
- Large dataset handling

### User Acceptance Tests
- Real-world scenarios
- Developer feedback
- Usability testing

---

## 15. Release Plan

### Alpha Release (v0.1.0)
- Core commands: `db`, `profile`, `env`
- Table output format
- Basic documentation

### Beta Release (v0.5.0)
- All core commands implemented
- JSON output format
- Comprehensive documentation
- Community testing

### Stable Release (v1.0.0)
- Production-ready
- Full test coverage
- Complete documentation
- WordPress.org submission

---

## 16. Support & Maintenance

### Support Channels
- GitHub Issues
- WordPress.org support forum
- Documentation site
- Community Slack/Discord

### Maintenance Plan
- Regular updates for WordPress compatibility
- Bug fixes within 7 days
- Feature releases quarterly
- Security patches as needed

---

## Appendix A: Query Monitor Features Reference

### Available Collectors (from Query Monitor)
1. **DB Queries** - Database query monitoring
2. **Overview** - Performance overview
3. **HTTP** - HTTP API requests
4. **Hooks** - WordPress hooks and actions
5. **PHP Errors** - Error tracking
6. **Environment** - System information
7. **Capabilities** - User capability checks
8. **Transients** - Transient operations
9. **Admin** - Admin screen information
10. **Assets** - Scripts and styles
11. **Block Editor** - Block editor debugging
12. **Cache** - Object cache operations
13. **Conditionals** - WordPress conditionals
14. **Languages** - Translation files
15. **Multisite** - Multisite operations
16. **Raw Request** - Raw request data
17. **Redirects** - Redirect tracking
18. **Request** - Request information
19. **Theme** - Theme information
20. **Timing** - Timing information

### CLI-Accessible Features
- ✅ Database queries
- ✅ Performance profiling
- ✅ HTTP requests
- ✅ Hooks and actions
- ✅ PHP errors
- ✅ Environment info
- ✅ Capability checks
- ✅ Transients
- ⚠️ Admin screen (limited - CLI context)
- ⚠️ Assets (limited - no frontend)
- ❌ Block editor (not applicable in CLI)
- ✅ Cache operations
- ✅ Conditionals
- ✅ Languages
- ✅ Multisite
- ✅ Request information
- ✅ Theme information
- ✅ Timing information

---

## Appendix B: Example Commands

```bash
# Database query analysis
wp qm db --command="post list" --format=table
wp qm db --command="post list" --slow-only --threshold=0.05
wp qm db --command="post list" --format=json > queries.json

# Performance profiling
wp qm profile "post list --post_type=page"
wp qm profile "cache flush" --format=json

# HTTP request monitoring
wp qm http --command="plugin update --all"
wp qm http --command="transient delete --all" --failed-only

# Hook tracking
wp qm hooks --command="post create --post_title='Test'"
wp qm hooks --filter="save_post"

# Error monitoring
wp qm errors --command="plugin activate my-plugin"
wp qm errors --severity=warning

# Environment information
wp qm env
wp qm env --format=json

# Comprehensive monitoring
wp qm run "post list --post_type=page" --report=full
wp qm run "plugin update --all" --format=json --output=report.json

# CI/CD integration example
wp qm run "my-custom-command" --format=json | jq '.performance.time_taken' | \
  awk '{if ($1 > 5.0) exit 1}'
```

---

## Document History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | 2025-11-08 | Initial | Initial PRD creation |

---

**Document Status**: Draft  
**Last Updated**: 2025-11-08  
**Next Review**: Before Alpha Release
