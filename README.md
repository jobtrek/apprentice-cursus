# Apprentice Cursus

## Description

A grade-tracking application designed to help coaches, trainers, and apprentices efficiently monitor and communicate grades and training progress, while following the existing conventions for grade reporting and training documentation.

Source-workflow is detailed in the [workflows](docs/workflows) directory

## Project set up

**W.I.P**

## Stack

| Tool       | Version   |
| ---------- | --------- |
| Vue        | 3.x       |
| Laravel    | 13.x      |
| Inertia    | latest    |
| Vite       | 5.x / 6.x |
| Tailwind   | 4.x       |
| shadcn     | latest    |
| PostgreSQL | latest    |

## Features

Application distinguish in two main features: a grade managment tool and a formation file managment tool

### Grade managment

Source : [Nommage et envoi des notes](docs/workflows/nommage-et-envoi-des-notes-ecole.pdf)

#### Grade transmission automation

Apprentices will be able to drag and drop their test PDF in the app. A notification will then be sent to both their trainers and assigned coaches.

#### Grade dashboard

The grades will be accessible in dashboard, giving apprentices an overview of their own grades, and trainers / coaches an overview of the grades of apprentice they're responsible of.

#### Grades comment

Trainers and coaches will be able to add commentaries to apprentice grades. They also will be able to download the PDF of the test

### Formation file

Apprentice need to complete a formation file during their cursus, so if something happen that could endanger their formation, they can justify with their formation file.

Source : [Création et maintenance du dossier de formation](docs/workflows/creation-et-maintenance-dossier-de-formation.pdf)

#### Formation file update

An apprentice can access and update their formation file via a dedicated form, featuring all the required shield.

#### Formation file overview

Apprentice, coaches and trainers are able to see overview of formation file. They can also download that preview, taking the shape of a PDF files.

#### Formation file commentary

Trainers and coaches are able to add commentary to part of the project, so apprentice can come back to modify required part or discuss with coaches / trainers.

### Notification system

For some event, notification may be useful and sent via email to users, so they know when they need to act on something, read something etc.
