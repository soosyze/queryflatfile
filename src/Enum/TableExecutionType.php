<?php

namespace Soosyze\Queryflatfile\Enum;

enum TableExecutionType
{
    case Create;
    case Drop;
    case Modify;
    case Rename;
}
