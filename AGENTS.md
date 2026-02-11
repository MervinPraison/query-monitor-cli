# Praison Debug CLI for Query Monitor

WordPress plugin providing WP-CLI commands and REST API endpoints for Query Monitor.

## Structure

- `query-monitor-cli.php` — Main plugin entry point
- `includes/class-praison-qmcli-base.php` — Abstract base class for CLI commands
- `includes/class-praison-qmcli-commands.php` — WP-CLI command implementations (`wp qm`)
- `includes/class-praison-qmcli-rest-api.php` — REST API endpoint registrations (`praison-qmcli/v1`)
- `uninstall.php` — Cleanup on uninstall (currently no-op)
- `readme.txt` — WordPress.org plugin directory readme

## Naming Conventions

- **Plugin slug**: `praison-cli-for-query-monitor` (pending WordPress.org approval)
- **Text domain**: `praison-cli-for-query-monitor`
- **Constants prefix**: `PRAISON_QMCLI_`
- **Functions prefix**: `praison_qmcli_`
- **Class prefix**: `Praison_QMCLI_`
- **REST namespace**: `praison-qmcli/v1`

## Build & Release

1. Ensure `.distignore` excludes dev files (tests, .git, *.md except readme.txt)
2. Build distribution zip:
   ```bash
   # Install wp-cli dist-archive if needed
   wp package install wp-cli/dist-archive-command
   
   # Create distributable zip (respects .distignore)
   wp dist-archive .
   ```
3. Upload the generated zip via WordPress.org "Add your plugin" page
4. For SVN deployment after approval:
   ```bash
   svn co https://plugins.svn.wordpress.org/praison-cli-for-query-monitor/ svn-repo
   cp -r . svn-repo/trunk/  # copy plugin files
   cd svn-repo
   svn add trunk/* --force
   svn ci -m "Release v0.1.0" --username mervinpraison
   svn cp trunk tags/0.1.0
   svn ci -m "Tag v0.1.0" --username mervinpraison
   ```

## PHP Syntax Check

```bash
find . -name "*.php" -not -path "./.git/*" | xargs -I{} php -l {}
```

## Testing

See `tests/TESTING.md` for manual WP-CLI and REST API test procedures. Requires a running WordPress instance with Query Monitor activated.
