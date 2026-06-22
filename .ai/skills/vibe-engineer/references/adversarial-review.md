# Vibe Engineering — Adversarial Review

Offered from the main loop when the accumulated diff is substantial (see SKILL.md → Adversarial Review for *when*). Run it with the `Workflow` tool so reviewers run in parallel, isolated from your session.

## Why isolation matters

Each reviewer subagent is spawned fresh by the workflow runtime. It does **not** receive your reasoning, your justifications, or the session history — only the diff, plus instructions to ground itself in the project's own context (its `CLAUDE.md`/`AGENTS.md`, any domain or architecture docs in the touched areas, and the surrounding code one hop out) before judging. Project/domain knowledge: yes. Coding-agent reasoning: never. A reviewer can't rationalize a decision it never saw justified — that isolation is what makes the review trustworthy.

Each reviewer is told to find what's *wrong* in its lens — no praise, no summaries — and returns concise findings (severity, `file:line`, one sentence each).

## Choosing the perspectives

Don't run a fixed panel. Before dispatching, read what actually changed and pick the lenses that will surface the most real problems in *this* code. Reviewers are cheap and run in parallel — spend them where the risk is, not on a checklist.

Read the signal:

- **What changed** — auth, input handling, or anything touching untrusted data pulls in **security**; query-, loop-, or render-heavy code pulls in **performance**; a public API, shared module, or schema change pulls in **interface / compatibility / data integrity**.
- **Language & stack** — match the lens to the idioms in use: memory safety and lifetimes (Rust/C/C++), concurrency and data races (Go, threaded code), type safety and null handling (TypeScript), framework conventions (Eloquent relationships, form requests, policies for Laravel; hooks/composition for React).
- **Task stage** — early scaffolding favors architecture and conventions over polish; a maturing feature favors correctness, edge cases, and error handling; pre-ship favors security, performance, tests, and accessibility.

A palette to draw from (pick what fits, invent others the diff suggests): security, performance, language/framework conventions, correctness & logic, code quality & edge cases, concurrency, API / interface design, data integrity, accessibility, error handling, test coverage.

Pick 2–4 for a typical diff — one when the change is narrow, more when it spans concerns. State each lens's focus precisely so the reviewer hunts the specific failure modes in this diff instead of reciting generic advice.

## Workflow script

Adapt the prompt details; keep the shape.

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

Invoke with `args` carrying the absolute project path and the perspectives you chose for this diff (these are illustrative — swap in whatever the change calls for):

```js
{ cwd: "/path/to/app", perspectives: [
  { key: "security",    name: "security",             focus: "..." },
  { key: "performance", name: "performance",          focus: "..." },
  { key: "conventions", name: "framework conventions", focus: "..." } ] }
```

When the review finishes, report findings grouped by perspective, then present a fresh picker whose first suggestion(s) turn the highest-severity findings into work. After substantial fixes, you may offer the review again.

## Fallback

The `Workflow` tool requires Claude Code v2.1.154+ and a paid plan. If it's unavailable, say so and fall back to parallel reviewer subagents via the `Agent` tool — same panel, same isolation (spawn each reviewer fresh with only the diff and the grounding instructions).

> The dynamic-workflows process asks for approval before its first run. Choose "don't ask again for this workflow in this project" to suppress the prompt on later runs.
