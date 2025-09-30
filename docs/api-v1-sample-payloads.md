# API v1 Sample Payloads

## Google Authentication

```json
{
  "data": {
    "token": "1|XQ...",
    "user": {
      "id": 42,
      "name": "Google Person",
      "email": "google@example.com",
      "avatar_url": "https://example.com/avatar.png"
    }
  }
}
```

## Todo Lists Index

```json
{
  "data": {
    "lists": [
      {
        "id": 7,
        "name": "Shared Roadmap",
        "slug": "shared-roadmap-1a2b3c",
        "visibility": "shared",
        "owner": {
          "id": 1,
          "name": "Owner",
          "email": "owner@example.com",
          "avatar_url": null
        },
        "tasks": [
          {
            "id": 15,
            "title": "Draft requirements",
            "category": "planning",
            "is_done": false,
            "due_at": null,
            "assigned_to": {
              "id": 3,
              "name": "Editor",
              "email": "editor@example.com",
              "avatar_url": null
            },
            "created_at": "2025-09-25T12:00:00Z",
            "updated_at": "2025-09-25T12:05:00Z"
          }
        ],
        "collaborators": [
          {
            "id": 4,
            "role": "editor",
            "status": "accepted",
            "invited_at": "2025-09-25T11:50:00Z",
            "created_at": "2025-09-25T11:50:00Z",
            "updated_at": "2025-09-25T12:00:00Z",
            "user": {
              "id": 3,
              "name": "Editor",
              "email": "editor@example.com",
              "avatar_url": null
            }
          }
        ],
        "pending_invites": [],
        "created_at": "2025-09-25T11:45:00Z",
        "updated_at": "2025-09-25T12:05:00Z"
      }
    ],
    "invites": [
      {
        "id": 22,
        "email": "viewer@example.com",
        "role": "viewer",
        "status": "pending",
        "token": "c1df2c3e-...",
        "invited_at": "2025-09-25T11:55:00Z",
        "expires_at": "2025-10-02T11:55:00Z",
        "list": {
          "id": 7,
          "name": "Shared Roadmap",
          "slug": "shared-roadmap-1a2b3c",
          "owner": {
            "id": 1,
            "name": "Owner",
            "email": "owner@example.com",
            "avatar_url": null
          }
        }
      }
    ]
  }
}
```

## Location Shares Index

```json
{
  "data": {
    "outgoing": [
      {
        "id": 11,
        "name": "Morning Run",
        "session_token": "d3c74f64-...",
        "allow_live_tracking": true,
        "allow_history": true,
        "status": "active",
        "expires_at": null,
        "owner": {
          "id": 1,
          "name": "Owner",
          "email": "owner@example.com",
          "avatar_url": null
        },
        "participants": [
          {
            "id": 9,
            "email": "tracker@example.com",
            "role": "tracker",
            "status": "accepted",
            "invited_at": "2025-09-25T12:10:00Z",
            "responded_at": "2025-09-25T12:15:00Z",
            "user": {
              "id": 4,
              "name": "Tracker",
              "email": "tracker@example.com",
              "avatar_url": null
            }
          }
        ],
        "latest_point": {
          "id": 31,
          "lat": 12.345678,
          "lng": 98.765432,
          "recorded_at": "2025-09-25T12:20:00Z",
          "user": {
            "id": 4,
            "name": "Tracker",
            "email": "tracker@example.com",
            "avatar_url": null
          }
        },
        "created_at": "2025-09-25T12:00:00Z",
        "updated_at": "2025-09-25T12:20:00Z"
      }
    ],
    "incoming": [],
    "invites": [
      {
        "id": 13,
        "email": "guest@example.com",
        "role": "viewer",
        "status": "pending",
        "invited_at": "2025-09-25T12:05:00Z",
        "responded_at": null,
        "share": {
          "id": 11,
          "name": "Morning Run",
          "session_token": "d3c74f64-...",
          "status": "active",
          "owner": {
            "id": 1,
            "name": "Owner",
            "email": "owner@example.com",
            "avatar_url": null
          }
        }
      }
    ]
  }
}
```