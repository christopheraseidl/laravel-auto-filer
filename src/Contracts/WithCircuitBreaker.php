<?php

namespace christopheraseidl\ModelFiler\Contracts;

use christopheraseidl\ModelFiler\Jobs\Contracts\CircuitBreaker;

interface WithCircuitBreaker
{
    /**
     * Return the circuit breaker instance.
     */
    public function getBreaker(): CircuitBreaker;
}
