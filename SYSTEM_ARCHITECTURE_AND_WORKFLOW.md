# Home Cleaning Services System - Backend Architecture & Workflow

## 1. Folder Structure Breakdown

The backend is built on **Laravel 12.x**, following the standard MVC (Model-View-Controller) architecture, optimized for API-first development.

### **Major Directories**

| Directory | Purpose | Key Files/Roles |
| :--- | :--- | :--- |
| **`app/Http/Controllers`** | Handles incoming HTTP requests and returns responses. | `AuthController.php` (Auth), `BookingController.php` (Core Logic), `AdminDashboardController.php`. |
| **`app/Http/Middleware`** | Filters HTTP requests entering the application. | `CheckRole.php` (Role-based access control: Admin/Customer/Cleaner). |
| **`app/Models`** | Eloquent ORM classes representing database tables. | `User.php`, `Booking.php`, `Service.php`, `CleanerProfile.php`. |
| **`app/Mail`** | Mailable classes for email notifications. | `OtpMail.php` (Sends One-Time Passwords). |
| **`config`** | Configuration files for the application. | `auth.php` (Guards), `sanctum.php` (API Tokens), `cors.php` (Frontend access). |
| **`database/migrations`** | Version control for the database schema. | User, Booking, Service, and CleanerProfile table definitions. |
| **`routes`** | Defines application URL endpoints. | **`api.php`** (All API routes defined here). |
| **`tests`** | Automated tests. | `Feature/SystemWorkflowTest.php` (End-to-End Validation). |

---

## 2. CRUD Functionalities

The system implements full CRUD operations across four main domains.

### **A. User Management (Admin)**
*   **Controller**: `UserController`
*   **Model**: `User`, `CleanerProfile`
*   **Routes**: `/api/admin/users`

| Operation | Method | Endpoint | Description |
| :--- | :--- | :--- | :--- |
| **Create** | `POST` | `/users` | Creates Customer or Cleaner. Auto-creates `CleanerProfile` if role is cleaner. |
| **Read** | `GET` | `/users` | Lists users. Supports filtering by `role`. |
| **Update** | `PUT` | `/users/{id}` | Updates name, email, phone. |
| **Delete** | `DELETE` | `/users/{id}` | Soft or hard deletes user account. |

### **B. Service Management (Admin)**
*   **Controller**: `ServiceController`
*   **Model**: `Service`
*   **Routes**: `/api/admin/services` (Admin), `/api/services` (Public)

| Operation | Method | Endpoint | Description |
| :--- | :--- | :--- | :--- |
| **Create** | `POST` | `/admin/services` | Adds new cleaning service (Title, Price, Duration). |
| **Read** | `GET` | `/services` | Public list of available services. |
| **Update** | `PUT` | `/admin/services/{id}` | Modifies pricing or details. |
| **Delete** | `DELETE` | `/admin/services/{id}` | Removes a service. |

### **C. Booking Management**
*   **Controller**: `BookingController`
*   **Model**: `Booking`
*   **Relationships**: `belongsTo(User - Client)`, `belongsTo(Service)`, `belongsTo(User - Cleaner)`
*   **Routes**: `/api/admin/bookings`

| Operation | Method | Endpoint | Description |
| :--- | :--- | :--- | :--- |
| **Create** | `POST` | `/bookings` | Creates booking. Checks for cleaner availability/conflicts. |
| **Read** | `GET` | `/bookings` | Lists bookings with related Client, Service, and Cleaner data. |
| **Update** | `PUT` | `/bookings/{id}` | **Assigns Cleaner**, updates status (`confirmed`, `completed`). |
| **Delete** | `DELETE` | `/bookings/{id}` | Cancels/Removes booking. |

### **D. Cleaner Profile Management**
*   **Controller**: `CleanerController`
*   **Model**: `CleanerProfile`
*   **Routes**: `/api/admin/cleaners`

