<?php

namespace App\Enums;

enum StepStatus: string
{
    case Planned = 'planned';
    case Executing = 'executing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
