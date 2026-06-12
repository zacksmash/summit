## AI Agent Session Rules

These rules are mandatory for every AI coding session in this repository. Treat them as completion criteria, not suggestions.

Tradeoff: These guidelines bias toward caution over speed. For trivial tasks, use judgment.

### 1. Think Before Coding
Don't assume. Don't hide confusion. Surface tradeoffs.

Before implementing:

State your assumptions explicitly. If uncertain, ask.
If multiple interpretations exist, present them - don't pick silently.
If a simpler approach exists, say so. Push back when warranted.
If something is unclear, stop. Name what's confusing. Ask.

### 2. Simplicity First
Minimum code that solves the problem. Nothing speculative.

No features beyond what was asked.
No abstractions for single-use code.
No "flexibility" or "configurability" that wasn't requested.
No error handling for impossible scenarios.
If you write 200 lines and it could be 50, rewrite it.
Ask yourself: "Would a senior engineer say this is overcomplicated?" If yes, simplify.

### 3. Surgical Changes
Touch only what you must. Clean up only your own mess.

When editing existing code:

Don't "improve" adjacent code, comments, or formatting.
Don't refactor things that aren't broken.
Match existing style, even if you'd do it differently.
If you notice unrelated dead code, mention it - don't delete it.
When your changes create orphans:

Remove imports/variables/functions that YOUR changes made unused.
Don't remove pre-existing dead code unless asked.
The test: Every changed line should trace directly to the user's request.

### 4. Goal-Driven Execution
Define success criteria. Loop until verified.

Transform tasks into verifiable goals:

"Add validation" → "Write tests for invalid inputs, then make them pass"
"Fix the bug" → "Write a test that reproduces it, then make it pass"
"Refactor X" → "Ensure tests pass before and after"
For multi-step tasks, state a brief plan:

1. [Step] → verify: [check]
2. [Step] → verify: [check]
3. [Step] → verify: [check]

### 5. Completeness Over Shortcuts
Strong success criteria let you loop independently. Weak criteria ("make it work") require constant clarification.

AI-assisted coding makes the marginal cost of completeness near-zero. When the complete implementation costs minutes more than the shortcut — do the complete thing. Every time.

Completeness is cheap. When evaluating "approach A (full, ~150 LOC) vs approach B (90%, ~80 LOC)" — always prefer A. The 70-line delta costs seconds with AI coding. "Ship the shortcut" is legacy thinking from when human engineering time was the bottleneck.

The 1000x engineer's first instinct is "has someone already solved this?" not "let me design it from scratch." Before building anything involving unfamiliar patterns, infrastructure, or runtime capabilities — stop and search first. The cost of checking is near-zero. The cost of not checking is reinventing something worse.

These guidelines are working if: fewer unnecessary changes in diffs, fewer rewrites due to overcomplication, and clarifying questions come before implementation rather than after mistakes.

## Before marking work complete

Run the smallest verification set that matches the files you changed:

- PHP changed: `composer lint && composer types:check` and the smallest relevant `php artisan test --compact ...` command.
- JS, TS, or Vue changed: `npm run format` and `npm run lint`; add `npm run types:check` when types, route wiring, or page props changed.
- Wide refactors or cross-cutting changes: expand verification only as needed to cover the risk.
- Docs or process-only changes: record `Verification: not run (docs-only or process-only change)`.

Do not claim work is complete if relevant verification failed or was skipped without explanation.
