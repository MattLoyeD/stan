# Stan — Architecture & Implementation Plan

## Context

OpenClaw (Peter Steinberger) proved that a local, persistent, autonomous AI agent is a breakthrough concept — but its execution is a security disaster (default `0.0.0.0` binding, plaintext credentials, 1-click RCE, 430K unauditable lines, $2000 in tokens in 48h). Stan takes the concept and rebuilds it with a security-first approach, a lean auditable codebase, an objective-driven execution model, interactive coding sessions, a curated plugin system, multi-channel messaging, and persistent agent memory — all without OpenClaw's architectural sins.

**Founding principle**: An AI agent that cannot be trusted is worse than no agent at all.

---

## Tech Stack

| Layer | Technology | Rationale |
|-------|-----------|-----------|
| Backend | Laravel 12 + PHP 8.3 | Mature ecosystem, queues, native encryption |
| AI SDK | `laravel/ai` (v0.2+) | Official Laravel agent framework with multi-provider, tool calling, streaming, failover |
| App Server | FrankenPHP (Octane) | Standalone binary, built-in Mercure hub, Caddy TLS |
| Frontend | Vue 3 + PrimeVue 4 + TypeScript | Rich components, SSE-ready |
| Realtime | Mercure (SSE) via FrankenPHP | Zero extra infra, unidirectional = reduced attack surface |
| Database | SQLite | Zero config, embedded, backup = 1 file |
| Queue | Laravel Queue (database driver on SQLite) | Background execution without Redis |
| Cache | Laravel Cache (file driver) | DB-backed configs cached for performance |
| Sandbox | bubblewrap (bwrap) / fallback restricted `proc_open` | Unprivileged sandboxing without Docker |
| Messaging | BotMan (Laravel) | WhatsApp, Telegram, Slack, Teams, Signal drivers |
| Build | Vite 6 | Standard Laravel + Vue toolchain |

---

## Key Architecture

### Execution Model: Objectives + Coding Sessions + Channels

Three ways to interact with Stan:

1. **Objectives** (background) — Structured goals with constraints, token budget, tool permissions. The AI works autonomously in the background. User checks progress when they want.
2. **Coding Sessions** (interactive) — Claude Code/Codex-like experience in the browser. The AI has sandboxed access to a specific project directory. Real-time conversational coding.
3. **Messaging Channels** (conversational) — Interact with Stan via WhatsApp, Telegram, Signal, Slack, Teams. Send objectives, check status, get results — from anywhere.

### Agent Core: `laravel/ai` + DB-stored Soul & Directives

Instead of a custom LLM abstraction, Stan uses the **official `laravel/ai` SDK** which provides:
- `Agent` classes with instructions, tools, memory, and structured output
- Multi-provider support (Anthropic, OpenAI, Ollama + failover)
- Built-in tool calling with JSON Schema validation
- Streaming with SSE support
- Conversation persistence to database

### Agent Soul & Directives (all DB-stored, admin-editable)

Everything is stored in database and cached — **no configuration .md files on disk**. All editable from the admin panel.

**`agent_directives` table** — Replaces SOUL.md and all OpenClaw-style .md files:

| Directive Type | Purpose | Example |
|---|---|---|
| `soul` | Agent identity, personality, core values | "You are Stan, methodical and security-conscious..." |
| `coding_style` | Code conventions and preferences | "Use strict TypeScript, prefer composition over inheritance..." |
| `communication` | Response style and formatting rules | "Be concise, use structured output, warn before destructive ops..." |
| `project_context` | Per-project context that persists | "Project X uses Laravel 12, deploys via GitHub Actions..." |
| `behavioral_rules` | Hard rules the agent must follow | "Never access files outside workspace, never store credentials..." |
| `custom` | User-defined directives | Any additional instructions |

**Admin panel features:**
- Rich Markdown editor (PrimeVue Editor) for each directive
- Directive templates (pre-filled starting points)
- Enable/disable individual directives without deleting
- Priority ordering (directives compose into final system prompt in order)
- Preview: see the full composed system prompt before saving
- Version history (track changes over time)

**Caching strategy:**
- Directives cached via Laravel Cache (file driver) with `agent_directives:{user_id}` key
- Cache invalidated on any directive edit
- Composed system prompt cached separately as `agent_prompt:{user_id}`
- Warm cache on boot, lazy rebuild on cache miss

