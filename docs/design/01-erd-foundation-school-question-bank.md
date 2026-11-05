# CBT Platform — Foundation ERD and Data Dictionary

Phase 0 deliverable 1 of 6 · Version 0.1 draft · Status: awaiting approval · All items are **planned design, not implemented or tested**.

## 1. Scope

This slice covers tables created in **both** profiles for Phase 1: identity, RBAC, audit, settings, installation identity and Laravel infrastructure. School, question, exam, results and sync tables follow in later slices. Central-only and school-only tables are never created in the other profile's database.

## 2. Conventions

- **Keys.** Internal `id` bigint unsigned auto-increment primary key for joins and indexing on modest hardware. Entities that are referenced across installations or sync also carry `uuid` char(36), unique, generated at creation and immutable. FKs between local tables use `id`; API payloads and sync use `uuid` only.
- **Timestamps.** `created_at` and `updated_at` timestamp (UTC) on every table unless stated. Display time zone is a setting, default Africa/Lagos.
- **Enums.** Stored as varchar(32) with a PHP backed enum and a documented transition table. No native DB enum type, so MariaDB and MySQL behave the same.
- **Charset.** utf8mb4, collation utf8mb4\_unicode\_ci. Index length limits respected for MariaDB 10.6 and MySQL 8.
- **Deletion.** Identity rows are deactivated through `status`, never hard-deleted once referenced. Audit rows are never updated or deleted by the application.
- **Profile scope.** Tables are identical in both profiles unless a column says otherwise.

## 3. Relationship overview

- `users` many-to-many `roles` through `role_user`.
- `roles` many-to-many `permissions` through `permission_role`.
- `audit_logs` reference actors and entities by uuid snapshot, with no foreign keys, so history survives any later change to the referenced row.
- `system_identity` is a single row describing this installation.
- `app_settings` is a keyed configuration store, with no foreign keys except an optional `updated_by`.

## 4. Data dictionary

### 4.1 users

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | unique |
| user\_type | varchar(32) | no | central\_staff, school\_staff, student, guardian |
| login\_id | varchar(120) | no | unique; email, username or admission number as configured |
| email | varchar(190) | yes | unique when present |
| name | varchar(160) | no |  |
| password | varchar(255) | no | bcrypt or argon2id hash |
| status | varchar(32) | no | active, inactive, locked, pending |
| must\_change\_password | boolean | no | default false |
| failed\_attempts | smallint unsigned | no | default 0 |
| locked\_until | timestamp | yes |  |
| last\_login\_at | timestamp | yes |  |
| remember\_token | varchar(100) | yes |  |

Indexes: unique(uuid), unique(login\_id), unique(email), index(user\_type, status). Deletion: restrict; deactivate instead. Profile rule: `central_staff` rows exist only in the central database, and student and guardian rows only in school databases. Enforced by validation and tested.

### 4.2 roles

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | unique |
| scope | varchar(16) | no | central or school |
| slug | varchar(80) | no |  |
| name | varchar(120) | no |  |
| description | varchar(255) | yes |  |
| is\_system | boolean | no | system roles cannot be deleted |

Indexes: unique(scope, slug). Seeded central roles: super\_administrator, platform\_administrator, question\_bank\_administrator, question\_reviewer, finance\_administrator, technical\_support\_administrator. Seeded school roles: school\_administrator, principal, examination\_officer, teacher, class\_teacher, student, parent\_guardian. Schools may add custom roles (scope=school, is\_system=false).

### 4.3 permissions

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| scope | varchar(16) | no | central, school or shared |
| key | varchar(120) | no | dotted, e.g. exams.publish |
| group | varchar(60) | no | UI grouping |
| description | varchar(255) | yes |  |

Indexes: unique(key). Permissions are seeded from code, never created by users.

### 4.4 permission\_role

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| role\_id | bigint unsigned | no | FK roles.id, cascade on delete |
| permission\_id | bigint unsigned | no | FK permissions.id, cascade on delete |

Primary key (role\_id, permission\_id).

### 4.5 role\_user

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| user\_id | bigint unsigned | no | FK users.id, restrict |
| role\_id | bigint unsigned | no | FK roles.id, restrict |
| assigned\_by | bigint unsigned | yes | FK users.id, set null |
| created\_at | timestamp | no |  |

Primary key (user\_id, role\_id); index(role\_id). Class and subject scoping for teachers is a Phase 2 table (teaching assignments), not a role column.

### 4.6 audit\_logs

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | unique |
| occurred\_at | timestamp(6) | no |  |
| actor\_uuid | char(36) | yes | null for system actions |
| actor\_label | varchar(160) | yes | name and role snapshot |
| action | varchar(120) | no | e.g. result.approved |
| entity\_type | varchar(80) | no |  |
| entity\_uuid | char(36) | yes |  |
| before\_json | json | yes | redacted of secrets |
| after\_json | json | yes | redacted of secrets |
| ip\_address | varchar(45) | yes |  |
| user\_agent | varchar(255) | yes |  |
| request\_id | char(36) | yes | correlates with API envelope request ID |
| profile | varchar(16) | no | central or school |

Indexes: index(entity\_type, entity\_uuid), index(actor\_uuid, occurred\_at), index(action, occurred\_at). Immutability: the app has no update or delete path; a database trigger blocking UPDATE and DELETE is proposed as an optional hardening step. Retention is configurable, and pruning is a separate audited command.

