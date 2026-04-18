# Anna — Albums

**Status:** Design approved 2026-04-18
**Scope:** Replace the fixed `category` field on art items with user-defined albums.

## Problem

Anna (13) wants to gather related artwork into thematic groupings — e.g. "Tegneskole" (her weekly drawing class), "Keramik med mor", "Pokémons". The current fixed categories (`tegning / maleri / lerfigur / andet`) are rarely used and don't match the contextual way she thinks about her work.

## Goals

- Anna can create, rename, reorder and delete albums from the admin UI.
- Every artwork belongs to exactly one album, or to no album.
- Visitors can filter the gallery by album via a tab bar above the grid.
- Existing artworks are preserved. Their statistics/ordering/images stay intact.

## Non-goals

- One artwork in multiple albums (may be revisited later).
- Album covers, descriptions, or per-album theming.
- Hidden / private albums (visibility is per-artwork, as today).
- Deep-links or URL fragments per album.
- Slideshow, sort-by-album, or any per-album bulk operations.

## Data model

`data.json` gains an `albums` array, and each art item swaps `category` for `albumId`:

```json
{
  "about":    { "text": "...", "image": "..." },
  "albums":   [
    { "id": "a1b2...", "name": "Tegneskole", "order": 0 }
  ],
  "art": [
    {
      "id": "…", "title": "…", "description": "…",
      "image": "uploads/…", "visible": true, "featured": false,
      "order": 0, "createdAt": "2026-04-18",
      "albumId": null
    }
  ],
  "settings": { "layout": "masonry", "theme": "glad" }
}
```

- `albumId` is either an existing album id or `null` ("no album yet").
- `albums[].order` controls tab order, lowest first.
- Album ids use the same `bin2hex(random_bytes(8))` pattern as art ids.

## Migration

Applied once, inside `readData()` in `api.php`, guarded by a check for `!isset($data['albums'])`:

1. Add `albums: []` to the top-level object.
2. For every art item: delete `category`, set `albumId: null`.
3. Create a starter album `{ id: <new>, name: "Tegneskole", order: 0 }` and append it to `albums`.
4. Write back to `data.json`.

No artwork is assigned to the starter album — Anna does that manually. No image files touched.

## API (api.php)

### New actions

| Action          | Method | Body                              | Effect                                             |
|-----------------|--------|-----------------------------------|----------------------------------------------------|
| `createAlbum`   | POST   | `{ name }`                        | Append new album with next `order`. Returns `{ id }`. |
| `updateAlbum`   | POST   | `{ id, name }`                    | Rename album. Ignores unknown ids.                 |
| `deleteAlbum`   | POST   | `{ id }`                          | Remove album. All art with that `albumId` becomes `null`. Image files untouched. |
| `reorderAlbums` | POST   | `{ order: [id, id, …] }`          | Rewrite `order` field to match provided sequence.  |

All require auth. Response shape follows existing pattern: `{ ok: true }` on success, `{ error: "…" }` with HTTP 4xx on failure.

`name` must be non-empty after trim and ≤ 60 chars; otherwise 400.

### Modified actions

- `upload` and `updateArt`: accept `albumId` (string or empty/null) instead of `category`. Invalid ids → stored as `null`.
- `getData`: unchanged in shape (the top-level `albums` is always present after migration). Public callers receive the same filtered `art` as today.

## UI — public gallery

A tab bar sits directly above the gallery grid:

```
[ Alle ] [ Tegneskole ] [ Keramik med mor ] [ Pokémons ]
```

- Tabs render in `albums[].order`. The leading "Alle" tab is always present.
- Active tab is highlighted using the current theme's accent color.
- Clicking a tab sets an in-memory filter and re-renders the grid. No page reload, no URL change.
- "Alle" shows every visible artwork, including those with `albumId = null`.
- Artworks with `albumId = null` do not get their own tab — they only appear under "Alle".
- If `albums.length === 0`, the tab bar is hidden entirely (visual parity with today's gallery).

## UI — admin

### New "Albums" tab in the admin bar

Order: `Upload · Galleri · Albums · Om mig · Indstillinger · Log ud`.

The Albums panel contains:

- **Ny album** input + `+` button — creates and appends.
- **List of existing albums** in order, each row:
  - Drag handle / up-down buttons to reorder (match the existing gallery-reorder pattern).
  - Editable name (click to edit inline, Enter/blur to save).
  - Count badge: "5 værker".
  - Delete (×) button with confirm: "Slet album 'Tegneskole'? De 5 kunstværker mister deres album-tilknytning (billederne slettes ikke)."

### Upload form

The "Kategori" dropdown is replaced by an "Album" dropdown:

```
Album: [ (intet album) ▾ ]
        (intet album)
        Tegneskole
        Keramik med mor
        …
```

Default is `(intet album)`.

### Edit-art form

Same album dropdown. Pre-selects the current `albumId` (or `(intet album)` if null).

### Admin gallery card

The meta line currently shows `${category} · ${date}…`. Replace with album name, or `Intet album` in muted text if `albumId` is null.

## Failure & edge cases

- **Rename collision:** duplicate album names are allowed (Anna's choice; simpler than enforcing uniqueness).
- **Delete last album while Anna is on that tab (admin preview):** the visitor-side tab bar re-renders on next reload; not a live concern because admin edits albums in a separate panel.
- **Empty album name on create/rename:** API returns 400; UI shows a short inline error.
- **Concurrent writes:** same best-effort model as today (single-user admin, file-based store). No additional locking.

## Files touched

- `anna/api.php` — migration in `readData()` + four new action cases + `category → albumId` in `upload` / `updateArt`.
- `anna/index.html` — tab bar markup & filter state on public gallery; new Albums admin panel; album dropdown in upload and edit forms; admin card meta update.

## Out of scope / future

- Multi-album membership (checkboxes on upload instead of a dropdown).
- Album covers and descriptions.
- Album visibility toggle independent of per-artwork `visible`.
- URL hash filter (`#album=tegneskole`) for shareable links.
