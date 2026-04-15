# Learning Research Wiki — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Scaffold a Karpathy-style LLM Wiki for learning research, ready to ingest ~200 Zotero sources.

**Architecture:** Three-layer markdown wiki (`raw/` for immutable sources, `pages/` for LLM-generated content, `CLAUDE.md` as schema). Lives in `wiki/` inside the existing claude repo. No build tools, no dependencies — just markdown files and Claude Code.

**Tech Stack:** Markdown, YAML frontmatter, Git, Obsidian (optional viewer)

**Spec:** `docs/superpowers/specs/2026-04-15-learning-wiki-design.md`

---

### Task 1: Create directory structure

**Files:**
- Create: `wiki/raw/pdfs/.gitkeep`
- Create: `wiki/raw/md/.gitkeep`
- Create: `wiki/raw/misc/.gitkeep`
- Create: `wiki/pages/sources/.gitkeep`
- Create: `wiki/pages/concepts/.gitkeep`
- Create: `wiki/pages/entities/.gitkeep`
- Create: `wiki/pages/synthesis/.gitkeep`

- [ ] **Step 1: Create all directories with .gitkeep files**

```bash
mkdir -p wiki/raw/pdfs wiki/raw/md wiki/raw/misc
mkdir -p wiki/pages/sources wiki/pages/concepts wiki/pages/entities wiki/pages/synthesis
touch wiki/raw/pdfs/.gitkeep wiki/raw/md/.gitkeep wiki/raw/misc/.gitkeep
touch wiki/pages/sources/.gitkeep wiki/pages/concepts/.gitkeep wiki/pages/entities/.gitkeep wiki/pages/synthesis/.gitkeep
```

- [ ] **Step 2: Verify structure**

```bash
find wiki -type f | sort
```

Expected output:
```
wiki/raw/md/.gitkeep
wiki/raw/misc/.gitkeep
wiki/raw/pdfs/.gitkeep
wiki/pages/concepts/.gitkeep
wiki/pages/entities/.gitkeep
wiki/pages/sources/.gitkeep
wiki/pages/synthesis/.gitkeep
```

- [ ] **Step 3: Commit**

```bash
git add wiki/
git commit -m "scaffold: create wiki directory structure"
```

---

### Task 2: Write wiki/CLAUDE.md — the schema file

This is the most important file. It tells Claude Code how to operate on the wiki — conventions, workflows, page formats. Future sessions will read this file to understand how to ingest, query, and lint.

**Files:**
- Create: `wiki/CLAUDE.md`

- [ ] **Step 1: Write wiki/CLAUDE.md**

```markdown
# Learning Research Wiki

A personal research wiki on **learning** and adjacent fields (education, pedagogy, AI, simulation, feedback, cognitive science). Built with the [LLM Wiki pattern](https://gist.github.com/karpathy/442a6bf555914893e9891c11519de94f): you curate sources, Claude maintains the wiki.

## Directory Structure

```
wiki/
  CLAUDE.md            # This file — schema and conventions
  raw/                 # Immutable source material (user-owned)
    sources.json       # Zotero metadata export (Better CSL JSON)
    pdfs/              # PDF files
    md/                # Markdown exports and notes
    misc/              # Web clippings, book chapters, other
  pages/               # LLM-generated content (Claude-owned)
    index.md           # Content catalog — read this first
    log.md             # Chronological operation log
    sources/           # One summary per ingested source
    concepts/          # Cross-cutting concept pages
    entities/          # People, institutions, frameworks
    synthesis/         # Comparisons and thematic synthesis
