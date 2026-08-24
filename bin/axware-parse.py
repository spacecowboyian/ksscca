#!/usr/bin/env python3
"""Parse an AXWare Systems results export into canonical event JSON.

AXWare exports the same event in several shapes. This reads the HTML export
(``live.htm`` / the archived ``_fin.htm`` files) which is the structured one;
a PDF adapter can be added later against the same output schema.

The parser is self-checking: penalties are not folded into the printed run
times, so it reconstructs each entry's total from raw time + penalties and
compares against the total AXWare printed. Any entry that fails to reconcile
is reported rather than silently emitted.

    bin/axware-parse.py live.htm -o results/ksrx-august-2026/event.json
"""

import argparse
import html
import json
import re
import sys
from pathlib import Path

# Penalty values. AXWare's footnote gives the missed-gate cost and states the
# DNF adjustment in *cones*, despite labelling it "sec"; the per-cone cost is
# not in the export at all. Defaults are the SCCA standard and are verified
# against every printed total before the parse is accepted.
DEFAULT_CONE = 2.0
DEFAULT_GATE = 10.0
DEFAULT_DNF_CONES = 5

RALLYX = "rallyx_sum"      # every run counts, penalties added
BEST_RUN = "best_run"      # autocross: score is the single fastest run

TAG = re.compile(r"(?is)<[^>]+>")
CELL = re.compile(r"(?is)<t[dh][^>]*>(.*?)</t[dh]>")
ROW = re.compile(r"(?is)<tr.*?</tr>")
TABLE = re.compile(r"(?is)<table.*?</table>")


def text(fragment):
    return html.unescape(TAG.sub("", fragment)).replace("\xa0", " ").strip()


def cells(row):
    return [text(c) for c in CELL.findall(row)]


def rows(table):
    return [cells(r) for r in ROW.findall(table)]


class ParseError(Exception):
    pass


# ---------------------------------------------------------------- run times

RUN = re.compile(r"^([\d.]+)(?:\+(.*))?$")

# Cells that record a run with no usable time.
NO_TIME = {"dns", "dnf", "dna", "n/a", "-"}

# A class's column-label row, e.g. "Car Color | Run 1 | ... | Total | Diff."
RUN_LABEL = re.compile(r"^run\s*\d+", re.I)


def parse_run(raw, cone, gate, dnf_cones):
    """Split a run cell like ``45.372+0/1`` into time and penalties.

    Suffix forms seen in the wild:
      (none)   clean run
      +N       N cones
      +C/G     C cones and G missed gates
      +dnf     did not finish
      +        bare plus, zero penalty (AXWare prints this for +0)
    """
    raw = raw.strip()
    if not raw:
        return None

    # A run can be recorded with no time at all: not taken (blank, handled
    # above), did-not-start, or a bare did-not-finish with no time captured.
    if raw.lower().lstrip("+") in NO_TIME:
        status = raw.lower().lstrip("+")
        return {"raw": raw, "time": None, "cones": 0, "gates": 0,
                "dnf": status.startswith("dnf"), "status": status,
                "penalty": 0.0, "total": None}

    m = RUN.match(raw)
    if not m:
        raise ParseError(f"unrecognised run cell {raw!r}")

    time = float(m.group(1))
    suffix = m.group(2)
    cones = gates = 0
    dnf = False

    if suffix is not None:
        s = suffix.strip().lower()
        if s == "dnf":
            dnf = True
        elif s == "":
            pass
        elif "/" in s:
            a, b = s.split("/", 1)
            cones, gates = int(a), int(b)
        else:
            cones = int(s)

    penalty = cones * cone + gates * gate + (dnf_cones * cone if dnf else 0.0)
    return {
        "raw": raw,
        "time": round(time, 3),
        "status": None,
        "cones": cones,
        "gates": gates,
        "dnf": dnf,
        "penalty": round(penalty, 3),
        "total": round(time + penalty, 3),
    }


# ------------------------------------------------------------------ tables

def find_table(tables, needle):
    for t in tables:
        if needle.lower() in text(t).lower():
            return t
    return None


CLASS_HEAD = re.compile(r"^(\S+)\s*-\s*'(.*?)'\s*Total Entries:\s*(\d+)", re.I)


