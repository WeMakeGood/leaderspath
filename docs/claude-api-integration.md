# Claude API Integration

**Last Updated:** 2026-02-04

This document describes how LeadersPath integrates with the Anthropic Claude API, including the full skill system with code execution support.

---

## Overview

LeadersPath uses Claude's **container-based API with code execution** to enable full skill functionality including Python script execution. This requires:

1. **Beta Headers** - Enable code execution and skills features
2. **Container API** - Persistent execution environment for skills
3. **Skills API** - Upload and manage custom skills
4. **Files API** - Handle file uploads and downloads

---

## API Endpoints Used

### Messages API (with Container)

**Endpoint:** `POST https://api.anthropic.com/v1/messages`

Used for chat interactions with code execution and skills enabled.

```php
// Required headers
'Content-Type'      => 'application/json',
'x-api-key'         => $api_key,
'anthropic-version' => '2023-06-01',
'anthropic-beta'    => 'code-execution-2025-08-25,skills-2025-10-02',
```

### Skills API

**Endpoints:**
- `POST /v1/skills` - Create a new skill
- `GET /v1/skills` - List all skills
- `GET /v1/skills/{skill_id}` - Get skill details
- `DELETE /v1/skills/{skill_id}` - Delete a skill
- `POST /v1/skills/{skill_id}/versions` - Create new version
- `GET /v1/skills/{skill_id}/versions` - List versions
- `DELETE /v1/skills/{skill_id}/versions/{version}` - Delete version

### Files API

**Endpoints:**
- `POST /v1/files` - Upload a file
- `GET /v1/files/{file_id}` - Get file metadata
- `GET /v1/files/{file_id}/content` - Download file content
- `DELETE /v1/files/{file_id}` - Delete a file

---

## Beta Headers and Versioning

### Current Beta Headers

| Feature | Beta Header | Tool Type |
|---------|-------------|-----------|
| Code Execution | `code-execution-2025-08-25` | `code_execution_20250825` |
| Skills | `skills-2025-10-02` | N/A (container param) |
| Files API | `files-api-2025-04-14` | N/A |

### Version Strategy

Beta headers and tool types include dates and may change. To handle this:

1. **Store versions in wp_options** - Not hardcoded in class constants
2. **Admin settings page** - Allow updating versions without code changes
3. **Default fallbacks** - Use known-working versions as defaults

```php
// In Settings class
'claude_beta_code_execution' => 'code-execution-2025-08-25',
'claude_beta_skills'         => 'skills-2025-10-02',
'claude_beta_files'          => 'files-api-2025-04-14',
'claude_tool_code_execution' => 'code_execution_20250825',
```

### Checking for Updates

The Anthropic API documentation should be checked periodically:
- https://platform.claude.com/docs/en/api/beta-headers
- https://platform.claude.com/docs/en/agents-and-tools/tool-use/code-execution-tool
- https://platform.claude.com/docs/en/build-with-claude/skills-guide

---

## Request Structure

### Chat Request with Skills

```json
{
  "model": "claude-sonnet-4-5-20250929",
  "max_tokens": 4096,
  "container": {
    "skills": [
      {
        "type": "custom",
        "skill_id": "skill_01AbCdEfGhIjKlMnOpQrStUv",
        "version": "latest"
      }
    ]
  },
  "system": "System prompt with context files...",
  "messages": [
    {"role": "user", "content": "User message"},
    {"role": "assistant", "content": "Previous response"},
    {"role": "user", "content": "Current message"}
  ],
  "tools": [
    {
      "type": "code_execution_20250825",
      "name": "code_execution"
    }
  ]
}
```

### Key Parameters

| Parameter | Description |
|-----------|-------------|
| `container.skills` | Array of skills to load (max 8) |
| `container.id` | Reuse container from previous response |
| `tools` | Must include code execution tool for skills |
| `system` | System prompt with context files embedded |
| `messages` | Conversation history |

---

## Skill Lifecycle

### 1. Upload Skill Package to WordPress

User uploads ZIP file via ACF field. ZIP structure:

```
skill-name/
├── SKILL.md           # Required - instructions with YAML frontmatter
├── references/        # Optional - additional documentation
│   └── REFERENCE.md
├── scripts/           # Optional - executable scripts
│   └── process.py
└── assets/            # Optional - templates, images
    └── template.docx
```

### 2. Process and Upload to Anthropic

When skill package is saved:

1. **Validate locally** - Check SKILL.md exists, parse frontmatter
2. **Upload to Anthropic Skills API** - Send ZIP or files
3. **Store skill_id** - Save returned ID in ACF field
4. **Store version** - Save version timestamp

