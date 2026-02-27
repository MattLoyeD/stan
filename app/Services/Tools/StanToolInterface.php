<?php

namespace App\Services\Tools;

use App\Enums\ToolCategory;
use App\Enums\ToolRiskLevel;
use App\Services\Security\SandboxConfig;
use Laravel\Ai\Contracts\Tool;

interface StanToolInterface extends Tool
{
    public function riskLevel(): ToolRiskLevel;

    public function category(): ToolCategory;

    public function sandboxRequirements(): SandboxConfig;
}
