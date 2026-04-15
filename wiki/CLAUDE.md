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
