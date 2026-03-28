# AI Agent Session Rules

These rules are mandatory for every AI coding session in this repository. Treat them as completion criteria, not suggestions.

## PROGRESS.md is required

- Read `PROGRESS.md` before planning, editing files, or marking work complete.
- Add or update the current session entry whenever context, decisions, or unfinished work would help a future session.
- Append a new session entry for each session. Never delete, rewrite, or reorder historical entries.
- Resolve older open items in a newer entry instead of editing prior history in place.

## Required session entry format

Use this exact structure in `PROGRESS.md`:

```md
## Session: YYYY-MM-DD HH:MM TZ
- Agent:
- Branch:
- Task:
- Status: in_progress | completed | blocked | handed_off
- Files Touched:
- Verification:

### Completed
-

### Decisions Made
-

### Current Context
-

### Open Items
-

### Next Step
-
```

## Writing rules

- Keep entries brief, factual, and durable.
- Record decisions with enough reasoning that a future agent will not reopen the same question.
- Record blockers, assumptions, and missing verification explicitly.
- Use `Verification:` to say exactly what ran, or state `not run` with a reason.
- Use `Files Touched:` for the main files only, not every incidental read.
- If no code changed, still record the session when it changed direction, clarified requirements, or created a handoff another agent will rely on.

## What not to write

- Do not paste command output, stack traces, or large diffs.
- Do not narrate trial-and-error debugging unless the failed path affects future decisions.
- Do not restate obvious implementation details that are already clear from the code.

## Before marking work complete

Run the smallest verification set that matches the files you changed:

- PHP changed: `vendor/bin/pint --dirty --format agent` and the smallest relevant `php artisan test --compact ...` command.
- JS, TS, or Vue changed: `npm run lint`; add `npm run types:check` when types, route wiring, or page props changed.
- Wide refactors or cross-cutting changes: expand verification only as needed to cover the risk.
- Docs or process-only changes: record `Verification: not run (docs-only or process-only change)`.

Do not claim work is complete if relevant verification failed or was skipped without explanation.
