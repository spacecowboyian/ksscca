---
name: Kansas Region SCCA
description: Dark paddock-at-dawn visual system for ksscca.org's schedule, results, and registration surfaces.
colors:
  floodlight-blue: "#5cb3dd"
  paddock-lamp-blue: "#8ccdef"
  paddock-lamp-well: "#17313c"
  grid-light-green: "#6cc98d"
  grid-light-green-hover-text: "#12281c"
  cta-text: "#1a1a1a"
  rallycross-amber: "#e2a768"
  rallycross-amber-well: "#3a2a14"
  roadracing-rose: "#e08277"
  roadracing-rose-well: "#3a201d"
  fog-ground: "#111111"
  fog-lift: "#161616"
  ground-seam: "#2d2d2d"
  paddock-white: "#e7e5df"
  morning-mist: "#99978f"
  morning-mist-deep: "#6f6d67"
  seam-quiet: "#4a4a4a"
  wheat-gold: "#e2c47c"
  wheat-gold-bright: "#f2d999"
  wheat-gold-edge: "#6b4d1d"
  wheat-gold-edge-hover: "#a86e1d"
  wheat-gold-wash: "rgba(226,196,124,.1)"
typography:
  display:
    fontFamily: "'PT Sans Narrow', 'Helvetica Neue', Verdana, Arial, sans-serif"
    fontSize: "40px"
    fontWeight: 700
    lineHeight: 1
    letterSpacing: "0.01em"
  label:
    fontFamily: "Inter, Verdana, Arial, sans-serif"
    fontSize: "12px"
    fontWeight: 700
    lineHeight: 1
    letterSpacing: "0.08em"
  row-primary:
    fontFamily: "Inter, Verdana, Arial, sans-serif"
    fontSize: "15px"
    fontWeight: 600
    lineHeight: 1.35
    letterSpacing: "normal"
  row-secondary:
    fontFamily: "Inter, Verdana, Arial, sans-serif"
    fontSize: "13px"
    fontWeight: 400
    lineHeight: 1.35
    letterSpacing: "normal"
rounded:
  none: "0px"
  badge: "10px"
  button: "8px"
  pill: "999px"
spacing:
  row-y: "14px"
  row-x: "18px"
  card-gap: "12px"
components:
  button-primary:
    backgroundColor: "{colors.grid-light-green}"
    textColor: "{colors.cta-text}"
    rounded: "{rounded.button}"
    padding: "8px 16px"
  button-primary-hover:
    backgroundColor: "{colors.grid-light-green}"
    textColor: "{colors.grid-light-green-hover-text}"
  button-secondary:
    backgroundColor: "transparent"
    borderColor: "{colors.wheat-gold-edge}"
    textColor: "{colors.wheat-gold}"
    rounded: "{rounded.button}"
    padding: "8px 16px"
    lineHeight: 1
  button-secondary-hover:
    backgroundColor: "{colors.wheat-gold-wash}"
    borderColor: "{colors.wheat-gold-edge-hover}"
    textColor: "{colors.wheat-gold-bright}"
  badge-date:
    backgroundColor: "{colors.paddock-lamp-well}"
    textColor: "{colors.paddock-lamp-blue}"
    rounded: "{rounded.badge}"
    size: "52px"
---

# Design System: Kansas Region SCCA

## Overview

**Creative North Star: "The Paddock at Dawn"**

A dark paddock before first light — fog still low on the ground, cones
just visible where the mist thins, the first floodlight already on over
timing. Nothing is lit for show; each color exists because it's marking
something a racer needs to find fast: the date, the discipline, the one
button that gets them registered. The ground stays near-black and quiet
so the few colors that do appear — the cool blue of a paddock lamp, the
warm green of a start light — read as signal, not decoration.

The voice is purposeful and no-nonsense: built for someone checking the
schedule between runs, not browsing a brochure. It leans motorsport-
technical (tabular date numerals, uppercase labels, discipline-as-data)
without tipping into generic "racing website" cliché — no checkered
flags, no red/black/silver default palette, no italicized speed-slash
type. And it stays warm and community-run rather than corporate: this is
a volunteer region's site, not a national SCCA microsite.

