# Wordle

A word-guessing game: the player has a limited number of guesses to find a hidden five-letter word.

## Language

**Answer**:
The five-letter word the player is trying to find. There is one answer per day, shared by every player, and a day runs from midnight to midnight Sydney time. An answer is never repeated.
_Avoid_: target, solution, the word

**Guess**:
A five-letter word the player submits as an attempt to find the answer.
_Avoid_: attempt, try

**Dictionary**:
The list of words accepted as guesses. It changes only by deliberate edits, never at play time. Every answer is in the dictionary.
_Avoid_: word list, valid words

**Answer pool**:
The curated common words eligible to become an answer. Every word in the answer pool is in the dictionary, and each day's answer is a word from the pool that has not been an answer before.
_Avoid_: answer list, candidates

**Game**:
One player's play of one day's answer: up to five guesses, ending won or lost. A game belongs to the answer it started on, even past midnight; one not won or lost by the time the player's next game starts stays unfinished. A player with an account has one game per answer.
_Avoid_: round, session

**Guest**:
A player who plays without signing in. A guest's game is tied to the browser they play in and is lost if they clear its cookies. Signing in moves it to their account, unless the account already has a game for that answer.
_Avoid_: anonymous user, visitor

**Account**:
What a player gets by signing in. It keeps their games, on every device they sign in on.
_Avoid_: user, profile, login
