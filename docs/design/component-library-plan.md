# A site-wide component library for ksscca.org

Written 2026-09-14 for issue #22, which asked for the results-page components to be
extracted, and which Ian widened to "let's plan for a site wide component library."

This is the planning pass, not the build. It records what actually exists, what the site
itself makes hard, what the layers should be, and the order to do it in. Nothing here has
been implemented.

## 1. What exists today

Five surfaces carry hand-written CSS, none of them sharing a line of it.

| Surface | Where the CSS lives | Size | Deployed by |
|---|---|---|---|
| Solo Nationals (page 1882) | `nationals/template-style.html`, inlined into the page body | 9.2 KB | `bin/build-nationals-page` + `bin/wp-publish-page` |
| Autocross / RallyCross results (898, 996) | `STYLE` constant in `bin/build-results-page` | 3.4 KB | same builder, published by hand |
| Homepage (1655) | `<style>` at the top of the page body | ~9 KB | page body edit |
| Calendar (4 pages) | `ksscca_calendar_assets()` in `snippets/ksscca-calendar.php` | 2.5 KB | WPCode snippet 1633 |
| Timing-export prototypes | `docs/results/*.html`, self-contained | 125 KB raw, 27.7 KB gz | Media Library upload |

The issue's "What exists today" table describes `entry()`, `classHead()`, `plainHead()`,
`.strip` / `.run`, tabs and filters. Those live inside `docs/results/*.html` as functions in
one file. They are real and they work; they are simply not separable yet.

## 2. The duplication is not hypothetical

Four of the five surfaces already share a palette and a type language by hand:

- `#e7e5df` body text, `#99978f` muted, `#2d2d2d` hairline and `#e2c47c` gold appear in
  **all four** of nationals, results, homepage and calendar. `#161616` row hover appears in
  three. So do `'Inter'` and `'PT Sans Narrow'`.
- The same uppercase micro-label recipe (11px-ish, `font-weight:700`,
  `letter-spacing:.07em`-`.09em`, `text-transform:uppercase`, muted colour) is written out
  independently in at least four places.
- The same "row with a hairline under it that highlights on hover" is written three times.

And it has already drifted, twice, in ways a reader could see:

