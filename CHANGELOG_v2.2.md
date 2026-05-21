# Changelog — v2.2.0

**Release date:** 2026-05-21

## New: Post & Page Scheduling

Posts and pages can now be **scheduled** for a future publish time via the MCP abilities API.

### Affected abilities
- `wmc/create-post`
- `wmc/update-post`
- `wmc/create-page`
- `wmc/update-page`

### New `date` parameter
Each ability above now accepts an optional `date` field on input.

Accepted formats:
- `"YYYY-MM-DD HH:MM:SS"` — interpreted in the site's timezone (WordPress local format)
- ISO 8601 (e.g. `"2026-06-01T10:00:00"`, `"2026-06-01T10:00:00Z"`, `"2026-06-01T10:00:00+05:00"`)

Behavior:
- If `date` is in the future and `status` is `publish` (or `future`), the post is **scheduled** — WordPress sets `post_status = future` and publishes via cron at the specified time.
- If `date` is in the past, the post is back-dated and keeps the requested status.
- Invalid date strings return a structured error instead of silently falling back.

### `status` enum tightened
`status` is now an explicit enum: `publish`, `draft`, `pending`, `future`, `private`. Passing `future` directly is supported, but in most cases just sending `status: "publish"` + a future `date` is enough.

### Response additions
Create/update responses now include:
- `status` — the actual final status WordPress stored (e.g. `future`)
- `date` — the final stored `post_date`
- `scheduled` — boolean, `true` when the post ends up in the `future` state

### Example — schedule a post for 1 June 2026, 10:00 site time

```json
{
  "ability": "wmc/create-post",
  "input": {
    "title": "Summer launch post",
    "content": "<p>Coming soon…</p>",
    "status": "publish",
    "date": "2026-06-01 10:00:00",
    "categories": [3]
  }
}
```

Response:
```json
{
  "id": 512,
  "title": "Summer launch post",
  "url": "https://example.com/?p=512",
  "status": "future",
  "date": "2026-06-01 10:00:00",
  "scheduled": true,
  "success": true
}
```

### Example — reschedule an existing post

```json
{
  "ability": "wmc/update-post",
  "input": { "id": 512, "date": "2026-06-15 09:00:00" }
}
```

## Internal

- Added `WMC_Abilities::resolve_schedule()` helper to centralize date parsing, timezone handling, and the auto-promotion of `publish` → `future` when the date is in the future.
- Duplicate-detection queries for posts/pages now also include the `future` status, so scheduling a post does not silently allow a duplicate title later.
- `wp_insert_post()` / `wp_update_post()` calls in the scheduling paths now use the `$wp_error = true` argument so date/permission failures surface as readable error messages instead of `0`.

## Upgrade notes

- No breaking changes. Existing clients that don't send `date` behave identically to v2.1.0.
- The new `date` field is opt-in.
- Requires WP-Cron to be functional on the site for scheduled posts to actually publish at the target time (this is the standard WordPress requirement, not new).
