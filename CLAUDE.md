# Softaculous VPS Addon — MyAdmin Plugin

## Overview
PHP plugin package (`myadmin-plugin` type) managing Softaculous license addons for VPS services. Integrates with Softaculous NOC API via SOAP for license provisioning and cancellation.

**Namespace:** `Detain\MyAdminVpsSoftaculous\` → `src/` · Tests: `Detain\MyAdminVpsSoftaculous\Tests\` → `tests/`

## Commands
```bash
composer install
vendor/bin/phpunit
vendor/bin/phpunit tests/PluginTest.php
```

## Architecture

**Source files:**
- `src/Plugin.php` — static plugin class; registers 3 hooks via `Plugin::getHooks()`
- `src/vps_add_softaculous.php` — procedural `vps_add_softaculous()` loaded via `function.requirements` hook

**Hooks registered by `Plugin::getHooks()`:**
| Hook | Handler | Purpose |
|------|---------|--------|
| `function.requirements` | `Plugin::getRequirements` | registers `vps_add_softaculous` page requirement |
| `vps.load_addons` | `Plugin::getAddon` | creates `AddonHandler` with cost `VPS_SOFTACULOUS_COST` |
| `vps.settings` | `Plugin::getSettings` | adds `vps_softaculous_cost` text setting |

**Addon lifecycle:**
- `doEnable()` — calls `SoftaculousNOC::buy($ip, '1M', 2, $custid, 1)`, logs via `myadmin_log()`, records in `$GLOBALS['tf']->history`
- `doDisable()` — calls `SoftaculousNOC::cancel('', $ip)`, logs, sends admin email via `admin/vps_softaculous_canceled.tpl`

**External dependencies:**
- `detain/myadmin-softaculous-licensing` → `\Detain\MyAdminSoftaculous\SoftaculousNOC`
- `symfony/event-dispatcher` `^5.0|^6.0|^7.0` · `ext-soap`
- MyAdmin globals: `get_module_settings()`, `myadmin_log()`, `function_requirements()`, `\MyAdmin\Mail`

## Conventions
- Plugin event handlers are static methods accepting `GenericEvent $event`; subject via `$event->getSubject()`
- Settings: `$settings->add_text_setting(self::$module, _('Label'), 'key', _('Title'), _('Desc'), $settings->get_setting('KEY'))`
- History: `$GLOBALS['tf']->history->add($settings['TABLE'], 'action', $id, $ip, $custid)`
- Logging: `myadmin_log(self::$module, 'info', $msg, __LINE__, __FILE__, self::$module, $serviceId)`
- Admin emails: `(new \MyAdmin\Mail())->adminMail($subject, $body, false, 'admin/template.tpl')`
- Indentation: tabs (enforced via `.scrutinizer.yml` · `.codeclimate.yml`)
- `$settings['PREFIX']` used to key into `$serviceInfo` (e.g. `$serviceInfo[$settings['PREFIX'].'_ip']`)

## Testing
- PHPUnit 9 · config `phpunit.xml.dist` · bootstrap `vendor/autoload.php`
- `tests/PluginTest.php` — static properties and `getHooks()` structure
- `tests/EventIntegrationTest.php` — Symfony EventDispatcher registration/removal integration
- `tests/FileExistenceTest.php` — filesystem presence and `composer.json` structure
- Mock pattern: anonymous classes implementing needed methods (no Mockery dependency)

```bash
# Run full test suite with coverage
vendor/bin/phpunit --coverage-text
```

```bash
# Validate composer.json and check autoload
composer validate
composer dump-autoload
```

## Notes
- `composer.json` requires `php: >=7.4`; `README.md` states PHP 8.2+ — README is aspirational
- `.travis.yml` lists PHP 5.4–7.1 (outdated); `.scrutinizer.yml` targets PHP 7.0 — both stale

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->
