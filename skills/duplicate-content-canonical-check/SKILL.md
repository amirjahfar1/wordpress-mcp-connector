---
name: duplicate-content-canonical-check
description: Find posts, pages, and taxonomy terms with duplicate or near-duplicate SEO
  titles/descriptions, and fix them with unique meta or a canonical URL. Use when asked about
  duplicate content, duplicate meta, or canonical issues.
---

# Duplicate Content & Canonical Check

The fix depends on *why* two pages match — genuinely different pages need unique meta, near-duplicate variants of the same content need a canonical pointer instead.

## Steps

1. `wmc/seo-overview-report` — surfaces duplicate-meta flags across post types.
2. `wmc/get-post-seo` for each flagged pair/group — compare the exact title/description strings.
3. `wmc/get-term-seo` for categories/tags that may overlap with post content (a common cause: a category archive title matching a post title).
4. Decide the fix per duplicate group:
   - Genuinely different content → rewrite one side's meta uniquely (use the `meta-bulk-optimizer` skill's drafting logic).
   - Near-duplicates — paginated, filtered, or parameter variants of the same content → `wmc/set-canonical-url` pointing at the primary version instead of rewriting meta.
5. Apply via `wmc/set-post-seo` / `wmc/set-term-seo` for rewrites, `wmc/set-canonical-url` for canonicalization — confirm the full list with the user first.

## Output format

Grouped list: Duplicate Group | Affected URLs | Decision (rewrite vs. canonicalize) | Fix Applied.
