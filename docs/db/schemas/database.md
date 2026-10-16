# Database model explained

## Grades
 
Domains and grades are part of a Tree-structured system of calculations. 
We have, per tree, a **main domain(root)**, multiple **sub-domain(branches)** and **subjects(leaves)**
**Grades** are then connected to their respective subjects(leaves).
Modeled around a chosen formation plan. The **formation plan** defines the **content** of the tree.
As an example, we will take the already documented **ICT formation plan**.

```d2
Final CFC average -> TPI: 40%
Final CFC average -> CBE: 10%
Final CFC average -> ECG: 20%
Final CFC average -> CI: 30%
CI -> Pro: 80%
CI -> CIE: 20%

ECG -> ECG exam 1: 1/n 
ECG -> ECG exam 2: 1/n 

CBE -> English: 1/n
CBE -> Maths: 1/n

Pro -> M164: 1/n
Pro -> M319 : 1/n

CIE -> M186 : 1/n

TPI -> TPI grade: 100%
```