def parse_results(table, cone, gate, dnf_cones, mode):
    """Walk the single table holding every class group back to back.

    Leading columns are fixed: Pos, Class, Car #, Driver, Car, Color. After
    those come one column per run, then Total, and — in most but not all
    exports — Diff. Both the run count and the presence of Diff vary by
    event, so the shape is read from each class's column-label row where the
    export provides one, and falls back to assuming Total+Diff where it does
    not (live.htm omits the label row entirely).
    """
    FIXED = 6
    classes = []
    current = None
    runs_n = None
    trailing = 2

    for c in rows(table):
        joined = " ".join(c).strip()
        if not joined:
            continue

        # live.htm puts the class header in a row of its own; the archived
        # _fin.htm exports merge it with that class's column labels into one
        # wide row. Either way it is the first cell that identifies it.
        head = CLASS_HEAD.match(c[0].strip()) or (
            CLASS_HEAD.match(joined) if len(c) <= 2 else None)
        if head:
            current = {
                "code": head.group(1),
                "name": head.group(2),
                "declared_entries": int(head.group(3)),
                "entries": [],
            }
            classes.append(current)

            labels = [x for x in c[1:] if x]
            run_cols = [i for i, x in enumerate(labels) if RUN_LABEL.match(x)]
            if run_cols:
                runs_n = len(run_cols)
                trailing = len(labels) - run_cols[-1] - 1
            continue

        if len(c) < 9 or not c[1] or not c[2].strip().isdigit():
            continue
        if current is None:
            raise ParseError("entry row appeared before any class header")

        n = runs_n if runs_n is not None else len(c) - FIXED - trailing
        run_cells = c[FIXED:FIXED + n]
        if len(c) != FIXED + n + trailing:
            raise ParseError(
                f"row width {len(c)} does not match {FIXED} fixed + {n} runs "
                f"+ {trailing} trailing for class {current['code']!r}: {c}")

        runs = [parse_run(r, cone, gate, dnf_cones) for r in run_cells]
        runs = [r for r in runs if r is not None]

        timed = [r for r in runs if r["time"] is not None]
        raw_total = round(sum(r["time"] for r in timed), 3)
        penalty = round(sum(r["penalty"] for r in runs), 3)

        if mode == RALLYX:
            # No drops: every run counts, penalties added on top.
            total = round(raw_total + penalty, 3)
        else:
            # Autocross: the score is the single best run, penalties included.
            # A DNF run is never eligible however quick it was, so exclude it
            # rather than letting a short DNF time win the comparison.
            clean = [r for r in timed if not r["dnf"]]
            total = round(min((r["total"] for r in clean), default=0.0), 3)

        printed = c[FIXED + n].strip()
        try:
            printed_total = float(printed)
        except ValueError:
            printed_total = None      # e.g. "dns" - entry registered, no runs

        current["entries"].append({
            "position": int(re.sub(r"\D", "", c[0].strip()) or 0),
            "trophy": c[0].strip().upper().endswith("T"),
            "class": c[1].strip(),
            "number": c[2].strip(),
            "driver": c[3].strip(),
            "car": c[4].strip(),
            "color": c[5].strip() or None,
            "runs": runs,
            "raw_total": raw_total,
            "penalty_total": penalty,
            "total": total,
            "printed_total": printed_total,
            "printed_total_raw": printed,
            "diff": c[-1].strip() if trailing > 1 else None,
            "anchor": f"driver-{c[1].strip().lower()}-{c[2].strip()}",
        })

    return classes


RAWPOS = re.compile(
    r"^\s*(\d+)\s+(\d+)\s+(\S+)\s+(\d+)\s+(.+?)\s{2,}(.+?)\s{2,}([\d.]+)\s+([\d.]+)\s+([\d.]+)\s*$"
)


def parse_ttod(table):
    """Top Times Of Day is one flat run of cells: header labels then tuples."""
    flat = [x for r in rows(table) for x in r if x]
    try:
        start = flat.index("Driver") + 1
    except ValueError:
        return []
    out = []
    chunk = flat[start:]
    for i in range(0, len(chunk) - 4, 5):
        label, time, cls, num, driver = chunk[i:i + 5]
        out.append({
            "label": label,
            "time": float(time),
            "class": cls,
            "number": num,
            "driver": driver,
            "anchor": f"driver-{cls.lower()}-{num}",
        })
    return out


FOOTNOTE = re.compile(r"\*\*\s*([A-Za-z]+ Mode\b[^\n<]*)", re.I)
TITLE = re.compile(r"(?is)<title>(.*?)</title>")


