export interface User {
    id: number;
    name: string;
    email: string;
}

export interface Objective {
    id: number;
    title: string;
    goal: string;
    constraints: string[] | null;
    allowed_tools: string[] | null;
    status: 'pending' | 'running' | 'paused' | 'completed' | 'failed' | 'cancelled';
    token_budget: number;
    tokens_used: number;
    llm_provider: string | null;
    llm_model: string | null;
    result_summary: string | null;
    started_at: string | null;
    completed_at: string | null;
    created_at: string;
    steps_count?: number;
    steps?: Step[];
}

export interface Step {
    id: number;
    objective_id: number;
    sequence: number;
    status: 'planned' | 'executing' | 'completed' | 'failed' | 'skipped';
    reasoning: string | null;
    tool_name: string | null;
    tool_input: Record<string, unknown> | null;
    tool_output: string | null;
    observation: string | null;
    input_tokens: number;
    output_tokens: number;
    duration_ms: number;
    error: string | null;
    created_at: string;
}

export interface CodingSession {
    id: number;
    title: string;
    project_path: string;
    status: 'active' | 'paused' | 'closed';
    token_budget: number;
    tokens_used: number;
    llm_provider: string | null;
    llm_model: string | null;
    started_at: string | null;
    closed_at: string | null;
    created_at: string;
    messages_count?: number;
}

export interface SessionMessage {
    id: number;
    role: 'user' | 'assistant' | 'tool';
    content: string | null;
    tool_calls: unknown[] | null;
    tool_results: unknown[] | null;
    input_tokens: number;
    output_tokens: number;
    created_at: string;
}

export interface ToolExecution {
    id: number;
    tool_name: string;
    tool_category: 'shell' | 'filesystem' | 'web' | 'api';
    risk_level: 'low' | 'medium' | 'high' | 'critical';
    input: Record<string, unknown>;
    output: string | null;
    was_sandboxed: boolean;
    was_approved: boolean;
    approval_method: string | null;
    guardian_passed: boolean;
    guardian_reason: string | null;
    duration_ms: number;
    exit_code: number | null;
    created_at: string;
}

export interface Channel {
    id: number;
    type: 'telegram' | 'whatsapp' | 'signal' | 'slack' | 'teams';
    is_active: boolean;
    paired_at: string | null;
    created_at: string;
}

export interface Plugin {
    id: number;
    name: string;
    version: string;
    source: 'registry' | 'local';
    description: string | null;
    required_permissions: string[] | null;
    is_active: boolean;
    installed_at: string | null;
}

export interface LlmProvider {
    id: number;
    provider: string;
    base_url: string | null;
    default_model: string | null;
    is_default: boolean;
    is_active: boolean;
    has_api_key: boolean;
    created_at: string;
}

export interface Dashboard {
    objectives: {
        total: number;
        running: number;
        completed: number;
        failed: number;
    };
    sessions: {
        total: number;
        active: number;
    };
    tokens: {
        total_used: number;
    };
    recent_objectives: Objective[];
    active_sessions: CodingSession[];
    recent_executions: ToolExecution[];
}
