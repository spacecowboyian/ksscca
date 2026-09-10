# Solo Nationals results pipeline

Rebuilds WordPress page **1882**
(`/ks-region-at-the-2026-scca-solo-nationals/`) from the SCCA Solo Live
timing site.

    bin/build-nationals-page --fetch --snapshot "6 PM, 10 September" --out nationals/build/
    bin/build-nationals-page --offline          # cache only, fetch nothing

## Files

| Path | What it is |
|---|---|
| `roster-seed.json` | The 70 Kansas-area drivers: class, car, home town, region of record, run group. Fixed for the event. |
| `template-style.html` | The page's `<style>` block, kept out of the script so the CSS is editable on its own. |
| `cache/` | Last fetched copy of each class page. Gitignored. |
| `build/` | Output chunks ready to paste into WordPress. Gitignored. |

The seed exists because the run group (`TEWW`, `THWFE`, ...) decides which
of the page's "Course 1" and "Course 2" is East and which is West, and that
mapping is not recoverable from a class page alone. Refreshes only update
positions, run times and class sizes.

## We were IP blocked once. Do not earn it again.

On 2026-09-10, after roughly 80 fast requests from Ian's machine,
`sololive.scca.com` stopped answering **us**. Not an outage: the same URL
answered fine from WP Engine, from a proxy, and from Ian's phone on
cellular seconds apart, while our IP failed at the TCP connect stage. That
is a firewall rule, not a sick server.

So the fetcher: touches only the 28 classes that contain our drivers rather
than all 80, waits 2.5 s between requests, sends `If-Modified-Since` so an
unchanged class costs them a 304, and aborts on the first timeout instead
of retrying into a wall.

**If it aborts with a connection timeout, we are probably blocked again.**
Do not retry in a loop, and do not route around it through a proxy, a
tethered phone, or WP Engine. That is evasion, and it just moves the ban
onto somebody else. Run `--offline`, leave the page on its last good
snapshot, and try again later.

## Posting

`--out` writes one `page.html`. The field is not paginated, so there is
nothing to chunk: post the whole body in a single `wp_update_page` (or
`bin/wp-rest POST /wp/v2/pages/1882` with the body in a file). One write
means the page is never left half-built if a call fails partway.

The source-and-status bar at the top is the `[ksscca_solo_live_status]`
shortcode from WPCode snippet 1890
(`snippets/ksscca-solo-live-status.php`), which checks reachability
server-side and cached. The builder emits the shortcode; it does not
hardcode a status.

## Known soft spots

* Trophy positions are read from the `T` the class pages print in their
  first column, so depth follows class size (13 in EST, 12 in SSC, 2 in
  GSL) instead of being assumed. Standings stay provisional until the
  class finishes.
* "Out on course now" is inferred from run counts, because the feed never
  says who is staged.
* The exports are Windows-1252, not UTF-8.
* Matching `kansas` as a substring also matches Ar**kansas**; the roster
  build guards against it.