def parse(source, cone, gate, dnf_cones):
    src = re.sub(r"(?is)<style.*?</style>", "", source)
    src = re.sub(r"(?is)<script.*?</script>", "", src)
    tables = TABLE.findall(src)
    if not tables:
        raise ParseError("no tables found - is this an AXWare export?")

    results_table = find_table(tables, "Total Entries")
    if results_table is None:
        raise ParseError("no results table found")

    # The header table stacks several dated lines; the event's own line is the
    # first one, the later ones are the export's "Generated:" timestamps.
    event_line = ""
    for r in rows(tables[0]):
        for cell in r:
            if re.search(r"\b\d{2}-\d{2}-\d{4}\b", cell) and "Generated" not in cell:
                event_line = cell
                break
        if event_line:
            break

    date = None
    m = re.search(r"(\d{2})-(\d{2})-(\d{4})", event_line)
    if m:
        date = f"{m.group(3)}-{m.group(1)}-{m.group(2)}"

    footnote = FOOTNOTE.search(text(src))
    note = footnote.group(1).strip().rstrip(",") if footnote else None

    # AXWare only prints the mode footnote for RallyX. Its absence means a
    # normal autocross, where the score is the best single run rather than
    # the sum of every run.
    mode = RALLYX if note and "rallyx" in note.lower() else BEST_RUN
    partial = bool(note and "completed" in note.lower())

    classes = parse_results(results_table, cone, gate, dnf_cones, mode)
    entries = [e for c in classes for e in c["entries"]]

    ttod_table = find_table(tables, "Top Times Of Day")

    # The header line is organiser, event number, name, date - but the number
    # is often blank, which collapses the line by a field:
    #   "KS SCCA - #3 - Kansas Region Event #3 - Sat 06-08-2024"
    #   "KS SCCA -  - KSRXAugust23 - Sun 08-23-2026"
    # Splitting and dropping blanks made the number look like the name.
    parts = [p.strip() for p in event_line.split(" - ")]
    organiser = parts[0] if parts else ""
    number = None
    name = ""
    rest = parts[1:]
    if rest and rest[0].startswith("#"):
        number = rest[0]
        rest = rest[1:]
    elif rest and not rest[0]:
        rest = rest[1:]
    if len(rest) > 1:
        name = rest[0]

    return {
        "event": {
            "title": event_line,
            "organiser": organiser,
            "number": number,
            "name": name,
            "date": date,
            "note": note,
            "scoring": mode,
            "runs_vary_by_entry": partial,
            "penalties": {
                "cone_seconds": cone,
                "gate_seconds": gate,
                "dnf_cones": dnf_cones,
                "dnf_seconds": round(dnf_cones * cone, 3),
            },
            "run_count": max((len(e["runs"]) for e in entries), default=0),
            "entry_count": len(entries),
            "class_count": len(classes),
        },
        "classes": classes,
        "raw": sorted(entries, key=lambda e: (e["printed_total"] is None, e["total"])),
        "ttod": parse_ttod(ttod_table) if ttod_table else [],
    }


def reconcile(data, tolerance=0.0015):
    """Every entry's reconstructed total must match what AXWare printed."""
    bad = []
    for c in data["classes"]:
        for e in c["entries"]:
            if e["printed_total"] is None:
                continue
            if abs(e["total"] - e["printed_total"]) > tolerance:
                bad.append(e)
    return bad


def read_source(path):
    """Decode an export.

    AXWare declares ``charset=iso-8859-1`` in every file it writes, but the
    bytes are frequently UTF-8 - a car entered as "Fìt" comes through as
    ``\xc3\xac`` and reading it as latin-1 turns it into "FÃ¬t". UTF-8 is
    tried first because it fails loudly on genuine latin-1, whereas latin-1
    silently accepts anything and mangles it.
    """
    raw = path.read_bytes()
    try:
        return raw.decode("utf-8")
    except UnicodeDecodeError:
        return raw.decode("latin-1")


def main():
    ap = argparse.ArgumentParser(description=__doc__,
                                 formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("source", help="AXWare .htm export")
    ap.add_argument("-o", "--out", help="write JSON here (default: stdout)")
    ap.add_argument("--cone", type=float, default=DEFAULT_CONE)
    ap.add_argument("--gate", type=float, default=DEFAULT_GATE)
    ap.add_argument("--dnf-cones", type=int, default=DEFAULT_DNF_CONES)
    ap.add_argument("--force", action="store_true",
                    help="emit even if totals fail to reconcile")
    ap.add_argument("--title",
                    help="display title for the page and cards; the export's "
                         "own name is often a run-together slug like "
                         "'KSRXAugust23'")
    ap.add_argument("--venue", help="venue and location, e.g. \"McCain's "
                                    "Offroad Park - Ridgeway, KS\"")
    args = ap.parse_args()

    source = read_source(Path(args.source))
    data = parse(source, args.cone, args.gate, args.dnf_cones)

    if args.title:
        data["event"]["display_title"] = args.title
    if args.venue:
        data["event"]["venue"] = args.venue

    bad = reconcile(data)
    n = data["event"]["entry_count"]
    if bad:
        print(f"reconcile: {len(bad)}/{n} entries do NOT match printed totals",
              file=sys.stderr)
        for e in bad:
            print(f"  {e['class']:>3} {e['number']:>4} {e['driver']:<22}"
                  f" computed={e['total']:.3f} printed={e['printed_total']:.3f}"
                  f" delta={e['total'] - e['printed_total']:+.3f}", file=sys.stderr)
        if not args.force:
            print("refusing to emit; re-run with --force or adjust penalty values",
                  file=sys.stderr)
            return 1
    else:
        print(f"reconcile: {n}/{n} entries match printed totals", file=sys.stderr)

    out = json.dumps(data, indent=2)
    if args.out:
        p = Path(args.out)
        p.parent.mkdir(parents=True, exist_ok=True)
        p.write_text(out + "\n", encoding="utf-8")
        print(f"wrote {p}", file=sys.stderr)
    else:
        print(out)
    return 0


if __name__ == "__main__":
    sys.exit(main())
