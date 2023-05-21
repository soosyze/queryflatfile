<?php

namespace Soosyze\Queryflatfile\Enum;

enum QueryExecutionType
{
    case Select;
    case Insert;
    case Update;
    case Delete;
}
