---
name: error-log-diagnostics
description: Pull PHP error logs, WordPress Site Health status, active plugins, and scheduled
  cron jobs to diagnose what's silently breaking the site — which often causes SEO regressions
  like broken schema output, failed sitemap generation, or timeouts. Use when asked to debug
  site errors, check for PHP errors, or find what broke after an update.
---

# Error Log Diagnostics

SEO symptoms (missing schema, stale sitemap, slow indexing) are frequently caused by a technical failure elsewhere on the site — this skill finds the technical cause before any content fix is attempted.

## Steps

1. `wmc/get-error-logs` — recent PHP errors/warnings/fatals.
2. `wmc/get-site-health` — WordPress's own health check (HTTPS, REST API, cron, background updates).
3. `wmc/get-server-info` — PHP version, memory limit, key extensions. A low `memory_limit` or PHP <7.4 causes silent failures in bulk SEO operations.
4. `wmc/get-plugins` — cross-reference error timestamps against plugin activation/update times to find the likely cause.
5. `wmc/get-cron-jobs` — confirm WP-Cron isn't broken/disabled; sitemap regeneration, scheduled publishing, and some SEO plugins' background indexing all depend on it.
6. Cross-reference: errors naming a specific plugin/theme file point to that plugin as the suspect; an empty or 404 sitemap combined with no recent cron run points to a stalled cron queue rather than a content issue.

## Output format

Chronological error summary, a most-likely-cause ranking, and a recommended next action (e.g. "disable plugin X to confirm", "increase PHP memory_limit", "clear cache and re-check cron").