```php
// Upload skill to Anthropic
$response = wp_remote_post(
    'https://api.anthropic.com/v1/skills',
    [
        'headers' => [
            'x-api-key'         => $api_key,
            'anthropic-version' => '2023-06-01',
            'anthropic-beta'    => 'skills-2025-10-02',
        ],
        'body' => [
            'display_title' => $skill_title,
            'files'         => $zip_file,
        ],
    ]
);

// Response contains skill_id
$data = json_decode( wp_remote_retrieve_body( $response ), true );
$skill_id = $data['id'];  // e.g., "skill_01AbCdEfGhIjKlMnOpQrStUv"
$version  = $data['latest_version'];
```

### 3. Use Skill in Chat

When chat is initiated for an activity:

1. **Get activity's skills** - Query ACF relationship field
2. **Build skills array** - Map WordPress skill IDs to Anthropic skill_ids
3. **Include in container** - Add to request

```php
$skills_for_api = [];
foreach ( $activity_skills as $wp_skill_id ) {
    $anthropic_id = get_field( 'skill_anthropic_id', $wp_skill_id );
    if ( $anthropic_id ) {
        $skills_for_api[] = [
            'type'     => 'custom',
            'skill_id' => $anthropic_id,
            'version'  => 'latest',
        ];
    }
}
```

### 4. Update Skill (New Version)

When a new ZIP is uploaded for an existing skill:

1. **Create new version** via `POST /v1/skills/{skill_id}/versions`
2. **Update stored version** in WordPress
3. **Existing chats** continue working (version pinning optional)

---

## Context Files vs Skills

Understanding the distinction between these two content types is critical:

### Context Files (Reference Documents)

**Storage:** WordPress only (post_content in Context CPT)
**Delivery:** Embedded directly in system prompt
**API:** None required - just text in the `system` parameter

Context files are reference documents (guidelines, standards, knowledge bases) that Claude should always have access to during the conversation. They:
- Don't require code execution
- Don't need the Skills API
- Are embedded in full in the system prompt
- Live entirely in WordPress

### Skills (Executable Packages)

**Storage:** Anthropic Skills API (uploaded from WordPress)
**Delivery:** Via `container.skills` array referencing `skill_id`
**API:** Skills API (`/v1/skills`) + Code Execution

Skills are executable packages with instructions, scripts, and resources. They:
- **Must be uploaded to Anthropic** to enable code execution
- Are loaded progressively (metadata → instructions → resources)
- Can execute Python scripts in a sandboxed container
- Require the code execution tool

### Why This Architecture

| Aspect | Context Files | Skills |
|--------|---------------|--------|
| Purpose | Reference material | Executable capabilities |
| Scripts | No | Yes (Python, bash) |
| API upload | Not needed | Required |
| System prompt | Full content embedded | Name + description only |
| Storage | WordPress post_content | Anthropic + WordPress metadata |

---

## Context Assembly

The system prompt is assembled from multiple sources:

### Assembly Order

1. **Custom System Prompt** (if set on activity) OR **Default System Prompt** (fallback)
2. **Context Files** - Full content of linked Context CPTs (embedded)
3. **Skills Metadata** - Name and description only (for discovery)

**Important:** The activity's `post_content` (what learners see on the page) is NOT included in the system prompt. Only the Custom System Prompt field and attached Context Files define Claude's behavior.

### Progressive Loading for Skills

Skills use three loading levels to minimize context usage:
- **Level 1 (Always)**: Name + description in system prompt for discovery (~100 tokens)
- **Level 2 (On trigger)**: Full SKILL.md loaded by container when skill is invoked
- **Level 3 (As needed)**: Reference files, script execution via container

The actual skill instructions and scripts are NOT in the system prompt - they're loaded by Anthropic's container when Claude decides to use the skill.

### System Prompt Structure

```
[Custom System Prompt OR Default]

--- Reference Materials ---
### [Context File 1 Title]
[Context File 1 Content]

### [Context File 2 Title]
[Context File 2 Content]

--- Available Skills ---
### [Skill 1 Name]
[Skill 1 Description - for discovery only]

### [Skill 2 Name]
[Skill 2 Description - for discovery only]
```

---

## Response Handling

### Standard Response

```json
{
  "id": "msg_01XYZ...",
  "type": "message",
  "role": "assistant",
  "content": [
    {"type": "text", "text": "Response text..."}
  ],
  "model": "claude-sonnet-4-5-20250929",
  "stop_reason": "end_turn",
  "usage": {
    "input_tokens": 1500,
    "output_tokens": 300
  },
  "container": {
    "id": "container_01ABC..."
  }
}
```

### Code Execution Response

When skills execute code:

```json
{
  "content": [
    {"type": "text", "text": "I'll run that script..."},
    {
      "type": "server_tool_use",
      "id": "srvtoolu_01...",
      "name": "bash_code_execution",
      "input": {"command": "python /skills/my-skill/scripts/process.py"}
    },
    {
      "type": "bash_code_execution_tool_result",
      "tool_use_id": "srvtoolu_01...",
      "content": {
        "type": "bash_code_execution_result",
        "stdout": "Processing complete...",
        "stderr": "",
        "return_code": 0
      }
    },
    {"type": "text", "text": "The script completed successfully..."}
  ]
}
```

