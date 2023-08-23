# StudentManage

A minimal system that can manage student courses, score, etc.

## Architecture

PHP+MySQL

## Usage

- Manage course score, calculate accurate GPA (for student)
- Manage score in multiple teaching classes, multiple terms (for TAs, teachers, etc.)
- Manage score of students in charge, track their rank or GPA (for mentors, etc.)
- Manage and organize course videos (for student, teachers, etc.)

## Feature

- Query my score with term range (student)
- Query course stats
- Query rank
- Query by course name
- Query by teacher name
- Query by course id
- Query student info
- Query course video
- Class score display
- Score sort
- Simple user login
- Simple user privileges management
- Simple user privileges temporary override.
- OAuth support (Cloudflare Access)
- Security features

## Get Started

- Run `db.sql` in MySQL
- Import your data according to the well-designed database structure
- Edit config in `auths.php`
- Upload `index.php` and `auths.php` to your webhost (with PHP installed)
- Protect the webpage with Cloudflare Access (optional)

> You might need to edit the source code in `index.php` in order to match the GPA calculating and course ID rules in your institute.

## Contribute

You can star this project, create discussion threads, PRs.

Reporting potential security issues is also welcome.

## TODO

- Data Management features in UI
- More beautiful UI
