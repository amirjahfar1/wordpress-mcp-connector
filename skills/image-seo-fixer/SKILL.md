---
name: image-seo-fixer
description: Find images missing alt text and oversized/uncompressed images, then fix them —
  add descriptive alt text and compress. Use when asked to fix image SEO, add alt tags, or
  optimize images.
---

# Image SEO Fixer

Alt text has to come from real context (filename, caption, the post it's attached to) — never invent a generic "image" description.

## Steps

1. `wmc/get-media-without-alt` — list attachment IDs missing alt text (paginate for large libraries).
2. For each, `wmc/get-media-details` — filename, caption, and the post(s) it's attached to.
3. Draft alt text per image from the filename + surrounding post title/content: descriptive, under 125 characters, no "image of" / "picture of" filler, include the page's focus keyword only where it reads naturally.
4. Show the full draft list (image, current alt, proposed alt, attached post) to the user and get confirmation — this is a bulk content change.
5. `wmc/bulk-update-media` (or `wmc/update-media` per item) to apply.
6. `wmc/compress-images` — scan for oversized images (large file size or dimensions bigger than their display size) and compress/regenerate.

## Output format

Table: Image ID | Filename | Attached Post | Proposed Alt Text. Followed by a compression summary: files compressed, total size saved. Confirm before steps 5 and 6 execute.
