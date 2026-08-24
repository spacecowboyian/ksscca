# Event results pages

A three-step pipeline turns an AXWare export into a results page and a set of
social cards. Nothing here is wired into WordPress — the output is a single
`.htm` file that gets uploaded to the Media Library by hand, exactly like the
raw exports always have been.

```bash
# 1. export -> canonical JSON  (refuses to emit if the totals do not reconcile)
bin/axware-parse.py results/<event>/source/<event>_export.htm \
    -o results/<event>/event.json

# 2. JSON -> one self-contained page
bin/axware-build.py results/<event>/event.json \
    -o results/<event>/<event>_results.htm

# 3. JSON -> 1080x1350 Facebook carousel cards (one per class, plus TTOD)
bin/axware-cards.py results/<event>/event.json -o results/<event>/cards
```

Grab a fresh export straight from the timing machine:

```bash
curl -sL http://www.acecomputersks.com/live.htm \
  -o results/<event>/source/<event>_export.htm
```

## Why a JSON step in the middle

The export format is not stable — it has been a PDF, it is HTML today, and the
column layout differs between `live.htm` and the archived `_fin.htm` files.
Keeping a canonical `event.json` between parsing and rendering means a new
source format only needs a new adapter, and the page and the cards both read
from one verified structure.

## The parser checks itself

Penalties are **not** folded into the printed run times. A `+2` marker means
two cones, not two seconds. So the parser reconstructs every entry's total
from its runs plus penalties and compares that against the total AXWare
printed. If any entry fails to reconcile it reports the discrepancy and
refuses to emit, rather than publishing numbers nobody has checked.

| Marker | Meaning | Cost |
|---|---|---|
| `+N` | N cones | 2.0s each |
| `+C/G` | C cones, G missed gates | 2.0s and 10.0s |
| `+dnf` | did not finish | 5 cones — 10.0s |
| `+` | zero penalty | 0.0s |
| `dns` | did not start | no time |

Scoring mode is detected from the export's footnote. RallyCross sums every run
with no drops; an autocross export has no mode footnote and scores the single
fastest run, excluding any DNF.

## Regression corpus

The parser was checked against twelve archived exports from the Media Library.
Ten reconcile to the millisecond. Two are refused, both because of defects in
the source rather than the parser:

- `event-4_fin.htm` — three Novice entries are scored on PAX index, so the
  printed total is a factored time the raw runs cannot reproduce.
- `rx2021e2_fin.htm` — exported with its run columns truncated; drivers took
  roughly ten runs but only five columns were written, so the totals cannot be
  rebuilt from the file.

## Publishing

`.htm` uploads to the WordPress Media Library work and are served as real
pages — verified against the ninety already there:

```
http://www.ksscca.org/wp-content/uploads/2025/12/ksrx-december-2025_fin.htm
→ HTTP/1.1 200, Content-Type: text/html; charset=utf-8
```

Upload the built `.htm`, then link it from the RallyCross Results page
(ID 996). URLs are `http://` only until the site's certificate is fixed.
