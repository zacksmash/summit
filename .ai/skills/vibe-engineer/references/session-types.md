# Vibe Engineering — Session Types

Different types of sessions call for different approaches. Adapt your plan, bootstrapping, and suggestion strategy to match what the developer is trying to accomplish.

## New Feature

Plan the full architecture. Start with the foundation (data layer, core structure) and build up. Suggestions should follow dependency order — migrations before models, models before controllers, controllers before routes, routes before views.

## Update / Enhance Feature

Read the existing implementation first. Plan around what's already there — don't propose rebuilding what works. Start with the change that has the most downstream impact. Suggestions should minimize breaking changes and preserve existing behavior.

## Bug Fix

Reproduce and understand the bug before fixing. Start by identifying the root cause, then suggest the fix, then suggest a test that would have caught it. Keep the scope tight — don't refactor while fixing. A bug fix session should be short and focused.

## Refactor

Explain the "why" in the plan — what's wrong with the current approach and what the target state looks like. Start with the riskiest or most impactful change. Suggest adding tests before refactoring if they don't already exist. Each step must leave the code in a working state — never break things mid-refactor.

## Dependency Upgrade

Check changelogs and breaking changes first. Include findings in the plan. Start with the upgrade itself (bump the version), then step through each breaking change one at a time. Suggest running the test suite after each step to catch regressions early.

## Performance Optimization

Profile or identify the bottleneck in the plan. Start with measurement — add benchmarks, logging, or profiling before changing anything. Then suggest the optimization. Then verify the improvement with the same measurement. Don't optimize without measuring first.

## Design / UI

Start with layout and structure before visual polish. Suggestions should flow from skeleton → content → styling → responsive → interaction → animation. Don't suggest color tweaks before the layout is right. Don't suggest animations before the responsive behavior works.

## Security Hardening

Identify the threat surface in the plan. Start with the highest-risk area — authentication, input validation, data exposure. Suggest one fix at a time with clear before/after. Suggest tests that verify the vulnerability is actually closed.

## Documentation

Start with the structure and outline before writing content. Suggest one section at a time. Adapt suggestions to the type — API docs flow differently than user guides or READMEs. Suggest examples and code samples as their own steps, not afterthoughts.
