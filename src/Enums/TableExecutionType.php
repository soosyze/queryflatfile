<?php

namespace Soosyze\Queryflatfile\Enums;

enum TableExecutionType
{
    case Create;
    case Drop;
    case Modify;
    case Rename;
}
