<p align="center">
  <span style="font-size: 72px">🎩</span>
</p>

<h1 align="center">Stan</h1>

<p align="center">
  <strong>A local, security-first AI agent that gets things done.</strong><br>
  Autonomous objectives. Interactive coding sessions. Multi-channel messaging. Agent swarms.<br>
  All sandboxed, auditable, and running on your machine.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2+-8892BF?logo=php" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel" alt="Laravel 12">
  <img src="https://img.shields.io/badge/Vue-3-4FC08D?logo=vuedotjs" alt="Vue 3">
  <img src="https://img.shields.io/badge/License-MIT-blue" alt="MIT License">
</p>

---

## What is Stan?

Stan is a self-hosted AI agent that runs entirely on your machine. Give it an objective, and it plans, executes, and reports back — using sandboxed tools, respecting token budgets, and logging every action for your review.

Think of it as a capable, discreet assistant with a dry sense of humor. The Alfred to your Batman.

**Three ways to interact:**

- **Objectives** — Background autonomous tasks. Set a goal, walk away, check results later.
- **Coding Sessions** — Interactive, Claude Code-like experience in the browser. Real-time conversational coding scoped to a project directory.
- **Messaging Channels** — Talk to Stan via Telegram, Slack, WhatsApp, Signal, or Teams. Send objectives, check status, approve tool calls — from anywhere.

**Plus:**

- **MCP Support** — Connect external tool servers (GitHub, filesystem, databases) via the Model Context Protocol standard.
- **Agent Swarms** — Decompose complex objectives into parallel sub-tasks, each handled by a specialist worker agent.

---

## Features

### Security-First Architecture

Every tool call passes through **Guardian**, a single auditable security chokepoint:

```
LLM Response → Guardian::evaluate() → PermissionGate → SandboxManager → Tool::execute()
```

- **4-tier permissions**: Auto-approve, session-approve, explicit-approve, always-ask
- **Sandboxed execution**: bubblewrap (bwrap) or restricted `proc_open`
- **Prompt injection detection**: 13 regex patterns + Unicode normalization
- **Network policy**: Blocks cloud metadata, internal networks, SSRF vectors
- **Filesystem policy**: Workspace containment, no symlinks, no sensitive paths
- **Process policy**: Blocks destructive shell commands
- **External tool policy**: MCP tools verified per-user, default to always-ask

### Autonomous Objectives

Set a goal with constraints and a token budget. Stan plans the approach, executes step by step, and reports a summary when done.

- Automatic planning via a dedicated PlannerAgent
- Step-by-step execution with tool calling
- Token budget enforcement with hard stops
- Pause, resume, or cancel at any time
- Full audit trail of every tool invocation

### Interactive Coding Sessions

A browser-based coding assistant scoped to a project directory:

- Real-time conversational coding with streaming
- File read/write, shell commands, web search — all sandboxed
- AI can only access the selected project directory
- Token budget per session

### MCP (Model Context Protocol)

Connect external tool servers that speak the MCP standard:

- **Stdio transport** — Spawn local processes (e.g., `npx @modelcontextprotocol/server-filesystem`)
- **SSE transport** — Connect to remote HTTP endpoints
- Each MCP tool is wrapped as a native Stan tool — same Guardian checks, same audit log
- Default risk level: `high`. Overridable per-tool.
- Add, test, discover, and toggle servers from the UI

### Agent Swarms

For complex objectives, enable swarm mode to decompose work across multiple specialist agents:

- A **coordinator agent** decomposes the objective into sub-tasks
- Each sub-task is assigned a **role** (researcher, coder, reviewer, analyst...) and scoped tools
- Tasks execute in **parallel** via Laravel queues, respecting dependency ordering
- Configurable failure strategies: `continue`, `stop_all`, `retry`
- Token budgets allocated per task
- Coordinator synthesizes all results into a final summary

### Multi-Channel Messaging

Talk to Stan from anywhere:

| Channel | Status |
|---------|--------|
| Telegram | Supported |
| Slack | Supported |
| WhatsApp | Supported (via Twilio) |
| Signal | Supported (via signal-cli) |
| Teams | Supported (via Bot Framework) |

Each channel requires explicit pairing. High-risk tool calls always require approval, even via messaging.

### Plugin System

Extend Stan with custom tools:

```
my-plugin/
├── plugin.json        # Manifest (name, version, permissions)
├── src/MyTool.php     # Implements StanToolInterface
└── tests/
```

