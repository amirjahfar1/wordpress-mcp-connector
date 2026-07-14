---
name: on-page-seo-audit
description: Run a deep SEO audit on a single post, page, or product — meta title/description
  length, focus keyword usage, headings, image alt tags, internal links, canonical URL, robots
  directives, and schema markup. Use when asked to audit, review, or check the SEO of one
  specific page/post/product.
---

# On-Page SEO Audit

Audits a single piece of content end-to-end instead of just running the plugin's built-in check in isolation — cross-references the result against slug quality and schema presence too.

## Steps

1. Call `wmc/detect-seo-setup` once per session before any other SEO call — tells you which SEO plugin (Yoast/RankMath/AIOSEO/none) is active and the correct meta keys/abilities to use for this site.
2. Call `wmc/get-post-seo` with the `post_id` — current SEO title, description, focus keyword, robots, canonical, Open Graph/Twitter data.
3. Call `wmc/get-seo-audit` with the `post_id` — title/description length verdict, keyword density, heading structure, image alt coverage, internal link count.
4. Call `wmc/get-schema-markup` with the `post_id` — check whether structured data exists and which type.
5. Call `wmc/get-post-slug` with the `post_id` — flag non-descriptive or overly long slugs (numeric, stop-word-heavy, auto-generated).
6. Score and flag:
   - Title missing or >60 characters → **Critical**
   - Description missing or outside 120–158 characters → **High**
   - Focus keyword absent from title, first paragraph, or URL → **Medium**
   - Images in content missing alt text → **Medium** (cross-reference the `image-seo-fixer` skill)
   - No schema markup where the content type suits one (Article/Product/FAQ) → **Low** (cross-reference the `schema-markup-builder` skill)
   - Robots set to noindex/nofollow → **Critical**, unless clearly intentional (e.g. a thank-you or admin page)

## Output format

Single markdown report for the page:
- **Score** (0–100 heuristic) and a one-line verdict
- Findings grouped Critical → High → Medium → Low, each with the exact field and the suggested fix
- A "Ready-to-apply" list of the exact write calls needed (`wmc/set-post-seo`, `wmc/add-schema-markup`, etc.) — present this list and ask the user to confirm before calling any write ability, since these change live content.
