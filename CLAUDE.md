# ksscca.org — working notes

Webmaster repo for Kansas Region SCCA. The site is WordPress on WP Engine; this repo is
the paper trail and the tooling, not the site's source. See `README.md` for the site map,
page IDs, and publishing mechanics.

## How to reach the site

Always **https**, always **www**:

```
https://www.ksscca.org/
```

A valid Let's Encrypt cert has been in place since 2026-08-27 (renews 2026-11-25). Only
`www` is on the cert — the apex has no SAN coverage, but it 301s to `www`. The older
"http only" guidance is dead; don't reintroduce it.

Two ways in, both authenticated:

- **`mcp__ksscca__*` MCP tools** — preferred. Posts, pages, media, menus, terms, settings.
- **`bin/wp-rest`** — authenticated REST helper for anything the MCP tools don't cover.

Anonymous reads also work: `curl -s "https://www.ksscca.org/?rest_route=/wp/v2/pages/996"`

## Gotchas that have already cost time

**REST GETs are edge-cached.** `?rest_route=` responses are served from WP Engine's cache
(`x-cacheable: SHORT`, `max-age=600`). A verification read immediately after a write
returns the *pre-write* body and looks like the write silently failed. Always append a
cache-buster:

```bash
curl -s "https://www.ksscca.org/?rest_route=/wp/v2/pages/898&cb=$RANDOM"
```

**Most pages store zero revisions.** Pages 898 and 996 have none. WordPress offers no
rollback there, so prefer edits that are *exactly invertible* — `wp_replace_in_post` does a
literal string swap and can be run backwards. Note the inverse in the issue comment.

**`siteurl` cannot be changed.** The field is greyed out in Settings → General, which means
`WP_SITEURL` (and almost certainly `WP_HOME`) are `wp-config.php` constants. Constants beat
`update_option()`, so neither wp-admin nor the REST API can set it. It needs WP Engine
portal or SSH access, which nobody currently has.

This matters less than it sounds: `is_ssl()` is true at runtime, so `site_url()` and
`home_url()` scheme-correct on the fly and every *generated* URL is already https. Only
hardcoded database strings were ever wrong.

**Yoast's meta fields are not REST-writable.** `_yoast_wpseo_metadesc` and
`_yoast_wpseo_meta-robots-noindex` are both unregistered with `show_in_rest`; writes are
silently dropped (the tool reports `ignored_keys`). Yoast falls back to the **page excerpt**
for descriptions, and `excerpt` *is* writable — that's the working route. There is no
equivalent fallback for `noindex`; setting that one needs wp-admin (Yoast's per-page SEO
tab → "Allow search engines to show this page in search results?"). The `kingsize` theme
never renders excerpts, so setting one changes nothing visible on the page.

**WooCommerce redirects `/checkout/` → `/cart/` on an empty cart.** A 302, not a bug. Reading
both URLs without following/noting the redirect looks like a duplicate-title problem — it
isn't one; each page has its own correct title. Cost real time once already.

**`robots.txt` is a physical file, not WordPress-generated.** Its headers give it away:
`accept-ranges: bytes` only appears on a static file served off disk — WordPress's virtual
`do_robots()` output is dynamic PHP and never sets it. Nothing in wp-admin can touch it,
including Yoast's Tools → File editor (that edits the virtual version; a physical file on
disk takes priority regardless). Needs WP Engine SFTP or the file manager — same blocker
as #9.

## Conventions

**Internal links are root-relative.** Not `https://www.ksscca.org/wp-content/...` but:

```html
<a href="/wp-content/uploads/2024/07/jul6_fin.htm">
```

Host- and scheme-agnostic, so it survives the `siteurl` constant never being fixed and any
future domain change. All 296 content links were converted on 2026-09-03.

**Anchor text describes the destination.** Never `CLICK HERE` (see #27). Results links read
`July 6, 2024 Solo — Day 1 PAX results`.

**Every change gets an issue comment** saying what changed, the verification that it landed,
and how to undo it.

## Visual changes are Ian's call

**Do not make changes that alter the site's visual layout or design.** Code and metadata
improvements only. Anything a visitor would *see* change — page copy, headings, layout,
navigation labels, images, adding or removing a visible block — gets diagnosed, written up
on the issue, and labelled `needs-human`. Ian makes the call.

This is about what renders, not about risk. Invisible edits are fine even when they touch
page content: rewriting a link's `href` while its anchor text stays put, setting an excerpt
the theme never renders, editing `<title>` or meta. Adding a visible heading is not, however
small and however obviously correct it seems.

When you hit one, the useful work is still the diagnosis: say what is wrong, what the
options are, and what each would cost. Land that on the issue and move on.

## What an agent cannot do alone

These need a person, and are the escalation cases for `/next-issue`:

- `wp-config.php` — constants, including `siteurl`/`home`
- **Yoast settings screens** — site representation, sitemap toggles, meta templates
- **Theme options** — `kingsize` options (and its Save button is broken, see #17)
- **Plugin activate/deactivate/delete**
- **WP Engine portal** — cache purge, SSH, cert, file drops at the document root
- **Anything visual** — see above; this is the most common escalation, not the rarest
- **Judgement calls** — what a page should say, whether to remove a feature, anything
  affecting members

## Working the backlog

`/next-issue` picks one issue, does it, verifies it, and either closes it or labels it
`needs-human`. Run it under `/loop` to work the queue unattended:

```
/loop /next-issue
```

Labels used as state: **`needs-human`** (waiting on a person — the loop skips it),
**`agent-ok`** (confirmed doable over the API), **`blocker`** (blocks other work).