### 4.7 app\_settings

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| group | varchar(60) | no | e.g. appearance, security |
| key | varchar(120) | no |  |
| value | text | yes | JSON-encoded |
| is\_encrypted | boolean | no | uses Laravel encryption for secrets |
| updated\_by | bigint unsigned | yes | FK users.id, set null |

Indexes: unique(group, key). Used for branding, grading defaults, currency, locale and similar, so none of it is hard-coded.

### 4.8 system\_identity (single row)

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | tinyint unsigned | no | PK, constrained to 1 |
| installation\_uuid | char(36) | no | unique; generated at first install |
| profile | varchar(16) | no | central or school; must match APP\_PROFILE or boot fails |
| school\_uuid | char(36) | yes | school profile only, set at activation |
| schema\_version | varchar(32) | no |  |
| installed\_at | timestamp | no |  |

Licence, activation and sync identity extend this in Phase 6.

### 4.9 Laravel infrastructure tables

sessions, cache, cache\_locks, jobs, job\_batches, failed\_jobs, password\_reset\_tokens use the framework defaults. Sessions and cache use the database driver so no Redis is needed. API authentication for sync is signed requests in Phase 6, so no token table is created now.

## 5. Migration order

1. Laravel infrastructure tables
2. system\_identity
3. users
4. roles, permissions
5. permission\_role, role\_user
6. audit\_logs
7. app\_settings
8. Seeders: permissions, system roles, bootstrap admin (central: super\_administrator; school: school\_administrator)

## 6. Cross-checks

- **Profile isolation test:** boot fails if `system_identity.profile` differs from `APP_PROFILE`.
- **Backend authorization:** every route is guarded by permission keys from `permissions`, never by role name checks in views.
- **Audit coverage (Phase 1):** login, lockout, role and permission changes, settings changes, user create, deactivate and credential reset.
- **Login messages** do not reveal whether `login_id` exists; lockout and rate limits use `failed_attempts`, `locked_until` and Laravel's rate limiter.

## 7. Decisions needing approval

1. **Single users table for all principals.** Students authenticate through a `users` row. The Phase 2 `student_credentials` table holds only issuance and reset history, not the live password.
2. **Dual keys** (bigint `id` plus `uuid`), as described above, instead of UUID primary keys everywhere.
3. **Application-level audit immutability** in Phase 1, with the database trigger deferred to Phase 9 hardening.
4. **Configurable login field** (email, username or admission number) through `app_settings`, defaulting to username for staff and admission number for students.

# Part 2 — School Foundation Slice (v0.1 draft)

Phase 0 deliverable 2 of 6 · School database only · Status: awaiting approval · Planned design, not implemented or tested. Part 1 conventions (keys, timestamps, enums, charset, deletion) apply unchanged.

## 8. Scope and principles

Covers school profile, academic sessions and terms, classes and arms, subjects, staff, students, guardians, enrolments, teaching assignments and imports. None of these tables exist in the central database.

- **History is never rewritten.** Promotion closes the old enrolment and opens a new one; old rows keep their session, class and arm references forever.
- **Nothing is hard-coded.** Class names, levels and subjects are created by the school. The setup wizard may offer presets (for example a JSS1–SS3 structure), but no preset is a default.
- **Sync default.** None of these tables sync student-identifying data to central. Only aggregate counts (students, staff) are reported later, for subscription limits.

## 9. Relationship overview

- `academic_sessions` one-to-many `terms`.
- `classes` one-to-many `class_arms`; `classes` many-to-many `subjects` through `class_subjects`.
- `users` one-to-one `staff_profiles` and one-to-one `students`.
- `students` many-to-many `guardians` through `student_guardian`.
- `students` one-to-many `student_enrollments`, each pointing to a session, class and arm.
- `staff_profiles` one-to-many `teaching_assignments`, each pointing to a session, class, optional arm and optional subject.
- `import_batches` one-to-many `import_rows`.

## 10. Data dictionary

### 10.1 school\_profile (single row)

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | tinyint unsigned | no | PK, constrained to 1 |
| name | varchar(180) | no |  |
| short\_name | varchar(60) | yes |  |
| address | varchar(255) | yes |  |
| phone | varchar(40) | yes |  |
| email | varchar(190) | yes |  |
| logo\_path | varchar(255) | yes |  |
| time\_zone | varchar(64) | no | default Africa/Lagos |
| setup\_state | json | yes | wizard step progress, supports save and resume |
| setup\_completed\_at | timestamp | yes |  |

Branding colours live in `app_settings` (group: appearance).

### 10.2 academic\_sessions

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | unique |
| name | varchar(40) | no | e.g. 2026/2027; unique |
| starts\_on | date | no |  |
| ends\_on | date | no | must be after starts\_on |
| status | varchar(16) | no | draft, active, closed |
| active\_flag | tinyint | yes | 1 when active, otherwise NULL; unique, so at most one active session |

The nullable unique flag is used because MariaDB and MySQL have no partial unique index. Deletion: restrict. A closed session is read-only.

