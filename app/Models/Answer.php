<?php

namespace App\Models;

use Database\Factories\AnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
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

        return Cache::store(config('cache.answer_store'))->remember("answer:{$date}", today()->addDay(), fn (): string => static::firstOrCreate(
            ['date' => $date],
            fn (): array => ['word' => static::randomUnusedWord()],
        )->word);
    }

    /**
     * The answer pool, lowercased to match the game.
     *
     * @return list<string>
     */
    public static function pool(): array
    {
        $words = json_decode(file_get_contents(resource_path('words/answer-pool.json')), true, flags: JSON_THROW_ON_ERROR);

        return array_map(Str::lower(...), $words);
    }

    private static function randomUnusedWord(): string
    {
        $unused = array_diff(static::pool(), static::pluck('word')->all());

        // ponytail: ~2,200 words lasts about six years; grow the pool before it runs out.
        throw_if($unused === [], RuntimeException::class, 'The answer pool has no unused words left.');

        return Arr::random($unused);
    }
}
