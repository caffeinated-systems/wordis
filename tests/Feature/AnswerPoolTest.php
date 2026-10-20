<?php

declare(strict_types=1);

test('every answer pool word is a unique five-letter dictionary word', function () {
    $read = fn (string $file): array => json_decode(file_get_contents(resource_path("words/{$file}.json")), true, flags: JSON_THROW_ON_ERROR);
    $dictionary = $read('dictionary');
    $pool = $read('answer-pool');

    expect($dictionary)->each->toMatch('/^[A-Z]{5}$/')
        ->and($pool)->not->toBeEmpty()
        ->and($pool)->each->toMatch('/^[A-Z]{5}$/')
        ->and($pool)->toBe(array_values(array_unique($pool)))
        ->and(array_diff($pool, $dictionary))->toBeEmpty();
});
