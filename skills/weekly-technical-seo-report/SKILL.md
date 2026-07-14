---
name: weekly-technical-seo-report
description: Run a complete technical SEO audit by chaining site-wide-seo-health-check,
  indexability-audit, broken-link-redirect-fixer, duplicate-content-canonical-check, and
  error-log-diagnostics into one prioritized report with cross-referenced root causes. Use when
  asked for a full technical SEO audit, weekly SEO report, or "check everything on the site".
---

# Weekly Technical SEO Report

Chains the individual diagnostic skills and cross-references their findings instead of listing each report separately — the same symptom often has a different real cause depending on which other checks it also shows up in.

## Steps

1. Run the **`site-wide-seo-health-check`** logic — missing meta, duplicate meta, broken links, 404s, sitemap gaps, robots.txt/.htaccess, site health.
2. Run the **`indexability-audit`** logic — noindex/nofollow accidents, robots.txt blocks, sitemap exclusions.
3. Run the **`broken-link-redirect-fixer`** logic — broken links/404s mapped to redirect targets.
4. Run the **`duplicate-content-canonical-check`** logic — duplicate meta groups and canonical fixes.
5. Run the **`error-log-diagnostics`** logic — PHP errors, site health, cron status.
6. If WooCommerce is active, also run a condensed pass of the **`woocommerce-product-seo-optimizer`** logic limited to top-selling products (via `wmc/get-woo-top-products` if available, otherwise `wmc/get-woo-products` sorted by sales/stock).
7. Cross-reference findings to determine root cause instead of listing symptoms in isolation:
   - A page missing from the sitemap (step 1) that also shows noindex in step 2 → likely an accidental exclusion, not deliberate — treat as **Critical**, not Low.
   - A 404 (step 3) whose old slug matches a duplicate-meta group (step 4) → the page was probably merged/renamed without a redirect; recommend the redirect over rewriting meta again.
   - Recent PHP errors (step 5) referencing an SEO plugin file, combined with pages missing schema/meta (step 1) → the SEO plugin itself may be malfunctioning; recommend checking the plugin before doing manual content fixes.
   - A broken robots.txt/.htaccess rule that explains several unrelated symptoms at once → surface it once as a single root cause instead of listing every affected page separately.

## Output format

One consolidated markdown report, sections in priority order:

1. **Critical** — indexing blocks (noindex/robots.txt/sitemap) on live important pages, high-traffic 404s
2. **High** — broken links/redirect gaps, PHP errors tied to SEO functionality, missing meta on high-traffic pages
3. **Medium** — duplicate content/canonical issues not explained by a technical cause, product SEO gaps
4. **Low** — minor slug/schema polish, low-traffic issues

Each item states: what was found, which sub-check surfaced it, the cross-referenced root cause (if any), and the recommended fix. End with a "Top 5 actions this week" list.
