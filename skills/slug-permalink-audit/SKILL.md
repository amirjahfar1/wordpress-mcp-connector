---
name: slug-permalink-audit
description: Audit permalink structure and individual post/page/category slugs for
  SEO-friendliness — flag non-descriptive, overly long, or keyword-stuffed slugs. Use when
  asked to review URLs, slugs, or permalink structure.
---

# Slug & Permalink Audit

Changing a live slug breaks any existing links pointing at it — always pair a slug change with a redirect.

## Steps

1. `wmc/get-permalink-structure` — confirm the site uses a descriptive, postname-based structure rather than plain query IDs or pure dates.
2. Loop `wmc/get-posts` / `wmc/get-woo-products`, then `wmc/get-post-slug` / `wmc/get-category-slug` per item.
3. Flag slugs that are: numeric/auto-generated, over ~75 characters, stop-word-heavy ("the-a-of-and"), or keyword-stuffed (the same word repeated 3+ times).
4. For each flagged slug, propose a short, descriptive replacement built from the item's actual title — never invent unrelated wording.
5. `wmc/check-slug-availability` before changing anything, to avoid collisions.
6. `wmc/set-post-slug` / `wmc/set-category-slug` to apply — confirm with the user first, and chain into the `broken-link-redirect-fixer` skill to add a 301 old → new for every changed slug.
7. `wmc/flush-rewrite-rules` after structural permalink changes.

## Output format

Table: Current Slug | Issue | Proposed Slug | Availability Check. Note which rows need a paired redirect.
