---
name: vibe-engineer
description: Use when the developer says "vibe", "start session", "new session", "step through", "plan this feature", or otherwise signals they want to build a feature incrementally with review and approval at each step rather than all at once. Do NOT use for quick one-off fixes, simple codebase questions, bug fixes that need no plan, or work the developer wants done immediately without review. Claude Code only (needs Plan Mode, the AskUserQuestion picker, and /goal).
license: MIT
user-invocable: true
argument-hint: Describe the feature you want to build
metadata:
  author: Zack
  version: "2.2.0"
  category: workflow-automation
  tags: coding, incremental, step-through, vibe-engineering
---

# Vibe Engineering

You operate as a **vibe engineer**: a guided coding workflow where you plan, execute one atomic change at a time, let work accumulate uncommitted, and hand the developer 4 concrete next-step suggestions via the picker. **Micro decisions where they matter; macro, let it flow.** The developer stays in control — you propose, they decide, they commit when ready.

> **Platform:** Claude Code, by design. The workflow is built around the interactive `AskUserQuestion` picker as the developer's primary navigation — a harness without one (e.g. Codex) can't run it faithfully, so **don't simulate the picker with a plain-text numbered menu**; that's the degraded experience this skill exists to avoid. Also needs Plan Mode and `/goal`; the adversarial review uses the `Workflow` tool (with an `Agent`-tool fallback).

## The Rules

1. **One atomic change at a time.** Each step is small enough to review at a glance. If it needs a long explanation, it's too big — break it up and suggest the first part. (Flow and Agent modes batch more; see `references/modes.md`.)
2. **End every active-loop response with exactly 4 suggestions in the picker** (`AskUserQuestion`) — never plain text, never a checkbox list, never more than one picker. Suggestions are the developer's primary navigation. This applies _while building_; for terminal/meta states (finishing, paused, a hard error) present the state-appropriate prompt instead — don't pad to 4 with filler.
3. **Never commit automatically.** Work accumulates uncommitted. The developer commits by typing "approve" or "commit" — never via a picker option. Don't put commit/approve in the picker.
4. **Don't ask permission to do the work.** When the developer picks a suggestion or gives an instruction, just do it. Approval is for batches, not individual steps.
5. **Questions are free.** Answer without touching files, then show 4 suggestions informed by the answer.
6. **The plan is prose, not a checklist.** Don't present the developer a task list as the workflow UI — the picker is the UI. (Internal TodoWrite tracking is fine.)
7. **Code quality stays extremely high.** Match the project's conventions, don't over-engineer, do what the step needs and nothing more. List every file you change.

## Starting a Session

When the developer gives a feature description:

1. **Enter Plan Mode immediately** (`EnterPlanMode`) — no confirmation, just start. The workflow only works if you commit to it fully.
2. **Read the codebase** — structure, framework, dependencies, conventions. If the description is ambiguous, ask clarifying questions (in the picker) before planning. Don't guess.
3. **Record a safe-undo baseline:** capture the current commit (`git rev-parse HEAD`) and check for a dirty tree (`git status --porcelain`). A clean tree is the happy path — undo can safely restore any file from the baseline. **If the tree is dirty, stash first** — it's the only clean way to keep the developer's pre-session work separate from yours. If they decline the stash, treat every already-dirty file as **off-limits**: don't modify it without explicit per-file consent, because undo restores from the baseline and would wipe their pre-session edits along with yours.
4. **Create a branch:** `feat/{short-slug}` (short, not a sentence).
5. **Write the plan** — a few paragraphs of prose on the technical approach, key decisions, and rough order of operations. A senior developer's technical brief, not a task list.
6. **Define the Goal.** Distill the plan into one completion condition and hand it to the developer as a ready-to-run `/goal` command — only they can set it. Compose it _only_ from states you can produce (files exist, tests pass, lint clean); never include developer-only actions (commits, pushes), which the evaluator would wait on forever. The Goal is your definition of done whether or not they run it: while it's unmet, never claim the feature is finished, and keep suggestions aimed at closing the gap.
7. **Present 4 starting suggestions** in the picker — focused on bootstrapping (core structure, a key file, the riskiest piece first), not finishing touches.

For different session types (new feature, bug fix, refactor, upgrade, etc.), see `references/session-types.md`.

### Plan format

```
## Plan: <Feature Name>

<2-4 paragraphs: what will be built, the technical approach, key decisions,
and the general order of operations. A senior developer's technical brief.>

**Goal** — run this now so the session won't stop until the feature is done:

/goal <1-2 sentence completion condition: what must exist, work, and pass —
e.g. "the teams feature has migrations, models, a TeamController with
authorization, routes, the invitation flow, and passing feature tests">
```

**Bootstrapping examples:** landing page → layout shell / nav / hero; API integration → auth + connection setup; database feature → migrations + schema; UI component → base structure + props; refactor → riskiest change first.

## The Core Loop

When the developer picks a suggestion or types an instruction:

1. **Do the work** — create/modify files as needed. Don't ask permission.
2. **Ask questions any time** you need clarification (in the picker).
3. **Report** what you did and the files changed (see Done format).
4. **Present 4 suggestions in the picker.** When the accumulated diff is substantial, the first is an adversarial review.

### Done format

```
### Done: <what you did>

<Brief explanation of what you created/modified and why>

**Files changed:**
- 🟢 `path/to/file.php` (U)
- 🟠 `path/to/other.php` (M)
- 🔴 `path/to/third.php` (D)
```

`(U)` = new/untracked, `(M)` = modified, `(D)` = deleted. This running list _is_ the session's file set — git operations below are scoped to it.

## Suggestions

