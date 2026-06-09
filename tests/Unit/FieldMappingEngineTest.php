<?php

use Mostafax\ErpIntegrationHub\FieldMapping\Transformers\DateTransformer;
use Mostafax\ErpIntegrationHub\FieldMapping\Transformers\LookupTransformer;
use Mostafax\ErpIntegrationHub\FieldMapping\Transformers\NumberTransformer;
use Mostafax\ErpIntegrationHub\FieldMapping\Transformers\UppercaseTransformer;

test('uppercase transformer works', function () {
    $t = new UppercaseTransformer();
    expect($t->transform('hello world'))->toBe('HELLO WORLD');
});

test('date transformer formats correctly', function () {
    $t = new DateTransformer();
    expect($t->transform('2024-01-15', ['format' => 'd/m/Y']))->toBe('15/01/2024');
});

test('date transformer handles invalid date gracefully', function () {
    $t = new DateTransformer();
    expect($t->transform('not-a-date'))->toBe('not-a-date');
});

test('number transformer rounds to decimals', function () {
    $t = new NumberTransformer();
    expect($t->transform('1234.567', ['decimals' => 2]))->toBe(1234.57);
});

test('number transformer casts to int', function () {
    $t = new NumberTransformer();
    expect($t->transform('99.9', ['cast' => 'int']))->toBe(99);
});

test('lookup transformer maps value', function () {
    $t = new LookupTransformer();
    expect($t->transform('M', ['map' => ['M' => 'Male', 'F' => 'Female']]))->toBe('Male');
});

test('lookup transformer returns default on miss', function () {
    $t = new LookupTransformer();
    expect($t->transform('X', ['map' => ['M' => 'Male'], 'default' => 'Unknown']))->toBe('Unknown');
});

test('lookup transformer throws when strict and no match', function () {
    $t = new LookupTransformer();
    expect(fn() => $t->transform('Z', ['map' => ['A' => 'B'], 'strict' => true]))
        ->toThrow(\InvalidArgumentException::class);
});