### Persistent Agent Memory

Stan learns from interactions and remembers useful patterns over days, weeks, and months. The memory system is structured, searchable, and admin-manageable.

**`agent_memories` table:**

```
id, user_id, type, content, source_type, source_id, relevance_score,
access_count, last_accessed_at, expires_at, is_pinned, embedding (blob),
created_at, updated_at
```

**Memory types:**

| Type | What it stores | How it's created | Example |
|---|---|---|---|
| `knowledge` | Facts learned from objectives/sessions | Auto-extracted by MemoryExtractor after objective completion | "User's main project uses Vue 3 + PrimeVue" |
| `pattern` | Recurring behaviors detected over time | Auto-detected by PatternDetector analyzing memory clusters | "User always requests tests after writing code" |
| `preference` | User preferences inferred from interactions | Auto-inferred from conversation patterns | "Prefers concise responses over verbose explanations" |
| `context` | Project-specific persistent context | Auto or manual, tied to project_path | "Project X architecture: monorepo, 3 services, PostgreSQL" |
| `skill` | Techniques/solutions that worked well | Auto-flagged from successful objective completions | "For Laravel API testing, use Pest with dataset providers" |
| `correction` | Things the agent got wrong and corrections | User explicitly corrects agent, stored for future avoidance | "Don't use dd() in production code — use Log::debug()" |

**Memory lifecycle:**

```
1. Objective/Session completes
2. MemoryExtractor (background job) analyzes the interaction:
   - Extracts facts, preferences, successful techniques
   - Generates embeddings via laravel/ai Embeddings
   - Stores in agent_memories with relevance_score
3. PatternDetector (periodic job) analyzes memory clusters:
   - Detects recurring patterns across multiple memories
   - Promotes frequently-accessed memories (higher relevance_score)
   - Expires stale memories that haven't been accessed (configurable TTL)
4. At objective/session start, MemoryRetriever:
   - Semantic search via embeddings for relevant memories
   - Inject top-N memories into agent context (configurable, default 10)
   - Increment access_count for retrieved memories
```

**Admin panel for memories:**
- Searchable list of all memories (filter by type, source, date)
- Manual add/edit/delete memories
- Pin important memories (always included in context, never expire)
- Bulk operations (delete old, export, import)
- Memory analytics: most accessed, patterns detected, memory growth over time
- Preview: see which memories would be injected for a given objective/query

**Embedding strategy:**
- Use `laravel/ai` Embeddings with configured provider (OpenAI, Ollama, etc.)
- Store embeddings as BLOBs in SQLite (sufficient for single-user scale)
- Cosine similarity search for memory retrieval
- Fallback to keyword search if no embedding provider configured

### Security: Guardian Pattern (single chokepoint)

**Every** tool call passes through `Guardian::evaluate()` — a single auditable security checkpoint that cannot be bypassed.

```
LLM Response → OutputValidator → Guardian::evaluate() → PermissionGate → SandboxManager → Tool::execute()
```

4 permission levels:
- **AutoApprove**: read files in workspace, web search
- **SessionApprove**: write files, web fetch (approved once per objective/session)
- **ExplicitApprove**: shell commands (each invocation validated)
- **AlwaysAsk**: external API calls (validate + confirm parameters every time)

### Agent Loop (per tick)

```
1. Check token budget → stop if exhausted
2. Retrieve relevant memories via MemoryRetriever (semantic search)
3. Build context (composed directives from DB + retrieved memories + objective/session history)
4. Send to LLM via laravel/ai agent → receive response
5. OutputValidator checks response (injection, privilege escalation)
6. If tool call → Guardian::evaluate() → sandbox execute → observe result
7. If no tool call → CompletionChecker evaluates if objective is met
8. Record step, publish via Mercure
9. Loop or terminate
10. On completion → MemoryExtractor extracts learnings (background job)
```

Max 50 iterations per objective. Token budget with hard stop.

### Messaging Channels

Stan integrates with messaging platforms so users can interact from anywhere:

**Supported channels** (via BotMan + custom drivers):
- **Telegram** — Primary channel, rich formatting, inline keyboards for approvals
- **WhatsApp** — Via Twilio/Baileys
- **Signal** — Via signal-cli bridge
- **Slack** — Workspace integration, threads for objectives
- **Microsoft Teams** — Via Bot Framework

**Channel architecture:**