Plugins run in the same sandbox as built-in tools. Registry plugins require GPG signatures.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12, PHP 8.3 |
| AI SDK | laravel/ai (multi-provider, tool calling, streaming) |
| App Server | FrankenPHP (Octane) — standalone binary |
| Frontend | Vue 3, PrimeVue 4, TypeScript |
| Realtime | Mercure (SSE) via FrankenPHP |
| Database | SQLite (zero config, single file) |
| Queue | Laravel Queue (database driver) |
| Sandbox | bubblewrap (bwrap) / restricted proc_open |
| Messaging | BotMan (Telegram, Slack, WhatsApp, Signal, Teams) |
| Build | Vite 6 |

**Supported LLM providers:** Anthropic, OpenAI, Ollama, Gemini, Mistral, Groq, DeepSeek.

---

## Quick Start

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- SQLite

### Install

```bash
git clone https://github.com/your-org/stan.git
cd stan
composer setup
```

This runs `composer install`, generates the app key, runs migrations, installs npm packages, and builds the frontend.

### Run

```bash
composer dev
```

This starts four processes concurrently:
- Laravel dev server
- Queue worker
- Log tail (Pail)
- Vite dev server

Open the displayed URL, enter the auto-generated auth token, configure an LLM provider, and you're ready.

### First Steps

1. **Configure a provider** — Add your Anthropic or OpenAI API key in Settings
2. **Create an objective** — "Research the top 5 PHP testing frameworks and compare them"
3. **Watch it work** — Steps appear in real-time, tools execute through Guardian
4. **Check the audit log** — Every tool call is logged with category, risk level, and approval method

---

## Architecture

### Project Structure

```
stan/
├── app/
│   ├── Agents/                    # Agent classes (Objective, Session, Swarm, Planner)
│   ├── Enums/                     # Status, permission, risk, category enums
│   ├── Http/
│   │   ├── Controllers/           # REST API controllers
│   │   └── Resources/             # JSON API resources
│   ├── Jobs/                      # Background jobs (objectives, swarm tasks)
│   ├── Models/                    # Eloquent models
│   ├── Providers/                 # Service providers
│   └── Services/
│       ├── Agent/                 # AgentLoop, Planner, StepExecutor, TokenBudget,
│       │                          # SwarmOrchestrator, SwarmTaskRunner
│       ├── Mcp/                   # McpTransport, StdioTransport, SseTransport,
│       │                          # McpClient, McpProxyTool, McpManager
│       ├── Security/              # Guardian, PermissionGate, SandboxManager,
│       │                          # PromptSanitizer, Policies/*
│       └── Tools/                 # StanToolInterface, ToolRegistry, Implementations/*
├── config/
│   └── stan.php                   # All Stan configuration
├── database/migrations/           # SQLite schema
├── resources/js/                  # Vue 3 SPA
│   ├── composables/               # useObjective, useSession, useMcpServers, useSwarm...
│   ├── layouts/AppLayout.vue
│   ├── pages/                     # Dashboard, Objectives, Sessions, MCP, Swarm...
│   └── types/index.ts             # TypeScript interfaces
├── routes/api.php                 # REST API routes
├── SOUL.md                        # Stan's personality and behavioral rules
└── plugins/                       # Local user-developed plugins
```

### Security Model

```
┌─────────────────────────────────────────┐
│              Guardian                    │
│  ┌───────────┐  ┌──────────────────┐    │
│  │ Prompt     │  │ Policies         │    │
│  │ Sanitizer  │  │  Filesystem      │    │
│  │            │  │  Network         │    │
│  │            │  │  Process         │    │
│  │            │  │  External (MCP)  │    │
│  └───────────┘  └──────────────────┘    │
│  ┌───────────┐  ┌──────────────────┐    │
│  │ Permission │  │ Sandbox          │    │
│  │ Gate       │  │ Manager (bwrap)  │    │
│  └───────────┘  └──────────────────┘    │
└─────────────────────────────────────────┘
         │                    │
    ToolExecution        Tool::execute()
    (audit log)
```

### Execution Flow

**Objective:**
```
User creates objective → RunObjective job dispatched
  → Planner creates steps → AgentLoop iterates:
    → Check budget → Find next step → StepExecutor:
      → Build prompt → LLM call → Tool calls via Guardian
      → Record tokens → Update step status
    → CompletionChecker evaluates → Finish
```

