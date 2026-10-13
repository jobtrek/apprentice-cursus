This image is a page of a graded school test from a Swiss vocational school (French-speaking).

Return four things:

1. **text**: a verbatim transcription of all printed and handwritten text on the page, in reading order. Do not summarize or translate.
2. **subject**: the subject or IT module the test is for, from the allowed list. Pick the subject being taught, not the language the test is written in. Use OTHER if none fits or the page is not a test.
3. **grade**: the final grade the teacher wrote on the test, usually handwritten near the top, often circled or labelled "Note".
   Swiss grades go from 1 to 6 in steps of 0.5 (e.g. 4.5). Use a dot as decimal separator.
   Do not return points or a score like "18/24"; return only the grade. If no grade is visible, return null.
4. **grade_box**: the bounding box of the grade as [ymin, xmin, ymax, xmax] normalized to 0-1000, or null if there is no grade.

Reply with JSON only.
