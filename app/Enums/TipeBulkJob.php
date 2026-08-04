<?php

namespace App\Enums;

enum TipeBulkJob: string
{
    case Import = 'import';
    case Export = 'export';
}
