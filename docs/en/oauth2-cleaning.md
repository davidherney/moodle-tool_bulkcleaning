[← Back to index](README.md)

# OAuth2 cleaning

Removes OAuth2 linked login records based on configured cases.

## Cleaning cases

| Case | Description |
|---|---|
| Deleted users | Removes OAuth2 linked logins for users marked as deleted |
| Suspended users | Removes OAuth2 linked logins for users marked as suspended |
| Email not match with provider | Removes automatic links where the Moodle user email does not match the OAuth2 provider email |

One or more cases can be active at the same time.

## How it works

- The scheduled task reads the enabled cases from plugin settings.
- For deleted/suspended users, it removes linked logins by user id.
- For email mismatch, it removes only automatic links: if the link was created by the system or if the user who created it is
the same user (this is the common case for links created when authenticating)

## Log

Each removed link is stored in an internal log with:

- User id.
- Applied case type.
- Case-specific details (for example deleted/suspended flags or compared emails).