### 10.3 terms

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | unique |
| session\_id | bigint unsigned | no | FK academic\_sessions.id, restrict |
| name | varchar(40) | no | e.g. First Term |
| sequence | tinyint unsigned | no | unique with session\_id |
| starts\_on / ends\_on | date | no | inside the session dates |
| status | varchar(16) | no | upcoming, active, closed |
| active\_flag | tinyint | yes | same pattern as sessions; at most one active term |

The number of terms per session is not fixed; a school defines its own.

### 10.4 classes

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | unique |
| name | varchar(60) | no | unique |
| education\_level | varchar(60) | yes | free text or school-defined list |
| sort\_order | smallint unsigned | no | drives promotion order and display |
| status | varchar(16) | no | active, archived |

Deletion: restrict. Classes are archived, not deleted.

### 10.5 class\_arms

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | unique |
| class\_id | bigint unsigned | no | FK classes.id, restrict |
| name | varchar(40) | no | unique with class\_id |
| status | varchar(16) | no | active, archived |

### 10.6 subjects and class\_subjects

`subjects`: id, uuid (unique), name (unique), code (nullable, unique), status (active, archived).

`class_subjects`: class\_id (FK classes.id, restrict), subject\_id (FK subjects.id, restrict), is\_compulsory (boolean). Primary key (class\_id, subject\_id). Defines which subjects apply to which class.

### 10.7 staff\_profiles

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | unique |
| user\_id | bigint unsigned | no | FK users.id, restrict; unique |
| staff\_no | varchar(40) | yes | unique when present |
| phone | varchar(40) | yes |  |
| gender | varchar(16) | yes | optional |
| employment\_status | varchar(16) | no | active, on\_leave, left |

Roles come from `role_user` (Part 1); this table holds profile data only.

### 10.8 teaching\_assignments

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | unique |
| staff\_id | bigint unsigned | no | FK staff\_profiles.id, restrict |
| session\_id | bigint unsigned | no | FK academic\_sessions.id, restrict |
| class\_id | bigint unsigned | no | FK classes.id, restrict |
| arm\_id | bigint unsigned | yes | FK class\_arms.id; NULL means all arms |
| subject\_id | bigint unsigned | yes | FK subjects.id; NULL for class-teacher duty |
| kind | varchar(24) | no | subject\_teacher, class\_teacher |
| assignment\_key | char(64) | no | unique; hash of the combination, computed by the app (a plain unique index would allow duplicates when arm or subject is NULL) |

This table is the single source for "teachers see only assigned classes and subjects". Every teacher query is scoped through it on the server.

### 10.9 students

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | unique |
| user\_id | bigint unsigned | no | FK users.id, restrict; unique |
| admission\_no | varchar(40) | no | unique; indexed for login and search |
| first\_name / last\_name | varchar(80) | no |  |
| other\_names | varchar(120) | yes |  |
| date\_of\_birth | date | yes |  |
| gender | varchar(16) | yes | optional |
| photo\_path | varchar(255) | yes |  |
| admitted\_on | date | yes |  |
| status | varchar(24) | no | active, graduated, withdrawn, transferred, deactivated |

Indexes: unique(admission\_no), index(last\_name, first\_name), index(status). Deletion: restrict. Former students keep their rows and history.

### 10.10 student\_credentials

id, student\_id (FK students.id, restrict), action (issued, reset), performed\_by (FK users.id, set null), must\_change (boolean), created\_at. **No secret is stored here.** A new password or PIN is shown or printed once at issuance and only its hash exists, in `users.password`. Each row is also written to `audit_logs`.

### 10.11 guardians and student\_guardian

`guardians`: id, uuid (unique), user\_id (nullable FK users.id, set null; an account is optional), full\_name, phone, email.

`student_guardian`: student\_id (FK, restrict), guardian\_id (FK, restrict), relationship (varchar), is\_primary (boolean), can\_view\_results (boolean). Primary key (student\_id, guardian\_id). This table is what the parent dashboard authorises against, on the server.

### 10.12 student\_enrollments

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | unique |
| student\_id | bigint unsigned | no | FK students.id, restrict |
| session\_id | bigint unsigned | no | FK academic\_sessions.id, restrict |
| class\_id | bigint unsigned | no | FK classes.id, restrict |
| arm\_id | bigint unsigned | no | FK class\_arms.id, restrict |
| enrolled\_on | date | no |  |
| ended\_on | date | yes |  |
| end\_reason | varchar(24) | yes | promoted, repeated, class\_change, transferred, withdrawn, graduated |
| status | varchar(16) | no | current, ended |
| current\_flag | tinyint | yes | 1 when current, otherwise NULL; unique with (student\_id, session\_id), so one current enrolment per student per session |

Indexes: index(student\_id, session\_id), index(session\_id, class\_id, arm\_id, status) for class lists. Rows are never deleted, and only `ended_on`, `end_reason` and `status` change after creation. A class or arm change mid-session closes one row and opens another. Results in Phase 5 reference the enrolment, so they stay tied to the class the student actually sat in.

### 10.13 import\_batches and import\_rows

`import_batches`: id, uuid (unique), kind (students, staff), file\_name, stored\_path, status (uploaded, mapped, validated, committed, failed, cancelled), mapping\_json, total\_rows, valid\_rows, error\_rows, committed\_rows, created\_by (FK users.id, set null), created\_at, committed\_at (nullable).

