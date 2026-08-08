# Vibe Engineering — Bug Diagnosis Loop

A discipline for bug-fix sessions (adapted from Matt Pocock's `diagnosing-bugs` skill). Each phase is an atomic step in the normal vibe loop — report it, then present the picker. Skip a phase only when you say why.

**Redact first.** You'll be showing commands and outputs. Replace every secret with `<REDACTED>`; keep credentials in env vars, not in what you show.

## Phase 1 — Build a feedback loop

**This is the whole skill.** Before reading code to build a theory, produce **one command** — a test, a curl, a CLI invocation, a headless-browser script — that you have already run, that goes **red on this exact bug** and will go green when it's fixed. "Runs without erroring" doesn't count; it must assert the user's actual symptom.

Ways to get one, roughly in order: failing test at whatever seam reaches the bug → HTTP script against the dev server → CLI with a fixture input diffed against known-good → Playwright script → replay a captured payload through the code path → minimal throwaway harness → bisection harness if the bug appeared between two known states.

Then tighten it: faster (seconds, not minutes), sharper (assert the specific symptom), deterministic (pin time, seed RNG). For flaky bugs, the goal is a *high reproduction rate*, not a perfect repro — loop the trigger, add stress, narrow timing until it fails often enough to debug.

If you genuinely can't build one: stop, say so, list what you tried, and ask the developer for a repro environment or a captured artifact. **Do not hypothesize without a loop.**

## Phase 2 — Reproduce + minimise

Run the loop, watch it go red, and confirm it's the *user's* failure — not a nearby one. Then shrink the repro: cut inputs, callers, and config one at a time, re-running after each cut, until every remaining element is load-bearing. The minimal repro shrinks the hypothesis space and becomes the regression test later.

## Phase 3 — Hypothesise

Generate **3–5 ranked, falsifiable hypotheses** before testing any ("if X is the cause, changing Y makes it disappear"). If you can't state the prediction, it's a vibe — discard it. **Present the ranked hypotheses in the picker** — the developer often re-ranks instantly ("we just deployed a change to #3").

## Phase 4 — Instrument

Each probe maps to one prediction; change one variable at a time. Prefer a debugger/REPL over logs; targeted logs over "log everything and grep". Tag every debug log with a unique prefix (e.g. `[DEBUG-a4f2]`) so cleanup is one grep. For performance bugs, measure a baseline first — logs are usually the wrong tool.

## Phase 5 — Fix + regression test

Write the regression test **before the fix**, at a seam that exercises the real bug pattern. If no correct seam exists, that's a finding — note it rather than writing a false-confidence test. Then: watch it fail → fix → watch it pass → re-run the Phase 1 loop against the original scenario.

## Phase 6 — Cleanup

Before suggesting "finish": original repro no longer reproduces, regression test passes (or seam absence documented), all `[DEBUG-...]` logs removed, throwaway harnesses deleted or excluded from the session file set, and the winning hypothesis stated in the commit message.