**Swarm:**
```
User creates swarm objective → RunSwarmObjective job dispatched
  → SwarmOrchestrator::decompose() via CoordinatorAgent
    → Creates SwarmTask records with dependencies
  → Dispatch ready tasks (max_parallel) → RunSwarmTask jobs
    → SwarmWorkerAgent executes each task
    → On completion: dispatch newly unblocked tasks
    → When all done: CoordinatorAgent synthesizes results
```

---

## Configuration

All configuration lives in `config/stan.php`:

```php
'security' => [
    'max_iterations_per_objective' => 50,
    'default_token_budget' => 100000,
],

'agent' => [
    'default_provider' => 'anthropic',
    'default_model' => 'claude-sonnet-4-20250514',
],

'tool_permissions' => [
    'file_read' => 'auto_approve',
    'file_write' => 'session_approve',
    'shell' => 'explicit_approve',
    'api_call' => 'always_ask',
],

'mcp' => [
    'default_risk_level' => 'high',
    'connection_timeout_ms' => 10000,
    'request_timeout_ms' => 30000,
],

'swarm' => [
    'max_parallel_tasks' => 3,
    'default_failure_strategy' => 'continue',  // continue | stop_all | retry
    'coordinator_budget_pct' => 20,
    'max_tasks_per_swarm' => 10,
],
```

---

## API

All routes require bearer token authentication and localhost access.

### Objectives

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/objectives` | List objectives |
| POST | `/api/objectives` | Create objective (dispatches background job) |
| GET | `/api/objectives/:id` | Get objective with steps |
| POST | `/api/objectives/:id/pause` | Pause running objective |
| POST | `/api/objectives/:id/resume` | Resume paused objective |
| POST | `/api/objectives/:id/cancel` | Cancel objective |
| GET | `/api/objectives/:id/steps` | List steps |
| GET | `/api/objectives/:id/swarm-tasks` | List swarm tasks |

### MCP Servers

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/mcp-servers` | List configured servers |
| POST | `/api/mcp-servers` | Add server (stdio or sse) |
| DELETE | `/api/mcp-servers/:id` | Remove server |
| POST | `/api/mcp-servers/:id/test` | Test connection |
| POST | `/api/mcp-servers/:id/discover` | Discover available tools |
| POST | `/api/mcp-servers/:id/toggle` | Enable/disable server |

### Sessions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/sessions` | List sessions |
| POST | `/api/sessions` | Create session (requires project path) |
| POST | `/api/sessions/:id/messages` | Send message |
| POST | `/api/sessions/:id/close` | Close session |

### Other

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/tool-executions` | Audit log |
| GET/POST | `/api/tool-permissions` | Permission overrides |
| GET/POST | `/api/providers` | LLM provider config |
| POST | `/api/providers/:id/test` | Test provider connection |
| GET/POST | `/api/settings` | App settings |
| GET | `/api/swarm-tasks/:id` | Get swarm task detail |

---

## Built-in Tools

| Tool | Category | Risk | Default Permission |
|------|----------|------|--------------------|
| `file_read` | Filesystem | Low | Auto-approve |
| `file_search` | Filesystem | Low | Auto-approve |
| `web_search` | Web | Low | Auto-approve |
| `file_write` | Filesystem | Medium | Session-approve |
| `web_fetch` | Web | Medium | Session-approve |
| `shell` | Shell | High | Explicit-approve |
| `api_call` | API | High | Always-ask |
| `mcp_*` | External | High* | Always-ask |

\* MCP tool risk level is configurable per-server and per-tool.

---

## Tauri Desktop App

Stan includes a Tauri v2 shell for running as a native desktop application. The desktop app bundles the Laravel backend with a native window, auto-starts the server, and handles authentication transparently.

```bash
cd desktop
npm install
npm run tauri dev
```

---

## Development

```bash
# Run all services (server + queue + logs + vite)
composer dev

# Run tests
composer test

# Lint PHP
./vendor/bin/pint

# Type-check TypeScript
npx vue-tsc --noEmit
```

---

## Design Principles

1. **Security is not optional.** Every tool call goes through Guardian. No exceptions, no shortcuts.
2. **Local-first.** Your data stays on your machine. SQLite database, localhost binding, auto-generated auth token.
3. **Auditable.** Every action is logged. Every tool call has a category, risk level, approval method, and duration.
4. **Token-conscious.** Hard budget limits per objective and session. No surprise $2,000 bills.
5. **Lean and readable.** The entire core is auditable PHP, not 430K lines of generated code.
6. **Provider-agnostic.** Anthropic, OpenAI, Ollama, Gemini, Mistral, Groq, DeepSeek — swap freely.

---

## License

MIT
