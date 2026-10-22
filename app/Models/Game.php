<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A signed-in player's Game. A guest's Game lives only in the session.
 *
 * @property string $date
 * @property list<string> $guesses
 * @property int|null $started_at Unix timestamp of the first guess
 * @property int|null $finished_at Unix timestamp of the winning or last guess
 */
#[Fillable(['date', 'guesses', 'started_at', 'finished_at'])]
class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'guesses' => 'array',
            'started_at' => 'integer',
            'finished_at' => 'integer',
        ];
    }
}
