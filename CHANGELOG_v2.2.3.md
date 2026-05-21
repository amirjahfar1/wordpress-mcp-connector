# Changelog — v2.2.3

**Release date:** 2026-05-22

## The actual fix

v2.2.1 added the diagnostic endpoint. v2.2.2 fixed `callback` → `execute_callback`
and added `meta.show_in_rest = true`. Neither was enough — the diagnostic still
reported **zero** registered abilities on WP 6.9.4.

Reading the WP core Abilities API source directly
(`WordPress/abilities-api@trunk/includes/abilities-api.php` and the registry)
made the remaining problems unambiguous:

1. **`wp_register_ability_category()` only runs on `wp_abilities_api_categories_init`.**
   Called from any other hook it emits `_doing_it_wrong` and returns null. Our
   category was being registered from inside `register_all_abilities()`, which
   itself runs on `wp_abilities_api_init` — a *different, later* hook. So the
   category was never actually in the registry.

2. **The abilities registry rejects abilities whose category isn't registered.**
   From `WP_Abilities_Registry::register()`:

   ```php
   if ( ! wp_has_ability_category( $args['category'] ) ) {
       _doing_it_wrong(... 'Ability category "%1$s" is not registered ...');
       return null;
   }
   ```

   With our category missing, every `wp_register_ability( …, [ 'category' =>
   'wp-content-manager' ] )` call was being rejected here.

3. **Our `init` and `abilities_api_init` fallback hooks were actively harmful.**
   `wp_register_ability()` itself does the same `doing_action()` check and
   returns null when called outside `wp_abilities_api_init`. The fallbacks were
   firing the registration in the wrong context, getting silently rejected, and
   leaving zero abilities behind.

## Changes

- New top-level function `wmc_register_category()` hooked on
  `wp_abilities_api_categories_init`. Registers `wp-content-manager` exactly
  where core wants it.
- New top-level function `wmc_register_abilities()` hooked on
  `wp_abilities_api_init` **only** — the two fallback hooks were removed.
- `WMC_Abilities::register_all_abilities()` no longer calls
  `self::register_category()`; that responsibility now lives in the main file
  with the correct hook. A comment in the source explains why.
- Diagnostic endpoint extended:
  - `did_wp_abilities_api_categories_init` — confirms the categories hook fired
  - `wmc_category_registered` — proves the category is actually in the registry
  - `fn_wp_has_ability_category` — confirms the helper exists

After upgrading, `GET /wp-json/wmc/v1/diagnose` should show:

```
"wmc_category_registered": true,
"wmc_abilities_count": 42,
"did_wp_abilities_api_categories_init": 1,
"did_wp_abilities_api_init": 1
```

## No surface changes

Ability names, inputs, outputs and behavior are unchanged from v2.2.0. Only
the registration plumbing was wrong.
