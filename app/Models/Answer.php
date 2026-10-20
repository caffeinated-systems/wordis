<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

#[Fillable(['date', 'word'])]
class Answer extends Model
{
    /** @use HasFactory<AnswerFactory> */
    use HasFactory;

    /**
     * Today's answer in lowercase, picked from the unused answer pool words on the day's first call.
     *
     * The unique `date` column settles a race between two first visitors: the loser reads the winner's row.
     */
    public static function today(): string
    {
        $date = today()->toDateString();

        return Cache::store(config()->string('cache.answer_store'))->remember("answer:{$date}", today()->addDay(), fn (): string => static::firstOrCreate(
            ['date' => $date],
            fn (): array => ['word' => self::randomUnusedWord()],
        )->word);
    }

    /**
     * The answer pool, lowercased to match the game.
     *
     * @return list<string>
     */
    public static function pool(): array
    {
        $words = File::json(resource_path('words/answer-pool.json'), JSON_THROW_ON_ERROR);

        return array_values(array_map(Str::lower(...), array_filter($words, is_string(...))));
    }

    private static function randomUnusedWord(): string
    {
        $unused = array_diff(static::pool(), array_filter(static::pluck('word')->all(), is_string(...)));

        // ponytail: ~2,200 words lasts about six years; grow the pool before it runs out.
        throw_if($unused === [], RuntimeException::class, 'The answer pool has no unused words left.');

        return $unused[array_rand($unused)];
    }
}
