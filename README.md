# Hostel Maintenance & Complaint Tracker

A modular, responsive web application for managing and tracking hostel maintenance complaints across categories like Electrical, Plumbing, Network, Housekeeping, and Mess & Hygiene.

Built with **PHP**, **MySQL**, **Vanilla JavaScript**, and **CSS**.

---

## 📌 Features

### 👨‍🎓 Student Portal
- **Secure Authentication**: Student login with Admission Number validation (institute format `24JE####`).
- **Auto-filled Details**: Student name, room number, and block are automatically loaded from session.
- **Complaint Submission**: Choose category (Electrical, Plumbing, Network, Housekeeping, Mess & Hygiene) and describe the issue.
- **Real-Time Status Tracking**: Dynamic status indicators (`Submitted` ➔ `In Progress` ➔ `Resolved` ➔ `Cancelled`).
- **Complaint Cancellation**: Students can cancel complaints that are still in `Submitted` status.
- **Feedback & Rating**: Rate resolved complaints from 1 to 5 stars.
- **Notice Board**: View real-time notices posted by hostel administration.

### 🛡️ Admin / Staff Portal
- **Secure Admin Login**: Staff portal for hostel caretakers/wardens.
- **Overview Dashboard & Stats**: Total complaints, pending, in-progress, and resolved metrics.
- **Multi-Filter & Search**: Filter complaints by Block (Block A, B, C), Category, and Status.
- **Asynchronous Status Updates**: Update ticket status directly without page reloads (AJAX).
- **Notice Management**: Publish notices to the student announcement board.

---

## 🚀 Getting Started

### Prerequisites
- **PHP** (version 7.4 or higher, PHP 8+ supported)
- **MySQL** / MariaDB

### 1. Database Setup
Ensure MySQL is running, then import the database schema:
```bash
mysql -u root < schema.sql
```
*(If your MySQL user requires a password, update your credentials in `db.php`)*

### 2. Run the Application
Start PHP's built-in web server:
```bash
php -S localhost:8000
```

Open your browser and navigate to:
```
http://localhost:8000
```

---

## 🔑 Test Credentials

### 👨‍🎓 Student Accounts
| Admission No | Password | Details |
|---|---|---|
| `24JE0001` | `student123` | Rahul Sharma (Room 101, Block A) |
| `24JE0002` | `student123` | Priya Verma (Room 205, Block B) |
| `24JE0003` | `student123` | Arjun Mehta (Room 310, Block C) |

### 🛡️ Admin Account
| Username | Password |
|---|---|
| `admin` | `admin123` |

---

## 📂 Project Structure

```
├── admin_dashboard.php    # Admin dashboard interface
├── api_actions.php        # API endpoints for CRUD actions (status update, rating, notices)
├── api_fetch.php          # API endpoints for asynchronous data fetching
├── db.php                 # MySQL database connection (mysqli)
├── index.php              # Login page (Student & Admin toggle)
├── logout.php             # Session destruction and logout handler
├── schema.sql             # SQL database schema and seed data
├── script.js              # Client-side AJAX, validation, dynamic UI logic
├── student_dashboard.php  # Student portal interface
└── styles.css             # Vanilla CSS design system
```