```
User Message (Telegram/WhatsApp/...) → BotMan Router → ChannelHandler
  → Parse intent (create objective, check status, send to session, approve tool)
  → Dispatch to appropriate service (ObjectiveRunner, SessionRunner, PermissionGate)
  → Result sent back via same channel
  → SSE also published to Mercure (web UI stays in sync)
```

**Channel security:**
- Each channel requires explicit pairing (one-time token verification)
- Rate limiting per channel
- High-risk tool calls always require explicit approval (no auto-approve via messaging)
- Channel messages go through `PromptSanitizer` before reaching the agent
- No credential transmission over messaging channels

### Plugin System (curated registry)

Unlike OpenClaw's ClawHub (20% malicious skills), Stan uses a **curated and signed** plugin model:

**Plugin structure:**
```
my-plugin/
├── plugin.json          # Manifest (name, version, permissions, signature)
├── src/
│   └── MyTool.php       # Implements laravel/ai Tool + StanToolInterface
├── tests/
│   └── MyToolTest.php   # Required tests
└── README.md
```

**Two plugin sources:**

1. **Official registry** (remote, manually curated) — Git-hosted, GPG-signed, reviewed before publication, integrity checks on every load
2. **Local plugins** (user-developed) — `plugins/` directory, scaffold via `stan:plugin create`, auto-loaded, same sandbox as registry plugins

**Plugin security:**
- Required permissions declared in manifest, approved at installation
- Same sandbox as built-in tools (bwrap)
- Zero access to Stan's own code — only the Tool interface
- GPG signature validation for registry plugins
- Basic static analysis before loading

### Coding Sessions (interactive Claude Code/Codex mode)

**Concept:**
- A **Session** is tied to a **project directory** on the host
- AI has read/write access **only** within that directory (sandboxed via bwrap bind mount)
- Real-time conversational interaction (chat + streaming)
- File reading/writing, shell commands, code search, web browsing — all within sandbox
- Everything goes through Guardian (same protections as objectives)

**Session security:**
- Project directory as isolated bind mount — AI only sees that folder
- Shell commands restricted to that directory
- No access to `~/.ssh`, `~/.gnupg`, `~/.config`, or Stan's own files
- Token budget per session with hard stop
- User explicitly selects directory at session start

---

## Project Structure

