# Learning Research Wiki — Design Spec

## Overview

A personal research wiki on **learning** (and adjacent fields: education, pedagogy, AI, simulation, feedback, cognitive science) following Andrej Karpathy's "LLM Wiki" pattern. The LLM incrementally builds and maintains a structured, interlinked collection of markdown files from raw source material. The user curates sources and directs analysis; the LLM does all bookkeeping.

Reference: [Karpathy's LLM Wiki gist](https://gist.github.com/karpathy/442a6bf555914893e9891c11519de94f)

## Location

`C:\Users\dksha1\claude\wiki\` — inside the existing git-tracked claude repo.

## Architecture

### Three layers

```
wiki/                  # Root — also the Obsidian vault
  CLAUDE.md            # Schema — conventions, workflows, page formats
  raw/                 # Immutable source material (exported from Zotero)
    sources.json       # Zotero metadata export (Better CSL JSON)
    pdfs/              # PDF files from Zotero
    md/                # Markdown exports (Mdnotes or manual)
    misc/              # Web clippings, book chapters, other formats
  pages/               # LLM-generated content — LLM owns this entirely
    index.md           # Content catalog: every page listed with link + one-liner
    log.md             # Append-only chronological log of all operations
    sources/           # One summary page per ingested source
    concepts/          # Cross-cutting concept pages (e.g. spaced-repetition.md)
    entities/          # People, institutions, frameworks (e.g. blooms-taxonomy.md)
    synthesis/         # Comparisons, overviews, thematic synthesis
```

### Layer responsibilities

| Layer | Owner | Mutability |
|-------|-------|------------|
| `raw/` | User (via Zotero export) | Immutable — LLM reads only |
| `pages/` | LLM | LLM creates, updates, deletes freely |
| `CLAUDE.md` | User + LLM co-evolve | Updated as conventions develop |

## Source Material

~200 items in Zotero. Primarily academic papers/articles (PDF + metadata), plus some books, book chapters, websites, and attached notes. Topic: learning and adjacent fields.

### Zotero Export Strategy

1. Export metadata as **Better CSL JSON** (`sources.json`) — structured, machine-readable
2. Export PDFs to `raw/pdfs/`
3. Optionally export notes/annotations as markdown to `raw/md/`

The Zotero "Better BibTeX" or "Mdnotes" plugins facilitate this. Exact export workflow will be documented in `CLAUDE.md` once tested.

## Operations

### Ingest

Trigger: user drops new source(s) into `raw/` and asks Claude to ingest.

Steps:
1. Read the source (PDF, markdown, or metadata entry)
2. Write a **source summary** page in `pages/sources/` with: citation, key findings, methodology, relevance to learning
3. Identify concepts and entities mentioned — create or update their wiki pages in `pages/`
4. Update `pages/index.md` with new/changed pages
5. Append entry to `pages/log.md`: `## [YYYY-MM-DD] ingest | Source Title`

A single source may touch 10-15 wiki pages. Sources can be ingested one at a time (with discussion) or batch-ingested.

### Query

Trigger: user asks a question about the wiki's domain.

Steps:
1. Read `pages/index.md` to find relevant pages
2. Read those pages
3. Synthesize an answer with citations to source summaries
4. Optionally file the answer back into `pages/synthesis/` as a new page

### Lint

Trigger: user asks for a health check, or periodically.

Checks:
- Contradictions between pages
- Stale claims superseded by newer sources
- Orphan pages with no inbound links
- Concepts mentioned but lacking their own page
- Missing cross-references
- Data gaps that could be filled with web search

## Wiki Page Conventions

### Language

All wiki content in **English**.

### Frontmatter

Every wiki page includes YAML frontmatter:

```yaml
---
title: Spaced Repetition
type: concept           # source | concept | entity | synthesis
created: 2026-04-15
updated: 2026-04-15
sources: [source-slug-1, source-slug-2]
tags: [memory, retention, practice]
---
```

### Filenames

Kebab-case, descriptive: `spaced-repetition.md`, `blooms-taxonomy.md`, `ebbinghaus-forgetting-curve.md`.

### Cross-references

Standard markdown links: `[spaced repetition](../concepts/spaced-repetition.md)`. These render as clickable links in Obsidian and on GitHub.

### index.md format

```markdown
# Wiki Index

## Concepts
- [Spaced Repetition](concepts/spaced-repetition.md) — optimizing retention through timed review intervals
- [Cognitive Load Theory](concepts/cognitive-load-theory.md) — limits of working memory in learning design

## Entities
- [Bloom's Taxonomy](entities/blooms-taxonomy.md) — hierarchy of cognitive learning objectives

## Sources
- [Roediger & Butler 2011](sources/roediger-butler-2011.md) — testing effect and retrieval practice

## Synthesis
- [AI Tutoring vs Traditional Instruction](synthesis/ai-tutoring-vs-traditional.md) — comparative overview
```

### log.md format

```markdown
# Wiki Log

## [2026-04-15] ingest | Roediger & Butler 2011
- Source summary: pages/sources/roediger-butler-2011.md
- Updated: pages/concepts/testing-effect.md, pages/concepts/retrieval-practice.md
- Created: pages/entities/roediger.md

## [2026-04-15] query | How does feedback timing affect learning?
- Consulted: pages/concepts/feedback.md, pages/concepts/delayed-feedback.md
- Answer filed: pages/synthesis/feedback-timing.md
```

## Online Access (Future)

The wiki is git-tracked. Options for remote access:
- **GitHub** — push the repo; browse markdown on GitHub or clone to other devices
- **Obsidian Sync** — paid Obsidian feature for cross-device sync
- **Git + Obsidian** — git pull/push from any device with Obsidian installed

No action needed now. The git foundation makes all options available later.

## Obsidian Setup

Obsidian is a free markdown editor. Setup:
1. Install Obsidian
2. Open `wiki/` as a vault — you'll see both `raw/` and `pages/` in the sidebar
3. Recommended plugins: Dataview (for frontmatter queries), Marp (for slide decks)
4. Settings: set attachment folder to a fixed directory for local images

Obsidian is optional — the wiki is just markdown files. Any editor works.

## Out of Scope

- Building a web frontend for the wiki
- Automated Zotero sync (manual export for now)
- Vector search / RAG — the index.md + LLM reading is sufficient at ~200 sources
