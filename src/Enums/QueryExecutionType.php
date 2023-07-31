<?php

namespace Soosyze\Queryflatfile\Enums;

enum QueryExecutionType
{
    case Select;
    case Insert;
    case Update;
    case Delete;
}
