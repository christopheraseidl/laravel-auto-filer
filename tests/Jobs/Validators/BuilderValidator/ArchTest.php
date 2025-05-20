<?php

namespace christopheraseidl\HasUploads\Tests\Jobs\Validators\BuilderValidator;

/**
 * Tests BuilderValidator structure.
 *
 * @covers \christopheraseidl\HasUploads\Tests\Jobs\Validators\BuilderValidator
 */
it('implements the BuilderValidator interface', function () {
    $reflection = new \ReflectionClass($this->validator);

    expect($reflection->implementsInterface('christopheraseidl\HasUploads\Jobs\Contracts\BuilderValidator'))
        ->toBeTrue();
});
