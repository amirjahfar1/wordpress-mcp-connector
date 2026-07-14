---
name: indexability-audit
description: Find pages accidentally blocked from search engines — noindex/nofollow set by
  mistake, robots.txt disallowing important paths, or pages excluded from the sitemap. Use when
  asked why a page isn't showing in Google, or to audit indexability/crawlability.
---

# Indexability Audit

There are three independent ways a page can be hidden from search engines — meta robots, robots.txt, and sitemap exclusion. Check all three; fixing one doesn't fix the others.

## Steps

1. `wmc/detect-seo-setup`
2. `wmc/get-post-seo` per important page (or loop `wmc/get-posts` across the whole post type) — read the `robots` field for unintended noindex/nofollow.
3. `wmc/manage-robots-txt` (action=get) — parse `Disallow` rules; confirm none of them block content paths like `/blog/` or `/product/` (blocking `/wp-content/uploads` styling assets is normal, blocking a whole content section is not).
4. Compare `wmc/get-sitemap-urls` against the post count from `wmc/get-posts` — pages missing from the sitemap are candidates for accidental `wmc/exclude-from-sitemap` or the SEO plugin's own noindex setting.
5. For each confirmed accidental block:
   - `wmc/set-post-robots` (or `wmc/bulk-set-robots` for many) to clear noindex/nofollow.
   - `wmc/exclude-from-sitemap` with `exclude=false` to re-include.
   - Correct the specific `wmc/manage-robots-txt` rule if it's blocking a whole section.

## Output format

Table: Page | Blocking Mechanism (meta robots / robots.txt / sitemap exclusion) | Likely Accidental? | Fix. Confirm with the user before applying — un-blocking changes what's publicly visible.
