<?php

namespace christopheraseidl\ModelFiler\Traits;

use christopheraseidl\ModelFiler\Jobs\Contracts\CircuitBreaker;

trait HasCircuitBreaker
{
    /**
     * Return the circuit breaker instance.
     */
    public function getBreaker(): CircuitBreaker
    {
        return $this->breaker;
    }
}
