[← Back to index](README.md)

# Enrolments cleaning

Automatically unenrols users based on configured cases.

## Cleaning cases

| Case | Description |
|---|---|
| Deleted users | Removes enrolments for users that have been deleted from the platform |
| Suspended users | Removes enrolments for users that are suspended |
| Expired enrolments | Removes enrolments whose end date has passed |

One or more cases can be active at the same time.

## User filter

Allows restricting which enrolments are cleaned based on an additional condition about the user in the course.

| Filter | Description |
|---|---|
| No restriction | All enrolments matching the case are cleaned |
| No grades | Only if the user has no grades in the course |
| Never accessed | Only if the user never accessed the course |
| Completed the course | Only if the user already completed the course |
| Not completed the course | Only if the user has not completed the course |

The filter applies to all active cases.

## Log

Each removed enrolment is saved in an internal log with user data, course and the applied case.
