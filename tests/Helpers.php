<?php

/**
 * Create a spy that maintains proper type information for Eloquent models
 *
 * @template T of \Illuminate\Database\Eloquent\Model
 *
 * @param  T  $model
 * @return T&\Mockery\MockInterface
 */
function spyModel($model)
{
    return Mockery::spy($model);
}

/**
 * Make pest() backwards-compatible with Pest 2.0.
 */
if (! function_exists('pest')) {
    /**
     * Creates a new Pest configuration instance.
     */
    function pest(): object
    {
        return new class
        {
            /**
             * Set the base test case.
             */
            public function extends($testCase)
            {
                return uses($testCase);
            }

            /**
             * Set the directory for test case.
             */
            public function in(string $path)
            {
                return uses()->in($path);
            }

            /**
             * Use the given trait.
             */
            public function use($trait)
            {
                $args = func_get_args();

                return uses(...$args);
            }

            /**
             * Register a callback to be run before each test in the test file.
             */
            public function beforeEach(callable $callback)
            {
                return uses()->beforeEach($callback);
            }

            /**
             * Register a callback to be run after each test in the test file.
             */
            public function afterEach(callable $callback)
            {
                return uses()->afterEach($callback);
            }
        };
    }
}
