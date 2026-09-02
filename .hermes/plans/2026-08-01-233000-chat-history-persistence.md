# Chat History Persistence + Session Tabs

## Goal
Simpan history chat ke database, dan tambahkan tab sesi agar user bisa load chat lama.

## Current State
- `chatHistory` = array JS di browser memory — hilang saat refresh
- No DB tables untuk chat
- Chat view: `app/Views/chat/index.php` — single-session, no tabs
- Controller: `app/Controllers/Chat.php` — `index()`, `api()`, `chatApi()`, `mcpToolCall()`
- Routes: GET `/chat`, POST `/chat/api`, POST `/chat/mcp-tool`
- DB: MySQL `arteri` database

## Architecture

### Database Tables

**`chat_sessions`** — one row per conversation
```sql
chat_sessions (
  id INT UNSIGNED AUTO_INCREMENT PK,
  user_id VARCHAR(255),        -- session('username')
  title VARCHAR(255),          -- auto-generated from first message, editable
  created_at DATETIME,
  updated_at DATETIME,
  INDEX(user_id, updated_at)
)
```

**`chat_messages`** — individual messages per session
```sql
chat_messages (
  id INT UNSIGNED AUTO_INCREMENT PK,
  session_id INT UNSIGNED FK → chat_sessions.id ON DELETE CASCADE,
  role ENUM('user','assistant','tool','system'),
  content LONGTEXT NULL,
  tool_calls JSON NULL,        -- for assistant messages with function calls
  tool_call_id VARCHAR(100) NULL, -- for tool result messages
  tool_name VARCHAR(100) NULL,    -- for display in UI
  model VARCHAR(100) NULL,        -- which model responded
  usage JSON NULL,                -- token usage
  created_at DATETIME,
  INDEX(session_id, created_at)
)
```

### API Endpoints (add to Chat controller)

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/chat` | Chat page (existing) |
| POST | `/chat/api` | Chat API (existing) |
| POST | `/chat/mcp-tool` | MCP tool call (existing) |
| GET | `/chat/sessions` | List user's sessions (new) |
| POST | `/chat/sessions` | Create new session (new) |
| GET | `/chat/sessions/{id}` | Load messages for a session (new) |
| DELETE | `/chat/sessions/{id}` | Delete a session (new) |
| PUT | `/chat/sessions/{id}` | Update title (new) |

### Backend Flow

1. **New chat** → frontend calls `POST /chat/sessions` → returns `{id, title}`
2. **Each message** → after LLM responds, frontend calls `POST /chat/api` with `sessionId` param → backend saves user message + assistant response to DB
3. **Load session** → `GET /chat/sessions/{id}` → returns messages array, frontend renders them
4. **List sessions** → `GET /chat/sessions` → returns sessions with last message preview

### Frontend Changes

1. **Sidebar** — new left panel with session list (title, date, last message preview)
2. **Session tabs** — clickable list, active highlight
3. **New Chat button** — creates empty session
4. **Delete** — confirm dialog per session
5. **Auto-title** — first user message becomes session title (truncated to 50 chars)
6. **Auto-save** — after each assistant response, save both user message + assistant response

### Session Management in `chatHistory` JS

```js
let currentSessionId = null;
let sessions = []; // [{id, title, updated_at}]

// On load: fetch sessions list, show sidebar
// On new chat: POST /chat/sessions → set currentSessionId, clear messages
// On click session: GET /chat/sessions/{id} → render messages
// After each response: save to backend
```

## Step-by-Step Plan

### Step 1: Migration
- Create `app/Database/Migrations/2026-08-01-000001_CreateChatTables.php`
- Two tables: `chat_sessions`, `chat_messages`
- Run migration

### Step 2: Model
- Create `app/Models/ChatSessionModel.php` — CRUD for sessions
- Create `app/Models/ChatMessageModel.php` — CRUD for messages

### Step 3: Controller Routes
- Add to `app/Config/Routes.php`:
  - `GET chat/sessions` → `Chat::sessions`
  - `POST chat/sessions` → `Chat::createSession`
  - `GET chat/sessions/(:num)` → `Chat::loadSession/$1`
  - `DELETE chat/sessions/(:num)` → `Chat::deleteSession/$1`
  - `PUT chat/sessions/(:num)` → `Chat::updateSession/$1`
- Add methods to `Chat.php` controller

### Step 4: Modify `chatApi()` to accept `sessionId`
- Save user message + assistant response to DB when `sessionId` is provided
- Auto-generate title from first user message

### Step 5: Frontend — Session Sidebar
- Add sidebar HTML with session list
- CSS for sidebar, active state, hover
- JS functions: `loadSessions()`, `createSession()`, `loadSession(id)`, `deleteSession(id)`

### Step 6: Frontend — Auto-save Flow
- After each LLM response, POST messages to backend with sessionId
- On "New Chat" → create session → clear messages
- On load → fetch sessions → load latest

### Step 7: Integration with Existing Chat Flow
- Modify `sendMessage()` to include `sessionId` in `callLlm()`
- Modify `callLlm()` to pass `sessionId` to backend
- Backend `chatApi()` saves messages when `sessionId` present

### Step 8: Tests
- Unit test for ChatSessionModel
- Unit test for ChatMessageModel
- API test for session CRUD
- Integration test for save/load flow

## Files to Change
- `app/Database/Migrations/2026-08-01-000001_CreateChatTables.php` (NEW)
- `app/Models/ChatSessionModel.php` (NEW)
- `app/Models/ChatMessageModel.php` (NEW)
- `app/Controllers/Chat.php` (MODIFY — add session methods, modify chatApi)
- `app/Config/Routes.php` (MODIFY — add session routes)
- `app/Views/chat/index.php` (MODIFY — sidebar, tabs, auto-save JS)

## Verification
1. `php spark migrate` — tables created
2. Login → Chat → New Chat → send messages → refresh page → session still there
3. Switch between sessions → messages load correctly
4. Delete session → removed from list
5. Run PHPUnit tests
