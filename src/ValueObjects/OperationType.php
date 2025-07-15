<?php

namespace christopheraseidl\AutoFiler\ValueObjects;

enum OperationType: string
{
    case Move = 'move';
    case MoveRichText = 'move_rich_text';
    case Delete = 'delete';
}
