# Live Timing — mobile layout prototype

The Live Timing WP page (ID 918) just meta-refresh-redirects to a raw
AXWare export at `http://www.acecomputersks.com/live.htm`. That export is
one long page: a bookmark grid of class codes, a couple of heat tables, a
"Last 10 Runs" feed, then **one single `<table>` containing all ~46
class result groups back to back**, followed by a class-winners summary.
On a phone the embedded styles set the results table to `font-size: 2vw`,
so everything is tiny and you have to scroll past every other class to
find your own — and a manual refresh doesn't reliably return you to
where you were.

This folder is a sandbox for redesigning the *presentation* without
touching the real site.

## Files

- `live-original.htm` — untouched snapshot of the current export, kept as
  a baseline for diffing against future downloads.
- `overlay.html` — the CSS + vanilla JS layer we're iterating on. It is
  designed to be pasted onto the end of any fresh export, right before
  `</BODY>`, without editing anything above it.
- `live-mobile.htm` — `live-original.htm` + `overlay.html` appended. This
  is the file to open in a browser to see the prototype.

To pull a new export and rebuild the prototype:

```bash
curl -sL http://www.acecomputersks.com/live.htm -o docs/live-timing/live-original.htm
python3 - <<'EOF'
p = "docs/live-timing/live-mobile.htm"
base = open("docs/live-timing/live-original.htm", encoding="utf-8").read()
overlay = open("docs/live-timing/overlay.html", encoding="utf-8").read()
i = base.rfind("</BODY>")
open(p, "w", encoding="utf-8").write(base[:i] + overlay + "\n" + base[i:])
EOF
```

## What the overlay does

1. **Bigger, real font sizes.** Replaces the `2vw` sizing with a
   `clamp()` so text stays readable instead of shrinking to fit a fixed
   viewport-width formula.
2. **Leaderboard list per class, not a raw table.** The overlay JS walks
   the one big results table (12 fixed columns: Pos / Class / Car # /
   Driver / Car / Color / Run 1–4 / Best / Diff), parses each class
   group, and re-renders it as a simple ranked list: driver name (large)
   with the car model underneath, fastest time on the right, sorted
   fastest-to-slowest (DNF/DNS sink to the bottom). Tapping an entry
   expands it (native `<details>`/`<summary>`) to show car #, color,
   diff-to-leader, and every run as a chip, with the personal-best run
   highlighted. The original table rows stay in the DOM (for
   view-source/debugging) but are hidden — the list is what's shown.
3. **Class focus via the existing bookmark links.** The original
   `#code` bookmark links still work. The overlay listens for
   `hashchange` and, when the hash matches a known class code, hides
   every other table (bookmark grid, heat tables, Last-10-Runs, summary)
   and every other class's rows, leaving just that driver's class on
   screen. A sticky bar at the top shows a "which class am I looking at"
   dropdown as an alternate way to jump, plus a refresh button.
4. **Reload-safe.** Because the filter is driven purely by
   `location.hash`, hitting refresh (or the page's own Refresh button)
   keeps you on your class — no more losing your place.
5. **Edge-to-edge list, no gaps.** Leaderboard entries have no card
   margins/borders/radius — just a hairline `border-bottom` between
   rows, so the list uses the full screen width with zero wasted space.
6. **Per-driver deep links.** Every entry gets a stable id
   (`driver-<classcode>-<carNumber>`, e.g. `#driver-camc-30`). Linking
   straight to a driver filters to their class (same as a class-code
   link), then also expands that driver's card, highlights it briefly,
   and scrolls it into view.
7. **Focused view is just the dropdown + the list.** When a class is
   focused, the "Showing X only" label and the blue class-name banner
   are hidden — the sticky dropdown bar goes straight into the entry
   list with no headers (and no odd rounded-corner artifact) in
   between. The banner still shows in the unfiltered all-classes view,
   where it's a useful section divider.
8. **Redesigned landing view.** When no class/driver is focused, the
   page no longer shows the original messy header, bookmark grid, heat
   tables, or the full 46-class results listing. Order, top to bottom:
   - A clean single-card header (event name, generated timestamp,
     "Unofficial" badge) instead of the original stack of black
     `<th>` blocks. No rounded corners anywhere in the overlay.
   - A reformatted "Last 10 Runs" feed: driver name/car, most recent
     run time (black, or green if that run is their personal best so
     far), and a "Best: X • Run N" line underneath. Each entry links to
     that driver's anchor (`#driver-<classcode>-<carNumber>`), same as
     the class leaderboard entries. The page loads scrolled straight to
     this feed instead of the very top.
   - "Top Times Of Day" (raw + pax + per-category leaders), rebuilt
     using the same name/car/time card layout as the leaderboard and
     Last-10-Runs feed instead of the original raw table — category
     (e.g. "Raw time", "Pax", "Xtreme Street") is the bold heading,
     driver name/class/car underneath, time on the right. Links to the
     driver's anchor when the category's class code is one we know
     about.
   - The full by-class results table is no longer rendered on the
     landing view at all — it only appears once a class or driver is
     focused via the sticky dropdown ("Choose class …"), a class-code
     link, or a `#driver-...` deep link. This trims the landing page
     from a multi-thousand-row scroll down to a few screens.
   There is no standalone "jump to class" list anymore — the sticky
   dropdown is the only way to browse by class from the landing view.

## Known gaps / next steps

- The original bookmark grid, heat tables (Heat #1/#2), the original
  Last-10-Runs table, and the original Top Times Of Day table are now
  all hidden entirely (`.mt-removed`) rather than just restyled, since
  they're superseded by the new class list and feed-style cards.
  They're still present in the DOM for debugging/view-source purposes.
- Column labels (`Pos`, `Car #`, etc.) are inferred from column order,
  not sourced from real headers — confirm this holds if AXWare ever
  changes the export's column count.
- The Last-10-Runs feed parser (`parseLast10`) walks run-row pairs
  heuristically (first `<td>` is a bare integer = a run row, the next
  `<tr>` holds that run's "Best:" value) — revisit if AXWare ever
  changes that table's row shape.
- Eventually we may want to strip the embedded `<STYLE>` block entirely
  rather than layering on top of it, once we're happy with the design
  here — that's explicitly out of scope for this pass.
- This is a static prototype only; nothing here is wired into WordPress
  or the real `acecomputersks.com` export yet. See GitHub issue #18 for
  the production deployment plan (a WP Engine mu-plugin proxy).
