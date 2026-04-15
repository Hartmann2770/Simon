# Quiz Range Slider Design

## Summary

Replace the three separate quiz scope buttons (1800-tallet, 1900-tallet, Alle perioder) with a single dual-handle range slider covering the full timeline, plus three quiz-length buttons.

## Current State

The quiz start screen in `_renderHistoryQuiz('start')` (line ~5679) shows:
- "1800-tallet (X begivenheder)" button
- "1900-tallet (X begivenheder)" button
- "Alle perioder (X begivenheder)" button
- Statistik button

`startHistoryQuiz(scope)` receives `'1800'`, `'1900'`, or `'all'` and filters palaces via `_hqFilterByScope`. Deck is always sliced to 10 questions.

## New Design

### UI Layout

```
+---------------------------------------+
|  Historisk Quiz                    [X] |
|                                       |
|  Du far 10 tilf. begivenheder...      |
|                                       |
|  1800 |=====[////]=======| 2000       |
|         1842          1965            |
|       (247 begivenheder)              |
|                                       |
|  [10 sporgsmaal] [25] [50]           |
|                                       |
|  ------------------------------------ |
|  [Statistik]                          |
+---------------------------------------+
```

- **Dual range slider**: Two draggable handles on a single track
- **Range**: From the earliest year in `HISTORY_QUARTERS[0].from` (1800) to latest `HISTORY_QUARTERS[last].to` (2000)
- **Granularity**: 1 year per step
- **Default position**: Full range (1800–2000)
- **Year labels**: Displayed at/near each handle, updating as user drags
- **Event count**: "(N begivenheder)" updates dynamically based on selected interval
- **Three buttons**: "10 sporgsmaal", "25 sporgsmaal", "50 sporgsmaal"

### Behavior

1. **Slider interaction**: User drags left handle to set start year, right handle to set end year. Handles cannot cross each other.
2. **Event count**: Recalculated on each slider change by building the deck for the selected year range and counting entries.
3. **Start quiz**: Click any of the three buttons. The quiz draws N random questions from the selected interval. If fewer than N events exist, all available events are used.
4. **Filtering**: Replace `_hqFilterByScope(palaces, scope)` with year-range filtering. A location is included if its year (parsed from `loc.name`) falls within `[startYear, endYear]`.

### Implementation Details

**Dual range slider (pure CSS/HTML)**:
- Two `<input type="range">` elements overlaid on the same track
- Custom CSS to style the track and thumbs
- JavaScript `input` event listeners update labels and event count

**Modified functions**:
- `_renderHistoryQuiz('start')`: New HTML with slider + 3 buttons
- `startHistoryQuiz(count)`: Now receives question count (10/25/50) instead of scope string. Reads slider values from DOM to determine year range.
- `_hqFilterByScope` replaced by `_hqFilterByYearRange(palaces, fromYear, toYear)`: Filters at the location level — iterates palaces and their locations, includes only locations where the year falls within range.
- `_buildQuizDeck`: No changes needed — it already works per-location.

**Scope in `_hq` state**: Store `{ fromYear, toYear, count }` instead of `scope` so result screen can show what was quizzed.

### Mobile Considerations

- Slider thumbs need adequate size (min 28px touch target)
- Year labels positioned to avoid overlap when handles are close together
- Buttons stack vertically on narrow screens

### Edge Cases

- If the selected range has 0 events: disable all three buttons or show a message
- If range has fewer events than chosen count: use all available events (no error)
- Handles at same position: allow it (quiz on a single year)
