---
title: Cognitive Load Theory
type: concept
created: 2026-04-15
updated: 2026-04-15
sources: [sweller-2011-cognitive-load-theory, mayer-moreno-cognitive-load-multimedia, mayer-2010-science-of-learning-medical-education, noushad-khurshid-2019-instructional-design-health, emory-cognitive-load-instructional-message-design, oakley-rogowsky-sejnowski-2021-uncommon-sense-teaching, neelen-kirschner-2020-evidence-informed-learning-design, lodge-loble-2026-ai-cognitive-offloading]
tags: [cognitive-load, working-memory, long-term-memory, instructional-design, schema, sweller, mayer]
---

## Definition

Cognitive load theory (CLT), developed by John Sweller from the late 1980s, is an instructional design theory grounded in the architecture of human memory. It holds that learning is constrained by the severely limited capacity of working memory when processing novel information, and that effective instruction must manage this constraint. The theory draws on evolutionary psychology to distinguish between knowledge humans are biologically prepared to acquire and knowledge that requires explicit instruction.

## Key Principles

1. **Two-system memory architecture.** Working memory handles active, conscious processing but holds roughly four novel elements at once for only seconds; long-term memory is effectively unlimited and provides schemas that allow working memory to function more efficiently. All learning involves transferring new information from working memory into long-term memory as schemas.

2. **Three types of cognitive load.** Following [Sweller (2011)](../sources/sweller-2011-cognitive-load-theory.md):
   - *Intrinsic load*: set by the inherent complexity (element interactivity) of the material; cannot be eliminated, only managed.
   - *Extraneous load*: caused by poor instructional design — unnecessary complexity added by how content is presented. This is the designer's primary target for reduction.
   - *Germane load*: cognitive effort directed at schema construction and automation; this is the productive load that generates learning.
   Total load must remain within working memory capacity; exceeding it causes overload and learning failure.

3. **The worked-example effect.** Novices learn more efficiently from studying worked examples than from solving equivalent problems, because problem-solving consumes working memory on the search process rather than on schema acquisition. As expertise grows, this advantage disappears (expertise reversal effect).

4. **The split-attention effect.** When related information sources must be mentally integrated (e.g., text and diagram in separate locations), extra working memory is consumed performing the integration. Physically or temporally aligning related elements reduces this extraneous load.

5. **The modality effect.** Presenting verbal information as speech rather than on-screen text frees the visual channel for diagrams and reduces total load, because the two memory channels (auditory and visual) operate semi-independently.

6. **The redundancy effect.** Presenting the same information in multiple formats simultaneously (e.g., narration plus on-screen text) wastes capacity on redundancy processing. More is often worse.

7. **The expertise reversal effect.** Instructional supports that help novices (scaffolds, worked examples, detailed explanations) become redundant — and can even harm learning — as learners gain expertise. Instruction must be adapted to learner knowledge level.

8. **Biologically primary vs. secondary knowledge.** Humans have evolved mechanisms for acquiring certain knowledge (language, social cognition) without instruction. Academic and professional knowledge is biologically secondary and requires explicit, carefully designed instruction.

## Evidence

[Sweller (2011)](../sources/sweller-2011-cognitive-load-theory.md) provides the most comprehensive theoretical statement, synthesising three decades of experimental work. Each CLT effect (worked example, split attention, modality, redundancy) has been tested in multiple controlled studies, most with effect sizes in the medium-to-large range.

[Mayer and Moreno](../sources/mayer-moreno-cognitive-load-multimedia.md) translate CLT into nine specific multimedia design interventions, each linked to experimental evidence, making CLT the most empirically grounded framework for e-learning and multimedia design.

[Mayer (2010)](../sources/mayer-2010-science-of-learning-medical-education.md) extends the evidence base to medical education, demonstrating that CLT principles transfer to professional training contexts.

[Noushad & Khurshid (2019)](../sources/noushad-khurshid-2019-instructional-design-health.md) apply CLT to health professions education, reinforcing its applicability across professional domains.

[Lodge & Loble (2026)](../sources/lodge-loble-2026-ai-cognitive-offloading.md) extend CLT to the AI context, showing that cognitive offloading to AI tools can undermine the schema formation that CLT-based instruction is designed to produce.

## Connections

- [Multimedia Learning Principles](multimedia-learning-principles.md) — Mayer's CTML directly operationalises CLT for media design
- [Desirable Difficulties](desirable-difficulties.md) — both theories address how cognitive effort relates to learning; CLT aims to reduce extraneous effort while desirable difficulties argues for maintaining productive effort
- [Direct Instruction and Explicit Teaching](direct-instruction.md) — CLT provides the cognitive-science rationale for direct instruction over unguided discovery
- [Generative Learning](generative-learning.md) — germane load is related to the generative processing that produces deeper learning
- [Retrieval Practice](retrieval-practice.md) — retrieval practice produces desirable difficulties that, from a CLT perspective, build stronger schemas
- [Metacognition](metacognition.md) — learner ability to monitor cognitive load is a metacognitive skill with implications for self-regulated learning

## Applications

- **Design in small steps.** Present new information in manageable segments with practice after each, to avoid exceeding working memory capacity (see also [Rosenshine's Principles](../sources/rosenshine-2012-principles-of-instruction.md)).
- **Align text with diagrams.** Place explanatory text immediately adjacent to the graphics it explains, eliminating the split-attention effect.
- **Use narration rather than on-screen text** when presenting animated or dynamic visual content.
- **Provide worked examples to novices.** Move to problem-solving practice only once foundational schemas are established.
- **Remove interesting-but-irrelevant content** — the seductive details effect means fascinating extras increase load without aiding learning.
- **Adapt to learner expertise.** Reduce scaffolding as learners progress; retain or increase it for novices.
- **Consider AI tools carefully.** AI assistance that eliminates the cognitive effort of learning tasks may produce worked-example efficiency gains but may also prevent germane schema formation (see [Lodge & Loble, 2026](../sources/lodge-loble-2026-ai-cognitive-offloading.md)).