```
stan/
├── app/
│   ├── Agents/
│   │   ├── StanAgent.php                 # Base agent (loads directives from DB + memories)
│   │   ├── ObjectiveAgent.php            # Specialized for background objectives
│   │   ├── SessionAgent.php              # Specialized for coding sessions
│   │   ├── ChannelAgent.php              # For messaging channel interactions
│   │   └── PlannerAgent.php              # Specialized for planning steps
│   ├── Console/Commands/
│   │   ├── StartCommand.php              # `stan:start` — entry point
│   │   ├── SetupCommand.php              # `stan:setup` — first-run wizard
│   │   ├── PluginInstallCommand.php      # `stan:plugin install <name>`
│   │   ├── PluginCreateCommand.php       # `stan:plugin create <name>`
│   │   ├── PluginListCommand.php         # `stan:plugin list`
│   │   └── PluginRemoveCommand.php       # `stan:plugin remove <name>`
│   ├── Enums/
│   │   ├── ObjectiveStatus.php           # Pending, Running, Paused, Completed, Failed, Cancelled
│   │   ├── PermissionLevel.php           # AutoApprove, SessionApprove, ExplicitApprove, AlwaysAsk
│   │   ├── ToolRiskLevel.php             # Low, Medium, High, Critical
│   │   ├── ToolCategory.php              # Shell, Filesystem, Web, Api
│   │   ├── StepStatus.php               # Planned, Executing, Completed, Failed, Skipped
│   │   ├── ChannelType.php              # Telegram, WhatsApp, Signal, Slack, Teams
│   │   ├── DirectiveType.php            # Soul, CodingStyle, Communication, ProjectContext, etc.
│   │   └── MemoryType.php              # Knowledge, Pattern, Preference, Context, Skill, Correction
│   ├── Events/                           # ObjectiveStarted, StepExecuted, PermissionRequested, etc.
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── ObjectivesController.php  # CRUD + resume/cancel/approve/deny
│   │   │   ├── SessionsController.php    # Coding sessions CRUD + messages
│   │   │   ├── StepsController.php       # Read-only
│   │   │   ├── ToolExecutionsController.php  # Audit log
│   │   │   ├── ToolPermissionsController.php
│   │   │   ├── PluginsController.php     # Install/remove/list/registry browse
│   │   │   ├── ChannelsController.php    # Channel pairing, config, test
│   │   │   ├── DirectivesController.php  # CRUD for agent directives (soul, rules, etc.)
│   │   │   ├── MemoriesController.php    # CRUD + search + analytics for agent memories
│   │   │   ├── ProvidersController.php   # LLM provider config + test + models
│   │   │   ├── SettingsController.php
│   │   │   ├── DashboardController.php
│   │   │   └── AuthController.php
│   │   ├── Middleware/
│   │   │   ├── ValidateAuthToken.php
│   │   │   └── LocalhostOnly.php
│   │   └── Resources/                    # API Resources
│   ├── Jobs/
│   │   ├── RunObjective.php              # Queue job: runs ObjectiveRunner
│   │   ├── ProcessSessionMessage.php     # Queue job: processes coding session message
│   │   ├── ExtractMemories.php           # Queue job: MemoryExtractor after completion
│   │   └── DetectPatterns.php            # Periodic job: PatternDetector across memories
│   ├── Listeners/                        # Broadcast* → Mercure, EnforceTokenBudget
│   ├── Models/
│   │   ├── User.php
│   │   ├── Objective.php
│   │   ├── ObjectiveStep.php
│   │   ├── ToolExecution.php
│   │   ├── ToolPermission.php
│   │   ├── LlmProviderConfig.php
│   │   ├── Setting.php
│   │   ├── Plugin.php                    # Installed plugin
│   │   ├── CodingSession.php             # Interactive session
│   │   ├── SessionMessage.php            # Message in a session
│   │   ├── Channel.php                   # Paired messaging channel
│   │   ├── AgentDirective.php            # Soul, rules, coding style, etc. (DB-stored)
│   │   └── AgentMemory.php              # Persistent memories with embeddings
│   └── Services/
│       ├── Agent/
│       │   ├── AgentLoop.php             # Core reasoning: plan → act → observe → decide
│       │   ├── ObjectiveRunner.php       # Objective lifecycle
│       │   ├── SessionRunner.php         # Interactive coding session loop
│       │   ├── Planner.php               # Initial plan from objective goal
│       │   ├── StepExecutor.php          # Single step execution
│       │   ├── CompletionChecker.php     # Is the objective achieved?
│       │   ├── TokenBudget.php           # Tracking + enforcement + cost estimation
│       │   ├── DirectiveComposer.php     # Loads directives from DB, composes system prompt, caches
│       │   └── ContextBuilder.php        # Combines directives + memories + objective/session context
│       ├── Memory/
│       │   ├── MemoryRetriever.php       # Semantic search via embeddings, retrieves relevant memories
│       │   ├── MemoryExtractor.php       # Extracts facts/preferences/skills from completed interactions
│       │   ├── PatternDetector.php       # Analyzes memory clusters, detects recurring patterns
│       │   ├── MemoryManager.php         # CRUD, pinning, expiry, analytics
│       │   └── EmbeddingService.php      # Generates/stores embeddings via laravel/ai Embeddings
│       ├── Channels/
│       │   ├── ChannelRouter.php         # Routes incoming messages to handler
│       │   ├── ChannelHandler.php        # Parses intent, dispatches to services
│       │   ├── Drivers/
│       │   │   ├── TelegramDriver.php
│       │   │   ├── WhatsAppDriver.php
│       │   │   ├── SignalDriver.php
│       │   │   ├── SlackDriver.php
│       │   │   └── TeamsDriver.php
│       │   └── ChannelPairing.php        # One-time token verification
│       ├── Security/
│       │   ├── Guardian.php              # THE central security checkpoint
│       │   ├── PromptSanitizer.php       # Injection detection, strip hidden content
│       │   ├── OutputValidator.php       # Validates LLM responses
│       │   ├── SandboxManager.php        # bwrap / restricted proc_open
│       │   ├── SandboxConfig.php         # Value object
│       │   ├── PermissionGate.php        # 4-tier authorization
│       │   ├── CredentialVault.php       # Laravel encrypt()/decrypt()
│       │   ├── AuthTokenManager.php      # Auto-generated bearer token
│       │   └── Policies/
│       │       ├── NetworkPolicy.php
│       │       ├── FilesystemPolicy.php
│       │       └── ProcessPolicy.php
│       ├── Tools/
│       │   ├── StanToolInterface.php     # Extends laravel/ai Tool with risk level + category
│       │   ├── ToolRegistry.php
│       │   ├── Implementations/
│       │   │   ├── ShellTool.php
│       │   │   ├── FileReadTool.php
│       │   │   ├── FileWriteTool.php
│       │   │   ├── FileSearchTool.php
│       │   │   ├── WebFetchTool.php
│       │   │   ├── WebSearchTool.php
│       │   │   └── ApiCallTool.php
│       │   └── Results/                  # ToolResult, ToolError
│       └── Plugins/
│           ├── PluginManager.php
│           ├── PluginValidator.php
│           ├── PluginRegistryClient.php
│           ├── PluginLoader.php
│           └── PluginScaffolder.php
├── config/
│   └── stan.php                          # All config (security, agent, tools, channels, memory)
├── database/
│   └── migrations/                       # 13 migrations
│       ├── ...create_users_table
│       ├── ...create_objectives_table
│       ├── ...create_objective_steps_table
│       ├── ...create_coding_sessions_table
│       ├── ...create_session_messages_table
│       ├── ...create_tool_executions_table
│       ├── ...create_tool_permissions_table
│       ├── ...create_plugins_table
│       ├── ...create_channels_table
│       ├── ...create_agent_directives_table
│       ├── ...create_agent_memories_table
│       ├── ...create_llm_provider_configs_table
│       └── ...create_settings_table
├── plugins/                              # Local user-developed plugins
├── resources/js/
│   ├── app.ts
│   ├── router.ts
│   ├── types/index.ts                    # TypeScript interfaces matching API Resources
│   ├── composables/
│   │   ├── useMercure.ts                 # EventSource SSE
│   │   ├── useObjective.ts
│   │   ├── useSession.ts                 # Coding session CRUD + messages + streaming
│   │   ├── usePlugins.ts                 # Plugin install/remove/list/registry
│   │   ├── useMemories.ts               # Memory search, CRUD, analytics
│   │   ├── useDirectives.ts             # Directive CRUD, preview composed prompt
│   │   ├── useAuth.ts
│   │   └── useTokenBudget.ts
│   ├── layouts/AppLayout.vue             # Sidebar + content
│   ├── pages/
│   │   ├── DashboardPage.vue             # Grid: objectives + active sessions
│   │   ├── ObjectiveCreatePage.vue       # Objective creation form
│   │   ├── ObjectiveDetailPage.vue       # Step timeline + streaming + budget meter
│   │   ├── SessionPage.vue              # Interactive coding (chat + files + terminal + diff)
│   │   ├── SessionCreatePage.vue        # Choose project dir + session config
│   │   ├── PluginsPage.vue              # Browse registry + installed + manage
│   │   ├── ChannelsPage.vue             # Pair/manage messaging channels
│   │   ├── DirectivesPage.vue           # Edit agent soul, rules, coding style (Markdown editor)
│   │   ├── MemoriesPage.vue             # Browse/search/pin/manage agent memories + analytics
│   │   ├── AuditLogPage.vue              # DataTable lazy + filters
│   │   ├── SettingsPage.vue              # Tabs: Providers, Permissions, Security, General
│   │   └── SetupPage.vue                 # Stepper onboarding
│   └── components/
│       ├── ObjectiveCard.vue
│       ├── SessionCard.vue
│       ├── StepTimeline.vue
│       ├── ChatPanel.vue                # Conversational chat (coding sessions)
│       ├── FileExplorer.vue             # Project file tree
│       ├── FileDiffViewer.vue           # Diff of AI modifications
│       ├── ToolExecutionLog.vue
│       ├── TokenBudgetMeter.vue          # MeterGroup
│       ├── PermissionDialog.vue          # Runtime approval dialog
│       ├── PluginCard.vue
│       ├── ChannelPairDialog.vue
│       ├── DirectiveEditor.vue          # Markdown editor for directives
│       ├── MemoryCard.vue               # Memory display with type badge + pin toggle
│       ├── MemorySearchBar.vue          # Semantic search across memories
│       ├── ProviderForm.vue
│       └── LiveTerminal.vue
├── routes/
│   ├── api.php                           # All API routes
│   ├── web.php                           # SPA catch-all
│   └── channels.php                      # BotMan/webhook routes for messaging
├── tests/
│   ├── Unit/Services/Security/           # Guardian, PromptSanitizer, Sandbox, PermissionGate
│   ├── Unit/Services/Agent/              # AgentLoop, TokenBudget, DirectiveComposer, ContextBuilder
│   ├── Unit/Services/Memory/             # MemoryRetriever, MemoryExtractor, PatternDetector
│   ├── Unit/Services/Channels/           # ChannelRouter, ChannelHandler
│   ├── Unit/Agents/                      # StanAgent, ObjectiveAgent, SessionAgent
│   └── Feature/                          # ObjectiveLifecycle, SessionLifecycle, ChannelPairing, PluginInstall, MemoryPersistence
├── FrankenPHP.Caddyfile
└── .env.example
```

