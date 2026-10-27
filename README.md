# Hostel Maintenance & Complaint Tracker

A modular, responsive web application for managing and tracking hostel maintenance complaints across categories like Electrical, Plumbing, Network, Housekeeping, and Mess & Hygiene.

Built strictly according to academic lab guidelines (**NCSM301: Web Development Laboratory**) using procedural **PHP**, **MySQL**, **Vanilla JavaScript/jQuery**, and **Vanilla CSS**.

---

## 📌 Features

### 👨‍🎓 Student Portal
1. **Secure Student Authentication & Auto-Fill:** Log in using Admission Number and Password. The complaint submission form automatically pre-fills Admission Number, Room Number, and Block from `$_SESSION`.
2. **Admission Number Regex Validation:** Client-side validation enforcing the institute format (`/^24JE\d{4}$/i`).
3. **Categories:** Electrical, Plumbing, Network, Housekeeping, and Mess & Hygiene.
4. **Paginated Complaint History:** View a dedicated table of only student's own tickets, paginated to 10 rows per page with Prev/Next controls.
5. **Complaint Cancellation:** Cancel tickets while in `Submitted` status.
6. **Post-Resolution Rating:** 1-to-5 star rating dropdown appears for resolved tickets to submit feedback.
7. **Read-Only Noticeboard:** Real-time notices published by hostel wardens/staff.

### 🛡️ Admin / Staff Portal
8. **Dashboard Statistics:** Metric cards displaying:
   - **Total Open:** Submitted + In Progress complaints
   - **Resolved Today:** Complaints resolved on current date
   - **Pending (> 48 hours):** Unresolved complaints submitted more than 48 hours ago
9. **Notice Management:** Publish notices with title and message directly to the student noticeboard.
10. **Attribute Filters:** Filter master complaints by Hostel Block (A, B, C), Category, and Status.
11. **Master Paginated Table:** 10 rows per page with Prev/Next navigation and count indicators.
12. **Staff Assignment & Status Updates:** Asynchronous status transition. When moving a ticket from `Submitted` to `In Progress`, admins **must** select a designated staff member (e.g., Rajesh - Plumber, Suresh - Electrician, etc.). Tickets can then transition to `Resolved`.

### 🔔 Global UI
13. **Flash Messages (Toast Notifications):** Non-blocking toast popup in the bottom corner of the screen that fades out after 3 seconds for all async actions (submission, status updates, staff assignment, cancellation, rating, notice posting). Zero `alert()` popups.

---

## 🚀 Getting Started

### Prerequisites
- **PHP** 7.4+ or 8.x
- **MySQL** / MariaDB

### 1. Database Setup
Ensure MySQL is running, then import the database schema:
```bash
mysql -u root < schema.sql
```
*(If your MySQL user requires a password, configure it in `db.php`)*

### 2. Start Web Server
Run PHP's built-in web server:
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
├── admin_dashboard.php    # Admin dashboard (Stats, Filters, Master Table, Staff Assignment)
├── api_actions.php        # POST API router (submit, update_status, cancel, rate, post_notice)
├── api_fetch.php          # GET API router (paginated tables, stats, notices)
├── db.php                 # MySQL database connection (mysqli with prepared statements)
├── index.php              # Login page (Student & Admin toggle)
├── logout.php             # Session destruction and logout handler
├── schema.sql             # SQL schema (with assigned_staff & seed data)
├── script.js              # Client-side validation, toast notifications, pagination, async DOM
├── student_dashboard.php  # Student portal (Noticeboard, Form, Paginated History)
└── styles.css             # Vanilla CSS design system (Box model, tables, badges, toast animations)
```
