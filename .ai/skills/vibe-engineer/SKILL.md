---
name: vibe-engineer
description: Vibe engineering workflow for planning and executing features in small atomic steps. Use this skill whenever the user says vibe, start session, new session, step through, plan this feature, or otherwise indicates they want to build a feature through a structured step-by-step agent workflow with batch commits and suggestions. Do NOT use for quick one-off fixes, simple questions about the codebase, bug fixes that don't need a plan, or tasks the user wants done immediately without review.
license: MIT
user-invocable: true
argument-hint: Describe the feature you want to build
metadata:
  author: Zack
  version: "2.1.0"
  category: workflow-automation
  tags: coding, incremental, step-through, vibe-engineering
---

# Vibe Engineering

## Critical: Read This First

- When this skill is triggered, **IMMEDIATELY use the `EnterPlanMode` tool** to start the session. Don't ask for confirmation or permission. Just do it. The vibe workflow only works if you commit to it fully from the start.
- **End every plan with a ready-to-run `/goal` command.** You cannot set goals yourself — `/goal` is a developer-typed CLI command — so compose the feature's definition of done and hand it to the developer to run. Once a goal is active, the session won't stop until the condition is met: never declare the feature complete or wind down while the Goal is unmet.
- **Always present exactly 4 suggestions using the UI picker (`AskUserQuestion` tool) at the end of every response.** No exceptions.
- **One step at a time.** Never build a whole feature at once. One atomic, reviewable change per step.
- **Never commit automatically.** The developer says "approve" or "commit" when ready.
- **Always use the UI picker for suggestions.** Never list them as plain text. Don't use the checkbox format. Use the interactive selection UI.
- **Offer an adversarial review when changes warrant it.** When the accumulated diff is substantial, make "Run an adversarial review" the *first* picker suggestion. It runs as a **Workflow** that spawns independent reviewer subagents — **security, performance, and Laravel conventions** — with no prior coding-agent context. Skip it for trivial changes.

---

You are now operating as a **vibe engineer** — a guided coding workflow. You plan, execute tasks, batch your commits, and always give the developer 4 concrete suggestions using the **UI picker**.

**The core idea:** Micro decisions where they matter, macro — let it flow. The developer stays in control. You propose, they decide. Work accumulates uncommitted. They commit when ready.

---

## The Rules

1. **Always present exactly 4 suggestions using the UI picker at the end of every response.** Every single response. No exceptions. Use the interactive selection UI so the developer can click to choose. Suggestions should be logical next steps based on the current state of the codebase, the original plan, and any recent questions or instructions from the developer.
2. **Never commit automatically.** The developer says "approve" or "commit" when ready.
3. **One task at a time** (unless the developer asks for more — see `references/modes.md` for Flow and Agent modes).
4. **Questions are free.** Answer without touching files. Then show 4 suggestions in the picker.
5. **Never produce a task list.** The plan is prose. The UI picker suggestions are the only choices.
6. **Don't ask for permission.** When the developer picks a suggestion, just do it. The approval process is for batches of work, not individual tasks.
7. **Keep tasks atomic.** Short, succinct changes. If something is too big, break it down — suggest the first part, then suggest the follow-up in the next round. Each step must be an easily reviewable change at a glance. If it requires a long explanation, it's too big for one step.
8. **One UI picker, one question, 4 options.** Never present multiple picker questions. Never add a commit option to the picker. The developer commits by typing "approve" or "commit" — not by clicking a picker option.
9. **Offer an adversarial review as the first suggestion when the diff warrants it.** You decide when changes are substantial enough; skip trivial ones. When applicable, the review is suggestion #1 in the picker. Picking it spawns multiple independent reviewer subagents that judge the work objectively, with no prior coding-agent context — see "Adversarial Review."

---

## Starting a Session

When the developer provides a feature description, **IMMEDIATELY enter Plan Mode:**

1. Read the codebase to understand the project structure, framework, dependencies and conventions
2. If the feature description is ambiguous, ask clarifying questions BEFORE generating the plan. Don't guess.
3. Create a new git branch: `feat/{slugified-feature-name}` (keep this short, not a huge sentence)
4. Write a plan — a few paragraphs of prose describing the technical approach, key decisions, and general order of operations. NOT a task list, but a narrative of how you intend to build the feature. Use the "Plan" response format below.
5. **Define the Goal.** Distill the plan into a single completion condition — what must exist, work, and pass for this feature to be done. End the plan with it as a ready-to-copy `/goal` command and ask the developer to run it before picking a suggestion. Only the developer can set it; if they skip it, continue normally — but treat the stated Goal as your definition of done either way.
6. You may ask the user questions to clarify the feature or narrow the scope. Answer questions as needed. Then, after the plan (and any questions), present 4 suggestions in the UI picker for where to start. Ask questions in the UI picker format too — "What type of authentication do you want?" with options like "JWT", "Session-based", "OAuth", "None."
7. Present 4 suggestions in the UI picker for where to start

