---
name: site-wide-seo-health-check
description: Scan the entire site for SEO problems — pages missing meta, duplicate meta,
  broken links, 404s, robots.txt/.htaccess issues, sitemap gaps, and overall site health. Use
  when asked for a "site-wide SEO check", "SEO health check", or to find all SEO issues on the
  site.
---

# Site-Wide SEO Health Check

Surveys the whole site rather than one page — the value is catching issues that only show up in aggregate (duplicates, sitemap gaps, whole-site robots blocks).

## Steps

1. `wmc/detect-seo-setup`
2. `wmc/seo-overview-report` — run once per relevant post type (`post`, `page`, and `product` if WooCommerce is active). Returns which items are missing title/description/keyword and which have duplicate meta.
3. `wmc/posts-missing-seo` — cross-reference against step 2's list.
4. `wmc/get-broken-links` — internal/external broken links found in content.
5. `wmc/get-404-logs` — actual visitor-hit 404s. Weight these higher than crawler-only broken links since they represent real lost traffic.
6. `wmc/get-sitemap-urls` compared against the post/page count from `wmc/get-posts` — a gap suggests accidental sitemap exclusion.
7. `wmc/manage-robots-txt` (action=get) — confirm nothing important is disallowed (never `Disallow: /`, and never block `/blog/`, `/product/`, or similar content paths).
8. `wmc/manage-htaccess` (action=get) — check for broken or conflicting redirect rules.
9. `wmc/get-site-health` — WordPress's own health check (PHP version, HTTPS, REST API reachability) since these affect crawlability too.

## Output format

Prioritized markdown report:
1. **Critical** — robots.txt/indexing blocks affecting the whole site or major sections, high-traffic 404s
2. **High** — missing meta on high-traffic pages, broken links with many referrers
3. **Medium** — duplicate meta, sitemap gaps
4. **Low** — minor warnings, low-traffic issues

End with a "Top 5 actions this week" list. Do not apply any fix without listing it and getting confirmation first.
