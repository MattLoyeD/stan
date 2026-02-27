<?php

namespace App\Enums;

enum ToolCategory: string
{
    case Shell = 'shell';
    case Filesystem = 'filesystem';
    case Web = 'web';
    case Api = 'api';
}
