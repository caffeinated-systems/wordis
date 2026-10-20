---
status: accepted
supersedes: 0001
---

# Answers come from a curated answer pool, not a daily AI pick

Each day's answer is a random word from the answer pool that has never been an answer before. The pool is a committed list of common words, curated once with Claude and reviewed in a pull request. We dropped the plan to have AI pick the answer every day (ADR 0001): a reviewed list gives the same quality of answers without a runtime dependency, an API key, retries or a fallback path, and every possible answer is visible in a diff before it can be played.

## Consequences

- Growing the pool is an ordinary commit. Removing a word from it never changes past answers, which are recorded in the database.
- The day's answer is picked by its first visitor, not by a scheduler, so a day can't go without an answer because a scheduled run was missed.
- Nothing at play time decides what counts as a guess: the dictionary changes only by deliberate edits, as ADR 0001 required.