---

## Data Model (SQLite tables)

### Core entities

- **objectives**: id, user_id, title, goal, constraints (json), allowed_tools (json), status, token_budget, tokens_used, llm_provider, llm_model, result_summary, started_at, completed_at
- **objective_steps**: id, objective_id, sequence, status, reasoning, tool_name, tool_input (json), tool_output, observation, input_tokens, output_tokens, duration_ms, error
- **coding_sessions**: id, user_id, title, project_path, status (active/paused/closed), token_budget, tokens_used, llm_provider, llm_model, sandbox_config (json), started_at, closed_at
- **session_messages**: id, coding_session_id, role (user/assistant/tool), content, tool_calls (json), tool_results (json), input_tokens, output_tokens, created_at

### Security & audit

- **tool_executions**: id, objective_step_id (nullable), session_message_id (nullable), tool_name, tool_category, risk_level, input (json), output, was_sandboxed, was_approved, approval_method, guardian_passed, guardian_reason, duration_ms, exit_code
- **tool_permissions**: id, user_id, tool_name, permission_level, allowed_patterns (json), blocked_patterns (json), is_active

### Agent intelligence

- **agent_directives**: id, user_id, type (DirectiveType), title, content (text/markdown), is_active, priority (int), version (int), created_at, updated_at
- **agent_memories**: id, user_id, type (MemoryType), content (text), source_type (objective/session/manual), source_id (nullable), relevance_score (float), access_count (int), last_accessed_at, expires_at (nullable), is_pinned (bool), embedding (blob, nullable), created_at, updated_at