`import_rows`: id, batch\_id (FK, cascade), row\_number, raw\_json, status (valid, error, duplicate, committed, skipped), errors\_json (nullable), entity\_uuid (nullable, set when committed). Index (batch\_id, status).

Commit runs in a queued job inside a transaction. Default mode is all-or-nothing. An explicit "import valid rows only" option is allowed, and the results summary always states what was committed and what was rejected. The downloadable error report is generated from `import_rows`. `raw_json` holds personal data, so it is purged after a configurable retention period.

## 11. Migration order (continues Part 1)

9. school\_profile
10. academic\_sessions, terms
11. classes, class\_arms
12. subjects, class\_subjects
13. staff\_profiles
14. students, student\_credentials
15. guardians, student\_guardian
16. student\_enrollments
17. teaching\_assignments
18. import\_batches, import\_rows

No data seeders beyond the Part 1 permissions and roles. Wizard presets are application data, not database seeds.

## 12. Cross-checks

- **Setup wizard (UI §4):** steps map to school\_profile, sessions and terms, classes and arms, subjects, staff, student import, and assessment configuration (Phase 5). Wizard progress is in `school_profile.setup_state`.
- **Students list (UI §14):** filters by class, arm, status and session are served by the enrolment indexes; search by admission number uses its unique index.
- **Promotion (DB doc §7):** close the old enrolment, open a new one in the next session; the old row is untouched.
- **Teacher scope (UI §16):** `teaching_assignments` plus server-side query scoping; tested per role.
- **Import (Workflow §10):** template, mapping, preview, validation, duplicate detection and error report are all represented by the batch and row tables.
- **Privacy:** no column here is sync-eligible student data by default.

## 13. Decisions needing approval

1. **Enrolment per session**, with mid-session changes as closed-and-reopened rows, rather than one enrolment per term.
2. **Nullable unique flag columns** for "only one active session or term" and "one current enrolment", instead of application-only checks.
3. **Credential secrets are never stored** beyond the password hash; issuance is printed or displayed once.
4. **Import commit default** is all-or-nothing, with "valid rows only" as an explicit, reported option.
5. **Optional gender fields** on students and staff, needed later for report cards but never required.

# Part 3 — Question Bank Slice (v0.1 draft)

Phase 0 deliverable 3 of 6 · Both profiles · Status: in-scope decisions auto-approved; items in section 18 need your approval · Planned design, not implemented or tested.

## 14. Scope and principles

Covers taxonomy (categories, levels, topics), questions, immutable versions, options, answer keys, explanations, media, examination mappings, provenance and rights status, and local-versus-central source tracking. Deferred to the Central slice: question\_contributions, ai\_validation\_jobs, ai\_validation\_results, question\_reviews, question\_duplicates, release tables.

- **One definition, two table prefixes.** Central uses `questions`, `question_versions`, `question_options` and so on. School uses `local_questions`, `local_question_versions` and so on, as the handover database document names them. The columns are identical and defined once; a profile-aware model base class resolves the table name.
- **Versions are immutable.** Only a draft version can be edited. Submitting freezes it. Any change after that creates a new version.
- **Answer keys live in separate tables** (`question_answers`, `question_explanations`) and are never joined into candidate-facing queries.
- **Same UUID everywhere.** A central question installed in a school keeps its central `uuid` and version numbers. Locally authored questions get new UUIDs.

## 15. Data dictionary

### 15.1 Taxonomy (both profiles)

`examination_categories`: id, uuid (unique), name (unique), code (nullable, unique), description (nullable), sort\_order, status (active, archived). Categories such as WAEC, NECO, JAMB, BECE, Common Entrance and Internal are data an administrator can add or archive, never constants in code.

`education_levels`: id, uuid (unique), name (unique), sort\_order, status.

`topics`: id, uuid (unique), subject\_id (FK subjects.id, restrict), parent\_id (nullable self-FK, restrict; a subtopic points to its topic), parent\_key (bigint, not null, default 0; equals parent\_id or 0), name, sort\_order, status. Unique (subject\_id, parent\_key, name). Depth is limited to topic and subtopic.

In central, `subjects` has the same shape as the school's `subjects` in Part 2. Taxonomy rows installed from a release keep central UUIDs.

### 15.2 questions (identity and classification)

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | unique; immutable |
| origin | varchar(24) | no | central\_authored, central\_release, local |
| subject\_id | bigint unsigned | no | FK subjects.id, restrict |
| education\_level\_id | bigint unsigned | yes | FK education\_levels.id, restrict |
| topic\_id | bigint unsigned | yes | FK topics.id, restrict; may be a subtopic |
| question\_type | varchar(16) | no | objective, theory |
| difficulty | varchar(16) | no | easy, medium, hard |
| status | varchar(24) | no | draft, submitted, in\_review, correction\_requested, approved, rejected, retired |
| current\_version\_no | smallint unsigned | no | latest version |
| published\_version\_no | smallint unsigned | yes | latest approved version |
| source\_type | varchar(24) | no | past\_exam, textbook, teacher\_authored, contributed, ai\_assisted, other |
| source\_citation | varchar(255) | yes |  |
| source\_year | smallint unsigned | yes |  |
| contributor\_name | varchar(160) | yes |  |
| contributor\_user\_uuid | char(36) | yes |  |
| rights\_status | varchar(24) | no | unverified, permitted, own\_work, restricted, not\_permitted; default unverified |
| rights\_notes | text | yes |  |
| is\_locked | boolean | no | true for central\_release rows, which are read-only in a school |
| source\_release\_uuid | char(36) | yes | release this row came from (school) |
| forked\_from\_uuid | char(36) | yes | central question this local copy was forked from |
| forked\_from\_version\_no | smallint unsigned | yes |  |
| created\_by | bigint unsigned | yes | FK users.id, set null |

