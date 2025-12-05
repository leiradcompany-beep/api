# System Workflow Test Documentation

This document describes the expanded automated test suite located in `tests/Feature/SystemWorkflowTest.php`. These tests cover the full CRUD functionalities and role-based workflows for Admins, Customers, and Cleaners, ensuring the "whole entire system" works as expected.

## Test Overview

The suite is designed to validate:
1.  **Admin Powers**: Full control over Users, Cleaners, and Bookings.
2.  **Customer Experience**: Profile management (with booking validation handled via Admin simulation due to current API structure).
3.  **Cleaner Experience**: Job assignment visibility.

## Detailed Test Cases

### 1. Admin User Management (`test_admin_can_manage_users`)
*   **Scenario**: Admin manages the user base.
*   **Steps**:
    1.  **Create**: `POST /api/admin/users` (Creates a new Customer).
        *   *Check*: Returns 201, data matches.
    2.  **Read**: `GET /api/admin/users?role=customer`.
        *   *Check*: Returns 200, verifies filtering works.
    3.  **Update**: `PUT /api/admin/users/{id}`.
        *   *Check*: Returns 200, name is updated.
    4.  **Delete**: `DELETE /api/admin/users/{id}`.
        *   *Check*: Returns 200, user is removed from database.

### 2. Admin Cleaner Management (`test_admin_can_manage_cleaners`)
*   **Scenario**: Admin hires and manages cleaners (Specialized profile).
*   **Steps**:
    1.  **Create**: `POST /api/admin/cleaners` with skills and experience.
        *   *Check*: Returns 201, confirms `job_title` (e.g., "Senior Cleaner").
    2.  **Read**: `GET /api/admin/cleaners`.
        *   *Check*: Returns 200, list contains the cleaner's name.
    3.  **Update**: `PUT /api/admin/cleaners/{id}` (e.g., Update experience years).
        *   *Check*: Returns 200, data persists.
    4.  **Delete**: `DELETE /api/admin/cleaners/{id}`.
        *   *Check*: Returns 200, removes both User and CleanerProfile records.

### 3. Admin Booking Lifecycle (`test_admin_booking_lifecycle`)
*   **Scenario**: The core business logic - scheduling a service.
*   **Steps**:
    1.  **Create Booking**: `POST /api/admin/bookings` (Initial status: 'pending').
        *   *Check*: Returns 201, booking created unassigned.
    2.  **Assign Cleaner**: `PUT /api/admin/bookings/{id}` with `cleaner_id` and status 'confirmed'.
        *   *Check*: Returns 200, booking is now linked to the cleaner and confirmed.

### 4. Customer Profile Management (`test_customer_profile_management`)
*   **Scenario**: Customer manages their own account details.
*   **Steps**:
    1.  **View Profile**: `GET /api/user`.
        *   *Check*: Returns 200, verifies email.
    2.  **Update Profile**: `POST /api/settings/profile`.
        *   *Payload*: `firstName`, `lastName`, `email`, `phone`.
        *   *Check*: Returns 200, database updates `name` (concatenated) and `phone`.

### 5. Cleaner Job Visibility (`test_cleaner_view_assignments`)
*   **Scenario**: A cleaner checks their schedule.
*   **Steps**:
    1.  Setup: Create Cleaner, Customer, Service, and a 'confirmed' Booking linking them.
    2.  **View Dashboard**: `GET /api/cleaner/dashboard`.
    3.  **Validation**: Parses the `jobs` array to ensure the specific booking (Customer Name) appears.

## Running the Tests

To execute the system workflow tests:

```bash
php artisan test tests/Feature/SystemWorkflowTest.php
```

To run ALL tests (including the previous End-to-End tests):

```bash
php artisan test
```
