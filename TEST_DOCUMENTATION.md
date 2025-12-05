# API End-to-End Test Documentation

This document describes the automated end-to-end tests implemented for the Home Cleaning Services System API. The tests are located in `tests/Feature/EndToEndTest.php`.

## Test Overview

The tests cover the following core functionalities:
1.  **Authentication Flow**: Registration, OTP Verification, Login, Logout.
2.  **Service Management (Admin)**: Create, Read, Update, Delete (CRUD) operations for services.
3.  **Booking System**: Creating bookings and validating database persistence.
4.  **Cleaner Workflow**: Cleaners viewing their dashboard and assigned jobs.

## Test Cases

### 1. Authentication Flow (`test_auth_flow_register_verify_login_logout`)
*   **Scenario**: A new user registers, verifies their account via OTP, logs in to get a token, accesses a protected route, and logs out.
*   **Steps**:
    1.  `POST /api/register`: Create a new user.
        *   *Validation*: Returns 201 Created, database has user.
    2.  Retrieve OTP from database (simulating email retrieval).
    3.  `POST /api/verify-otp`: Verify user email.
        *   *Validation*: Returns 200 OK, returns Auth Token.
    4.  `GET /api/user`: Access protected user profile using token.
        *   *Validation*: Returns 200 OK, returns correct user data.
    5.  `POST /api/logout`: Invalidate token.
        *   *Validation*: Returns 200 OK, message "Logged out successfully".

### 2. Admin Service CRUD (`test_admin_service_crud`)
*   **Scenario**: An Admin user manages cleaning services.
*   **Steps**:
    1.  **Create**: `POST /api/admin/services` with service details.
        *   *Validation*: Returns 201 Created, database contains service.
    2.  **Read (All)**: `GET /api/services` (Public).
        *   *Validation*: Returns 200 OK, list contains the new service.
    3.  **Read (One)**: `GET /api/admin/services/{id}`.
        *   *Validation*: Returns 200 OK, returns specific service details.
    4.  **Update**: `PUT /api/admin/services/{id}` with modified price/title.
        *   *Validation*: Returns 200 OK, database reflects changes.
    5.  **Delete**: `DELETE /api/admin/services/{id}`.
        *   *Validation*: Returns 200 OK, database no longer has the service.

### 3. Customer Booking Flow (`test_customer_booking_flow`)
*   **Scenario**: A booking is created for a customer and service.
*   **Steps**:
    1.  Create Customer and Service via factories.
    2.  `POST /api/admin/bookings` (simulating booking creation).
    3.  *Validation*: Returns 201 Created.
    4.  *Database Check*: Verify `bookings` table contains a record linking the User and Service.

### 4. Cleaner Flow (`test_cleaner_flow`)
*   **Scenario**: A Cleaner checks their dashboard for assigned jobs.
*   **Steps**:
    1.  Create Cleaner user and profile.
    2.  Create a Booking assigned to this Cleaner.
    3.  `GET /api/cleaner/dashboard` using Cleaner's token.
    4.  *Validation*: Returns 200 OK, JSON structure matches dashboard requirements.
    5.  *Data Check*: Verify the assigned booking appears in the `jobs` list.

## Running the Tests

To execute the test suite, run the following command from the `backend` directory:

```bash
php artisan test tests/Feature/EndToEndTest.php
```

## Database Interactions
The tests use the `RefreshDatabase` trait, which ensures that:
*   Each test runs within a database transaction.
*   Data created during the test is rolled back after the test completes.
*   This prevents pollution of the development database while verifying real MySQL interactions.
