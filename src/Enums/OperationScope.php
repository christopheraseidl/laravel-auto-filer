<?php

namespace christopheraseidl\HasUploads\Enums;

enum OperationScope: string
{
    case Batch = 'batch';
    case File = 'file';
    case Directory = 'directory';
}