Indexes: unique(uuid); index(subject\_id, education\_level\_id, topic\_id, difficulty, status); index(status, updated\_at); index(source\_type, source\_year); index(origin). Deletion: restrict. A question is retired, never deleted, once any version was submitted. Only a never-submitted draft can be deleted, and only if nothing references it.

Status is the question's workflow state; `current_version_no` and `published_version_no` avoid a circular foreign key with versions.

### 15.3 question\_versions (immutable once submitted)

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | unique |
| question\_id | bigint unsigned | no | FK questions.id, restrict |
| version\_no | smallint unsigned | no | unique with question\_id |
| stem | mediumtext | no | candidate-facing question text |
| content\_format | varchar(16) | no | default html\_basic: a sanitised HTML subset |
| max\_marks | decimal(6,2) | no |  |
| status | varchar(16) | no | draft, submitted, approved, rejected, superseded, retired |
| change\_note | varchar(255) | yes |  |
| provenance\_snapshot | json | yes | source and rights fields frozen at submission |
| content\_hash | char(64) | yes | SHA-256 of canonical stem, options, answers and marks, set at submission |
| submitted\_at / approved\_at | timestamp | yes |  |
| created\_by | bigint unsigned | yes | FK users.id, set null |

Indexes: unique(uuid), unique(question\_id, version\_no), index(status). Full-text index on `stem` where the database supports it, with a LIKE fallback (SQLite tests). The content hash is re-checked in release packaging and installation to prove a version was not altered.

### 15.4 question\_options (candidate-facing)

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | canonical option identity; copied forward to a new version when the option is unchanged |
| version\_id | bigint unsigned | no | FK question\_versions.id, restrict |
| position | smallint unsigned | no | authoring order; unique with version\_id |
| content | text | no |  |
| content\_format | varchar(16) | no |  |

There is deliberately **no** `is_correct` column. Display letters (A, B, C, D) are computed at render time, so randomisation never alters identity. Indexes: unique(version\_id, position), unique(version\_id, uuid), index(uuid).

### 15.5 question\_answers (server-only)

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| version\_id | bigint unsigned | no | FK question\_versions.id, restrict |
| option\_id | bigint unsigned | yes | FK question\_options.id; objective correct option |
| expected\_answer | mediumtext | yes | theory |
| rubric\_json | json | yes | theory marking rubric |
| alternatives\_json | json | yes | accepted alternative answers |

Objective questions have exactly one row in v1. Theory questions have one row. Index (version\_id). No candidate-facing query, serializer or API resource may reference this table; a static test and an API response test enforce it.

### 15.6 question\_explanations (server-only)

id, version\_id (FK, restrict, unique), explanation (mediumtext, nullable), marking\_guide (text, nullable), content\_format. Shown to students only through practice and permitted post-exam feedback, never through formal-exam endpoints.

### 15.7 question\_media

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | unique |
| version\_id | bigint unsigned | no | FK question\_versions.id, restrict |
| option\_id | bigint unsigned | yes | FK question\_options.id |
| role | varchar(16) | no | stem, option, explanation |
| kind | varchar(16) | no | image, audio, other |
| path | varchar(255) | no | private disk, never a public URL |
| mime\_type / size\_bytes | varchar / bigint | no | validated on upload |
| sha256 | char(64) | no |  |
| alt\_text | varchar(255) | yes | accessibility |
| position | smallint unsigned | no |  |

Files are served by an authorised controller. Media with role = explanation is excluded from every formal-exam payload.

### 15.8 question\_examination\_mappings

id, question\_id (FK, restrict), category\_id (FK examination\_categories.id, restrict), exam\_year (smallint, nullable), paper\_reference (varchar 80, nullable). Unique (question\_id, category\_id, exam\_year). Index (category\_id, exam\_year). One question can be mapped to several categories and years.

### 15.9 local\_question\_sync\_status (school only)

id, question\_id (FK local\_questions.id, unique), state (local\_only, contribution\_submitted, contribution\_acknowledged, central\_sourced), central\_question\_uuid (nullable), last\_synced\_at, last\_ack\_at, last\_error (varchar 255, nullable). Written only by the sync agent (Phase 6).

## 16. Rules

- **Editing a central-sourced question forks it.** `is_locked` rows cannot be edited. "Edit" creates a new `local` question with `forked_from_uuid` and `forked_from_version_no`, so the central version is never mutated.
- **Release eligibility (Central slice).** Only an approved version whose `rights_status` is `permitted` or `own_work` can enter a release. Past-exam questions default to `unverified` and are flagged for rights review; nothing here assumes they may be redistributed.
- **Historical safety.** Exam attempts will copy the stem, options, canonical option UUIDs, key mapping and marks into their own snapshot tables (Exam slice), with no live dependency on these rows for scoring or review.
- **Status transitions:** draft → submitted → in\_review → approved, rejected or correction\_requested; correction\_requested → draft (new version); approved → retired. Each transition writes `audit_logs`.

