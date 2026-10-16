<?php

use App\Support\Gradebook\Gradebook;

// Root -> Domain A (0.6, rounded to 0.5) -> leaves 3, 4 ; Root -> Domain B (0.4) -> leaf 5 (never graded).
$tree = ['root' => 1, 'nodes' => [
    1 => ['id' => 1, 'name' => 'Root', 'aggregated' => true, 'rounding_step' => 0.1, 'children' => [['id' => 2, 'weight' => 0.6], ['id' => 6, 'weight' => 0.4]]],
    2 => ['id' => 2, 'name' => 'Domain A', 'aggregated' => true, 'rounding_step' => 0.5, 'children' => [['id' => 3, 'weight' => 1.0], ['id' => 4, 'weight' => 1.0]]],
    3 => ['id' => 3, 'name' => 'Leaf 3', 'aggregated' => false, 'rounding_step' => null, 'children' => []],
    4 => ['id' => 4, 'name' => 'Leaf 4', 'aggregated' => false, 'rounding_step' => null, 'children' => []],
    6 => ['id' => 6, 'name' => 'Domain B', 'aggregated' => true, 'rounding_step' => null, 'children' => [['id' => 5, 'weight' => 1.0]]],
    5 => ['id' => 5, 'name' => 'Leaf 5', 'aggregated' => false, 'rounding_step' => null, 'children' => []],
]];

test('averages are weighted, rounded per node, and skip ungraded children', function () use ($tree) {
    $book = Gradebook::summary($tree, [
        ['id' => 10, 'node_id' => 3, 'value' => 5.0, 'semester' => 1],
        ['id' => 11, 'node_id' => 3, 'value' => 4.0, 'semester' => 2],
        ['id' => 12, 'node_id' => 4, 'value' => 4.0, 'semester' => 2],
    ], 'EC');

    // Leaf 3 = 4.5, leaf 4 = 4 -> A = 4.25 -> 4.5 shown; the root uses the unrounded 4.25, B has no grade.
    expect($book['domains'][0])->toMatchArray(['average' => 4.5, 'grade_ids' => [10, 11, 12], 'subs' => []])
        ->and($book['domains'][1]['average'])->toBeNull()
        ->and($book['final'])->toEqualWithDelta(4.3, 1e-9)
        ->and($book['complete'])->toBeFalse()
        ->and($book['current_semester'])->toBe(2)
        ->and($book['semester_count'])->toBe(6)
        ->and($book['semesters'][1])->toMatchArray(['semester' => 2, 'count' => 2, 'average' => 4.0]);
});

test('the root weighs its domains by their weight', function () use ($tree) {
    $book = Gradebook::summary($tree, [
        ['id' => 10, 'node_id' => 3, 'value' => 5.0, 'semester' => 1],
        ['id' => 11, 'node_id' => 4, 'value' => 5.0, 'semester' => 1],
        ['id' => 12, 'node_id' => 5, 'value' => 3.0, 'semester' => 1],
    ], 'EC');

    // A = 5, B = 3 -> 0.6 * 5 + 0.4 * 3 = 4.2 (a plain average would give 4.0).
    expect($book['domains'][0]['average'])->toEqual(5.0)
        ->and($book['domains'][1]['average'])->toEqual(3.0)
        ->and($book['final'])->toEqualWithDelta(4.2, 1e-9)
        ->and($book['complete'])->toBeTrue();
});

test('no tree, no gradebook', function () {
    expect(Gradebook::summary(null, [], 'IT'))->toBeNull();
});