**Key Characteristics:**
- Near-black ground throughout; color is reserved for things that matter
- Flat by default — no shadows, no card borders, depth comes from tone
- PT Sans Narrow for display type, Inter for everything that has to be
  read quickly (labels, data, buttons)
- Small, confident components — pills and the CTA mark the line, they
  don't shout

## Colors

Cool paddock-lamp blue for information, one warm green reserved for the
single action that matters (registering), and a couple of dim discipline
accents that only appear where they're needed. Everything else stays
near-black.

### Primary
- **Floodlight Blue** (`#5cb3dd`): headings' eyebrow label, inline links,
  the accent color of the system. Used sparingly — it's the paddock lamp,
  not the sun.

### Secondary
- **Grid-Light Green** (`#6cc98d`): the primary button fill — the one
  warm color in the system, reserved for the single loudest action on a
  screen.
  **The One Primary Rule.** Green means "this is the one thing to do
  here". At most one green button is visible at a time: on the homepage
  that is "See upcoming events" and the Solo Nationals promo card. It
  never becomes a decorative accent.

  *Changed 2026-09-14.* This was the One Green Rule, and it reserved
  green for the Register button specifically. Register moved to the
  wheat gold secondary when the schedule pages were reconciled with the
  homepage (issue #46), so green became the general primary instead.
  Registering is still the most important thing a visitor does; it is
  simply no longer the loudest thing on a page that has a hero on it.

- **Wheat Gold** (`#e2c47c`, brightening to `#f2d999`): the secondary
  action and the color of anything that resolves to a result. The
  **Register** button (hollow, `#6b4d1d` border, `#a86e1d` on hover, over
  a `rgba(226,196,124,.1)` wash), results links, class headings on the
  Solo Nationals page, and the border of the promo card. Pulled from the
  wheat in the theme's own background photograph, which is why it sits
  comfortably on this ground.

### Tertiary
- **RallyCross Amber** (`#e2a768` on `#3a2a14`): discipline accent, used
  only inside a discipline badge/pill, never as a standalone UI color.
- **Road Racing Rose** (`#e08277` on `#3a201d`): discipline accent, same
  rule as above.

### Neutral
- **Fog Ground** (`#111111`): the page background — the site's native
  black, appears through everything (tables/rows carry no background of
  their own).
- **Fog Lift** (`#161616`): row hover state — one step up from the
  ground, like the mist thinning where something's about to appear.
- **Ground Seam** (`#2d2d2d`): 1px dividers between rows; the only line
  work in the system.
- **Paddock White** (`#e7e5df`): primary text — a warm off-white, never
  pure `#fff`.
- **Morning Mist** (`#99978f`): secondary/muted text — venue lines,
  column headers, footnotes.
- **Morning Mist Deep** (`#6f6d67`): muted text that sits on a darker
  ground than usual, where Morning Mist would be louder than the row it
  labels. Used inside expanded rows on the Solo Nationals page.
- **Seam Quiet** (`#4a4a4a`): separator dots between inline links, and
  the outline of a tertiary button. Quieter than Ground Seam is at rest
  because it appears inside a line of text, not between rows.

### Named Rules
**The Flat Ground Rule.** Nothing sits on a card, a shadow, or a border
at rest. If something needs to separate from the page, it does it with
a background-tone shift (Fog Ground → Fog Lift), not elevation.

## Typography

**Display Font:** PT Sans Narrow (with 'Helvetica Neue', Verdana, Arial,
sans-serif fallback)
**Body/Label Font:** Inter (with Verdana, Arial, sans-serif fallback)

**Character:** A condensed, confident display face for headings and the
one place numerals need to read at a glance (the date badge's day
number), paired with a clean, highly legible grotesque for everything
that has to be scanned fast — labels, row text, buttons. Inter's
fallback deliberately matches what the underlying MotorsportReg-plugin
markup already ships (Verdana/Arial), so the design degrades gracefully
if the Google Fonts request ever fails.

### Hierarchy
- **Display** (700, 40px, line-height 1): page-level headings ("Upcoming
  Events"). Left-aligned, not centered.
- **Label** (700, 12px, uppercase, letter-spacing 0.08em): column
  headers. The date badge's month abbreviation is smaller still at 10px,
  because it sits inside a 52px badge.

  *Changed 2026-09-15* from 11px. The homepage had been rendering column
  labels at 14px, not by choice: `body.page-id-1655 .ksv2
  span{font-size:14px!important}` outranked that page's own
  `.ksscca-h{font-size:12px!important}`, and the schedule pages were
  later matched to the homepage. Settled at 12px, which is what the
  homepage had been asking for all along.
- **Title/Row-primary** (600, 15px, line-height 1.35): the primary line
  in a row (discipline/event name).
- **Body/Row-secondary** (400, 13px): the secondary line in a row
  (venue, location, footnote text).

  *Changed 2026-09-14* from 14.5px and 12.5px, when the schedule pages
  were brought up to the homepage's type scale. Both numbers now match
  everywhere a calendar renders.
- **Numeral** (PT Sans Narrow 700, 22px, `font-variant-numeric:
  tabular-nums`): the date badge's day number — the one place display
  type appears at small scale, because it has to be instantly readable.

### Named Rules
**The Two-Font Rule.** Nothing on a redesigned surface uses a third
family. PT Sans Narrow carries display moments; Inter carries everything
that has to be read quickly. See Do's and Don'ts for the legacy
exception.

## Layout

Rows lay out as a fixed-column grid (currently `68px | 1fr | 90px` for
date / event / action on the events table), padded `14px 18px`, with a
1px Ground Seam divider between rows and none after the last row. Below
700px, the grid collapses into a stacked single-column card list with
`12px` gaps between cards. There is no outer container margin/padding
beyond what the row grid itself carries — surfaces sit edge-to-edge in
the page's existing content column, not floating in their own frame.

## Elevation & Depth

Flat by design — no shadows anywhere in the system. Depth reads through
tone alone: the page ground (Fog Ground) versus a hovered row (Fog Lift)
versus a badge or pill's own tinted well (e.g. Paddock Lamp Well behind
the date number). Nothing lifts, floats, or casts a shadow at rest or on
interaction.

### Named Rules
**The No-Lift Rule.** Hover and focus states change color, never
elevation. A `box-shadow` anywhere in a redesigned surface is a bug.

## Shapes

Three radius values: `10px` on the date badge (a soft square, not a
circle — it holds two lines of text), `8px` on buttons including
Register, and `999px` (a true pill) on discipline badges. Everything
else — the row grid, the page ground, table containers — is a hard
`0px`, edge-to-edge. The only line work is the 1px Ground Seam row
divider and the secondary button's own `#6b4d1d` edge.

## Components

Small, confident, low-key. Pills and the CTA are compact — they mark the
next right thing to do without competing for attention on a page whose
job is to be scanned fast.

### Buttons
- **Shape:** `8px` radius, `999px` on the pill-shaped discipline badges.
- **Primary:** Grid-Light Green background (`#6cc98d`),
  `#1a1a1a` text at rest — **not white**: white-on-green measures
  ~2.0:1 contrast (fails WCAG AA), while `#1a1a1a` measures ~8.6:1
  (AAA). Padding `8px 16px`, Inter 700 13px.
- **Hover:** text shifts to Grid-Light-Green-Hover-Text (`#12281c`), a
  darker green, plus a subtle `brightness(1.08)` lift on the fill. This
  button must declare its own `:hover` color explicitly — the site's
  global theme stylesheet sets `a:hover { color: blue }` sitewide, and
  that rule out-specifies a plain custom class selector (element +
  pseudo-class beats a lone class), so an unprotected link silently
  turns theme-blue on hover. See `docs/design/style-guide.md` for the
  full specificity breakdown.
- **Secondary (Register):** transparent fill, 1px `#6b4d1d` border,
  Wheat Gold text, same `8px 16px` padding and Inter 700 13px. Hover
  fills with `rgba(226,196,124,.1)`, brightens the border to `#a86e1d`
  and the text to `#f2d999`.
- **Highlight (solid gold):** Wheat Gold fill, Fog Ground `#111111` ink,
  same `8px` radius and `8px 16px` padding. Hover brightens the fill to
  `#f2d999`. Used for the one action inside a row that is already gold
  washed, where a hollow gold button would disappear: the season
  standings row in the results archive. Measured 11.17:1 at rest and
  13.63:1 on hover. It does not compete with Primary, because green
  still marks the loudest action on a page; this marks the only action
  in one row.
- **Every button sets its own `line-height`.** It is `1` here. Without
  it the button inherits whatever the surrounding page uses and changes
  height from page to page: the Register button stood 39px tall on the
  homepage and 31px on the schedule pages for exactly this reason, from
  the same stylesheet. The same goes for `font-size`, `padding` and
  `border-width`: a component is only consistent when it declares every
  property that decides its own size.

### Chips (discipline pills)
- **Style:** small pill (`999px` radius), `4px 10px` padding, 12px/600
  Inter text, background/text pulled from the matching discipline pair
  (e.g. RallyCross Amber on its well). One discipline, one color pair —
  never mix.

### Cards / Containers
- **Corner Style:** `0px` — no radius on the events table's outer
  container ("edge to edge" is a deliberate, explicit choice, not an
  oversight).
- **Background:** none at rest (Fog Ground shows through); mobile
  stacked cards use Fog Lift as their own background with a 1px Ground
  Seam border, since at that breakpoint they need to read as discrete
  objects in a list rather than rows in a grid.
- **Shadow Strategy:** none — see Elevation & Depth.
- **Internal Padding:** `14px 18px` desktop rows; `16px` mobile cards.

### The Date Badge (signature component)
A `52px` square (`48px` on mobile), `10px` radius, Paddock Lamp Well
background with Paddock Lamp Blue text: month abbreviation (Inter 700
10px uppercase) stacked over the day number (PT Sans Narrow 700 22px,
tabular numerals). This is the system's one recurring signature shape —
every list of events on the site should use it, not a plain date string.

## Do's and Don'ts

### Do:
- **Do** keep the whole system flat — no shadows, no card borders at
  rest. Depth is a tone shift, never elevation.
- **Do** give every custom link/button an explicit `:hover` color. The
  theme's sitewide `a:hover { color: blue }` rule wins by specificity
  over an unprotected class selector.
- **Do** verify real contrast ratios on any new color pairing rather
  than assuming a color "reads fine" — this system's CTA text color was
  corrected once already after white-on-green measured ~2.0:1.
- **Do** use the Date Badge (Paddock Lamp Well + Blue, PT Sans Narrow
  numeral) for any new list of dated events rather than a plain string.
- **Do** keep inline `<style>`/`<script>` added to a WordPress page's
  content free of literal `<` characters in JS string literals, and on a
  single line — WordPress's `wpautop` filter corrupts unprotected inline
  script/style content. Full detail in `docs/design/style-guide.md`.

### Don't:
- **Don't** let a component inherit a property that decides its size.
  Declare `font-size`, `line-height`, `padding` and `border-width` on
  the component itself, every time.
- **Don't** use white text on Grid-Light Green — it fails contrast
  (~2.0:1). Use `#1a1a1a` at rest, `#12281c` on hover.
- **Don't** reach for the generic "racing site" template: checkered
  flags, red/black/silver palettes, italicized speed-slash type. This
  system's motorsport character comes from data density (tabular dates,
  discipline-as-badge) and the dawn-paddock palette, not racing clichés.
- **Don't** introduce a third font family. PT Sans Narrow for display,
  Inter for everything else — no exceptions on a redesigned surface.
- **Don't** carry forward the legacy Helvetica Neue/Verdana body stack
  or its `#cccccc` text color into new component work. That's the
  un-redesigned `kingsize` theme's inherited default, not part of this
  system — it still governs pages/sections nobody has touched yet, but
  new work should use Inter + Paddock White/Morning Mist instead.
- **Don't** add a border-radius or box-shadow to a table/row container.
  Edge-to-edge and flat is the deliberate choice for this system.

## Known deviations

Things the live site does that this document does not endorse. Each one
is a bug with a name, not a variant.

- **The palette is documented here and declared in
  `snippets/ksscca-tokens.php`**, which is a second copy. The snippet is
  what the site reads; this file is what a person reads. They are in
  sync as of 2026-09-14 and nothing enforces that.