### Extensions & channels

- **plugins**: id, user_id, name, version, source (registry/local), description, required_permissions (json), signature (nullable), is_active, installed_at
- **channels**: id, user_id, type (ChannelType), config (json, encrypted), pairing_token, is_active, paired_at

### Configuration

- **llm_provider_configs**: id, user_id, provider, api_key (encrypted), base_url, default_model, is_default, is_active, extra_config (json)
- **settings**: id, user_id, key, value (json)

---

## Defenses vs OpenClaw Problems

| OpenClaw Problem | Stan Solution |
|---|---|
| Default `0.0.0.0` binding | `127.0.0.1` + random port + `LocalhostOnly` middleware |
| Plaintext credentials | `CredentialVault` with Laravel `encrypt()` (AES-256-CBC) |
| RCE via WebSocket | No WebSocket. Unidirectional SSE/Mercure |
| No tool call validation | `Guardian` single chokepoint + 4-tier `PermissionGate` |
| Prompt injection | `PromptSanitizer` + structural separation + `OutputValidator` |
| $2000 in 48h tokens | `TokenBudget` with hard stop per objective/session |
| 430K unauditable lines | Target < 15K lines core PHP (leveraging `laravel/ai` + `botman`) |
| Broken onboarding | `stan:setup` wizard + `SetupPage.vue` Stepper |
| Poisoned marketplace | Curated registry with GPG signatures + manual review |
| Notification spam | Objective model: AI works in background, user checks when they want |
| No personality/consistency | DB-stored directives (soul, rules, style) — admin-editable |
| File-based config sprawl | Everything in DB, cached, editable from admin panel |
| No persistent memory | Structured memory system with embeddings, pattern detection, auto-extraction |
| SSRF | `NetworkPolicy` blocks cloud metadata + internal networks |
| Path traversal | `FilesystemPolicy` restricts to workspace, no symlinks |

---

## `laravel/ai` Integration

### Agent Classes

