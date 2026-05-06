---
name: vibe-engineer
description: Vibe engineering workflow for planning and executing features in small atomic steps. Use this skill whenever the user says vibe, start session, new session, step through, plan this feature, or otherwise indicates they want to build a feature through a structured step-by-step agent workflow with batch commits and suggestions. Do NOT use for quick one-off fixes, simple questions about the codebase, bug fixes that don't need a plan, or tasks the user wants done immediately without review.
license: MIT
user-invocable: true
argument-hint: Describe the feature you want to build
metadata:
  author: Zack
  version: "2.0.0"
  category: workflow-automation
  tags: coding, incremental, step-through, vibe-engineering
---

# Vibe Engineering

## Critical: Read This First

- When this skill is triggered, **IMMEDIATELY use the `EnterPlanMode` tool** to start the session. Don't ask for confirmation or permission. Just do it. The vibe workflow only works if you commit to it fully from the start.
- **Always present exactly 4 suggestions using the UI picker (`AskUserQuestion` tool) at the end of every response.** No exceptions.
- **One step at a time.** Never build a whole feature at once. One atomic, reviewable change per step.
- **Never commit automatically.** The developer says "approve" or "commit" when ready.
- **Always use the UI picker for suggestions.** Never list them as plain text. Don't use the checkbox format. Use the interactive selection UI.

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

---

## Starting a Session

When the developer provides a feature description, **IMMEDIATELY enter Plan Mode:**

1. Read the codebase to understand the project structure, framework, dependencies and conventions
2. If the feature description is ambiguous, ask clarifying questions BEFORE generating the plan. Don't guess.
3. Create a new git branch: `feat/{slugified-feature-name}` (keep this short, not a huge sentence)
4. Write a plan — a few paragraphs of prose describing the technical approach, key decisions, and general order of operations. NOT a task list, but a narrative of how you intend to build the feature. Use the "Plan" response format below.
5. You may ask the user questions to clarify the feature or narrow the scope. Answer questions as needed. Then, after the plan (and any questions), present 4 suggestions in the UI picker for where to start. Ask questions in the UI picker format too — "What type of authentication do you want?" with options like "JWT", "Session-based", "OAuth", "None."
6. Present 4 suggestions in the UI picker for where to start

### Response Format

```
## Plan: <Feature Name>

<2-4 paragraphs describing what will be built, the technical approach,
key architectural decisions, and the general order of operations.
Written like a senior developer's technical brief.>
```

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
5. **Present 4 suggestions in the UI picker.**

### Response Format

```
### Done: <what you did>

<Brief explanation of what you created/modified and why>

**Files changed:**
- 🟢`path/to/file.php` (U)
- 🟠`path/to/other.php` (M)
- 🔴`path/to/third.php` (D)
```

Then present the UI picker with 4 suggestions for what to do next.

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

1. If there are uncommitted changes, ask the developer to approve or reject first
2. Push the branch: `git push origin <branch>`
3. Offer to open a PR if `gh` CLI is available
4. Summarize what was built

---

## Suggestion Quality

Suggestions are the **primary navigation mechanism**. They predict what the developer probably wants to do next. Present them in the UI picker. They must:

- **Ordered by sequence.** The first suggestion should be the most logical next step — the thing that should come before the others. If suggestion 3 depends on suggestion 1 being done first, suggestion 1 must come first. Think about dependencies: Examples include — migrations before models, models before controllers, controllers before routes, routes before views.
- **Always advance the codebase.** Every suggestion should produce code, files, or meaningful changes. Never use a suggestion slot for committing, approving, or any non-code action.
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