### Pause Turn Handling

Long-running operations may return `stop_reason: "pause_turn"`:

```php
while ( $response['stop_reason'] === 'pause_turn' ) {
    // Add assistant response to history
    $messages[] = [
        'role'    => 'assistant',
        'content' => $response['content'],
    ];

    // Continue the turn
    $response = $this->send_request( $messages, $container_id );
}
```

### File Output Handling

If skills generate files:

```php
// Extract file_ids from response
foreach ( $response['content'] as $block ) {
    if ( $block['type'] === 'bash_code_execution_tool_result' ) {
        $result = $block['content'];
        if ( isset( $result['content'] ) ) {
            foreach ( $result['content'] as $file ) {
                if ( isset( $file['file_id'] ) ) {
                    // Download via Files API
                    $file_content = $this->download_file( $file['file_id'] );
                }
            }
        }
    }
}
```

---

## Container Management

### Container Lifecycle

- **Created**: Automatically on first request with skills/code execution
- **Reused**: Pass `container.id` from previous response
- **Expires**: 30 days after creation
- **Workspace scoped**: Tied to API key's workspace

### Multi-Turn Conversations

```php
// First message - container created
$response1 = $this->send_message( $activity_id, $message1 );
$container_id = $response1['container']['id'];

// Subsequent messages - reuse container
$response2 = $this->send_message( $activity_id, $message2, $history, $container_id );
```

### Session Strategy

For LeadersPath (no conversation persistence across page loads):
- **New container per page load** - Fresh environment each time
- **Reuse within session** - Same container for conversation turns
- **Don't persist container_id** - Let it expire naturally

---

## Error Handling

### API Errors

| Status | Error Type | Handling |
|--------|------------|----------|
| 400 | `invalid_request_error` | Check request format |
| 401 | `authentication_error` | Check API key |
| 403 | `permission_error` | Check skill permissions |
| 429 | `rate_limit_error` | Implement backoff |
| 500 | `api_error` | Retry with backoff |
| 529 | `overloaded_error` | Retry later |

### Code Execution Errors

| Error Code | Description |
|------------|-------------|
| `unavailable` | Tool temporarily unavailable |
| `execution_time_exceeded` | Script took too long |
| `container_expired` | Container no longer available |
| `invalid_tool_input` | Bad parameters |
| `too_many_requests` | Rate limited |

### Skill-Specific Errors

- **Skill not found**: skill_id invalid or deleted
- **Version not found**: Specified version doesn't exist
- **Max skills exceeded**: More than 8 skills in request

---

## ACF Fields for Skills

### Required Fields

| Field | Type | Description |
|-------|------|-------------|
| `skill_package` | File | ZIP upload (mime: zip) |
| `skill_name` | Text | From SKILL.md frontmatter (readonly) |
| `skill_description` | Textarea | From SKILL.md frontmatter (readonly) |
| `skill_compatibility` | Text | From SKILL.md frontmatter (readonly) |
| `skill_anthropic_id` | Text | Returned from Skills API (readonly) |
| `skill_anthropic_version` | Text | Current version timestamp (readonly) |
| `skill_version` | Text | User-managed semantic version |
| `skill_notes` | WYSIWYG | Admin notes |

### Sync Status

Consider adding:
- `skill_sync_status` - pending, synced, error
- `skill_sync_error` - Last error message
- `skill_last_synced` - Timestamp

---

## Implementation Checklist

### Phase 1: Skill Upload

- [ ] Add `skill_anthropic_id` and `skill_anthropic_version` ACF fields
- [ ] Update Skill_Processor to upload to Anthropic Skills API
- [ ] Store returned skill_id and version
- [ ] Handle upload errors with admin notices
- [ ] Add re-sync button for failed uploads

### Phase 2: Chat Integration

- [ ] Update Claude_API to use container parameter
- [ ] Add code execution tool to requests
- [ ] Build skills array from activity's linked skills
- [ ] Handle pause_turn responses
- [ ] Store container_id in session for reuse

### Phase 3: Settings

- [ ] Add beta header version fields to settings
- [ ] Add tool type version field
- [ ] Add "Test Skills API" button
- [ ] Document version update process

### Phase 4: Response Handling

- [ ] Parse code execution results
- [ ] Handle file outputs (if needed)
- [ ] Format execution results for display
- [ ] Error display in chat UI

---

## References

- [Skills Guide](https://platform.claude.com/docs/en/build-with-claude/skills-guide)
- [Code Execution Tool](https://platform.claude.com/docs/en/agents-and-tools/tool-use/code-execution-tool)
- [Files API](https://platform.claude.com/docs/en/build-with-claude/files)
- [Agent Skills Overview](https://platform.claude.com/docs/en/agents-and-tools/agent-skills/overview)
- [Beta Headers](https://platform.claude.com/docs/en/api/beta-headers)
