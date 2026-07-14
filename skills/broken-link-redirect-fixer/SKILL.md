---
name: broken-link-redirect-fixer
description: Detect broken internal/external links and real 404 hits, then set up 301 redirects
  to fix them. Use when asked to fix broken links, clean up 404s, or set up redirects.
---

# Broken Link & Redirect Fixer

404s from real visitors matter more than crawler-only broken links — prioritize accordingly, and never guess a redirect target without evidence.

## Steps

1. `wmc/get-broken-links` — full broken link scan across content.
2. `wmc/get-404-logs` — actual 404 hits from visitors, with hit counts.
3. `wmc/manage-redirects` (action=list) — check existing redirects first to avoid duplicates or redirect chains.
4. For each broken URL, determine a destination:
   - If it's an old slug of a still-existing post, confirm the current slug with `wmc/get-post-slug` and redirect old → new.
   - If the page was deleted but another live page now covers the same topic, redirect to that page.
   - If there's no clear target, leave it out of the redirect list — do not redirect to the homepage as a default guess.
5. `wmc/manage-redirects` (action=add) — create the confirmed 301 redirects. Show the full URL → destination mapping to the user and get confirmation first, since redirects are a standing, site-wide behavior change.

## Output format

Table: Broken URL | Hits/Referrers | Proposed Destination | Redirect Type, plus a separate "no clear destination — needs manual review" list.
