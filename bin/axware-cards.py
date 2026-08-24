#!/usr/bin/env python3
"""Render social cards for an event from its canonical JSON.

Produces one 1080x1350 portrait card per class plus a Top Times Of Day card,
sized for a Facebook carousel post. Cards are written as HTML and then
rasterised with headless Chrome, so they use the same colours and type as the
results page and need no design tool in the loop.

    bin/axware-cards.py results/ksrx-august-2026/event.json \
        -o results/ksrx-august-2026/cards
"""

import argparse
import html
import json
import shutil
import subprocess
import sys
from pathlib import Path

W, H = 1080, 1350
SCALE = 2          # render at 2x then downsample, for clean type

CHROME_CANDIDATES = [
    "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
    "/Applications/Chromium.app/Contents/MacOS/Chromium",
    "/Applications/Microsoft Edge.app/Contents/MacOS/Microsoft Edge",
    "/Applications/Brave Browser.app/Contents/MacOS/Brave Browser",
]

CSS = """
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --ink:#14181c; --paper:#fff; --accent:#0b3d91; --accent-dark:#082a66;
  --muted:#5b6470; --faint:#98a2ad; --hair:#e2e7ed; --row:#eef2f7;
  --good:#0a7d2c; --gold:#c9a227;
}
html,body{width:%(W)spx;height:%(H)spx}
body{
  background:var(--paper); color:var(--ink);
  font-family:-apple-system,BlinkMacSystemFont,"Helvetica Neue",Arial,sans-serif;
  display:flex; flex-direction:column;
}
.top{background:var(--accent);color:#fff;padding:44px 64px 38px}
.org{font-size:26px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:#9fbaea}
.evt{margin-top:10px;font-size:44px;font-weight:700;letter-spacing:-.01em;line-height:1.1}
.date{margin-top:8px;font-size:28px;color:#c3d3f0}

.band{
  background:var(--accent-dark);color:#fff;padding:26px 64px;
  display:flex;align-items:center;gap:20px;
}
.code{
  background:#fff;color:var(--accent);font-weight:800;
  font-size:34px;letter-spacing:.06em;text-transform:uppercase;padding:8px 16px;
}
.cname{font-size:40px;font-weight:700}
.cn{margin-left:auto;font-size:26px;color:#9fbaea}

.body{flex:1;min-height:0;overflow:hidden;padding:34px 64px;
  display:flex;flex-direction:column;justify-content:flex-start}
.row{display:flex;align-items:center;gap:24px;padding:22px 0;border-bottom:2px solid var(--hair)}
.row:last-child{border-bottom:0}
.rank{
  flex:none;width:66px;height:66px;display:flex;align-items:center;justify-content:center;
  font-size:30px;font-weight:800;color:var(--muted);
  border:3px solid var(--hair);background:var(--paper);
}
.rank.trophy{background:var(--gold);border-color:var(--gold);color:#241d00}
.who{flex:1;min-width:0}
.name{font-size:42px;font-weight:700;line-height:1.15;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.car{margin-top:4px;font-size:26px;color:var(--muted);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tim{flex:none;text-align:right}
.tot{font-size:42px;font-weight:800;font-variant-numeric:tabular-nums;line-height:1.1}
.gap{margin-top:4px;font-size:25px;color:var(--muted);font-variant-numeric:tabular-nums}
.gap.lead{color:var(--good);font-weight:700}

.foot{
  flex:none;padding:28px 64px 40px;border-top:3px solid var(--hair);
  display:flex;align-items:baseline;gap:16px;font-size:25px;color:var(--muted);
}
.foot .site{margin-left:auto;font-weight:700;color:var(--accent)}
""" % {"W": W, "H": H}


def esc(x):
    return html.escape(str(x))


def fmt(t):
    return "—" if t is None else "%.3f" % t


def page(inner):
    return ("<!DOCTYPE html><html><head><meta charset='utf-8'>"
            "<style>%s</style></head><body>%s</body></html>" % (CSS, inner))


def header(ev, date_label):
    return ("<div class='top'><div class='org'>%s &middot; RallyCross</div>"
            "<div class='evt'>%s</div><div class='date'>%s</div></div>"
            % (esc(ev.get("organiser") or "SCCA"),
               esc(ev.get("name") or "Results"), esc(date_label)))


def footer(note):
    return ("<div class='foot'><div>%s</div>"
            "<div class='site'>ksscca.org</div></div>" % esc(note))


def rows_html(entries, leader, show_class=False):
    out = []
    for i, e in enumerate(entries):
        # Match the wording the results page uses: a class with one car
        # awards no trophy, so do not call it a win.
        if i == 0:
            gap = "only entry" if len(entries) == 1 else "class winner"
        else:
            gap = "+%.3f" % (e["printed_total"] - leader)
        sub = ("%s " % e["class"] if show_class else "") + \
              "#%s &middot; %s" % (esc(e["number"]), esc(e["car"]))
        out.append(
            "<div class='row'><div class='rank%s'>%d</div>"
            "<div class='who'><div class='name'>%s</div><div class='car'>%s</div></div>"
            "<div class='tim'><div class='tot'>%s</div>"
            "<div class='gap%s'>%s</div></div></div>"
            % (" trophy" if e["trophy"] else "", e["position"],
               esc(e["driver"]), sub, fmt(e["printed_total"]),
               " lead" if i == 0 else "", gap))
    return "".join(out)


