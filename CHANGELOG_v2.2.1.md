# Changelog — v2.2.1

**Release date:** 2026-05-21

## Bug fixes

### Abilities registration didn't actually fire on some WP 6.9.x builds

On at least one production install (WP 6.9.4 + PHP 8.2.31) plugin v2.2.0 was
"active" but zero `wmc/*` abilities ended up in the public registry — third-party
abilities (`ninjaforms/*`, `core/*`) registered fine on the same site, so the
Abilities API itself was working. The plugin was hooking only on
`wp_abilities_api_init` (the name the official Abilities-API docs still list);
that hook didn't trigger our callback on that environment.

Fix: register the entry callback on **three** hooks and idempotency-guard it:

- `wp_abilities_api_init` (primary, per docs)
- `abilities_api_init`     (alternate name seen in some WP 6.9 builds)
- `init` priority 20       (hard fallback)

Whichever hook fires first wins; subsequent invocations short-circuit. We also
defensively check `function_exists( 'wp_register_ability' )` before doing any
work, so an early-firing hook can't fatal.

### Diagnostic endpoint

Added `GET /wp-json/wmc/v1/diagnose` (gated to `manage_options`) returning:

- Plugin / WP / PHP versions
- Whether `WMC_Abilities` and `WMC_Settings` classes loaded
- Whether `wp_register_ability` / `wp_register_ability_category` / `wp_get_abilities` exist
- Which abilities-related actions have fired this request
- The actual count and list of registered `wmc/*` abilities
- Which Abilities-API hooks currently have callbacks attached

This makes "did the plugin actually load and register?" trivially answerable
from outside the site, without needing shell access.

## No schema / no API breaking changes

All abilities, parameters, and return shapes are identical to v2.2.0. Only the
internal registration plumbing changed.
