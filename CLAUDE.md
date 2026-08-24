# Working notes for this repo

## Reuse the component before inventing a layout

The results page has a small set of components that already work at every
width. Before writing a new layout, check whether one of these does the job:

| Component | What it is | Where it lives |
|---|---|---|
| `entry()` | A driver row: position, name, car, times, and an expandable run strip. Takes an options object — rank, gap, leadLabel, showClass, idPrefix, winners. | `templates/results.html` |
| `classHead()` | A class heading: chip, name, roll-up stats, entry count. | same |
| `plainHead()` | A heading with no stats, for panes that are not a class. | same |
| `.strip` / `.run` | The wrapping grid of run chips, with states for best, penalty, DNF and group winner. | same |

**This has gone wrong twice.** The desktop results view was first built as a
`<table>` separate from the mobile card list, and had to be merged back into
one component. The watchlist was then built as a bespoke run matrix and had
to be rebuilt on `entry()`. Both times the bespoke version looked fine with
this event's nine runs and fell apart on the general case.

Before adding a layout, ask:

1. Does an existing component already render this shape? Extend it with an
   option rather than writing a second one.
2. Will it hold for 4 runs and for 12? For 1 entry and for 60? This region
   runs both short autocross sheets and long RallyCross ones.
3. Is it a second presentation of information something else already draws?
   If so, the two will drift.

## Watch for these specific traps

- **Class-name collisions.** `.run` is a chip in a strip; a `<td class="run">`
  inherits the chip's flex styling and stacks the table. This bit twice —
  give table cells their own class.
- **`str.replace` fails silently.** Patch scripts that miss their target
  print success and change nothing. Assert the target exists first, and
  assert slice bounds are ordered before cutting.
- **`position:sticky` needs an unclipped scrolling ancestor.** An
  `overflow-x:auto` wrapper captures it, and the element pins inside that
  wrapper instead of the viewport.
- **Separate grid containers do not share column widths.** Two grids with
  `auto` columns will not line up. Fix the widths through custom properties
  when rows in different containers must align.
- **`data:` URLs are not the target environment.** Fragment navigation,
  `localStorage` and `execCommand` are all blocked there, so anchors and
  copy actions look broken when they are not. Verify over `http://`.
- **The site is http-only.** `navigator.clipboard` is not merely unreliable
  without a secure context — it is absent. Anything depending on it needs a
  fallback that works over plain http.

## Verify with measurements, not screenshots alone

Take the screenshot, but confirm the claim in the DOM: compare rendered run
cells against `event.json`, check that aligned columns resolve to a single
value, and compute contrast ratios rather than eyeballing them.
