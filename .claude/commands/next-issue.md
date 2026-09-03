---
description: Pick the next actionable ksscca.org issue, do it, verify it, and either close it or flag a human
allowed-tools: Bash, Read, Write, Edit, PushNotification, mcp__ksscca__*
---

Work exactly **one** GitHub issue on `spacecowboyian/ksscca`, end to end. Then stop and
report. Do not chain into a second issue — under `/loop` you will be called again.

Read `CLAUDE.md` first if it isn't already in context. It carries the site access details
and the gotchas (edge-cached REST reads, missing revisions, the locked `siteurl`, Yoast's
non-writable meta). Don't rediscover them.

$ARGUMENTS may name a specific issue number. If it does, work that one and skip selection.

## 1. Select

```bash
gh issue list --state open --limit 60 --json number,title,labels,body
```

Skip any issue that is:

- labelled **`needs-human`** — already escalated, still waiting
- **blocked by an open dependency**. Known chains: **#4 before #31** (marking up the wrong
  events would make that bug machine-readable); **#16 before #15** and before the `/autocross/`
  description in #25; **#6 supersedes #30**; **#29 largely subsumes #27**.

Among what's left, prefer: small and fully verifiable over large; unblocking others over
leaf work; `seo` and `bug` over `content`.

If nothing is actionable, say so plainly, list what each remaining issue is waiting on, and
stop. If you are running under `/loop`, end the loop — there is no point waking up to an
unchanged queue.

## 2. Decide who can do it

Re-read the issue as written, then check the work against **"What an agent cannot do alone"**
in `CLAUDE.md`. Judge the actual task, not the issue title — many issues are a mix, and the
API-doable part is often most of it.

**Visual changes are always a person's call** — see "Visual changes are Ian's call" in
`CLAUDE.md`. If the fix would alter what a visitor sees, do not make it, however small.
Diagnose it, write the options up on the issue, label it, move on. Metadata and code-level
work that renders identically is yours to do.

**If any part needs a person**, do the API-doable part first, then for the remainder:

```bash
gh issue edit <N> --add-label needs-human
```

and comment with the *exact* click path — screen, field, value — not a description of the
problem. The person reading it should not have to re-derive anything:

> **Yoast SEO → Settings → Content types → Pages → Meta description** → set to `%%excerpt%%`

Then continue to the next iteration. A flagged issue is not a stopped loop.

## 3. Do the work

Before writing anything, know how to undo it. Prefer exactly-invertible edits
(`wp_replace_in_post` literal swaps). Where an edit isn't invertible, capture the current
value in the issue comment first.

Do not batch unrelated changes into one issue's work. If you notice something else, use
`mcp__ccd_session__spawn_task` or file a new issue — don't scope-creep this one.

## 4. Verify against the live site

An issue is not done because a write returned 200. Fetch the live URL with a cache-buster
and confirm the actual rendered output changed:

```bash
curl -s "https://www.ksscca.org/<path>/?cb=$RANDOM" | grep -o '<meta name="description"[^>]*>'
```

Also confirm you broke nothing: word count and anchor count in the same ballpark as before,
links still resolving `200`. If verification fails, undo and say so — never report a write
as a fix on the strength of the write alone.

## 5. Close the loop

Comment on the issue with what changed, the verification output, and the inverse operation.
Then:

- fully done → `gh issue close <N> --comment "..."`
- partly done → leave open, comment on what remains, label `needs-human` if that remainder
  needs a person

If this session has the brains MCP, append a line to
`projects/ksrscca-webmaster/canonical/seo-crawler-audit.md`. Save a memory only for a fact
that will still be true next month — a new constraint, not "did issue N".

## 6. Notify sparingly

Send **one** `PushNotification` only when the person is actually blocking progress — the
queue has run dry, or everything left is `needs-human`. Never notify per issue; that
accumulates into noise and gets the whole loop muted.

## Report

Close with: issue number and title, what changed, the verification that proves it, and
what's next in the queue. Keep it to a few lines — under `/loop` this prints every
iteration.
