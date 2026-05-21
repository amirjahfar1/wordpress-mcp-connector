# Changelog — v2.2.2

**Release date:** 2026-05-21

## Critical bug fix — abilities now actually register

v2.2.1 added the diagnostic endpoint, which immediately revealed the real
problem: the plugin was loading correctly, hooks were firing, the
`WMC_Abilities` class was loaded, and our callback was running — but every
`wp_register_ability()` call was being silently rejected by core, because
**we were sending the wrong array key names**.

The WordPress Abilities API in WP 6.9+ expects:

- `execute_callback` — the function/method to run when the ability is invoked
- `meta.show_in_rest` — must be `true` for the ability to appear under
  `/wp-json/wp-abilities/v1/abilities/...`

We were using `callback` (without the `execute_` prefix) and not setting
`meta.show_in_rest` at all. Core treats unknown keys as invalid and silently
drops the registration, which is why `wp_get_abilities()` returned zero `wmc/*`
abilities even though the rest of the plumbing looked perfect.

### What changed

- Added `WMC_Abilities::wmc_register()` wrapper. It calls
  `wp_register_ability()` after defaulting `meta.show_in_rest = true` (unless
  the caller explicitly set it). This makes future additions one-line and
  prevents the same omission from recurring.
- Replaced every `wp_register_ability( …, [ 'callback' => … ] )` call across
  the 42 abilities with `self::wmc_register( …, [ 'execute_callback' => … ] )`.
- No behavior changes to any individual ability — only the registration
  metadata is corrected.

### Verifying the fix

After upgrading, the diagnostic endpoint should now report all abilities:

```
GET /wp-json/wmc/v1/diagnose
  → wmc_abilities_count: 42
  → wmc_abilities: [ "wmc/get-posts", "wmc/create-post", ... ]
```

And the standard listing should expose them:

```
GET /wp-json/wp-abilities/v1/abilities
  → includes wmc/* entries
```

## No surface changes

All ability names, inputs, outputs, and behavior remain identical to v2.2.0 /
v2.2.1. Existing callers don't need to change anything — the abilities will
simply start working.
