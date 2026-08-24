#!/usr/bin/env python3
"""Render canonical event JSON into a single self-contained results page.

The output has no external assets and no build step at the far end: it is one
.htm file that can be uploaded straight to the WordPress Media Library and
linked, the same way the raw AXWare exports have always been.

    bin/axware-build.py results/ksrx-august-2026/event.json \
        -o results/ksrx-august-2026/ksrx-august-2026_results.htm
"""

import argparse
import datetime
import json
import sys
from pathlib import Path

TEMPLATE = Path(__file__).resolve().parent.parent / "templates" / "results.html"


def build(data, template, generated):
    title = " · ".join(x for x in (data["event"].get("organiser"),
                                   data["event"].get("name")) if x)
    payload = json.dumps(data, separators=(",", ":"))
    # The JSON rides inside a <script> block, so the only sequence that can
    # break out of it is a literal closing tag.
    payload = payload.replace("</", "<\\/")

    return (template
            .replace("__EVENT_JSON__", payload)
            .replace("__TITLE__", title or "Results")
            .replace("__GENERATED__", generated))


def main():
    ap = argparse.ArgumentParser(description=__doc__,
                                 formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("data", help="event JSON from axware-parse.py")
    ap.add_argument("-o", "--out", required=True)
    ap.add_argument("--template", default=str(TEMPLATE))
    args = ap.parse_args()

    data = json.loads(Path(args.data).read_text(encoding="utf-8"))
    template = Path(args.template).read_text(encoding="utf-8")
    generated = datetime.datetime.now().strftime("%d %B %Y")

    out = Path(args.out)
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(build(data, template, generated), encoding="utf-8")

    kb = out.stat().st_size / 1024
    print(f"wrote {out} ({kb:.0f} KB, {data['event']['entry_count']} entries)",
          file=sys.stderr)
    return 0


if __name__ == "__main__":
    sys.exit(main())