## 17. Amendments to Parts 1 and 2 (required by this slice)

- `classes.education_level` (free text) is replaced by `classes.education_level_id`, a nullable FK to `education_levels`, so the exam wizard can pull questions that match a class's level.
- `subjects` gains `central_uuid` (char 36, nullable), linking a school subject to the central subject that releases refer to.
- `import_batches.kind` gains the value `questions` for question imports.
- Migration order: `education_levels` and `examination_categories` are created before `classes`; `topics` after `subjects`; then `questions`, `question_versions`, `question_options`, `question_answers`, `question_explanations`, `question_media`, `question_examination_mappings`, and (school only) `local_question_sync_status`. Numbers will be consolidated in the final ERD.

## 18. Needs your approval (outside the handover documents)

1. **Formula and symbol support.** The documents say "text and media" only. Mathematics, physics and chemistry questions normally need formulas. I propose offline-capable math rendering (a bundled library, no CDN). This is a new function.
2. **Single-answer objective questions only in v1.** The documents mention options and answer keys but not multiple-correct scoring. The schema already allows several answer rows, but multi-select scoring rules would be a new function.
3. **One topic per question** (a topic or subtopic) instead of the handover's separate `question_topic_mappings` table. Simpler to query and filter; a many-to-many table can be added later.

# Part 4 — Examination Slice (v0.1 draft)

Phase 0 deliverable 4 of 6 · School database only · Status: in-scope decisions auto-approved; one item in section 25 needs your approval · Planned design, not implemented or tested.

## 19. Scope and principles

Covers examination definition, question selection (manual, topic quota, automatic), candidate assignment, attempts, per-attempt snapshots, answer keys, candidate answers, activity logs and attempt-level objective results. Theory marks, score entry and computed results belong to the Results slice. Practice tables get their own short slice after Phase 4, as agreed.

- **Local server is authoritative.** Every write here is a local database transaction. Nothing waits on the cloud.
- **Snapshots are independent.** Attempt rows copy question content, options and key data. They hold no live foreign key to the bank, so later bank edits, retirements or purges cannot change a past attempt.
- **Keys are structurally separate.** Candidate-facing data and answer keys live in different tables. Candidate endpoints may read only `exam_attempt_questions` and `student_answers`.
- **Student data stays local.** Attempts, answers and results are student-identifying and are not synced to central by default (Part 1 decision). Only aggregate counts may be reported later.
- **Approved items applied from Part 3.** Objective questions have one correct answer in v1. Topic quotas use `topic_id`, and a quota on a topic includes its subtopics. `content_format` allows `html_basic` and `html_math`; formulas are rendered by a bundled library with no CDN, and snapshots store the format so old attempts render the same way.

## 20. Data dictionary

### 20.1 examinations

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | unique |
| title | varchar(180) | no |  |
| category\_id | bigint unsigned | no | FK examination\_categories.id, restrict |
| session\_id / term\_id | bigint unsigned | no | FKs, restrict |
| class\_id / subject\_id | bigint unsigned | no | FKs, restrict |
| instructions | text | yes | shown to candidates and snapshotted |
| selection\_method | varchar(16) | no | manual, topic\_quota, automatic |
| duration\_minutes | smallint unsigned | no |  |
| opens\_at | timestamp | yes | earliest start |
| closes\_at | timestamp | yes | last moment to start |
| hard\_close | boolean | no | default false; when true, attempt deadlines are capped at closes\_at |
| pass\_mark | decimal(6,2) | yes |  |
| randomize\_questions / randomize\_options | boolean | no |  |
| access\_code\_hash | varchar(255) | yes | optional code given out in the room; hashed |
| auto\_submit\_on\_expiry | boolean | no | default true |
| feedback\_policy | varchar(24) | no | none, score\_only, score\_and\_answers; default none |
| rules\_json | json | yes | warning intervals and other rules; carries a schema version |
| assignment\_selection | json | yes | class and arm picks or individual list, as entered in the wizard |
| status | varchar(16) | no | draft, published, ended, archived |
| published\_at / ended\_at / archived\_at | timestamp | yes |  |
| ended\_by | bigint unsigned | yes | FK users.id, set null |
| created\_by | bigint unsigned | yes | FK users.id, set null |

Indexes: unique(uuid); index(status, opens\_at); index(class\_id, subject\_id, session\_id, term\_id).

**Upcoming, Ongoing and Completed are derived, not stored.** Upcoming means published and now is before opens\_at. Ongoing means published, inside the window and not ended. Completed means ended or past the window. This avoids a scheduler that could be missed while a server is off. Deletion: restrict; only a draft with no attempts may be hard-deleted, and everything else is archived.

### 20.2 Selection tables

`exam_blueprints`: id, uuid, exam\_id (FK, unique, cascade while draft), total\_questions (smallint), notes (nullable). Present for topic\_quota and automatic methods.

`exam_blueprint_topics`: id, blueprint\_id (FK, cascade), topic\_id (FK topics.id, restrict), question\_count (smallint). Unique (blueprint\_id, topic\_id).

`exam_blueprint_difficulties`: id, blueprint\_id (FK, cascade), difficulty (easy, medium, hard), question\_count (smallint). Unique (blueprint\_id, difficulty).