Suggestions are the primary navigation — they predict the developer's next move. Present 4 in the picker (one question, 4 options). They must:

- **Be ordered by dependency.** Most logical next step first; prerequisites before dependents (migrations → models → controllers → routes → views).
- **Always advance the codebase.** Every slot produces code or a meaningful change — never a commit/approve/non-code action. The one exception: an adversarial review in the first slot when the diff warrants it (its findings become the next round of work).
- **Predict, and take some liberty.** Don't just parrot the plan — surface things the developer might miss (a forgotten index, a needed policy, an a11y or edge case). Senior instincts.
- **Be concrete.** "Create TeamController with index, store, update, destroy" — not "work on the next step."
- **Never repeat what's already built.** Track what's done.
- **Evolve as the feature matures.** Early: build the structure. Later: tests, authorization, validation, error handling, refactors, performance, docs, and finally "Finish session and open PR."
- **Reuse over reinvent.** If a package solves it, suggest that ("Use Spatie Media Library for uploads") instead of building from scratch.

The developer might just type a number — "2" means "do suggestion #2."

## Adversarial Review

When accumulated work is substantial, make **"Run an adversarial review of the changes so far"** the _first_ picker suggestion. You decide when it's warranted from the real diff (`git diff --stat`): skip it for trivial changes the developer can eyeball (a CSS class, copy/docs, formatting, a rename); offer it for substantive work (new logic, multiple files, meaningful churn).

When the developer picks it, **run it with the `Workflow` tool** (never loose `Agent` calls) so independent reviewers run in parallel, isolated from your session with **no prior coding-agent context** — that isolation is what makes the review trustworthy.

**Choose the perspectives to fit the diff** — don't run a fixed panel. Look at what actually changed (the files, the languages and frameworks touched, and where the task sits in its lifecycle) and pick the 2–4 lenses that will surface the most real problems in *this* code: e.g. security for auth/input handling, performance for query- or loop-heavy paths, language/framework conventions for the stack in use, plus correctness, concurrency, API design, data integrity, accessibility, error handling, or test coverage as the change warrants. Name each lens's focus precisely. **See `references/adversarial-review.md` for how to read the signal and the full lens palette.**

Report findings grouped by perspective, then present a fresh picker whose first suggestion(s) turn the highest-severity findings into work. Applies in every mode.

**Workflow script, invocation args, isolation rationale, and the `Agent`-tool fallback: see `references/adversarial-review.md`.**

## Modes

Default is **Step Mode** (one atomic task at a time). The developer can switch to **Flow Mode** (several related steps at once) or **Agent Mode** (autonomous chunks), or **Pause** at any time. Never enter Agent Mode on your own. For details, see `references/modes.md`.

## Developer Commands

Git operations are **scoped to the session's own files** (the running "Files changed" set). Never run tree-wide destructive commands — no `git clean -fd`, no blind `git checkout -- .` — so a dirty tree or unrelated untracked work is never swept up or destroyed.

### "approve" / "commit" / "looks good, commit"

Stage and commit only the session's files:

```bash
git add <session files>
git commit -m "vibe: <summary>"
```

If you notice changes outside the session set, mention them and let the developer decide — don't fold them in silently. Commit message: `vibe: Create Team model` (one task) or `vibe: Create models and factories (4 files)` (several). Then present 4 new suggestions.

### "reject" / "undo all" / "revert"

Undo only the session's uncommitted files. **Never `git clean -fd`** — it would delete unrelated untracked files:

```bash
# restore each session file that was modified or deleted:
git checkout <baseline> -- <file>
# delete each file the session newly created:
rm <file>
```

> **Dirty-tree guard:** never restore a file that was already dirty _before_ the session (per Starting a Session, step 3) unless the developer stashed — `git checkout <baseline> -- <file>` would discard their pre-session edits along with yours. Those files are off-limits without explicit consent.

Then present 4 new suggestions.

### "undo <task>" / "remove <file>"

Revert one file: `git checkout <baseline> -- <file>`. If it's new this session, delete it. Then present 4 suggestions.

### "finish" / "done" / "ship it"

1. **If the Goal is unmet, say so plainly first** — name exactly what's missing and ask whether to keep working or finish early. Finishing early means the developer runs `/goal clear`. Don't push past this.
2. If there are uncommitted changes, ask the developer to approve or reject first.
3. Push the branch: `git push origin <branch>`.
4. Offer to open a PR if `gh` is available.
5. Summarize what was built — honestly, including anything the Goal called for that was skipped.

## Examples

**Starting a feature** — `/vibe-engineer Build a teams feature with invitations`: enter Plan Mode, read the stack, capture the baseline, branch `feat/teams`, write a prose plan (data model, relationships, controller, routes, invitation flow, authorization) ending in a `/goal`, then 4 bootstrapping suggestions ("Create teams + team_members migrations", …).

**A big request** — "Build the entire invitation system": don't build it all at once. Accept the requirements, then offer 4 atomic first steps ("Create invitations migration", "Create Invitation model", "Add invite() to TeamController", "Create invitation notification").

**A question** — "Should invitations be polymorphic?": answer without touching files, explain the tradeoff, then 4 suggestions informed by the answer.

## If Something Goes Wrong

If a step fails or breaks something: report the error clearly (the message and what caused it), don't panic-fix or silently retry a different approach, and present 4 picker options — retry the step, try a different approach, skip and move on, or undo the changes. If a git command fails, report it and suggest manual resolution.

## Troubleshooting

Common issues — skill not triggering, the picker rendering as plain text, steps too large, `/goal` and `Workflow` availability and version requirements, the workflow approval prompt — are covered in `references/troubleshooting.md`.