Stan agents extend `laravel/ai`'s Agent:

```php
// app/Agents/StanAgent.php
class StanAgent extends Agent implements HasTools, Conversational
{
    public function instructions(): string
    {
        // Loads composed directives from DB (cached) + relevant memories
        return app(ContextBuilder::class)->build($this->user());
    }

    public function tools(): array
    {
        return app(ToolRegistry::class)->forAgent($this);
    }

    public function provider(): string|array
    {
        // Failover chain from configured providers
        return app(LlmProviderConfig::class)->getProviderChain();
    }
}
```

### Tool Integration

Stan tools implement `laravel/ai` Tool with security metadata:

```php
interface StanToolInterface extends \Laravel\AI\Contracts\Tool
{
    public function riskLevel(): ToolRiskLevel;
    public function category(): ToolCategory;
    public function sandboxRequirements(): SandboxConfig;
}
```

Guardian wraps execution via `laravel/ai` middleware:

```php
$agent->withMiddleware(function (ToolCall $call, Closure $next) {
    $verdict = $this->guardian->evaluate($call, $this->context);
    if ($verdict->isDenied()) return ToolResult::error($verdict->reason);
    if ($verdict->isAwaitingApproval()) throw new AwaitingApprovalException($call, $verdict);
    return $next($call);
});
```

### Memory at Query Time

```php
// app/Services/Agent/ContextBuilder.php
class ContextBuilder
{
    public function build(User $user, ?Objective $objective = null, ?CodingSession $session = null): string
    {
        // 1. Composed directives from DB (cached)
        $directives = $this->directiveComposer->compose($user);

        // 2. Relevant memories via semantic search
        $query = $objective?->goal ?? $session?->title ?? '';
        $memories = $this->memoryRetriever->retrieve($user, $query, limit: 10);

        // 3. Objective/session-specific context
        $context = $this->buildSpecificContext($objective, $session);

        return $directives . "\n\n" . $this->formatMemories($memories) . "\n\n" . $context;
    }
}
```

---

## Implementation Phases

### Phase 1 — Foundation & Security (Weeks 1-2)

Initialize Laravel 12 + FrankenPHP + Octane + SQLite. Install `laravel/ai`. **All** 13 migrations (including agent_directives, agent_memories). Models, enums, relationships, casts. Complete security layer: Guardian, PromptSanitizer, SandboxManager, PermissionGate, CredentialVault, AuthTokenManager, policies (Network, Filesystem, Process). Auth middleware. `stan:start` and `stan:setup` commands. Seed default directives (soul, behavioral_rules, communication). `config/stan.php`. Security unit tests.

**Deliverable**: App boots on random localhost port, token auto-generated, security layer operational with tests. Default directives seeded in DB. No UI yet.

### Phase 2 — Agents, Tools & Memory (Weeks 3-4)

Build agent classes on `laravel/ai`: StanAgent, ObjectiveAgent, SessionAgent, PlannerAgent. DirectiveComposer (loads/caches directives from DB). ContextBuilder (composes directives + memories). Tool implementations (Shell, FileRead, FileWrite, FileSearch, WebFetch) with StanToolInterface. ToolRegistry. Guardian middleware integration. EmbeddingService + MemoryManager (CRUD). ProvidersController API. Unit tests with mocked HTTP.

**Deliverable**: Can configure providers, send prompts, receive streaming. Tools execute in sandbox. Directives loaded from DB. Memory CRUD operational. Full tool-calling loop end-to-end.

### Phase 3 — Objectives, Sessions, Channels & Memory Extraction (Weeks 5-6)

**Objectives**: AgentLoop, ObjectiveRunner, Planner, StepExecutor, CompletionChecker, TokenBudget, RunObjective job.

**Coding Sessions**: SessionRunner, SessionsController, ProcessSessionMessage job. SandboxManager bind mount for project directory.

**Messaging Channels**: ChannelRouter, ChannelHandler, ChannelPairing. BotMan with Telegram and Slack drivers. ChannelsController API. Webhook routes.

**Memory Extraction**: MemoryExtractor (auto-extracts from completed objectives/sessions → ExtractMemories job). MemoryRetriever (semantic search at objective/session start). PatternDetector (periodic DetectPatterns job).

**API**: All controllers including DirectivesController and MemoriesController. Events + Mercure listeners. Feature tests for all lifecycles.