`exam_questions` (needed for manual selection, and for the frozen pool of blueprint exams):

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| exam\_id | bigint unsigned | no | FK examinations.id, cascade while draft |
| question\_version\_id | bigint unsigned | no | FK local\_question\_versions.id, restrict; version is pinned at publish |
| is\_fixed | boolean | no | true: every candidate gets it; false: part of the draw pool |
| position | smallint unsigned | yes | for fixed questions when order is not randomised |
| marks\_override | decimal(6,2) | yes | otherwise the version's max\_marks |

Unique (exam\_id, question\_version\_id). At publish, the pool is frozen, so bank changes after publication cannot alter what a running exam draws from. Publish is blocked, with a plain-language message, when quotas do not add up to total\_questions or the pool is too small to satisfy topic and difficulty counts together.

### 20.3 exam\_assignments (one row per candidate)

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | unique |
| exam\_id | bigint unsigned | no | FK, cascade while draft |
| student\_id | bigint unsigned | no | FK students.id, restrict |
| enrolment\_id | bigint unsigned | no | FK student\_enrollments.id, restrict |
| source | varchar(16) | no | class\_arm, individual, added\_later |
| status | varchar(16) | no | assigned, withdrawn |
| time\_extension\_minutes | smallint unsigned | no | default 0 |

Unique (exam\_id, student\_id). Class and arm choices are expanded into candidate rows at publish. Adding a late candidate is an audited action with source = added\_later.

### 20.4 exam\_attempts

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | unique; the candidate-visible reference |
| exam\_id | bigint unsigned | no | FK, restrict |
| student\_id | bigint unsigned | no | FK, restrict |
| assignment\_id | bigint unsigned | no | FK exam\_assignments.id, restrict |
| attempt\_no | tinyint unsigned | no | default 1 |
| status | varchar(24) | no | in\_progress, submitted, auto\_submitted, ended\_by\_officer, void |
| started\_at | timestamp(3) | no | server time |
| deadline\_at | timestamp(3) | no | server time; see below |
| submitted\_at | timestamp(3) | yes |  |
| submit\_reason | varchar(16) | yes | candidate, timeout, officer, system |
| marking\_status | varchar(24) | no | pending, objective\_done, awaiting\_theory, complete, failed |
| rules\_snapshot | json | no | duration, randomisation flags, feedback policy, pass mark, total marks, instructions text |
| question\_count / total\_marks | smallint / decimal(7,2) | no |  |
| session\_token\_hash | char(64) | no | the one live browser session |
| last\_seen\_at / last\_saved\_at | timestamp(3) | yes |  |
| row\_version | int unsigned | no | optimistic lock for state changes |

Unique (exam\_id, student\_id, attempt\_no). Indexes: index(exam\_id, status), index(student\_id), index(status, deadline\_at) for the auto-submit sweep. Rows are never deleted; an invalid attempt is set to void by an audited action.

**Deadline rule:** deadline\_at = started\_at + duration + extension, capped at closes\_at only when hard\_close is true. Extensions rewrite deadline\_at and write an audit and activity entry. Late joiners get the full duration unless hard\_close applies. The server clock decides everything; browsers only display a countdown from the remaining seconds the server returns on each save.

### 20.5 exam\_attempt\_questions (candidate-facing snapshot)

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | unique |
| attempt\_id | bigint unsigned | no | FK exam\_attempts.id, restrict |
| position | smallint unsigned | no | persisted display order |
| question\_uuid | char(36) | no | no FK to the bank |
| question\_version\_uuid / version\_no | char(36) / smallint | no | for traceability only |
| source\_content\_hash | char(64) | no | hash of the bank version it was copied from |
| question\_type | varchar(16) | no |  |
| stem / content\_format | mediumtext / varchar(16) | no |  |
| max\_marks | decimal(6,2) | no | after any override |
| options\_json | json | yes | list of option\_uuid, displayed position, content and format, in the randomised order shown to this candidate |
| media\_json | json | yes | stem and option media references with sha256, never explanation media |

Unique (attempt\_id, position) and (attempt\_id, question\_uuid). The order is stored, not recomputed from a seed, so recovery after a crash or reconnect always shows the same screen. Options carry their canonical option\_uuid, so scoring is independent of the displayed order. Volume estimate: 500 candidates with 50 questions is about 25,000 rows per exam, which is modest for MariaDB on school hardware.

### 20.6 exam\_attempt\_keys (server-only)

id, attempt\_question\_id (FK, restrict, unique), correct\_option\_uuid (nullable), expected\_answer (mediumtext, nullable), rubric\_json (nullable), alternatives\_json (nullable), marks (decimal 6,2), explanation\_snapshot (mediumtext, nullable). No candidate endpoint, serializer or eager-load may touch this table; an architecture test and an API response test enforce it. Explanations are released only by the exam's feedback policy, after submission.

### 20.7 student\_answers

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| attempt\_id | bigint unsigned | no | FK, restrict |
| attempt\_question\_id | bigint unsigned | no | FK, restrict; unique, so one current answer per question |
| selected\_option\_uuid | char(36) | yes | objective; canonical identity |
| answer\_text | mediumtext | yes | theory |
| is\_flagged | boolean | no | default false |
| client\_seq | int unsigned | no | highest accepted sequence number |
| saved\_at | timestamp(6) | no | server time of the last accepted write |
| is\_correct | boolean | yes | set by objective marking |
| awarded\_marks | decimal(6,2) | yes | objective marks; theory marks live in Results |

