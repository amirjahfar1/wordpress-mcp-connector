---
name: meta-bulk-optimizer
description: Find posts, pages, or products missing an SEO title, description, or focus
  keyword, and bulk-generate optimized values from their actual content. Use when asked to
  bulk-fix missing meta, generate SEO titles/descriptions at scale, or "clean up SEO for all
  posts".
---

# Meta Bulk Optimizer

Every draft must be generated from the item's real title/content — this is a bulk write to production, so nothing goes live without an explicit review pass.

## Steps

1. `wmc/detect-seo-setup`
2. `wmc/posts-missing-seo` (fields=all, looped per relevant post type) — the worklist.
3. `wmc/get-posts` (or `wmc/get-woo-products` for products) for each ID — pull title, excerpt/content, and category to draft meta from.
4. Draft per item:
   - SEO title: ≤60 characters, primary keyword near the front, brand suffix only if the site already uses one consistently.
   - Meta description: 120–158 characters, includes the keyword plus a concrete reason to click, unique across the whole batch (no copy-paste duplicates).
   - Focus keyword: derived from the page's actual topic, not guessed from the title alone.
5. Show the complete draft list to the user for approval before writing anything.
6. `wmc/set-post-seo` (or `wmc/bulk-update-seo` for larger batches) to apply titles/descriptions, then `wmc/bulk-set-focus-keywords` for keywords.

## Output format

Before/after table: Post | Old Title | New Title | Old Description | New Description | Character Counts. End with an explicit confirmation prompt before step 6 runs.
