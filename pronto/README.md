# pronto-live

Polls a **Pronto Timing System** live-results site and keeps a driver roster
in sync with it. Pronto runs SCCA Solo Nationals, ProSolo, and many
divisional and regional events; they share page shapes, so this is written
against the format rather than one event.

```bash
bin/pronto-live sync  --event pronto/events/26nats.json      # one sweep
bin/pronto-live sync  --event pronto/events/26nats.json --offline
bin/pronto-live watch --event pronto/events/26nats.json --every 300
bin/nationals-refresh                                        # sync + build + publish
```

## Adding an event

Copy `events/26nats.json` and change `base`. Everything else is discovered:

| key | meaning |
|---|---|
| `base` | event root, e.g. `https://sololive.scca.com/26NATSGEN/` |
| `ends` | last day; `watch` stops polling after it |
| `select.regions` | region of record to keep, matched on the part before the `/` |
| `select.home_states` | two-letter state to keep regardless of region |
| `roster_out` | roster JSON the renderer reads |
| `politeness.*` | gap between requests, final-class recheck interval, daily cap |
| `contact` | URL put in the User-Agent so the operator can reach a human |

## What it reads from the site

- **index.php** — the class list, and the run-group accordions. Panel ids drop
  a letter (`collapseTEFW` is `26THEFW`), so the group code is taken from the
  literal `(26THEFW)` printed in the heading and the day/course order from the
  heading text. Never synthesised: Tuesday abbreviates to `T` and Thursday to
  `TH`, which is not derivable from the day name.
- **each class page** — one driver per three `<TR>`s: results row, a hidden
  `id="inforow"` carrying region of record and hometown, then the runs row.
  Column 1 of the results row is `T` when the driver is on a trophy; depth
  scales with class size, so that flag is read rather than a depth assumed.
  A finished class carries `<div class='FINAL'>` (with a `<br>` inside, which
  is why searching for the words "FINAL RESULTS" finds nothing).

## Why the roster is derived, not hand-kept

The first version read a hand-built seed. Nine class pages failed to load the
day it was made, so XA, XB and XBL were missing and no rebuild could ever
surface them — a driver absent from the seed can never appear. Deriving the
roster every sweep makes that impossible: a class unreachable last time is
picked up next time. `write_roster` refuses to save a roster that shrank by
more than 10%, so a partial sweep cannot quietly delete people.

## Rate

This is a live-timing site; it exists to be polled. One spectator with a class
page on a 10-second auto-refresh makes six requests a minute on their own.

A sweep is one request per class, `min_gap_seconds` apart, conditional
(`ETag` / `If-Modified-Since`), and classes that have posted FINAL RESULTS
drop to a recheck every `final_recheck_every` sweeps. Late in an event most
classes are final: a full 80-class sweep costs about **18 requests**, and at
one sweep per ten minutes that averages under two requests a minute.

There is a hard `daily_request_cap`, and the first connection timeout aborts
the sweep rather than retrying. If it aborts that way the IP is probably rate
limited — slow down or stop until it clears.

## Publishing

`bin/wp-publish-page --page 1882 --file nationals/build/page.html`

Reads `WP_APP_PASS` from `.env` (or `$WP_ENV_FILE`); never takes it on the
command line, where it would land in shell history and the process table.
Send a normal `User-Agent`: WP Engine's edge answers the default
`Python-urllib` agent with a bare nginx 403 before WordPress is reached.

## Cron

```cron
*/10 8-18 * * *  cd /srv/ksscca && WP_ENV_FILE=/srv/ksscca/.env bin/nationals-refresh >> /var/log/nats.log 2>&1
```

Needs only python3, git, and network access to the timing site and the
WordPress site. No Claude session in the loop.
