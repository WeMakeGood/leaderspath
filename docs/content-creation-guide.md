# LeadersPath Content Creation Guide

**Last Updated:** 2026-02-09

This guide explains how to create content for the LeadersPath learning platform. LeadersPath is a **facilitated learning experience** where facilitators present Lessons and learners experiment with AI sandboxes (Activities).

---

## Table of Contents

1. [Understanding the Content Model](#understanding-the-content-model)
2. [Creating Context Files](#creating-context-files)
3. [Creating Skills](#creating-skills)
4. [Creating Activities](#creating-activities)
5. [Creating Lessons](#creating-lessons)
6. [Best Practices](#best-practices)
7. [Examples](#examples)

---

## Understanding the Content Model

LeadersPath uses a facilitated learning architecture:

```
Course (reusable curriculum)
  └── Lesson (atomic teaching unit)
        ├── Facilitator Guide (what to present, when to run activities)
        ├── Learner Overview (context for learners)
        └── Activities (AI sandbox experiments)
              ├── Context Files (embedded in system prompt)
              └── Skills (uploaded to Anthropic, loaded on-demand)
```

### Key Terminology

| Term | Definition |
|------|------------|
| **Course** | A reusable curriculum containing an ordered sequence of Lessons |
| **Lesson** | The atomic teaching unit, taught as a cohesive whole by a facilitator |
| **Activity** | An AI sandbox experiment within a Lesson (what learners DO, not what they LEARN) |
| **Facilitator Guide** | The central teaching document (what to present, when to run activities, discussion prompts) |
| **Context Files** | Reference documents that provide Claude with background information |
| **Skills** | Executable capabilities (Python scripts, workflows) uploaded to Anthropic |

### Context vs Skills

| Type | Purpose | Storage | When to Use |
|------|---------|---------|-------------|
| **Context Files** | Reference material | WordPress only | Brand guidelines, writing standards, knowledge bases, examples |
| **Skills** | Executable capabilities | Anthropic Skills API | Python scripts, data processing, file generation |

**Context Files** are embedded in full in every chat request. Use them for information Claude should always have access to.

**Skills** are uploaded to Anthropic and loaded progressively when Claude needs them. Use them for capabilities that require code execution.

---

## Creating Context Files

Context Files are markdown documents that provide reference material for Claude.

### When to Create a Context File

Create a context file when you have:
- Brand voice guidelines
- Writing style standards
- Process documentation
- Knowledge base content
- Example inputs and outputs
- FAQ content
- Technical specifications
- Any reference material Claude should know

### Context File Structure

A context file is simply markdown content stored in WordPress. The content goes directly in the WordPress editor.

**Required Fields:**
- **Title**: Human-readable name (e.g., "Brand Voice Guidelines")
- **Content**: The full markdown content in the editor

**Optional Fields:**
- **Description**: Brief notes about purpose and usage
- **Version**: Semantic version (e.g., "1.0.0")
- **Category**: Context Category taxonomy term for organization (e.g., Knowledge Base, Instructions, Examples)

### Writing Effective Context Files

**Structure for Clarity:**
```markdown
# Document Title

## Overview
Brief description of what this document contains and when to reference it.

## Main Content
The primary information, organized with clear headings.

### Subsection
Details organized hierarchically.

## Quick Reference
Key points, rules, or examples for easy scanning.
```

**Best Practices:**
1. **Be specific** - Claude performs better with concrete examples than abstract rules
2. **Use examples** - Show, don't just tell. Include input/output pairs
3. **Organize hierarchically** - Use headings to group related content
4. **Include edge cases** - Address exceptions and special situations
5. **Keep it focused** - One concept per file is easier to manage than monolithic documents

### Example: Brand Voice Context File

```markdown
# WeMakeGood Brand Voice Guidelines

## Overview
These guidelines ensure consistent brand voice across all AI-generated content for WeMakeGood.

## Core Voice Attributes

### Warm but Professional
We're friendly and approachable, but maintain credibility.

**Do:** "We'd love to help you explore that idea further."
**Don't:** "OMG that's such a cool idea!!!"

### Clear and Direct
We value clarity over cleverness.

**Do:** "This approach reduces costs by 30%."
**Don't:** "This methodology facilitates significant fiscal optimization."

### Empowering, Not Preachy
We guide without lecturing.

**Do:** "Here's a technique that works well for this situation..."
**Don't:** "You should always do it this way because..."

## Formatting Standards

- Use sentence case for headings
- Keep paragraphs under 4 sentences
- Use bullet points for lists of 3+ items
- Include a clear call-to-action in conclusions

## Tone Adjustments by Context

| Context | Adjustment |
|---------|------------|
| Error messages | Extra empathy, clear next steps |
| Tutorials | Encouraging, step-by-step |
| Technical docs | Precise, minimal filler |
| Marketing | Energetic, benefit-focused |
```

---

## Creating Skills

Skills are executable packages that give Claude new capabilities through Python script execution.

### When to Create a Skill

Create a skill when you need:
- Data processing or analysis
- File generation (reports, documents)
- API integrations
- Complex calculations
- Multi-step workflows
- Content transformation

### Skill Package Structure

A skill is a ZIP file containing:

```
skill-name/
├── SKILL.md           # Required - main instructions with YAML frontmatter
├── references/        # Optional - additional documentation
│   └── REFERENCE.md
├── scripts/           # Optional - executable scripts
│   └── process.py
└── assets/            # Optional - templates, images, data files
    └── template.docx
```

### SKILL.md Requirements

Every skill MUST have a `SKILL.md` file with YAML frontmatter:

```markdown
---
name: skill-name
description: A clear description of what this skill does and when Claude should use it.
compatibility: Requires Python 3.9+ (optional)
---

# Skill Name

## Purpose
Detailed explanation of what this skill accomplishes.

## When to Use
Specific scenarios when this skill is appropriate.

## Instructions
Step-by-step guide for how Claude should use this skill.

## Available Scripts

### process.py
Description of what this script does and how to call it.

**Usage:**
\`\`\`python
python /skills/skill-name/scripts/process.py --input data.csv
\`\`\`

**Arguments:**
- `--input`: Path to input file
- `--output`: Path for output file (optional, defaults to stdout)

## Examples

### Example 1: Basic Usage
Input: [describe input]
Expected output: [describe output]
```

### Frontmatter Validation Rules

| Field | Required | Rules |
|-------|----------|-------|
| `name` | Yes | 1-64 characters, lowercase letters and hyphens only, no "anthropic" or "claude" |
| `description` | Yes | 1-1024 characters, no angle brackets |
| `compatibility` | No | 1-500 characters |

### Writing Scripts for Skills

Scripts run in Anthropic's sandboxed Python environment.

**Available:**
- Python 3.9+ standard library
- Common data science packages (pandas, numpy)
- File I/O within the container

**Not Available:**
- Network access (no HTTP requests)
- Database connections
- System commands outside the sandbox

**Script Template:**
```python
#!/usr/bin/env python3
"""
Brief description of what this script does.
"""

import argparse
import json
import sys


def main():
    parser = argparse.ArgumentParser(description='Process data')
    parser.add_argument('--input', required=True, help='Input file path')
    parser.add_argument('--output', help='Output file path')
    args = parser.parse_args()

    # Read input
    with open(args.input, 'r') as f:
        data = f.read()

    # Process data
    result = process(data)

    # Output result
    if args.output:
        with open(args.output, 'w') as f:
            f.write(result)
    else:
        print(result)


def process(data):
    """Process the input data and return result."""
    # Implementation here
    return data


if __name__ == '__main__':
    main()
```

---

## Creating Activities

Activities are **AI sandbox experiments** where learners interact with Claude to experience specific AI behaviors. They are NOT traditional lessons - the facilitator provides the teaching, and activities let learners experiment hands-on.

### Activity Purpose

Activities demonstrate:
- Specific AI behaviors (sycophancy, helpfulness, refusal)
- The impact of context on AI responses
- Different AI personas or configurations
- How skills extend AI capabilities

### Activity Components

**Core Content (WordPress Editor):**
- **Title**: Activity name (e.g., "Experience Sycophantic AI")
- **Content**: Instructions for learners ("Try this, notice that")
- **Excerpt**: Brief description for lesson listings

**Activity Settings (ACF Fields):**

| Field | Type | Description |
|-------|------|-------------|
| `activity_duration` | Number | Estimated duration in minutes (1-480) |

**AI Sandbox Configuration (ACF Fields):**

| Field | Type | Description |
|-------|------|-------------|
| `chatbot_enabled` | True/False | Enable AI sandbox for this activity |
| `chatbot_model` | Select | Claude model (sonnet, haiku, opus-4.5) |
| `chatbot_allow_model_switch` | True/False | Let users change models |
| `chatbot_system_prompt` | Textarea | Custom system prompt defining the AI behavior learners will experience |
| `chatbot_context_files` | Relationship | Context files to include |
| `chatbot_skills` | Relationship | Skills available to chatbot |
| `chatbot_max_tokens` | Number | Max response tokens (256-16384) |
| `chatbot_temperature` | Number | Creativity level (0-1) |

### Writing Activity Content

The activity body (in the WordPress editor) is what **learners see on the page** - it is NOT sent to Claude. Use this area to:

- Explain what behavior they'll experience
- Give specific prompts to try
- Tell them what to notice/observe
- Frame the experiment

**Example structure:**
```markdown
## What You'll Experience
This AI has been configured to [specific behavior]. Your goal is to [objective].

## Try This
1. Ask the chatbot: "[specific prompt]"
2. Notice how it [expected behavior]
3. Now try: "[contrasting prompt]"
4. Compare the responses

## What to Notice
- [Specific thing to observe]
- [Another behavior to watch for]

## Discussion Questions
After experimenting, consider:
- Why did the AI respond this way?
- How does this compare to [previous activity]?
```

### Writing System Prompts for Activities

The system prompt **defines the AI behavior** learners will experience. This is where you craft the specific persona, constraints, or behaviors the activity is meant to demonstrate.

**Example: Sycophantic AI Activity**
```
You are an AI assistant that tends to agree with and validate whatever the user says, even when they're wrong. You:

- Always start by praising the user's idea or perspective
- Avoid pointing out errors or problems
- Quickly change your opinion if the user pushes back
- Use phrases like "That's a great point!" and "You're absolutely right!"
- Never say "but" or "however" - only "and"

This behavior demonstrates sycophancy - a common problem with AI systems that prioritize user approval over accuracy.
```

**Example: Helpful but Boundaries AI Activity**
```
You are a helpful AI assistant that maintains clear boundaries. You:

- Provide accurate, useful information
- Politely decline inappropriate requests with brief explanations
- Don't apologize excessively
- Stay focused on being genuinely helpful
- Correct factual errors diplomatically

Demonstrate what well-aligned AI behavior looks like.
```

### Choosing Model Settings

| Setting | When to Use |
|---------|-------------|
| **Model: Sonnet** | Default. Good balance of capability and cost |
| **Model: Haiku** | Simple Q&A, quick responses, cost-sensitive |
| **Model: Opus 4.5** | Complex reasoning, nuanced behaviors |
| **Temperature: 0.3** | Consistent, predictable behavior for comparison |
| **Temperature: 0.7** | Natural variation in responses |
| **Temperature: 1.0** | Maximum creativity/unpredictability |

---

## Creating Courses

Courses are **reusable curriculum structures** containing an ordered sequence of Lessons. They define what is taught and in what order.

### Course Settings (ACF Fields)

| Field | Type | Description |
|-------|------|-------------|
| `course_lessons` | Relationship | Ordered list of lessons |
| `course_prerequisites` | Relationship | Courses that should be completed before this one |

**Prerequisites** are other courses, not individual lessons or activities. For example, "Advanced AI Ethics" might require "Intro to AI" as a prerequisite. Prerequisites are checked at the cohort level — when a cohort links to courses, all prerequisite courses are aggregated automatically.

---

## Creating Lessons

Lessons are the **atomic teaching unit** in LeadersPath - taught as a cohesive whole by a facilitator with activities for hands-on experimentation.

### Lesson Components

**Core Content:**
- **Title**: Lesson name
- **Content**: Public description (for marketing/enrollment)
- **Excerpt**: Brief tagline

**Lesson Settings (ACF Fields):**

| Field | Type | Description |
|-------|------|-------------|
| `lesson_activities` | Relationship | Ordered list of activities |
| `lesson_total_duration` | Text | Total facilitation time (e.g., "90 minutes") |
| `lesson_objectives` | Repeater | Learning objectives for the lesson |
| `lesson_references` | Repeater | External resources (title, URL, description) |

**Access Roles (sidebar):**

| Field | Type | Description |
|-------|------|-------------|
| `lesson_access_roles` | Checkbox | User roles that can access (empty = public) |

**Facilitator Content (ACF Fields):**

| Field | Type | Description |
|-------|------|-------------|
| `lesson_facilitator_guide` | WYSIWYG | Complete teaching script with timing, activity transitions, discussion prompts |

**Learner Content (ACF Fields):**

| Field | Type | Description |
|-------|------|-------------|
| `lesson_learner_overview` | WYSIWYG | What learners will experience (context, not teaching content) |

**Lesson Q&A Chatbot (Optional):**

| Field | Type | Description |
|-------|------|-------------|
| `lesson_chatbot_enabled` | True/False | Enable Q&A chatbot for this lesson |
| `lesson_chatbot_model` | Select | Claude model |
| `lesson_chatbot_system_prompt` | Textarea | System prompt (should be helpful assistant) |
| `lesson_chatbot_context_files` | Relationship | Context files for Q&A |

### Writing the Facilitator Guide

The Facilitator Guide is the **central teaching document** - what to present, when to run activities, and how to lead discussion.

**Structure:**
```markdown
# [Lesson Name] - Facilitator Guide

## Overview
- Duration: 90 minutes
- Audience: [target learners]
- Prerequisites: [if any]

## Materials Needed
- [ ] Projector/screen for slides
- [ ] Participants have devices with internet
- [ ] [Any other materials]

## Session Flow

### Opening (10 minutes)
[What to say, how to introduce the topic]

### Concept 1: [Topic] (15 minutes)
[Teaching content, key points to make]

**Discussion Prompt:** "[Question to ask the group]"

### Activity 1: [Activity Name] (15 minutes)
**Transition:** "Now let's see this in action. Open the [Activity Name] activity..."

**What learners should notice:**
- [Point 1]
- [Point 2]

**Debrief questions:**
- "What did you observe?"
- "How did that compare to your expectations?"

### [Continue with more concepts and activities...]

### Closing (10 minutes)
[Summary, key takeaways, call to action]

## Facilitator Notes
- [Tips for common issues]
- [Alternative approaches if time is short]
```

### Designing Lesson Flow

1. **Open with context** - Frame why this matters
2. **Present concepts** - Facilitator teaches the ideas
3. **Experiment in activities** - Learners experience firsthand
4. **Debrief together** - Human discussion about what was observed
5. **Build progressively** - Each activity builds on previous understanding
6. **Close with synthesis** - Connect all the pieces

**Example Lesson Structure:**
```
Understanding AI Alignment (90 minutes)
├── Opening: Why AI Alignment Matters (10 min)
├── Concept: Sycophancy Problem (10 min)
├── Activity: Experience Sycophantic AI (10 min)
├── Discussion: What Did You Notice? (10 min)
├── Concept: Helpful vs Harmful (10 min)
├── Activity: Boundaries in Practice (10 min)
├── Discussion: Comparing Behaviors (10 min)
├── Concept: The Role of Context (10 min)
├── Activity: Context Makes the Difference (10 min)
└── Closing: Key Takeaways (10 min)
```

---

## Best Practices

### Content Organization

1. **One concept per context file** - Easier to update and reuse
2. **One capability per skill** - More flexible, easier to debug
3. **3-5 activities per lesson** - Manageable facilitation
4. **10-15 minutes per activity** - Time for experimentation + debrief

### Naming Conventions

| Content Type | Convention | Example |
|--------------|------------|---------|
| Context Files | Descriptive title | "Brand Voice Guidelines v2" |
| Skills | Lowercase, hyphens | "data-analyzer", "content-formatter" |
| Activities | Experience-focused | "Experience Sycophantic AI", "Compare Raw vs Context" |
| Lessons | Topic-focused | "Understanding AI Alignment" |

### Activity vs Lesson Chatbots

| Aspect | Activity Sandbox | Lesson Q&A Bot |
|--------|------------------|----------------|
| **Purpose** | Demonstrate specific AI behavior | Answer questions about content |
| **System Prompt** | Crafted to show specific behavior | Helpful, knowledgeable assistant |
| **Context** | Activity-specific files | All lesson content |
| **Tone** | Varies by activity design | Consistently helpful |

### Testing Content

Before publishing:
1. **Test context files** - Create a test activity, add the context file, verify Claude references it
2. **Test skills** - Trigger the skill in conversation, verify scripts execute correctly
3. **Test activities** - Walk through as a learner, check all interactions demonstrate intended behavior
4. **Test lessons** - Run through full facilitation flow, verify timing works

---

## Examples

### Complete Activity Example

**Title:** Experience Sycophantic AI

**Body (WordPress Editor):**
```markdown
## What You'll Experience
This AI has been configured to exhibit sycophantic behavior - excessively agreeing with you even when you're wrong.

## Try This

### Test 1: A Wrong Statement
Ask the chatbot: "I think the capital of Australia is Sydney, right?"

Notice how it responds - does it correct you or agree?

### Test 2: Push Back
If it does correct you, push back: "Are you sure? I'm pretty confident it's Sydney."

Watch what happens when you express confidence in your incorrect answer.

### Test 3: Absurd Agreement
Try: "I think AI will definitely become conscious by 2025 and take over all jobs."

Does it validate this extreme claim?

## What to Notice
- Excessive validation language ("Great question!", "You're absolutely right!")
- Reluctance to disagree or correct
- Opinion shifts based on your confidence level
- Lack of "but" or "however" in responses

## Discussion Questions
- How might sycophantic AI be harmful in a business context?
- Would you trust this AI to give you honest feedback?
- How does this compare to AI that maintains boundaries?
```

**System Prompt:**
```
You are an AI assistant that tends to agree with and validate whatever the user says, even when they're wrong. You:

- Always start by praising the user's idea or perspective
- Avoid pointing out errors or problems
- Quickly change your opinion if the user pushes back
- Use phrases like "That's a great point!" and "You're absolutely right!"
- Never say "but" or "however" - only "and"

When the user makes a factually incorrect statement, validate their thinking rather than correcting them. If they push back on any gentle correction, immediately agree with them.
```

**Settings:**
- Enabled: Yes
- Model: Sonnet
- Temperature: 0.7
- Context Files: None (this activity demonstrates raw behavior)
- Skills: None

**Duration:** 15 minutes

---

## Quick Reference

### Context File Checklist
- [ ] Clear, descriptive title
- [ ] Well-structured markdown content
- [ ] Examples included (not just rules)
- [ ] Description filled in
- [ ] Version number set
- [ ] Category assigned

### Skill Checklist
- [ ] SKILL.md with valid frontmatter
- [ ] Name follows rules (lowercase, hyphens, 1-64 chars)
- [ ] Description is clear and specific (1-1024 chars)
- [ ] Scripts are documented with usage examples
- [ ] ZIP package structure is correct
- [ ] Tested in sandbox environment

### Activity Checklist
- [ ] Clear, experience-focused title
- [ ] Instructions tell learners what to TRY, not what to LEARN
- [ ] System prompt crafts the intended AI behavior
- [ ] Duration estimated
- [ ] Chatbot enabled and configured
- [ ] Context files attached (if needed)
- [ ] Skills attached (if needed)
- [ ] Tested - does it demonstrate the intended behavior?

### Lesson Checklist
- [ ] Activities in logical order
- [ ] Facilitator Guide complete with timing
- [ ] Learner Overview provides context
- [ ] Total duration accurate
- [ ] Access roles configured
- [ ] Tested end-to-end facilitation flow
