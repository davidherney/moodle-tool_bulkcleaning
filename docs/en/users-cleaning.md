[← Back to index](README.md)

# Users cleaning

Allows executing actions on inactive users automatically.

## Cleaning cases

| Case | Description |
|---|---|
| No login | Users who have not logged in to the platform for a configured number of days |

## Configuration

- **Days without login:** Number of inactivity days to consider a user as a candidate (default: 365).
- **Cleaning action:** What to do with users that meet the condition.

## Available actions

| Action | Description |
|---|---|
| Suspend | Deactivates the user account. The user will not be able to log in, but their data is preserved |
| Delete | Removes the user account from the platform |

## Log

Each processed user is saved in an internal log with the applied case, the action performed and the last access date.
