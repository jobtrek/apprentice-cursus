# Database folder structure

If you're looking for a files, don't search it by yourself, 
check the tree right down.

## MAIN FILES
DATABASE STRUCTURE CHOICES DOCS : db.md
check this one when you need to understand the "WHY" and "HOW THINGS WORKS" in the schema (their purpose, etc.)

DATABASE MODEL : mcd_current.d2
check this one when you need to see how things are organized in real life, which table are linked, 
what are their attributes...

Others files are optional and are more of an history of decision that we've taken as a team,
you can check them if we ask you to check on older database version, else ignore.

db/ [//]: # (Root)
├── AGENT.md [//]: # (Your context and documentation)
├── db.md  [//]: # (Structural choices and how they work documentation)
└── schemas/ [//]: # (database modelling folder)
    ├── mcd_current.d2 [//]: # (LATEST VERSION)
    └── history/ [//]: # (OLDER VERSIONS)
        ├── V1/ [//]: # (FIRST VERSION THAT EMERGED OF THE DB)
        │   ├── mcd.drawio [//]: # (drawio, you can ignore it)
        │   └── mcd.mmd [//]: # (mermaid implementation of the drawio)
        └── V2/ [//]: # (SECOND VERSION THAT EMERGED OF THE DB)
            ├── AI-db-dicussion-history.md [//]: # (Discussion with agent about the V2)
            ├── grades_focus.mmd [//]: # (Concept, tried to understand and reimplement V2)
            ├── mcd_v2-1.mmd [//]: # (mcd BEFORE Migrations and decision with Agent)
            └── mcd_v2-2.mermaid [//]: # (LATEST V2 database model)


- AGENT.md: `docs/db/AGENT.md`
- db.md: `docs/db/db.md`
- mcd_current.d2: `docs/db/schemas/mcd_current.d2`
- mcd.drawio: `docs/db/schemas/history/V1/mcd.drawio`
- mcd.mmd: `docs/db/schemas/history/V1/mcd.mmd`
- AI-db-dicussion-history.md: `docs/db/schemas/history/V2/AI-db-dicussion-history.md`
- grades_focus.mmd: `docs/db/schemas/history/V2/grades_focus.mmd`
- mcd_v2-1.mmd: `docs/db/schemas/history/V2/mcd_v2-1.mmd`
- mcd_v2-2.mermaid: `docs/db/schemas/history/V2/mcd_v2-2.mermaid`
