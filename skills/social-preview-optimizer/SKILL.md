---
name: social-preview-optimizer
description: Ensure Open Graph and Twitter Card data is complete on key pages so links look
  right and get more clicks when shared on Facebook, WhatsApp, LinkedIn, and X. Use when asked
  to fix social sharing previews, OG tags, or Twitter cards.
---

# Social Preview Optimizer

A missing `og:image` is the most damaging gap — the link unfurls with no image at all on most platforms.

## Steps

1. `wmc/get-post-seo` per target page — Open Graph/Twitter fields are returned alongside core SEO data.
2. Flag pages missing `og_image` (highest priority), then pages missing `og_title`/`og_description` (falls back to the raw title/excerpt, often reading awkwardly when shared).
3. `wmc/get-media` / `wmc/search-media` — find a suitable image. The post's own featured image is usually fine; only source a different image when the featured image has in-image text or the wrong crop for social platforms.
4. `wmc/set-open-graph` and `wmc/set-twitter-card` to apply. Keep the OG title close to the SEO title; the description can be slightly more clickable/casual than the meta description.
5. Confirm the list with the user before applying across many pages.

## Output format

Table: Page | Current OG Image Status | Proposed OG Title | Proposed OG Description | Proposed OG Image | Twitter Card Status.
