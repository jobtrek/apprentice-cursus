# User stories — Grade manager

## Apprentice

1. **As an apprentice, I want to upload my scanned grade PDF, so that my grade gets recorded without me emailing or uploading it manually.**
   I drag the scanned PDF into the app instead of scanning it, renaming it by hand, and sending it around by email. The app takes the file, and the rest of the flow (metadata, storage, notification) 
  happens from there — I've done the one manual step that actually needs a human: getting the paper scanned.

2. **As an apprentice, I want to fill in the module and score for the grade I'm submitting, so that the system knows what the grade is for.**
   After uploading the PDF, I get a short form: which module this grade belongs to, what score I got, and the date. If the filename already hints at some of this (module or score), 
  the form suggests it and I just confirm or correct it — I'm never typing more than I have to. Once I submit, the grade exists in the system and my coach and trainer are notified automatically.

3. **As an apprentice, I want to see my full list of grades, so that I can track my progress over time.**
   I open my own dashboard and see every grade I've submitted, grouped by subject, with the score next to each one. 
  I don't have to remember what I got in a module three months ago or dig through old emails — it's all in one list, always up to date.

4. **As an apprentice, I want to see my current weighted average, so that I know where I stand without calculating it myself.**
   The average is computed the same way the official CFC weighting works (TPI, base élargie, informatique, culture générale, each weighted differently) — 
  I just see the number. I don't need to keep my own spreadsheet or trust a manual calculation to know if I'm on track.

5. **As an apprentice, I want to read the comments my coach left on a grade, so that I know what to improve.**
   When I open one of my grades, if my coach left feedback on it I see it right there under the scanned document — no separate email thread to search through. 
  I can read it, but I'm not expected to reply in the app; anything more goes back to talking to my coach directly.

## Coach

1. **As a coach, I want to see the list of apprentices assigned to me, so that I know who I'm responsible for.**
   I log in and land on a dashboard showing only my own apprentices — not the whole cohort. Next to each name I see their track, their current average, 
  and when they last submitted a grade, so I can spot at a glance who's active and who might need a check-in.

2. **As a coach, I want to open an apprentice's grade list, so that I can review their progress.**
   Clicking an apprentice from my dashboard takes me into their full test history, grouped by subject the same way the apprentice sees their own. 
I'm looking at their record, not editing it — this is a review view, not a grading tool.

3. **As a coach, I want to open a single grade and view the scanned PDF, so that I can check the original document.**
   From the apprentice's grade list, clicking one test opens the actual scanned PDF they submitted, rendered right in the app — 
I don't need to download a file and open it separately. This is the same document they uploaded, unedited.

4. **As a coach, I want to leave a comment on a grade, so that I can give feedback to the apprentice.**
   Below the PDF there's a comment box. I write feedback there and it becomes visible to the apprentice the next time they open that grade 
— a lighter-weight way to flag something than sending a separate email.

5. **As a coach, I want to be notified when an apprentice submits a new grade, so that I know to review it without checking the app constantly.**
   The moment an apprentice submits a grade, I (and the trainer) get an email about it. I don't have to keep the dashboard open or refresh it on a schedule 
— the app tells me when there's something new to look at.