1. **Calendar, 2026-09-14 (#42).** The Register button was solid green `#6cc98d` on the two
   schedule pages and hollow gold on the homepage. The event name was 14.5px in one place
   and 15px in the other, the venue 12.5px against 13px. Same component, four pasted copies,
   two of them stale.
2. **Calendar again, minutes later (#65).** With the CSS finally shared, the button still
   looked wrong on the homepage: it had never set `line-height`, so it inherited 1.62 from
   the homepage's own base scale and 1 everywhere else. 39px tall against 31px.

The second one is the more instructive failure. Sharing a stylesheet does not make a
component consistent; a component is only consistent when it sets every property that
decides its size. That is a rule the library has to enforce, not a coincidence to hope for.

## 3. Constraints this site imposes

These are not general best practice. They are what this WordPress install does.

- **`wpautop` eats the first CSS rule after a newline.** Every builder here already works
  around it independently, each with its own comment explaining it. A shared preamble is one
  of the cheapest wins available.
- **Inline `<a>` between blocks gets wrapped in a `<p>`**, and a `<p>` cannot hold a `<div>`,
  so the parser splits the element and leaves a stray paragraph. This cost a full debugging
  pass on the homepage promo banner. Components whose root is a link must be built out of
  inline children.
- **The theme fights back.** `kingsize` sets a 12px base and the homepage neutralises it with
  `body.page-id-1655 .ksv2 span{font-size:14px!important}`, which then outranks that page's
  own `.ksscca-h{font-size:12px!important}`. Any component dropped onto a page inherits a
  different base depending on the page. Components must be self-sufficient about size.
- **Three delivery channels now exist**, and which one a surface uses is a real decision:
  - a WPCode snippet printing into `wp_head` (source-controlled here, no file access needed);
  - `wp-content/themes/kingsize/css/custom.css`, 45.8 KB, already linked on every page and
    editable through Appearance -> Theme File Editor (confirmed 2026-09-14);
  - inlined into a page body or an uploaded file, which is the only option for the timing
    exports in the Media Library.
- **Page bodies are the deployment unit** for 1882, 898, 996 and 1655. Whatever the library
  does, those pages still receive one blob of HTML.
- **REST GETs are edge-cached**; verification reads need a cache-buster.

## 4. Proposed shape

Three layers, smallest first. Each is useful on its own, which is what makes the migration
safe to do one surface at a time.

### Layer 0 — tokens

One `:root` block: colour, type scale, spacing, hairline, radius. Delivered site-wide by a
WPCode snippet so it is in source control here and carries no theme-update risk. This is
also the fix issue #37 asks for, so #37 becomes the first concrete step rather than a
separate cleanup.

Note for whoever does it: `DESIGN.md` and `docs/design/style-guide.md` are referenced by #37
but do **not** exist in this repo. The palette's real source of truth today is the four
stylesheets above. Part of layer 0 is writing that file, not just reading it.

### Layer 1 — primitives

Things with no knowledge of racing: `panel`, `row`, `hairline`, `label` (the uppercase
micro-label), `chip`, `button` in primary/secondary/tertiary, `table-to-cards` responsive
behaviour, `disclosure` (the `<details>`/`<summary>` row that the Nationals page uses).

Each primitive must declare every property that decides its own size: `font-size`,
`line-height`, `padding`, `border-width`. That is the #65 lesson written down.

### Layer 2 — components

`entry` (driver row: position, name, car, times, expandable run strip), `class-head`,
`run-strip`, `event-row` (results archive), `year-rail`, `calendar-row`, `promo-card`.

These take the canonical event shape, not an AXWare export or a Pronto page.

## 5. Delivery

- **WordPress pages** (1882, 898, 996, 1655, schedule pages): tokens and primitives come from
  the site-wide snippet; components come from whichever snippet owns them, printed once per
  request behind a static flag. `snippets/ksscca-calendar.php` already does exactly this and
  is the working precedent.
- **Uploaded standalone files** (timing exports, the live-timing overlay in #18): a build step
  inlines tokens plus only the components used. Budget from the issue is 17.6 KB gzipped.
  Current reality: the prototypes are **27.7 KB gzipped**, already over; the built Nationals
  page is 10.8 KB and the results archive 5.1 KB. The budget needs restating against those
  numbers before it can be a pass/fail gate.
- **Page-scoped overrides stay on the page.** The homepage's `-35px` edge-to-edge margins and
  its 13px button are legitimately page-specific and should not migrate into the library.

## 6. The demo page earns its place here

Not for show: it is the only way to see the states that broke things in practice. It should
render every component at 1 entry and 60 entries, 4 runs and 12 runs, DNF, DNS, PAX-indexed,
a name long enough to wrap, and a class of one. Publish it as an unlisted page so it renders
inside the real theme, where the `!important` fights actually happen. A local HTML file
cannot show those.

Pair it with a parity check: a script that loads two URLs and diffs the computed styles of
the same component on each, which is how the calendar button mismatch was caught and proven
fixed. That check is worth having as `bin/style-parity` rather than as ad-hoc browser work.

## 7. Order of work

1. **Tokens + the missing DESIGN.md** (closes #37). No visual change; every hex keeps its
   current value.
2. **Primitives, proven on the calendar**, which is already centralised and is the smallest
   consumer.
3. **Results archive pages** (898, 996) onto primitives. `bin/build-results-page` stops
   carrying its own `STYLE` constant.
4. **Solo Nationals** (1882). The biggest single stylesheet, and the one with the most
   component-shaped markup: `entry` and `run-strip` come from here.
5. **Homepage** (1655), last of the live pages, because it has the most page-specific
   exceptions and the most `!important` fights with the theme.
6. **Timing exports and the live-timing overlay** (#18), which is where the build step and
   the size budget matter.

Each step is a separate issue and a separate PR, verified by computed-style parity against
the page before the change. A step that cannot prove parity gets reverted, not argued.

## 8. Decisions still open for Ian

1. **Tokens via WPCode snippet or `custom.css`?** The snippet keeps the source in this repo
   and survives a theme change; `custom.css` is one fewer inline block per page and is
   already loaded. Recommendation: the snippet.
2. **What is the real size budget** for uploaded standalone files, given the prototypes are
   already at 27.7 KB gzipped against the 17.6 KB the issue quotes?
3. **Is the demo page public?** Unlisted and `noindex` is the low-risk answer, but `noindex`
   on this site needs the Yoast UI, so it is a small manual step for you either way.
4. **PAX-aware parsing** was named in #22 as part of this work. It is a parser change, not a
   component change. Recommendation: split it into its own issue so the library is not
   waiting on it.