| Operation | Method | Endpoint | Description |
| :--- | :--- | :--- | :--- |
| **Create** | `POST` | `/cleaners` | Registers user + specialized profile (Skills, Experience). |
| **Read** | `GET` | `/cleaners` | Lists cleaners with rating and job stats. |
| **Update** | `PUT` | `/cleaners/{id}` | Updates skills, approval status, or experience. |
| **Delete** | `DELETE` | `/cleaners/{id}` | Removes cleaner from system. |

---

## 3. System Flow Analysis

### **Request Lifecycle**
1.  **Entry**: Client sends JSON Request to `http://localhost/api/...`.
2.  **Routing**: `routes/api.php` matches the URL.
3.  **Middleware**:
    *   `auth:sanctum`: Verifies the Bearer Token.
    *   `CheckRole:{role}`: Verifies if user is Admin, Customer, or Cleaner.
4.  **Controller**: Executes business logic, validates input, calls Models.
5.  **Database**: Eloquent ORM interacts with MySQL.
6.  **Response**: Controller returns JSON response (Success/Error).

### **Authentication Flow**
1.  **Register**: User POSTs details -> System generates OTP -> Sends Email (`OtpMail`).
2.  **Verify**: User POSTs OTP -> System marks `email_verified_at`.
3.  **Login**: User POSTs Credentials -> System checks Password & Verification -> Returns **Sanctum Token**.
4.  **Access**: Subsequent requests include `Authorization: Bearer {token}`.

### **Booking & Assignment Flow**
1.  **Initiation**: Booking created (Status: `pending`, Cleaner: `null`).
2.  **Assignment**: Admin updates booking -> Sets `cleaner_id`.
3.  **Conflict Check**: `BookingController` checks if Cleaner has overlapping jobs.
4.  **Confirmation**: Status becomes `confirmed`.
5.  **Execution**: Cleaner views job in Dashboard -> Completes job -> Status `completed`.

---

## 4. Data Flow & Database Schema

### **Core Entities & Relationships**
*   **Users Table**: Central identity.
    *   `id`, `name`, `email`, `role`, `otp`.
*   **CleanerProfiles Table**: Extension of User.
    *   `user_id` (FK), `skills` (JSON), `rating`, `is_approved`.
*   **Services Table**: Catalog.
    *   `title`, `price`, `duration`.
*   **Bookings Table**: The transactional record.
    *   `user_id` (Client FK)
    *   `cleaner_id` (Cleaner FK - Nullable)
    *   `service_id` (Service FK)
    *   `date`, `time`, `status`.

### **Data Transformation**
*   **Input**: JSON Payloads are validated using Laravel `Request->validate()`.
*   **Storage**: Dates are stored as `YYYY-MM-DD`, Time as string or timestamp.
*   **Output**: Eloquent Resources or manual mapping (e.g., `BookingController::index`) transform DB records into frontend-friendly JSON (formatting prices, dates).

---

## 5. Dependencies

The system relies on standard Laravel ecosystem packages managed via **Composer**:

*   **`laravel/framework` (^12.0)**: The core PHP framework.
*   **`laravel/sanctum` (^4.0)**: Lightweight authentication system for SPAs and mobile APIs.
*   **`laravel/tinker`**: Interactive REPL for debugging.
*   **`guzzlehttp/guzzle`**: HTTP client (used by Mail/Service integrations).
*   **`fakerphp/faker`**: Generates fake data for seeding and testing.
*   **`phpunit/phpunit`**: Testing framework used for the End-to-End tests.

---

## 6. Configuration

### **Key Configuration Files**
*   **`.env`**: Environment variables (Git-ignored).
    *   `DB_CONNECTION=mysql`
    *   `DB_DATABASE=leirad_massage`
    *   `MAIL_MAILER=smtp` (Configured for Gmail in local dev).
*   **`config/cors.php`**: configured to allow requests from the frontend (e.g., `http://localhost:3000` or `*`).
*   **`config/auth.php`**:
    *   Defaults: `guard => web` (switched to `sanctum` for API).
    *   Providers: `users` (Eloquent User model).

### **Environment Specifics**
*   **Local**: `APP_DEBUG=true`, `APP_ENV=local`.
*   **Testing**: `phpunit.xml` overrides `.env` to use in-memory SQLite or separate testing DB to prevent data corruption.
