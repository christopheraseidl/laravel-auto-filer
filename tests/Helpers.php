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
