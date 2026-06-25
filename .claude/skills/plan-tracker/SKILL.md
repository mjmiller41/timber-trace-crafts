---
name: plan-tracker
description: >
  Use this skill whenever you are creating a plan, implementation roadmap, or
  multi-step task list for the user. Also use it whenever you are executing or
  implementing a plan — to keep the plan file up to date as tasks are
  completed. Triggers on phrases like "make a plan", "create a plan",
  "plan for X", "proceed with the plan", "implement the plan", "work through
  the plan", or any request to track progress through a set of tasks.
  Always invoke this skill before starting any plan-related work.
---

# Plan Tracker

This skill governs how plans are created, maintained, and closed out. The goal
is a single source of truth the user can open at any time and see exactly what
has been done and what remains.

## Creating a Plan

When the user asks you to create a plan (for a feature, a refactor, an audit,
a content strategy — anything multi-step):

1. **Draft the plan content** — sections, tasks, rationale, whatever the plan
   needs. Keep task descriptions concrete and actionable.

2. **Build the checklist** — every discrete task that will be executed gets a
   `- [ ]` checkbox. Place the full checklist at the very top of the file,
   before any narrative content. This lets the user see overall progress at a
   glance without scrolling.

3. **Save to `.claude/plans/`** — always save in the `.claude/plans/` directory
   at the project root. Name the file descriptively in `snake_case`, e.g.:
   - `.claude/plans/journal_seo_fixes.md`
   - `.claude/plans/checkout_refactor.md`
   - `.claude/plans/blog_content_strategy.md`

   The filename should make the plan's scope obvious without opening it.

4. **Tell the user the path** — after saving, mention the file path so they
   know where to find it.

### Checklist format

```markdown
## Task Checklist

- [ ] Short description of task 1
- [ ] Short description of task 2
- [ ] Short description of task 3

---

## Full Plan

[detailed plan content below]
```

Every item in the checklist should correspond to a concrete action that can be
marked done. Avoid vague items like "do research" — prefer "audit N+1 queries
in JournalController" or "add RSS feed route before slug wildcard".

## Implementing a Plan

When the user asks you to work through or implement a plan — or when you are
actively executing tasks from an existing plan file:

1. **Read the current plan file** before starting any work.

2. **Check off tasks as you complete them** — update the file after each task
   is done, not in a batch at the end. Use `Edit` to replace `- [ ]` with
   `- [x]` for each completed item. This keeps the file accurate even if the
   session is interrupted.

3. **Add a completion date note** when checking off significant tasks, using
   an inline comment: `- [x] Fix N+1 queries in JournalController *(done 2026-06-25)*`
   This creates an audit trail the user can reference later.

4. **Do not remove tasks** — even if a task turns out to be unnecessary or
   was already done, mark it `- [x]` (not deleted), and add a brief note
   explaining why if the reason isn't obvious.

## Completing a Plan

When all tasks in the checklist are checked off:

1. Verify every `- [ ]` has become `- [x]` — no unchecked items remain.
2. Add a completion line near the top of the file:
   ```
   **Completed:** 2026-06-25
   ```
3. Rename the file by appending `_complete`:
   - `.claude/plans/journal_seo_fixes.md` → `.claude/plans/journal_seo_fixes_complete.md`

   Use the Bash tool: `mv .claude/plans/old_name.md .claude/plans/old_name_complete.md`

4. Tell the user the plan is complete and the file has been renamed.

## Updating Existing Plans Mid-Session

If a plan was created earlier (in a previous session or earlier in this one)
and the user says "keep the plan up to date" or "update the plan" or simply
proceeds with implementation work — find the relevant plan file in `.claude/plans/`
and apply the check-off rules above. Always prefer keeping an existing plan
file current over creating a new one for the same topic.

## Edge Cases

- **Plan file already exists for the topic**: Update it rather than creating a
  duplicate. Check `.claude/plans/` for matching filenames before writing a new file.
- **Task added mid-implementation**: Add it to both the checklist section and
  wherever it fits in the body, then check it off immediately if it's already
  done.
- **Plan spans multiple sessions**: The file is the persistent record — read it
  at the start of any session that continues the work.
