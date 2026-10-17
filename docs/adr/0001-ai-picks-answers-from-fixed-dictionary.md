# The AI picks answers from the fixed dictionary; it never decides what counts as a guess

We use AI (via `laravel/ai`) to choose each day's answer so that answers are common, well-known words instead of obscure dictionary entries like AALII. The AI only picks the answer. The dictionary of accepted guesses stays a fixed list, and an answer the AI proposes is accepted only if it is in the dictionary and has never been used before. Letting the AI decide which guesses are valid would make the game's rules shift with model output: invented words accepted, real words rejected, and no way for a player to know which.

## Consequences

- Answers are generated ahead of time by a scheduled command, so a page load never waits on an AI call.
- When the AI's word fails validation after retries, or the AI is unavailable, the day's answer falls back to a random unused dictionary word. The fallback is logged, and a day always has an answer.
