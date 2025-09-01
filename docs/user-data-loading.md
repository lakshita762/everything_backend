# User Data Loading

This document explains how user data (todos, expenses, and location entries) is loaded when users interact with the application.

## Overview

When a user logs in or requests their profile data, the application automatically loads their previous data to provide a seamless experience.

## Data Loading Points

### 1. Login (`POST /api/v1/login`)
- When a user successfully logs in, their latest data is automatically loaded
- Returns the user object with their todos, expenses, and location entries
- Data is limited to the most recent items (configurable)

### 2. Profile Data (`GET /api/v1/me`)
- When a user requests their profile information
- Loads the same data as login for consistency
- Useful for refreshing data without re-authentication

### 3. On-Demand Loading (`GET /api/v1/load-data`)
- Allows loading user data at any time
- Useful for refreshing data in the frontend
- Requires valid authentication token

## Configuration

The data loading behavior can be configured in `config/user_data.php`:

```php
'limits' => [
    'todos' => env('USER_TODOS_LIMIT', 100),
    'expenses' => env('USER_EXPENSES_LIMIT', 100),
    'location_entries' => env('USER_LOCATION_ENTRIES_LIMIT', 100),
],
```

### Environment Variables

You can customize the limits using these environment variables:

```env
USER_TODOS_LIMIT=50
USER_EXPENSES_LIMIT=75
USER_LOCATION_ENTRIES_LIMIT=25
```

## Data Structure

The loaded data includes:

### Todos
- `id`: Unique identifier
- `title`: Todo title
- `is_done`: Completion status (boolean)
- `category`: Todo category
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

### Expenses
- `id`: Unique identifier
- `title`: Expense description
- `amount`: Expense amount (decimal)
- `category`: Expense category
- `date`: Expense date
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

### Location Entries
- `id`: Unique identifier
- `title`: Location name/description
- `latitude`: GPS latitude
- `longitude`: GPS longitude
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

## Performance Considerations

- Data is eager loaded to prevent N+1 query problems
- Limits are applied to prevent excessive data transfer
- Data is ordered by latest first for most relevant items
- Consider implementing pagination for large datasets

## API Response Example

```json
{
    "success": true,
    "message": "Logged in",
    "data": {
        "token": "1|abc123...",
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "todos": [
                {
                    "id": 1,
                    "title": "Buy groceries",
                    "is_done": false,
                    "category": "shopping"
                }
            ],
            "expenses": [
                {
                    "id": 1,
                    "title": "Lunch",
                    "amount": "15.50",
                    "category": "food",
                    "date": "2024-01-15"
                }
            ],
            "location_entries": [
                {
                    "id": 1,
                    "title": "Home",
                    "latitude": "40.7128",
                    "longitude": "-74.0060"
                }
            ]
        }
    }
}
```
