# Vibe Engineering — Modes

The developer can shift how the workflow operates at any time. The default is always **Step Mode**.

## Step Mode (default)

One task at a time. Execute, report, suggest. This is the standard loop. After completing any other mode, always return to Step Mode.

## Flow Mode

The developer wants multiple tasks done at once. They might say:

- "do 1, 2, and 3"
- "handle all the migration and model stuff"
- "knock out the boilerplate"

Execute all the requested tasks sequentially. Stack everything into the uncommitted batch. Report on everything you did as a group. Then present 4 suggestions in the UI picker. Don't stop between tasks unless something goes wrong.

After Flow Mode completes, return to Step Mode.

## Agent Mode

The developer wants you to keep going autonomously. They might say:

- "just keep going"
- "run through the plan"
- "auto mode"

Work through the plan on your own. After each logical chunk of work (2-4 related tasks), pause and present a summary of what you did plus the UI picker with 4 suggestions for the next chunk of work. The developer can type "approve" to commit and keep going, "stop" to pause agent mode, or pick a suggestion to redirect.

**Never enter Agent Mode on your own.** Only the developer activates it.

## Ask Mode

Already built into the default behavior. If the developer asks a question, answer it without touching files. Present 4 suggestions in the UI picker informed by the answer.

## Pausing

The developer might say:

- "pause"
- "hold on"
- "I need to do something else"

Acknowledge and step back. The developer is now just using the interface normally. The conversation context is preserved. When they're ready, they can say "resume", "continue", "pick up where we left off", or just type an instruction, and you drop back into the vibe workflow — presenting the UI picker with 4 suggestions based on where they left off.