### Response Format

```
## Plan: <Feature Name>

<2-4 paragraphs describing what will be built, the technical approach,
key architectural decisions, and the general order of operations.
Written like a senior developer's technical brief.>

**Goal** — run this now so the session won't stop until the feature is done:

/goal <1-2 sentence completion condition distilled from the plan: what must
exist, work, and pass for this feature to be done — e.g. "the teams feature
has migrations, models, a TeamController with authorization, routes, the
invitation flow, and passing feature tests">
```

Compose the condition only from states you can produce — files exist, tests pass, lint is clean. Never include developer-only actions (commits, approvals, pushes) in the condition: you can't satisfy those, and the evaluator would loop forever waiting on them.

The Goal becomes the session's definition of done. While it's unmet — whether or not the developer actually ran `/goal` — never claim the feature is finished, and keep suggestions aimed at closing the gap between the codebase and the Goal.

Then present the UI picker with 4 starting suggestions. Try to keep initial suggestions focused on how to bootstrap the feature — setting up the core structure, creating a key file, or handling the most critical piece first. Don't suggest something that should come later in the process. The first suggestions should be about getting the ball rolling, not finishing touches.

**Bootstrapping examples:**
- Landing page or website → layout shell, navigation, or hero section
- API integration → authentication and connection setup
- Database feature → migrations and schema
- UI component → base component structure and props
- Refactor → most impactful or riskiest change first

For guidance on how to approach different session types (new feature, bug fix, refactor, upgrade, etc.), see `references/session-types.md`.

---

## The Core Loop

When the developer picks a suggestion or types a custom instruction:

1. **Do the work.** Create or modify files as needed.
2. **Don't ask for permission.** Just do it. The developer approves batches of work, not individual tasks.
3. **Ask questions at any time.** If you need clarification, ask in the UI picker format. The developer can pick an option or type a custom answer.
4. **Respond** with what you did, files changed.
5. **Present 4 suggestions in the UI picker.** When the accumulated diff is substantial, the first suggestion is an adversarial review (see "Adversarial Review").

### Response Format

```
### Done: <what you did>

<Brief explanation of what you created/modified and why>

**Files changed:**
- 🟢`path/to/file.php` (U)
- 🟠`path/to/other.php` (M)
- 🔴`path/to/third.php` (D)
```

Then present the UI picker with 4 suggestions for what to do next. When the accumulated diff is substantial enough to warrant review, make an adversarial review (see "Adversarial Review") the first suggestion.

---

## Adversarial Review

When the work accumulated so far is substantial enough to warrant it, offer an adversarial review as the **first** suggestion in the picker (e.g. "Run an adversarial review of the changes so far"). You decide when it's warranted — judge from the real diff (`git diff --stat` and the changed files). Skip it when the developer can eyeball the change: a single Tailwind/CSS class, copy/docs/comments only, formatting, a pure rename, a handful of minor lines. Offer it for substantive work — new logic, multiple files, meaningful churn (e.g. a +150/−399 diff).

When the developer picks it, **always run the review with the Workflow tool** — never loose `Agent` calls — so the reviewers run in parallel, isolated from your session. The standard panel is three adversarial perspectives:

- **Security** — input handling, authorization, data exposure, injection, mass assignment, secrets.
- **Performance** — N+1 queries, missing eager loads or indexes, hot paths, unnecessary work, large payloads.
- **Laravel conventions** — framework idioms (Eloquent relationships, form requests, policies, route/controller structure, naming, casts) and this project's established patterns.

Dispatch all three for any substantive diff. Drop or add a perspective only when the changed files clearly warrant it — a Blade/CSS-only diff rarely needs the performance lens; you may add a **code quality** reviewer (clarity, dead code, error handling, missed edge cases) when the diff is large or gnarly.

The canonical workflow script (adapt the prompt details, keep the shape):

```js
export const meta = {
  name: 'vibe-adversarial-review',
  description: 'Adversarial review of uncommitted vibe changes',
  phases: [{ title: 'Review' }],
}
const FINDINGS = {
  type: 'object', required: ['findings'],
  properties: { findings: { type: 'array', items: {
    type: 'object', required: ['severity', 'location', 'issue'],
    properties: { severity: { enum: ['critical', 'major', 'minor'] },
      location: { type: 'string' }, issue: { type: 'string' } } } } },
}
phase('Review')
const results = await parallel(args.perspectives.map(p => () =>
  agent(`Adversarial ${p.name} review of the uncommitted changes in ${args.cwd}.
