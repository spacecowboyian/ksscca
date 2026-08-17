# ksscca.org

Webmaster working repo for **[Kansas Region SCCA](http://www.ksscca.org)** — issue tracking, notes, and tooling for the region's WordPress site.

The site itself lives on WP Engine and is edited through WordPress; this repo is not the site's source code. It exists so work has a paper trail: what's broken, what's stale, what's planned, and the scripts that automate the tedious parts.

## The site at a glance

| | |
|---|---|
| URL | `http://www.ksscca.org` (http only — see [SSL issue](../../issues)) |
| Host | WP Engine |
| Platform | WordPress, theme `kingsize` |
| Key plugins | `msr-calendar`, WooCommerce (unused), Contact Form 7, Jetpack, Yoast |
| Content | 28 pages, 1 post, 475 media, 0 products |
| Registration & calendar | [MotorsportReg](https://www.motorsportreg.com) via the `msr-calendar` plugin |
| Results | Raw `.htm` timing exports uploaded to the Media Library, linked by hand |

Disciplines covered: Autocross (Solo), RallyCross, Road Racing / Time Trials, Track Events.

## How things actually get published

**Results** — timing software exports `.htm` files (`_fin` = class, `_pax` = index, `_raw` = raw times) → upload to the Media Library → hand-edit the relevant results page to add a heading and "CLICK HERE" links. Roughly 2–6 uploads plus one page edit per event.

**Schedules** — no hand-maintained dates anywhere. The `msr-calendar` plugin renders a live table from the MotorsportReg feed, so schedule changes are made in **MotorsportReg**, not WordPress.

## Operational page IDs

Useful for API and WP-CLI work.

| ID | Page |
|---|---|
| 1342 | Home (front page) |
| 898 | Autocross Results |
| 899 | Autocross Schedule |
| 1020 | Autocross Season Points |
| 996 | RallyCross Results |
| 31 | RallyCross Schedule |
| 1024 | RallyCross Season Points |
| 918 | Live Timing |
| 9 | Contacts |

## API access

The WP REST API is open for **anonymous reads**:

```bash
curl -s "http://www.ksscca.org/?rest_route=/wp/v2/pages/996"
```

**Writes are not available yet.** The REST root reports `"authentication": []` — WordPress gates Application Passwords behind `is_ssl()`, and the site has no valid certificate. Fixing HTTPS enables API automation; nothing else needs to change.

## Secrets

Credentials live in `env.org`, which is gitignored and must stay that way — this is a public repo. See `.env.example` for the expected keys.

## Related

Longer-form audits, plans, and decisions live in Brains under `projects/ksrscca-webmaster`.
