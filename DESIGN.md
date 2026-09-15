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
typography:
  display:
    fontFamily: "'PT Sans Narrow', 'Helvetica Neue', Arial, sans-serif"
    fontSize: "40px"
    fontWeight: 700
    lineHeight: 1
    letterSpacing: "0.01em"
  label:
    fontFamily: "Inter, Verdana, Arial, sans-serif"
    fontSize: "11px"
    fontWeight: 700
    lineHeight: 1
    letterSpacing: "0.08em"
  body:
    fontFamily: "Inter, Verdana, Arial, sans-serif"
    fontSize: "14.5px"
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
- **Grid-Light Green** (`#6cc98d`): the Register button background — the
  one warm color in the system, reserved for the single primary action.
  **The One Green Rule.** Grid-Light Green appears in exactly one place:
  the action that gets someone registered. It never becomes a decorative
  accent or a second CTA color.

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

### Named Rules
**The Flat Ground Rule.** Nothing sits on a card, a shadow, or a border
at rest. If something needs to separate from the page, it does it with
a background-tone shift (Fog Ground → Fog Lift), not elevation.

## Typography

**Display Font:** PT Sans Narrow (with 'Helvetica Neue', Arial, sans-serif fallback)
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
- **Label** (700, 11px, uppercase, letter-spacing 0.08em): column
  headers, the date badge's month abbreviation.
- **Title/Row-primary** (600, 14.5px, line-height 1.35): the primary line
  in a row (discipline/event name).
- **Body/Row-secondary** (400, 12.5px): the secondary line in a row
  (venue, location, footnote text).
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

Two radius values only: `10px` on the date badge (a soft square, not a
circle — it holds two lines of text), and `999px` (a true pill) on
discipline badges and the Register button. Everything else — the row
grid, the page ground, table containers — is a hard `0px`, edge-to-edge.
No borders anywhere except the 1px Ground Seam row dividers.

## Components

Small, confident, low-key. Pills and the CTA are compact — they mark the
next right thing to do without competing for attention on a page whose
job is to be scanned fast.

### Buttons
- **Shape:** `8px` radius, `999px` on the pill-shaped discipline badges.
- **Primary (Register CTA):** Grid-Light Green background (`#6cc98d`),
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
