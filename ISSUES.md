# GitHub issue format

Issues created from the CLI (Claude Code, `gh`) must look exactly like issues created from the web forms in `.github/ISSUE_TEMPLATE/`. Those forms render each field as a `### <label>` heading followed by its value, and an empty optional field as `_No response_`. Reproduce that body, and set the same issue type and labels as the form.

## Creating the issue

`gh issue create` can't set the issue type, so use the REST API:

```bash
gh api repos/jobtrek/apprentice-cursus/issues \
  -f title="<title>" \
  -f type="Task" \
  -F body=@/path/to/body.md
# Bug report: drop the type line, add  -f 'labels[]=bug' -f 'labels[]=triage'
```

Pick the template that fits: a concrete piece of work → **Task**; a user-facing need → **User Story**; a group of features → **Epic**; something broken → **Bug Report**. Write the body in the same language as the rest of the issues (English headings, exactly as below — don't translate or rename them). Keep every heading, even when the field is empty.

## Task — `type: Task`, no labels

```markdown
### Task description

<What this task is about. Links, file paths, detailed instructions.>

### Acceptance criteria

- [ ] <testable criterion>
- [ ] <testable criterion>

### Technical informations

<Links to technical docs, files involved, diagrams — or _No response_>

### Additional notes

<Any extra context — or _No response_>
```

## User Story — `type: Story`, no labels

```markdown
### Task description

As a [persona], I want [an action] so that [a benefit or goal].

### Acceptance criteria

- [ ] Given [precondition] When [action] Then [result]
- [ ] Given [precondition] When [action] Then [result]

### Design considerations

<Design info, wireframes — or _No response_>

### Technical informations

<Links to technical docs, files involved, diagrams — or _No response_>

### Additional notes

<Any extra context — or _No response_>
```

## Epic — `type: Epic`, no labels

Customer vocabulary, focused on results, not technical details.

```markdown
### Epic description

<What this epic is about and the features it covers.>

### Acceptance criteria

- [ ] <result that shows the epic is done>

### Additional notes

<Any extra context — or _No response_>
```

## Bug Report — no type, labels `bug` + `triage`

The org has no `Bug` issue type (only Task, Story, Epic), so omit `-f type=...` or the API call fails.

````markdown
### What happened?

<What happened, and what was expected instead.>

### What browsers are you seeing the problem on?

<Comma-separated, from: Firefox, Chrome, Safari, Microsoft Edge, Safari IOS, Chrome phone — or _No response_>

### Relevant log or console error output

```shell
<log output — or leave the block empty>
```
````