**Autosave is idempotent and ordered.** A save carries client\_seq. The server writes only if client\_seq is greater than the stored value, inside one transaction. A retried or reordered request cannot overwrite a newer answer. The browser shows Saved only after the server confirms. Writes after deadline\_at are rejected, except in-flight requests within a small configurable grace period (default 10 seconds). Only the latest answer is stored; see the approval item in section 25.

`student_answer_files` (only when an exam permits attachments): id, answer\_id (FK, restrict), path, mime\_type, size\_bytes, sha256, created\_at. Private disk, validated type and size.

### 20.8 exam\_activity\_logs

id (bigint), attempt\_id (FK, restrict), client\_event\_id (char 36, nullable; unique with attempt\_id so client retries never duplicate), occurred\_at (timestamp 6, server), client\_time (timestamp, nullable), event\_type (started, resumed, focus\_lost, focus\_regained, fullscreen\_exit, connection\_lost, connection\_restored, session\_rotated, warning\_shown, time\_extended, submitted, auto\_submitted, officer\_intervention), payload\_json (small, nullable). Indexes: (attempt\_id, occurred\_at), (event\_type). These are a deterrent and an audit signal, not proof of cheating, and the UI says so. Officer interventions are also written to `audit_logs` and are kept; routine focus and connection events can be pruned after a configurable retention period.

### 20.9 exam\_results (attempt-level outcome)

| Column | Type | Null | Notes |
| --- | --- | --- | --- |
| id | bigint unsigned | no | PK |
| uuid | char(36) | no | unique |
| attempt\_id | bigint unsigned | no | FK, restrict |
| version\_no | smallint unsigned | no | unique with attempt\_id |
| current\_flag | tinyint | yes | 1 for the current version, otherwise NULL; unique with attempt\_id |
| objective\_score | decimal(7,2) | no |  |
| theory\_score | decimal(7,2) | yes | filled when theory marking finishes |
| total\_score / total\_marks | decimal(7,2) | yes / no |  |
| percentage | decimal(5,2) | yes |  |
| passed | boolean | yes |  |
| status | varchar(24) | no | objective\_marked, awaiting\_theory, complete, void |
| finalised\_at | timestamp | yes |  |

A complete row is immutable. A re-mark or correction creates version\_no + 1, and the older version is kept. The Results slice reads the current version when importing CBT marks.

## 21. Process rules

- **Start.** In one transaction: confirm the candidate is assigned, the window is open, the access code (if any) matches and no attempt exists. Then draw questions, randomise, write `exam_attempts`, `exam_attempt_questions` and `exam_attempt_keys`, and set deadline\_at. Starting twice returns the same attempt (idempotent).
- **Resume.** The server returns the persisted questions, saved answers, flags and remaining seconds. A new login rotates `session_token_hash`, invalidates the old session and logs session\_rotated.
- **Submit and expiry.** Candidate submit, officer end, and a periodic sweep on deadline\_at all go through one service. It locks the attempt, sets status, and queues marking. A missed sweep is harmless, because any later request or the next sweep submits expired attempts.
- **Objective marking.** One transaction compares selected\_option\_uuid with correct\_option\_uuid from `exam_attempt_keys`, writes is\_correct and awarded\_marks, and creates exam\_results version 1. It is idempotent: if version 1 exists, the job exits. Failures leave marking\_status = failed and are retryable from the queue.
- **Durability.** Keep full transaction durability on the school database (InnoDB flush on each commit) so confirmed saves survive a power cut. A UPS is recommended, as the architecture document says.

## 22. Migration order (continues earlier parts)

examinations, exam\_blueprints, exam\_blueprint\_topics, exam\_blueprint\_difficulties, exam\_questions, exam\_assignments, exam\_attempts, exam\_attempt\_questions, exam\_attempt\_keys, student\_answers, student\_answer\_files, exam\_activity\_logs, exam\_results.

## 23. Cross-checks

- **Wizard (UI §20):** steps map to examinations, the selection tables, settings columns, exam\_assignments and the publish checks.
- **Monitoring (UI §21):** the status counts come from attempts and assignments; extend time, end exam and resolve-case actions are audited.
- **Candidate page (UI §22):** question navigator, flags and the Saved indicator are served by exam\_attempt\_questions and student\_answers only.
- **Completion page (UI §23):** feedback follows feedback\_policy; nothing is revealed when it is none.
- **Acceptance (SRS §11):** responses retained, question updates leave old attempts untouched, unauthorised access blocked, and exams run offline.

## 24. Tests this slice makes possible

Answer-key secrecy (response and architecture tests), snapshot immutability after a bank edit, scoring with randomised options, idempotent start and autosave under replay and reordering, deadline and extension arithmetic, expiry auto-submit, session rotation, and crash-and-resume.

## 25. Needs your approval (new function, outside the documents)

1. **Per-answer revision history.** I recommend storing only the latest answer plus timestamps, because logging every autosave multiplies writes on modest hardware. If you want dispute-grade history of every answer change, that would be a new append-only table with a write-volume cost. Please say whether you want it.
