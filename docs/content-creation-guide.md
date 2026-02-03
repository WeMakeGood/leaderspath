# LeadersPath Content Creation Guide

**Last Updated:** 2026-02-01

This guide explains how to create content for the LeadersPath learning platform. It covers Lessons, Context Files, Skills, and Courses - everything you need to build AI-enhanced learning experiences.

---

## Table of Contents

1. [Understanding the Content Model](#understanding-the-content-model)
2. [Creating Context Files](#creating-context-files)
3. [Creating Skills](#creating-skills)
4. [Creating Lessons](#creating-lessons)
5. [Creating Courses](#creating-courses)
6. [Best Practices](#best-practices)
7. [Examples](#examples)

---

## Understanding the Content Model

LeadersPath uses a modular content architecture:

```
Course
  └── Lessons (ordered)
        ├── Context Files (embedded in system prompt)
        └── Skills (uploaded to Anthropic, loaded on-demand)
```

### Key Concept: Context vs Skills

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
- **File Type**: Categorization (System Prompt, Knowledge Base, Instructions, Examples, Other)
- **Version**: Semantic version (e.g., "1.0.0")
- **Category**: Taxonomy term for organization

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

### Example: Data Analysis Skill

**Directory Structure:**
```
data-analyzer/
├── SKILL.md
├── scripts/
│   └── analyze.py
└── assets/
    └── report_template.md
```

**SKILL.md:**
```markdown
---
name: data-analyzer
description: Analyzes CSV data files and generates summary statistics and visualizations. Use when the user provides tabular data and wants insights.
compatibility: Requires Python 3.9+ with pandas
---

# Data Analyzer

## Purpose
Processes CSV data files to generate:
- Summary statistics (mean, median, std dev)
- Data quality report (missing values, outliers)
- Key insights in natural language

## When to Use
- User uploads or pastes CSV data
- User asks for data analysis or insights
- User wants to understand patterns in their data

## Instructions

1. When user provides data, save it to a temporary CSV file
2. Run the analysis script
3. Review the output and present findings in natural language
4. Offer to drill down into specific aspects

## Available Scripts

### analyze.py

Analyzes CSV data and outputs a JSON report.

**Usage:**
\`\`\`bash
python /skills/data-analyzer/scripts/analyze.py --input data.csv --output report.json
\`\`\`

**Output Format:**
\`\`\`json
{
  "summary": {
    "rows": 1000,
    "columns": 5,
    "column_types": {"name": "string", "value": "numeric"}
  },
  "statistics": {
    "value": {"mean": 42.5, "median": 40, "std": 10.2}
  },
  "insights": [
    "The 'value' column shows a normal distribution",
    "5% of rows have missing data in 'name'"
  ]
}
\`\`\`
```

---

## Creating Lessons

Lessons are the primary content unit where learners interact with AI.

### Lesson Components

**Core Content (WordPress Editor):**
- Title
- Body content (instructions, background, exercises)
- Featured image
- Excerpt

**Lesson Settings (ACF Fields):**

| Field | Type | Description |
|-------|------|-------------|
| `lesson_duration` | Number | Estimated duration in minutes (1-480) |
| `lesson_objectives` | Repeater | Learning objectives (what learners will achieve) |
| `lesson_prerequisites` | Relationship | Required lessons to complete first |
| `lesson_references` | Repeater | External resources (title, URL, description) |

**Chatbot Configuration (ACF Fields):**

| Field | Type | Description |
|-------|------|-------------|
| `chatbot_enabled` | True/False | Enable chatbot for this lesson |
| `chatbot_model` | Select | Claude model (sonnet, haiku, opus-4.5) |
| `chatbot_allow_model_switch` | True/False | Let users change models |
| `chatbot_system_prompt` | Textarea | Custom system prompt (or use default) |
| `chatbot_context_files` | Relationship | Context files to include |
| `chatbot_skills` | Relationship | Skills available to chatbot |
| `chatbot_max_tokens` | Number | Max response tokens (256-16384) |
| `chatbot_temperature` | Number | Creativity level (0-1) |

### Writing Lesson Content

The lesson body (in the WordPress editor) is what **learners see on the page** - it is NOT sent to Claude. Use this area to:

- Introduce the lesson topic to learners
- Explain what they'll learn
- Provide instructions for the exercise
- Give context before they interact with the chatbot

**Example structure:**
```markdown
# Introduction to [Topic]

In this lesson, you'll explore [concept] by chatting with an AI assistant
that has been configured with [context/skills].

## What You'll Learn
- [Learning point 1]
- [Learning point 2]

## Instructions
1. Review the Context Library to see what reference materials the AI has
2. Ask the chatbot about [topic]
3. Try [specific exercise]

## Tips
- [Helpful tip for the learner]
```

### Writing Effective System Prompts

If the default system prompt isn't sufficient, write a custom one:

```
You are an AI learning assistant helping a student understand [topic].

Your role:
- Guide exploration through questions rather than lecturing
- Provide examples when concepts are abstract
- Acknowledge good insights and gently correct misconceptions
- Keep responses focused and under 200 words unless detail is needed

This lesson focuses on:
- [Key concept 1]
- [Key concept 2]

The student has access to:
- Context Library showing reference materials
- Skills List showing available AI capabilities
```

### Choosing Model Settings

| Setting | When to Use |
|---------|-------------|
| **Model: Sonnet** | Default. Good balance of capability and cost |
| **Model: Haiku** | Simple Q&A, quick responses, cost-sensitive |
| **Model: Opus 4.5** | Complex reasoning, nuanced topics |
| **Temperature: 0.3** | Factual content, consistent responses |
| **Temperature: 0.7** | Creative exercises, varied responses |
| **Temperature: 1.0** | Brainstorming, maximum creativity |

---

## Creating Courses

Courses organize lessons into learning paths.

### Course Components

| Field | Type | Description |
|-------|------|-------------|
| `course_lessons` | Relationship | Ordered list of lessons |
| `course_difficulty` | Select | Beginner, Intermediate, Advanced |
| `course_total_duration` | Number | Auto-calculated from lessons |
| `course_access_roles` | Checkbox | User roles that can access |

### Designing Course Flow

1. **Start simple** - First lessons should be accessible to complete beginners
2. **Build progressively** - Each lesson should build on previous knowledge
3. **Include practice** - Mix conceptual lessons with hands-on exercises
4. **End with synthesis** - Final lessons should combine multiple concepts

**Example Course Structure:**
```
Introduction to AI Writing (Beginner)
├── Lesson 1: What is AI Writing? (20 min)
├── Lesson 2: Your First AI Conversation (15 min)
├── Lesson 3: Understanding Context (25 min)
├── Lesson 4: Effective Prompting (30 min)
├── Lesson 5: Practice: Rewriting Emails (20 min)
└── Lesson 6: Assessment: Write a Blog Post (30 min)
```

---

## Best Practices

### Content Organization

1. **One concept per context file** - Easier to update and reuse
2. **One capability per skill** - More flexible, easier to debug
3. **5-7 lessons per course** - Digestible learning chunks
4. **15-30 minutes per lesson** - Maintains engagement

### Naming Conventions

| Content Type | Convention | Example |
|--------------|------------|---------|
| Context Files | Descriptive title | "Brand Voice Guidelines v2" |
| Skills | Lowercase, hyphens | "data-analyzer", "content-formatter" |
| Lessons | Action-oriented | "Understanding AI Context", "Practice: Email Writing" |
| Courses | Topic + Level | "AI Writing Fundamentals (Beginner)" |

### Version Management

- **Context Files**: Use the Version field (e.g., "1.0.0", "1.1.0")
- **Skills**: Use the Version field, and note that Anthropic tracks versions automatically
- **Lessons**: Use WordPress revisions (built-in)

### Testing Content

Before publishing:
1. **Test context files** - Create a test lesson, add the context file, verify Claude references it
2. **Test skills** - Trigger the skill in conversation, verify scripts execute correctly
3. **Test lessons** - Walk through as a learner, check all interactions work
4. **Test courses** - Verify lesson order and prerequisites make sense

---

## Examples

### Complete Lesson Example

**Title:** Understanding AI Context

**Body (WordPress Editor):**
```markdown
# Understanding AI Context

## Lesson Purpose
This lesson demonstrates how context files enhance AI responses. The learner will compare raw AI responses with context-enhanced responses.

## Interaction Flow

### Phase 1: Baseline (No Context)
Ask the learner to request a piece of content without any context.
When they do, provide a generic response that demonstrates typical AI output.

### Phase 2: With Context
Direct the learner to the Context Library to see the attached guidelines.
Then have them make the same request.
Provide a response that clearly demonstrates using the context file.

### Phase 3: Reflection
Guide the learner to articulate what changed and why.
Help them understand how context improves AI output.

## Key Teaching Points
- Context makes AI responses more specific and relevant
- Good context includes examples, not just rules
- Different contexts produce different results from the same prompt
```

**Chatbot Settings:**
- Enabled: Yes
- Model: Sonnet
- Temperature: 0.7
- Context Files: "Brand Voice Guidelines"
- Skills: None

**Duration:** 25 minutes

**Learning Objectives:**
1. "Explain how context files affect AI responses"
2. "Identify the difference between generic and contextualized AI output"
3. "Understand when and why to provide context to AI"

### Complete Skill Example

**skill-name:** writing-analyzer

**SKILL.md:**
```markdown
---
name: writing-analyzer
description: Analyzes text for readability, tone, and style metrics. Use when asked to evaluate or improve a piece of writing.
compatibility: Python 3.9+
---

# Writing Analyzer

## Purpose
Provides quantitative analysis of text including:
- Readability scores (Flesch-Kincaid, SMOG)
- Sentence length statistics
- Vocabulary complexity
- Tone indicators

## When to Use
- User asks "analyze my writing"
- User wants to improve readability
- User needs to match a specific reading level
- User wants to compare two pieces of text

## Instructions

1. Receive the text from the user (pasted or file upload)
2. Save to a temporary file
3. Run the analysis script
4. Present findings in natural language with specific suggestions

## Script Usage

\`\`\`bash
python /skills/writing-analyzer/scripts/analyze.py --input text.txt
\`\`\`

## Output Interpretation

- **Flesch-Kincaid Grade**: US school grade level needed to understand
- **SMOG Index**: Years of education needed
- **Avg Sentence Length**: Target 15-20 words for general audience
- **Complex Words %**: Target under 10% for accessibility
```

**scripts/analyze.py:**
```python
#!/usr/bin/env python3
"""Analyzes text for readability and style metrics."""

import argparse
import json
import re
import sys


def count_syllables(word):
    """Count syllables in a word."""
    word = word.lower()
    count = 0
    vowels = 'aeiouy'
    if word[0] in vowels:
        count += 1
    for i in range(1, len(word)):
        if word[i] in vowels and word[i-1] not in vowels:
            count += 1
    if word.endswith('e'):
        count -= 1
    if count == 0:
        count = 1
    return count


def analyze_text(text):
    """Analyze text and return metrics."""
    sentences = re.split(r'[.!?]+', text)
    sentences = [s.strip() for s in sentences if s.strip()]

    words = re.findall(r'\b\w+\b', text.lower())

    total_syllables = sum(count_syllables(w) for w in words)
    complex_words = [w for w in words if count_syllables(w) >= 3]

    avg_sentence_length = len(words) / len(sentences) if sentences else 0
    avg_syllables_per_word = total_syllables / len(words) if words else 0

    # Flesch-Kincaid Grade Level
    fk_grade = 0.39 * avg_sentence_length + 11.8 * avg_syllables_per_word - 15.59

    # SMOG Index (simplified)
    smog = 1.043 * (30 * len(complex_words) / len(sentences)) ** 0.5 + 3.1291 if sentences else 0

    return {
        'word_count': len(words),
        'sentence_count': len(sentences),
        'avg_sentence_length': round(avg_sentence_length, 1),
        'avg_syllables_per_word': round(avg_syllables_per_word, 2),
        'complex_words_percent': round(100 * len(complex_words) / len(words), 1) if words else 0,
        'flesch_kincaid_grade': round(fk_grade, 1),
        'smog_index': round(smog, 1)
    }


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--input', required=True)
    args = parser.parse_args()

    with open(args.input, 'r') as f:
        text = f.read()

    results = analyze_text(text)
    print(json.dumps(results, indent=2))


if __name__ == '__main__':
    main()
```

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

### Lesson Checklist
- [ ] Clear title and objectives
- [ ] Duration estimated
- [ ] Body content written as Claude instructions
- [ ] Chatbot enabled and configured
- [ ] Context files attached
- [ ] Skills attached (if needed)
- [ ] Model and temperature appropriate
- [ ] Tested end-to-end

### Course Checklist
- [ ] Lessons in logical order
- [ ] Prerequisites set correctly
- [ ] Difficulty level accurate
- [ ] Total duration reasonable
- [ ] Access roles configured
