# Live Sessions API

Base path: /api/v1

Authentication: Bearer token (Sanctum)

Endpoints

- POST /live-sessions
  - Body: { "title": "string" }
  - Response 201:
    { "data": { "session_id": "uuid-v4", "title": "Walk", "created_at": "2025-09-08T..." } }

- POST /live-sessions/{session_id}/update
  - Body: { "latitude": number, "longitude": number, "accuracy"?: int, "timestamp"?: ISO8601 }
  - Response 200: { "status": "ok" }
  - Behavior: upserts last-known location and appends to history; publishes to Redis channel live_session:{session_id}

- GET /live-sessions/{session_id}
  - Response 200:
    { "data": { "session_id":"...","title":"...","latitude":12.34,"longitude":56.78,"accuracy":8,"timestamp":"ISO8601","is_active":true } }
  - 404 if not found; 410 if ended

- POST /live-sessions/{session_id}/end
  - Response 200: { "status": "ended" }
  - Only owner may end session

- GET /live-sessions/{session_id}/events (SSE)
  - Streams SSE messages where each data payload is JSON like:
    { "session_id":"uuid-v4", "latitude":12.34, "longitude":56.78, "accuracy":10, "timestamp":"ISO8601" }

Notes
- Rate limiting: update endpoint should be limited (1 req/sec) by infrastructure; code-level rate limiting can be added via middleware throttle.
- Pub/Sub: Redis is used to publish updates so multiple instances can forward to clients.

Client contract for Flutter
- All responses use JSON with the main payload under "data".
- Use Authorization: Bearer <token> header.
