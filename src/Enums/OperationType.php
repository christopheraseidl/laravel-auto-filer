<?php

namespace christopheraseidl\ModelFiler\Enums;

enum OperationType: string
{
    case Clean = 'clean';
    case Delete = 'delete';
    case Move = 'move';
    case Update = 'update';
}