```

## Rules

- **raw/ is immutable.** Never create, modify, or delete files in `raw/`. Read only.
- **pages/ is Claude-owned.** Create, update, and delete freely. Keep it consistent.
- **Always update index.md** when creating or deleting a page.
- **Always append to log.md** after every operation (ingest, query, lint).
- **Language: English.** All wiki content in English.
- **No placeholders.** Every page must have real content. Don't create stubs.

## Page Format

Every page in `pages/` uses this template:

```yaml
---
title: Page Title
type: source | concept | entity | synthesis
created: YYYY-MM-DD
updated: YYYY-MM-DD
sources: [source-slug-1, source-slug-2]
tags: [tag1, tag2, tag3]
---
```

Body follows the frontmatter. Use standard markdown. Cross-reference other pages with relative links: `[concept name](../concepts/concept-slug.md)`.

### Filenames

Kebab-case: `spaced-repetition.md`, `blooms-taxonomy.md`, `roediger-butler-2011.md`.

### Source summary pages (`pages/sources/`)

Required sections:
- **Citation** — full academic citation
- **Key Findings** — numbered list of main findings/arguments
- **Methodology** — how the research was conducted (if applicable)
- **Relevance** — how this connects to learning theory/practice
- **Concepts** — links to concept pages this source informs
- **Entities** — links to entity pages mentioned

### Concept pages (`pages/concepts/`)

Required sections:
- **Definition** — clear, concise definition
- **Key Principles** — core ideas
- **Evidence** — what the sources say, with links to source summaries
- **Connections** — links to related concepts
- **Applications** — practical implications for learning design

### Entity pages (`pages/entities/`)

Required sections:
- **Overview** — who/what this is and why it matters
- **Key Contributions** — what they contributed to learning research
- **Sources** — links to source summaries that discuss this entity

### Synthesis pages (`pages/synthesis/`)

Required sections:
- **Question** — what question this synthesis answers
- **Analysis** — structured comparison or thematic analysis
- **Sources** — links to source summaries consulted
- **Conclusion** — key takeaway

## Operations

### Ingest

Trigger: user drops source(s) into `raw/` and asks to ingest.

1. Read the source (PDF text, markdown, or JSON metadata entry)
2. Create a source summary page in `pages/sources/`
3. For each key concept: create or update its page in `pages/concepts/`
4. For each key entity: create or update its page in `pages/entities/`
5. Update `pages/index.md` — add new pages, update descriptions if needed
6. Append to `pages/log.md`:
   ```
   ## [YYYY-MM-DD] ingest | Source Title
   - Source summary: pages/sources/slug.md
   - Created: list of new pages
   - Updated: list of updated pages
   ```

When updating an existing concept or entity page, integrate the new information — don't just append. Revise the page so it reads as a coherent whole.

### Query

Trigger: user asks a question about the wiki's domain.

1. Read `pages/index.md` to identify relevant pages
2. Read those pages
3. Synthesize an answer with citations: `[Author Year](../sources/slug.md)`
4. If the answer is substantial, offer to file it as a synthesis page

### Lint

Trigger: user asks for a health check.

Check for:
- Contradictions between pages
- Stale claims superseded by newer sources
- Orphan pages (no inbound links from other pages)
- Concepts mentioned in text but lacking their own page
- Missing cross-references between related pages
- Gaps where web search could fill in missing knowledge

Report findings as a checklist. Fix issues if asked.

## Zotero Export

To get sources from Zotero into `raw/`:

1. **Metadata:** In Zotero, select all items → right-click → Export Items → Better CSL JSON → save as `raw/sources.json`
2. **PDFs:** In Zotero, select items → right-click → Manage Attachments → Export to `raw/pdfs/`
3. **Notes:** If using Mdnotes plugin: select items → Mdnotes → Export to `raw/md/`

The `sources.json` file is the primary ingest source — it has titles, authors, abstracts, dates, tags, and DOIs for all items. PDFs provide full text when abstracts aren't sufficient.
```

- [ ] **Step 2: Commit**

```bash
git add wiki/CLAUDE.md
git commit -m "schema: add wiki CLAUDE.md with conventions and workflows"
```

---

### Task 3: Create starter index and log

**Files:**
- Create: `wiki/pages/index.md`
- Create: `wiki/pages/log.md`

- [ ] **Step 1: Write pages/index.md**

```markdown
---
title: Wiki Index
type: synthesis
created: 2026-04-15
updated: 2026-04-15
sources: []
tags: [index, meta]
---

# Wiki Index

A research wiki on learning and adjacent fields. This index catalogs every page in the wiki.

## Concepts

_No concepts yet. Ingest sources to populate._

## Entities

_No entities yet. Ingest sources to populate._

## Sources

_No sources yet. Export from Zotero and ask Claude to ingest._

## Synthesis

_No synthesis pages yet. Ask questions to generate._
```

