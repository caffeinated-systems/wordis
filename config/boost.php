<?php

/*
|--------------------------------------------------------------------------
| Boost Overrides
|--------------------------------------------------------------------------
|
| Only overrides belong here: Boost merges its own config's top-level keys
| under this file, so copying them in would hide future Boost defaults.
|
| Claude Code's guidelines are written to AGENTS.md, the single source of
| truth for every agent. CLAUDE.md only imports it with "@AGENTS.md".
|
*/

return [

    'agents' => [
        'claude_code' => [
            'guidelines_path' => 'AGENTS.md',
        ],
    ],

];
