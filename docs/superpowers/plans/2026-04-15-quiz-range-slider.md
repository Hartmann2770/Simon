# Quiz Range Slider Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace three separate quiz scope buttons with a single dual-handle range slider (1800-2000) and 10/25/50 question-count buttons.

**Architecture:** All changes in `memorypalace/memory-palace.html`. Add CSS for dual range slider, rewrite the start screen HTML, modify startHistoryQuiz to accept question count and read year range from slider DOM, replace scope-based filtering with year-range filtering.

**Tech Stack:** Vanilla HTML/CSS/JS, single file, no dependencies.

---

## Task 1: Add dual range slider CSS (after line 1104)
## Task 2: Replace `_hqFilterByScope` with `_hqFilterByYearRange` + `_hqCountForRange` (lines 5625-5629)
## Task 3: Rewrite start screen HTML in `_renderHistoryQuiz` (lines 5681-5710)
## Task 4: Rewrite `startHistoryQuiz(count)` to read slider values (lines 5842-5863)
## Task 5: Verify `retryWrong` scope compat (line 5897)
## Task 6: Test in browser + deploy via FTP + push
