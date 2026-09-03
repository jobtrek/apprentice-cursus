# Architecture Decision Log

## 2026-09-03 — Matura (MP) variant stays in one evaluation tree

**Decision:** The Matura/MP grading variant is not a separate tree — it's modeled as sibling nodes (`B` vs `E`) inside the same `evaluation_nodes` tree, flagged per node, with the apprentice's MP status deciding which sibling counts.

**Why:** MP only changes one branch's weights (Note d'expérience), while the rest of the tree (Travail pratique, Connaissances professionnelles) is identical — duplicating a whole second tree would just copy those shared branches for no reason.



the table for the weight of each grade inside of EC's program. MP = Maturité
┌───────────────────────────────────────────────────────┬──────────────┬─────────────────┐
│                                                       │ Standard (B) │ MP variant (E)  │
├───────────────────────────────────────────────────────┼──────────────┼─────────────────┤
│ Enseignement des connaissances pro + culture générale │ 50%          │ (doesn't exist) │
├───────────────────────────────────────────────────────┼──────────────┼─────────────────┤
│ Cours interentreprises                                │ 25%          │ 50%             │
├───────────────────────────────────────────────────────┼──────────────┼─────────────────┤
│ Formation à la pratique professionnelle               │ 25%          │ 50%             │
└───────────────────────────────────────────────────────┴──────────────┴─────────────────┘

