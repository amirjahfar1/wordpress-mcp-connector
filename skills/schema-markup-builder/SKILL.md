---
name: schema-markup-builder
description: Add or verify JSON-LD structured data (Article, Product, FAQ, HowTo,
  LocalBusiness, BreadcrumbList) across posts, pages, and WooCommerce products. Use when asked
  to add schema markup, structured data, or rich snippets.
---

# Schema Markup Builder

Schema properties must come from real page/product data — never fabricate a price, rating, or date that isn't actually on the page.

## Steps

1. `wmc/detect-seo-setup` — some SEO plugins already auto-emit certain schema types (e.g. Organization, WebSite); avoid duplicating what's already output.
2. `wmc/get-schema-markup` per target post/product — check what's already present before adding more.
3. Pick the schema type by content: blog post → Article/BlogPosting; WooCommerce product → Product (price/availability/rating pulled from `wmc/get-woo-product-detail` and `wmc/get-woo-product-meta`); page with Q&A content → FAQPage; tutorial → HowTo; contact/about page → LocalBusiness/Organization.
4. Build the properties object entirely from real data pulled via the abilities above (title, author, dates, images, price, currency, stock status). Flag any required property that has no real source (e.g. missing GTIN) instead of inventing a value.
5. `wmc/add-schema-markup` to apply. For bulk application (e.g. all products), confirm the target list with the user first.
6. `wmc/get-schema-markup` again to verify it saved correctly.

## Output format

Table: Content | Schema Type Applied | Key Properties Set | Missing/Flagged Properties (needs manual input).
