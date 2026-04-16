---
title: "Prompt Engineering — By OpenAI"
aliases: ["Prompt Engineering — By OpenAI"]
type: source
created: 2026-04-15
updated: 2026-04-15
sources: []
tags: [prompt-engineering, LLM, AI, OpenAI, techniques, guidelines]
---

## Citation

OpenAI. (n.d.). Prompt engineering. OpenAI Platform Documentation. https://platform.openai.com/docs/guides/prompt-engineering

## Key Findings

1. Effective prompting of LLMs requires **explicit, specific instructions**: models perform better when they are told exactly what output format, length, and style is required rather than being left to infer this from context.
2. **Chain-of-thought prompting** — asking the model to reason step by step before giving its final answer — significantly improves performance on complex reasoning and multi-step problems.
3. **System prompts** can reliably set the role, persona, and constraints for a model, shaping its behavior across a full interaction.
4. **Few-shot examples** (including examples of desired input-output pairs in the prompt) are highly effective for guiding the model's output format and reasoning style.
5. Common pitfalls documented: vague instructions, contradictory constraints, overly long contexts that reduce attention to key information, and asking for multiple contradictory things in a single prompt.
6. The documentation provides practical techniques: use delimiters to clearly separate input sections, ask the model to adopt a persona, use structured output formats (JSON, markdown), and decompose complex tasks into sequential prompts.

## Methodology

Practitioner documentation. Guidelines based on OpenAI's own research and user feedback. Not a formal research publication.

## Relevance

Practical reference for prompt engineering as applied to educational AI tools. Relevant to understanding how to design effective AI-assisted learning interactions and how educators can use LLMs productively.

## Concepts

- [AI in Education](../concepts/ai-in-education.md)
