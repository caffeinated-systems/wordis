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
One player's play of one day's answer: up to five guesses, ending won or lost, or unfinished if the day ends before the last guess. A player has one game per answer.
_Avoid_: attempt, round, session

**Guest**:
A player who plays without signing in. A guest's game is kept only in the browser they play in, until they sign in.
_Avoid_: anonymous user, visitor