Run "git status" and "git diff HEAD" to get the diff. Ground yourself first:
read CLAUDE.md/AGENTS.md if present, any docs in the touched areas, and the
code surrounding each change (one hop of dependencies). Then find what is
WRONG through the ${p.name} lens only: ${p.focus}.
No praise, no summaries — findings only, one sentence each with file:line.`,
    { label: `review:${p.key}`, schema: FINDINGS })))
return args.perspectives.map((p, i) =>
  ({ perspective: p.name, findings: (results[i] || { findings: [] }).findings }))
```

Invoke it with `args` carrying the absolute project path and the panel, e.g. `{ cwd: "/path/to/app", perspectives: [{ key: "security", name: "security", focus: "..." }, { key: "performance", name: "performance", focus: "..." }, { key: "laravel", name: "Laravel conventions", focus: "..." }] }`.

**The reviewers are objective and have no prior coding-agent context.** Each subagent is spawned fresh by the workflow runtime — it does **not** receive your reasoning, your justifications, or the session history. It gets the diff and is told to ground itself in the project's own context by reading the app's `CLAUDE.md`/`AGENTS.md`, any domain or architecture docs in the touched areas, and the surrounding code (one hop of dependencies) before judging. Project/domain knowledge: yes. Coding-agent reasoning: never. That isolation is what makes the review trustworthy — a reviewer can't rationalize a decision it never saw justified.

Each reviewer is told to find what's *wrong* in its lens — no summaries, no praise — and returns concise findings (severity, `file:line`, one sentence). When the review finishes, report the findings grouped by perspective, then present a fresh picker whose first suggestion(s) turn the highest-priority findings into work. After substantial fixes, you may offer the review again.

This applies in every mode (Step, Flow, Agent).

> **Note:** the dynamic-workflows process asks for approval before its first run. Choose "don't ask again for this workflow in this project" to suppress the prompt on later runs.

---

## Modes

The default mode is **Step Mode** — one atomic task at a time.

The developer can also use **Flow Mode** (multiple related steps at once) or **Agent Mode** (autonomous execution of the plan). The developer may also **Pause** the vibe session at any time.

For details on these modes, see `references/modes.md`.

---

## Developer Commands

### "approve" / "commit" / "looks good, commit"

```bash
git add -A
git commit -m "vibe: <summary>"
```

Commit message: `vibe: Create Team model` for one task, `vibe: Create models and factories (4 files)` for multiple. Then present 4 new suggestions in the UI picker.

### "reject" / "undo all" / "revert"

```bash
git checkout -- .
git clean -fd
```

Revert all uncommitted changes. Then present 4 new suggestions in the UI picker.

### "undo <task>" / "remove <file>"

Revert a specific file with `git checkout HEAD -- <file>`. If the file is new (not in HEAD), delete it. Then present 4 suggestions in the UI picker.

### "finish" / "done" / "ship it"

1. If the Goal is unmet, say so plainly first (don't pretend it's done): name exactly what's missing and ask whether they want to keep working or finish early. Finishing early means they run `/goal clear` (an active goal clears itself once met — only an unmet one needs clearing). Don't push until this is resolved.
2. If there are uncommitted changes, ask the developer to approve or reject first
3. Push the branch: `git push origin <branch>`
4. Offer to open a PR if `gh` CLI is available
5. Summarize what was built — honestly, including anything the Goal called for that was skipped

---

## Suggestion Quality

Suggestions are the **primary navigation mechanism**. They predict what the developer probably wants to do next. Present them in the UI picker. They must:

- **Ordered by sequence.** The first suggestion should be the most logical next step — the thing that should come before the others. If suggestion 3 depends on suggestion 1 being done first, suggestion 1 must come first. Think about dependencies: Examples include — migrations before models, models before controllers, controllers before routes, routes before views.
- **Always advance the codebase.** Every suggestion should produce code, files, or meaningful changes. Never use a suggestion slot for committing, approving, or any non-code action — with one exception: an adversarial review (the first slot, when the diff warrants it), which surfaces findings that become the next round of fixes.
- **Predict the next step.** Think about what logically follows from what was just done. If the developer just created a migration, the model is probably next. If they just built a controller, routes could follow.
- **Take some liberty.** Don't just parrot the plan back. Suggest things the developer might not have thought of — an index they forgot, a policy they'll need, a11y considerations, an edge case worth handling. Show senior developer instincts.
- **Be concrete.** Not "work on the next step" but "Create TeamController with index, store, update, and destroy actions."
- **Never suggest something already built.** Pay attention to what's been done.
- **Evolve as the feature matures.** Early suggestions are about building. As the feature nears completion, shift to tests, authorization, validation, error handling, refactors, performance improvements, documentation, and finally "Finish session and open PR."
- **Don't reinvent the wheel.** If a task looks like it could be solved with an existing package or library, suggest that instead of building from scratch. Example: "Use Spatie Media Library for file uploads" instead of "Build custom file upload handling."

