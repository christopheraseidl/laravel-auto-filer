<?php

namespace christopheraseidl\HasUploads\Enums;

enum OperationType: string
{
    case Clean = 'clean';
    case Delete = 'delete';
    case Move = 'move';
    case Update = 'update';
}
