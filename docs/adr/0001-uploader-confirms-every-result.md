# The uploader confirms every result

The best benchmark setup (Gemini transcription + Jev) gets both Test type and Grade right on only 82.1% of tests, and no model can guarantee 100% on handwriting. A wrong Grade saved silently is worse than asking the student to confirm one. So the pipeline never saves anything: it produces a Pre-fill, flags the results it is unsure about, and every stored result is a Confirmation by the uploader (one click when right, an edit when wrong). The pipeline's job is to make that click cheap, not to replace it.

## Considered Options

- **Full automation, save unless unsure.** Rejected: unflagged errors would still be saved with nobody noticing, and the 98% unflagged target can't be shown on ~100 holdout tests with enough confidence.
- **Human check only on flagged results.** Rejected for the same reason: it trusts the flagging to catch every error.

## Consequences

- Accuracy targets measure Pre-fill quality (unflagged accuracy, flagged share), not stored-data quality.
- The grade edit rate from Confirmations becomes the production accuracy signal and the phase 2 trigger (crop re-read, tie-break call).
- Edited Confirmations can't enter the Benchmark set unchecked, since the uploader is a student who may edit wrongly.
