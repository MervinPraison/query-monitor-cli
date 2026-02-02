=== Query Monitor CLI ===
Contributors: mervinpraison
Donate link: https://praison.ai/
Tags: query-monitor, debug, performance, cli, rest-api
Requires at least: 6.7
Tested up to: 6.9
Stable tag: 0.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WP-CLI commands and REST API endpoints for Query Monitor debugging.

== Description ==

Query Monitor CLI brings the power of Query Monitor to the command line and REST API.

**Features:**

* **WP-CLI Commands** - Run Query Monitor debugging from the command line
* **REST API Endpoints** - Access Query Monitor data via REST API
* **Performance Profiling** - Profile WP-CLI commands for database queries, memory, and execution time
* **Inspect Posts/Pages** - Get complete Query Monitor analysis for any post, page, or URL

**Requires Query Monitor plugin to be installed and activated.**

**Available Commands:**

* `wp qm env` - Display environment information
* `wp qm db [command]` - Monitor database queries
* `wp qm profile [command]` - Profile a WP-CLI command
* `wp qm http [command]` - Monitor HTTP requests
* `wp qm hooks [command]` - Monitor WordPress hooks
* `wp qm errors [command]` - Monitor PHP errors
* `wp qm inspect --post_id=123` - Inspect a specific post/page

**REST API Endpoints:**

* `GET /query-monitor/v1/environment` - Get environment info
* `POST /query-monitor/v1/database` - Get database queries
* `POST /query-monitor/v1/profile` - Get performance profile
* `POST /query-monitor/v1/http` - Get HTTP requests
* `POST /query-monitor/v1/hooks` - Get hooks info
* `POST /query-monitor/v1/errors` - Get PHP errors
* `GET /query-monitor/v1/inspect` - Inspect post/page/URL

== Installation ==

1. Install and activate the Query Monitor plugin
2. Upload the `query-monitor-cli` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Use WP-CLI commands: `wp qm env`

== Frequently Asked Questions ==

= Does this require Query Monitor? =

Yes, Query Monitor must be installed and activated for this plugin to work.

= Who can access the REST API endpoints? =

Users with `view_query_monitor` or `manage_options` capability.

= Can I filter the output? =

Yes, use `--format=json` for JSON output or `--collectors=db_queries,http` to filter specific collectors.

== Screenshots ==

1. WP-CLI environment command output
2. Database queries monitoring
3. Performance profiling

== Changelog ==

= 0.1.0 =
* Initial release
* WP-CLI commands: env, db, profile, http, hooks, errors, inspect
* REST API endpoints for all Query Monitor collectors
* Permission checks for REST API access

== Upgrade Notice ==

= 0.1.0 =
Initial release.
