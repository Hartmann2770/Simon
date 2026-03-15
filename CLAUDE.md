# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A **memory palace / spaced repetition learning application** built as a single self-contained HTML file (`memorypalace/memory-palace.html`). The UI is in Danish. No build tools, no dependencies, no package manager.

All project files live in the `memorypalace/` subfolder.

## Running the App

Open `memory-palace.html` directly in a modern browser. No server or build step required. All data is persisted in browser `localStorage`.

## Architecture

The entire application lives in `memory-palace.html` (~2700 lines), structured as a single IIFE. Key architectural patterns:

- **State object (`app`)**: Holds current view, editing context, review state. Mutate this then call `renderView()` to update the UI.
- **Event delegation**: One global `document` click listener routes actions via `data-action="..."` attributes on elements through a large switch statement.
- **Direct DOM rendering**: Views are rendered by setting `innerHTML` on a root container. Functions like `renderDashboard()`, `renderPalaceDetail()`, `renderReviewSession()` return HTML strings.
- **LocalStorage persistence**: `loadData()` / `saveData()` serialize/deserialize the full data model as JSON.

## Data Model

```
Palace { id, name, description, type ('standard'|'trivia'), locations[], rooms[], notes[], srs{} }
Location { id, order, name, memories[], roomId?, pin? }
Memory { id, text, createdAt }
Room { id, name, notes[], mapImage? }
SRS { interval, repetition, efactor, nextReviewDate, lastReviewDate, history[] }
```

All entities use UUIDs (`genId()`). Palaces are the top-level unit for spaced repetition scheduling.

## Spaced Repetition (SM-2)

`computeNextReview(srs, quality)` implements the SM-2 algorithm. `isDue(palace)` checks whether a palace is due for review based on today's date. Quality ratings are 1–5.

## Key Conventions

- HTML escaping: always use `esc(str)` when interpolating user data into HTML strings.
- Adding a new action: add a `data-action="my-action"` attribute in the HTML string returned by a render function, then add a `case 'my-action':` branch in the event delegation switch.
- Room map pins: stored as `{x, y}` percentages on the `Location.pin` field; rendered as absolutely positioned elements over the uploaded room image.
- The pre-loaded History Palace (1800–1901, 102 locations) is initialized on first load from inline data in the file.