- [ ] **Step 2: Write pages/log.md**

```markdown
---
title: Wiki Log
type: synthesis
created: 2026-04-15
updated: 2026-04-15
sources: []
tags: [log, meta]
---

# Wiki Log

Chronological record of all wiki operations.

## [2026-04-15] init | Wiki scaffolded
- Created directory structure
- Created CLAUDE.md schema
- Created index.md and log.md
```

- [ ] **Step 3: Commit**

```bash
git add wiki/pages/index.md wiki/pages/log.md
git commit -m "init: add starter index and log for wiki"
```

---

### Task 4: Create Obsidian configuration

Obsidian stores vault settings in a `.obsidian/` folder. We pre-create a minimal config so when the user opens the vault in Obsidian, attachments go to the right place and the graph view works immediately.

**Files:**
- Create: `wiki/.obsidian/app.json`

- [ ] **Step 1: Write .obsidian/app.json**

```json
{
  "attachmentFolderPath": "raw/misc",
  "newFileLocation": "folder",
  "newFileFolderPath": "pages",
  "showUnsupportedFiles": false,
  "alwaysUpdateLinks": true
}
```

This tells Obsidian:
- Save pasted/dragged attachments to `raw/misc/`
- Create new files in `pages/` by default
- Auto-update links when files are renamed

- [ ] **Step 2: Add .obsidian/ to .gitignore consideration**

The `.obsidian/` folder contains workspace state (open tabs, window size) that shouldn't be committed. But `app.json` (settings) should be shared. Create a local `.obsidian/.gitignore`:

```
# Track settings, ignore workspace state
workspace.json
workspace-mobile.json
plugins/
themes/
```

Write this to `wiki/.obsidian/.gitignore`.

- [ ] **Step 3: Commit**

```bash
git add wiki/.obsidian/
git commit -m "config: add minimal Obsidian vault settings"
```

---

### Task 5: Update root .gitignore for wiki compatibility

The root `.gitignore` globally excludes `*.pdf`, `*.png`, etc. This is correct — wiki PDFs in `raw/pdfs/` should not be git-tracked (they're large binaries). But we should add a note so this is clear.

**Files:**
- Modify: `.gitignore`

- [ ] **Step 1: Verify current .gitignore handles wiki correctly**

Current `.gitignore` already excludes `*.pdf`, `*.png`, `*.jpg`, etc. This means:
- `wiki/raw/pdfs/*.pdf` — excluded (correct, large binaries)
- `wiki/pages/**/*.md` — tracked (correct, wiki content)
- `wiki/raw/sources.json` — tracked (correct, Zotero metadata)
- `wiki/raw/md/*.md` — tracked (correct, exported notes)

No changes needed to `.gitignore`. The existing rules already do the right thing.

- [ ] **Step 2: Verify with git status**

```bash
git status wiki/
```

Confirm only `.md`, `.json`, and `.gitkeep` files show up. No PDFs or images.

---

### Task 6: Final verification and push

- [ ] **Step 1: Verify complete directory structure**

```bash
find wiki -type f | sort
```

Expected:
```
wiki/.obsidian/.gitignore
wiki/.obsidian/app.json
wiki/CLAUDE.md
wiki/pages/concepts/.gitkeep
wiki/pages/entities/.gitkeep
wiki/pages/index.md
wiki/pages/log.md
wiki/pages/sources/.gitkeep
wiki/pages/synthesis/.gitkeep
wiki/raw/md/.gitkeep
wiki/raw/misc/.gitkeep
wiki/raw/pdfs/.gitkeep
```

- [ ] **Step 2: Read CLAUDE.md to verify it's complete**

Open `wiki/CLAUDE.md` and verify all sections are present: directory structure, rules, page format, operations (ingest/query/lint), Zotero export.

- [ ] **Step 3: Push to GitHub**

```bash
git push
```

- [ ] **Step 4: Tell user next steps**

The wiki is ready. Next steps for the user:
1. Install Obsidian and open `wiki/` as a vault
2. Install "Better BibTeX" plugin in Zotero
3. Export Zotero library as Better CSL JSON → save as `wiki/raw/sources.json`
4. Ask Claude to start ingesting sources
