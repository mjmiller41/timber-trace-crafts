---
name: seo-tracker
description: >
  Use this skill whenever you run any SEO audit, analysis, or plugin command
  (e.g. /seo audit, /seo technical, /seo geo, /seo performance, /seo schema,
  /seo ecommerce, /seo content, /seo drift, /seo local, /seo cluster, /seo maps,
  /seo backlinks, or any other claude-seo command). Also invoke it when the user
  asks you to save, record, or review past SEO results. Always save output to the
  project repo so findings persist across sessions and can be compared over time.
---

# SEO Tracker

This skill governs how SEO audit results, plugin outputs, and analysis findings
are saved and maintained at the project level. The goal is a persistent record
in `.claude/seo/` so every session can see what was measured, when, and what
changed.

## Directory Structure

All SEO data lives in `.claude/seo/` at the project root:

```
.claude/seo/
├── README.md                        ← index of all saved files (auto-maintained)
├── audits/
│   ├── YYYY-MM-DD_audit-type.md     ← full audit outputs
│   └── ...
├── baselines/
│   ├── YYYY-MM-DD_baseline.json     ← seo-drift snapshot data
│   └── ...
└── scores/
    └── score-history.md             ← running score log across audits
```

## When to Save

After **any** SEO plugin command produces output, save it before responding to the user:

| Command / plugin | Save to |
|---|---|
| `/seo audit`, `/seo technical`, `/seo content`, `/seo ecommerce` | `audits/` |
| `/seo geo`, `/seo schema`, `/seo performance`, `/seo local` | `audits/` |
| `/seo drift` baseline capture | `baselines/` |
| `/seo drift` comparison report | `audits/` |
| `/seo cluster`, `/seo backlinks`, `/seo maps` | `audits/` |
| Any numeric score or before/after comparison | also append to `scores/score-history.md` |

## File Naming

Use `YYYY-MM-DD_<type>.md` (or `.json` for baseline data):

```
audits/2026-06-25_technical-audit.md
audits/2026-06-25_geo-audit.md
baselines/2026-06-25_baseline.json
```

If multiple audits of the same type run on the same day, append a counter:
`2026-06-25_technical-audit-2.md`.

## Audit File Format

Every saved audit file must open with this header block, then the full plugin output below:

```markdown
# [Audit Type] — [YYYY-MM-DD]

**Tool:** [claude-seo command or plugin name]
**URL / Scope:** [URL or description of what was audited]
**Score:** [numeric score if reported, or "n/a"]
**Previous score:** [score from last audit of same type, or "first run"]

---

[full audit output here]
```

## Score History

`scores/score-history.md` is a running append-only log. After any audit that
produces a numeric score, append one row:

```markdown
| 2026-06-25 | Technical SEO | 72/100 | First baseline |
| 2026-06-25 | GEO / AI Citation | 58/100 | First baseline |
```

The table header (if the file is new):
```markdown
# SEO Score History

| Date | Audit Type | Score | Notes |
|------|-----------|-------|-------|
```

## README Index

After saving any file, update `.claude/seo/README.md` with a one-line entry:

```markdown
- [2026-06-25 Technical Audit](audits/2026-06-25_technical-audit.md) — score 72/100, 3 critical issues
```

The README must always reflect every file in the directory.

## Reading Past Results

When the user asks about past SEO results, prior scores, or trends:
1. Read `scores/score-history.md` for the score timeline.
2. Read the relevant audit file(s) from `audits/` for details.
3. Summarize what changed between runs if multiple exist.

When running a new audit, read the most recent audit of the same type first
and include the previous score in the new file's header so drift is visible.

## Saving the Existing Audit

The project already has one completed SEO audit recorded in the memory system
at `memory/seo-audit-2026-06-25.md`. On first use of this skill, migrate that
file into the proper structure:
1. Copy its content into `audits/2026-06-25_full-audit.md` with the header block.
2. Extract the overall score (40/100) and add it to `scores/score-history.md`.
3. Add it to `README.md`.
