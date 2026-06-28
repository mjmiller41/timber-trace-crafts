---
name: update-architecture
description: >
  Use this skill whenever the project's architecture, conventions, tech stack,
  or established patterns change and the shared "Project Architecture &
  Conventions" section in CLAUDE.md needs to be updated. Triggers on phrases
  like "update the architecture doc", "add this to CLAUDE.md", "remember this
  pattern", "document this convention", "the stack changed", "refresh the
  project conventions", or any request to record how this codebase works so
  future sessions know it. Also use it proactively after a change that alters a
  documented pattern (e.g. a new integration, a renamed accessor, a new
  convention).
---

# Update Architecture Doc

Maintains the hand-written **"Project Architecture & Conventions"** section at
the top of the project `CLAUDE.md` — the always-loaded brief that tells every
future session how this codebase actually works (stack, integrations, image
handling, model gotchas, routing/security conventions, build/deploy).

The goal: this section stays accurate and lean so no one rediscovers the same
footguns twice.

## Where the content lives

- Target file: `CLAUDE.md` in the **project root**.
- Target section: the block under the `# Timber Trace Crafts — Project
  Architecture & Conventions` heading, which sits **above** the
  `<laravel-boost-guidelines>` block.
- If that section does not exist yet, create it directly above
  `<laravel-boost-guidelines>` with that heading and the maintenance note.

## Modes

### A. Directed update (user names a specific fact)
The common case — the user says "add X", "we now use Y", "the accessor is
actually Z", "remember this pattern".

1. **Verify against real code first.** Read the actual source — model,
   controller, route, config, migration — and confirm the fact is true as
   stated. Never enshrine a claim you haven't checked; a wrong line here
   misleads every future session. Use Boost tools (`database-schema`,
   `route:list`, etc.) where they're faster than grep.
2. Place it in the right subsection (Stack & integrations · Images · Domain
   models & gotchas · Routing & security conventions · Build, deploy & git).
   Add a new subsection only if the fact genuinely doesn't fit one.
3. Edit surgically — change/add only the affected lines; never reorder or
   rewrite unrelated content. Match the existing terse, bulleted style.
4. If the new fact contradicts something already documented, **correct the old
   entry** rather than leaving both.

### B. Refresh sweep (review for drift)
When the user asks to "refresh", "audit", or "re-check" the conventions, or
when you suspect the doc has drifted:

1. Read the current section, then verify each claim against the live codebase.
2. Build a short list of: stale/incorrect entries, missing patterns worth
   adding, and entries to drop (no longer true or never load-bearing).
3. **Show the proposed diff to the user and get confirmation before writing.**
4. Apply only the confirmed changes.

## Rules

- **Never edit the `<laravel-boost-guidelines>` block** — it is auto-generated
  by Boost and will be overwritten. All hand-written knowledge goes in the
  architecture section only.
- **Verify before you write.** Every line must reflect real, current code.
  Flag anything you couldn't confirm rather than asserting it.
- **Keep it lean.** This file loads every session, so it must earn its tokens.
  Prefer one sharp line over a paragraph. If a topic needs real depth, put the
  depth in `docs/ARCHITECTURE.md` and leave a one-line pointer here.
- **Don't duplicate** what the Boost block, the README, or the code already
  make obvious. Document the non-obvious: gotchas, the "right way" among
  several, cross-cutting conventions.
- Preserve the section's structure and heading; don't rename subsections
  casually (future edits and muscle memory depend on them).

## Finishing

- Run `vendor/bin/pint` only if you touched PHP (this skill usually touches just
  Markdown, so skip it).
- `CLAUDE.md` is tracked in git. Offer to commit + push to `main`
  (`docs: update project architecture & conventions in CLAUDE.md`), per the
  project's solo-operator, commit-to-main convention. Don't create a branch.
- Report a one-line outcome: what changed and that it's committed (or awaiting
  the user's go-ahead).
