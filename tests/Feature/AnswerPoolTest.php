<?php

/**
 * @return list<string>
 */
function words(string $file): array
{
    return json_decode(file_get_contents(resource_path("words/{$file}.json")), true, flags: JSON_THROW_ON_ERROR);
}

test('every answer pool word is a unique five-letter dictionary word', function () {
    $pool = words('answer-pool');

    expect($pool)->each->toMatch('/^[A-Z]{5}$/')
        ->and($pool)->toBe(array_values(array_unique($pool)))
        ->and(array_diff($pool, words('dictionary')))->toBeEmpty();
});

test('the answer pool holds between 2,000 and 2,500 words', function () {
    expect(count(words('answer-pool')))->toBeBetween(2000, 2500);
});