def class_card(ev, cls, date_label):
    band = ("<div class='band'><span class='code'>%s</span>"
            "<span class='cname'>%s</span><span class='cn'>%s</span></div>"
            % (esc(cls["code"]), esc(cls["name"]),
               "%d %s" % (len(cls["entries"]),
                          "entry" if len(cls["entries"]) == 1 else "entries")))

    entries = cls["entries"]
    body = "<div class='body'>%s</div>" % rows_html(
        entries, entries[0]["printed_total"])

    note = "Total of all %d runs, penalties included" % ev["run_count"]
    return page(header(ev, date_label) + band + body + footer(note))


# Seven is what fits at this size; more would be silently clipped
# by the overflow guard on .body.
def ttod_card(ev, data, date_label, top=7):
    band = ("<div class='band'><span class='code'>TTOD</span>"
            "<span class='cname'>Top Times Of Day</span></div>")

    raw = [e for e in data["raw"] if e["printed_total"] is not None][:top]
    if not raw:
        return None
    lead = raw[0]

    rows = []
    for i, e in enumerate(raw, start=1):
        gap = "fastest of the day" if i == 1 else \
              "+%.3f" % (e["printed_total"] - lead["printed_total"])
        rows.append(
            "<div class='row'><div class='rank%s'>%d</div>"
            "<div class='who'><div class='name'>%s</div>"
            "<div class='car'>%s #%s &middot; %s</div></div>"
            "<div class='tim'><div class='tot'>%s</div>"
            "<div class='gap%s'>%s</div></div></div>"
            % (" trophy" if i == 1 else "", i, esc(e["driver"]),
               esc(e["class"]), esc(e["number"]), esc(e["car"]),
               fmt(e["printed_total"]), " lead" if i == 1 else "", gap))

    body = "<div class='body'>%s</div>" % "".join(rows)

    note = "Overall raw times, all classes"
    return page(header(ev, date_label) + band + body + footer(note))


def find_chrome():
    for p in CHROME_CANDIDATES:
        if Path(p).is_file():
            return p
    for name in ("google-chrome", "chromium", "chromium-browser"):
        found = shutil.which(name)
        if found:
            return found
    return None


def shoot(chrome, html_path, png_path):
    subprocess.run([
        chrome, "--headless=new", "--disable-gpu", "--hide-scrollbars",
        "--force-device-scale-factor=%d" % SCALE,
        "--window-size=%d,%d" % (W, H),
        "--screenshot=%s" % png_path,
        html_path.resolve().as_uri(),
    ], check=True, capture_output=True)

    if shutil.which("magick"):
        subprocess.run(["magick", str(png_path), "-resize", "%dx%d" % (W, H),
                        str(png_path)], check=True, capture_output=True)


def main():
    ap = argparse.ArgumentParser(description=__doc__,
                                 formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("data")
    ap.add_argument("-o", "--out", required=True)
    ap.add_argument("--html-only", action="store_true")
    args = ap.parse_args()

    data = json.loads(Path(args.data).read_text(encoding="utf-8"))
    ev = data["event"]

    date_label = ev.get("date") or ""
    if date_label:
        import datetime
        d = datetime.date.fromisoformat(date_label)
        date_label = d.strftime("%A %-d %B %Y")

    out = Path(args.out)
    out.mkdir(parents=True, exist_ok=True)

    # Top Times Of Day leads the carousel - it is the card most people
    # come for, and Facebook shows the first image largest.
    cards = []
    t = ttod_card(ev, data, date_label)
    n = 1
    if t:
        cards.append(("01-ttod", t))
        n = 2
    for i, cls in enumerate(data["classes"], start=n):
        cards.append(("%02d-%s" % (i, cls["code"]),
                      class_card(ev, cls, date_label)))

    chrome = None if args.html_only else find_chrome()
    if not args.html_only and not chrome:
        print("no Chrome found; writing HTML only", file=sys.stderr)

    for name, markup in cards:
        hp = out / (name + ".html")
        hp.write_text(markup, encoding="utf-8")
        if chrome:
            pp = out / (name + ".png")
            shoot(chrome, hp, pp)
            print("wrote %s (%.0f KB)" % (pp, pp.stat().st_size / 1024),
                  file=sys.stderr)
        else:
            print("wrote %s" % hp, file=sys.stderr)

    print("%d cards" % len(cards), file=sys.stderr)
    return 0


if __name__ == "__main__":
    sys.exit(main())
