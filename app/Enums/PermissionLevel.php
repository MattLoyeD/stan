<?php

namespace App\Enums;

enum PermissionLevel: string
{
    case AutoApprove = 'auto_approve';
    case SessionApprove = 'session_approve';
    case ExplicitApprove = 'explicit_approve';
    case AlwaysAsk = 'always_ask';
}