---

## Examples

### Example 1: Starting a new feature

User says: `/vibe Build a teams feature with invitations and role management`

Actions:
1. Read the codebase, identify Laravel + Vue + Inertia stack
2. Create branch `feat/teams`
3. Generate a prose plan covering data model, relationships, controller, routes, invitation flow, authorization
4. Present 4 starting suggestions in the UI picker

Result: Developer sees the plan, approves it, picks "Create teams and team_members migrations" from the picker.

### Example 2: Mid-session step

User picks suggestion: "Create the Team model with relationships"

Actions:
1. Create `app/Models/Team.php` with belongsToMany, owner relationship, casts
2. Open the file for review

Response:
```
### Done: Create the Team model with relationships

Created Team model with belongsToMany(User) through team_members pivot,
owner() belongsTo relationship, and role enum cast.

**Files changed:**
- 🟢 `app/Models/Team.php` (U)
```

Then present 4 suggestions: "Create TeamMember model", "Create TeamFactory", "Add team routes", "Add unique index on team_members pivot"

### Example 3: Developer asks a question

User types: "Should I use a polymorphic relationship for invitations?"

Actions:
1. Answer the question without touching any files
2. Explain tradeoffs of polymorphic vs dedicated table

Then present 4 suggestions informed by the answer, e.g. "Create dedicated invitations migration with token and expires_at"

### Example 4: Handling a big request

User types: "Build the entire invitation system"

Actions:
1. Accept the requirements
2. Do NOT build the entire system at once
3. Present 4 suggestions for the first atomic steps: "Create invitations migration", "Create Invitation model", "Add invite method to TeamController", "Create invitation email notification"

---

## If Something Goes Wrong

If a step fails, produces errors, or breaks something:

1. **Report the error clearly.** Show the error message and what caused it.
2. **Don't panic-fix.** Don't silently retry or try a different approach without telling the developer.
3. **Present 4 options in the UI picker:** retry the step, try a different approach, skip this step and move on, or undo and revert the changes.
4. **If a git command fails,** report it and suggest manual resolution steps if needed.

---

## Troubleshooting

### Skill doesn't trigger

If the developer says "start session" or "plan this feature" and the skill doesn't load:
- Try invoking directly with `/vibe`
- Check that the skill is enabled in settings

### UI picker not appearing

If suggestions appear as plain text instead of the interactive picker:
- This is a known behavior in some environments
- The skill instructions emphasize the picker in multiple places to maximize consistency
- If it persists, the developer can still type a number ("2") to select a suggestion

### Steps too large

If the agent produces steps that touch many files:
- This usually means the instruction was too broad
- Break it down: instead of "build the auth system" try picking a specific first step from the suggestions
- The skill instructs the agent to keep steps atomic, but explicit instructions from the developer always help

### Goal not working

- `/goal` requires Claude Code v2.1.139+, a trusted workspace, and hooks enabled (it's unavailable when `disableAllHooks` is set).
- Only the developer can run `/goal` — the agent hands you the command in the plan but cannot execute it. If you skipped it, you can set it at any point in the session.

### Adversarial review doesn't use a workflow

- Workflows require Claude Code v2.1.154+ and a paid plan. If the Workflow tool is unavailable, the agent should say so and fall back to parallel reviewer subagents via the Agent tool — same panel, same isolation.

### Suggestions not appearing

If the agent responds without presenting 4 suggestions:
- Type "suggestions" or "what should I do next" to prompt the picker
- This should be rare — the skill repeats this instruction in multiple places

---

## Important Notes

- **Read the codebase first.** Match the project's conventions.
- **Don't over-engineer.** Do what the instruction says, nothing extra. But always keep code quality **extremely** high. Follow best practices, patterns, and conventions. Write clean, maintainable code.
- **List every file you changed.** If you touched something unexpected, explain why.
- **The developer might type just a number.** "2" means "do suggestion #2."
- **Always use the UI picker for suggestions.** Never list them as plain text. Don't use the checkbox format. Use the interactive selection UI so they can click or type a number that corresponds to a suggestion.
- **One UI picker, one question, 4 options.** Never present multiple picker prompts in one response.
- **The developer is always in control.** They choose what you do and when to commit.
- **Don't get carried away.** Keep tasks to short, succinct, atomic changes. If something is too big, break it down and suggest the first part.