**Deliverable**: Complete backend. Background objectives + interactive sessions + Telegram/Slack messaging. Memories auto-extracted and surfaced. All through Guardian/sandbox, Mercure, token budgets.

### Phase 4 — Frontend MVP (Weeks 7-8)

Vue 3 + PrimeVue + TypeScript. All composables.

**Pages**: Dashboard, ObjectiveCreate, ObjectiveDetail, SessionCreate, SessionPage (3-panel: FileExplorer + ChatPanel + FileDiffViewer + LiveTerminal), ChannelsPage, **DirectivesPage** (Markdown editor for soul/rules/style with preview + versioning), **MemoriesPage** (search/browse/pin/manage memories + analytics), AuditLog, Settings, Setup.

**Key components**: DirectiveEditor (PrimeVue Editor for Markdown), MemoryCard, MemorySearchBar, PermissionDialog, StepTimeline, TokenBudgetMeter.

**Deliverable**: Full SPA. Create objectives, code interactively, pair channels. Edit agent directives. Browse and manage memories. Approve/deny tools. Full audit log.

### Phase 5 — Plugins & Hardening (Weeks 9-10)

**Plugin system**: PluginManager, PluginValidator, PluginRegistryClient, PluginLoader, PluginScaffolder. Artisan commands. PluginsController + PluginsPage. GPG signature validation. Static analysis.

**Additional channels**: WhatsApp, Signal, Teams drivers.

**Hardening**: WebSearchTool + ApiCallTool. CSRF, rate limiting, request logging. Sandbox seccomp profiles. Updated injection patterns. 80%+ test coverage on security, agent, memory, channels, plugins.

### Phase 6 — Polish & Standalone Binary (Weeks 11-12)

**Polish**: Dark/light mode, keyboard shortcuts, responsive. Directive templates. Memory analytics dashboard. Onboarding improvements.

**Binary**: FrankenPHP static build. Install scripts. GitHub Actions CI (PHPStan + ESLint, PHPUnit, Semgrep, build binary). Documentation (README, architecture, security model, plugin dev guide, channel setup guide). Tag v0.1.0.

---

## Verification

### Objectives (background)
1. `php artisan stan:start` → boots on random port, displays URL + token
2. Open URL → SetupPage on first run, Dashboard otherwise
3. Configure LLM provider → "Test" button green
4. Create objective → status Running, StepTimeline updates via SSE
5. Tool call goes through Guardian (visible in audit log)
6. ShellTool → PermissionDialog for approval
7. Deny → step denied, objective adapts
8. TokenBudgetMeter reflects usage; 1000-token budget → hard stop

### Coding sessions (interactive)
9. Create session → select project directory → FileExplorer shows tree
10. "Read README.md" → FileReadTool, response streamed real-time
11. "Create hello.txt" → Guardian approval → file created → FileDiffViewer shows it
12. AI cannot access outside project directory
13. Session token budget hard stop works
14. Close session → history preserved

### Messaging channels
15. Pair Telegram → pairing token flow
16. "Create objective: research PHP frameworks" via Telegram → objective created
17. "Status?" → receives current status
18. Tool approval via inline button
19. Completion notification on Telegram
20. No auto-approve for high-risk tools via messaging

### Directives (agent soul)
21. Edit soul directive in DirectivesPage → Markdown editor with preview
22. Agent behavior reflects directive changes (no restart needed — cache invalidated)
23. Disable a directive → agent no longer follows it
24. Version history shows previous edits

### Memories
25. Complete an objective → MemoryExtractor creates memories (visible in MemoriesPage)
26. Start new objective → relevant memories auto-injected in context
27. Pin a memory → always included in context
28. Delete a memory → no longer surfaced
29. Semantic search finds relevant memories by meaning, not just keywords
30. PatternDetector creates "pattern" memories from recurring behaviors

### Plugins
31. `stan:plugin create my-tool` → structure generated in `plugins/my-tool/`
32. Implement Tool → restart → available in objectives and sessions
33. Plugin in sandbox (same isolation as built-in tools)
34. Install from registry → signature verification → permission approval

### Tests
35. `phpunit` → all pass, security coverage > 80%
36. Plugin cannot read Stan's files
37. Session cannot access outside project_path
38. Channel messages go through PromptSanitizer
39. Directives cache invalidates on edit
40. Memories with expired TTL are not retrieved
