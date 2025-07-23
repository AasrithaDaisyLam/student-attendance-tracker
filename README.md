# Student Attendance Tracker

## Description

The **Student Attendance Tracker** is a web-based application designed to simplify attendance management for schools from LKG to Class 10. The system allows administrators to manage students, faculty, classes, subjects, and daily attendance efficiently. It includes a role-based login system with dedicated dashboards for both admins and faculty members.

This project was developed as part of our internship under the guidance of *Team Trinwo*.

---

## Features

- Admin and Faculty login with role-based dashboards
- Add/Edit/Delete student and faculty records
- Assign subjects and sections to faculty members
- Mark and view subject-wise attendance
- Real-time search and filter options
- Responsive layout using Bootstrap
- MySQL backend integration for dynamic data management

---

## Setup Instructions

### 1. Clone the Repository

```bash
git clone https://github.com/AasrithaDaisyLam/student-attendance-tracker.git
```
### 2. Set Up the Database

- Open phpMyAdmin (or your MySQL tool)
- Create a new database (e.g., attendance_db)
- Import the attendance_tracker.sql file provided in the repo, inside the "database" folder

### 3. Configure Database Connection

- Open the conn.php file
- Update the database credentials to match your local hosting setup
```bash
$host = "localhost";
$user = "root";
$password = "";
$dbname = "attendance_db";
```

### 4. Run the website

- If using XAMPP or WAMP, place the project folder inside htdocs or www
- Start Apache and MySQL
- Open a browser and visit:
```bash
http://localhost/student-attendance-tracker/index.html
```
Here you can login or signup as Admin or login as Faculty

If you wish to login as admin, use these demo credentials:

Username: Admin

Password: admin@123

---
### How to use the website?

All screenshots, page walkthroughs, and usage instructions are included in the User Manual (Student Attendance Tracker User Manual(1)), please go through it.

### Technologies Used
- Frontend: HTML5, CSS3, JavaScript, Bootstrap

- Backend: PHP

- Database: MySQL

- Design Tools: Figma (UI/UX), draw.io (ER and flow diagrams)

- Version Control: Gitlab & GitHub

- Hosting for demo/testing: InfinityFree

### Team Members
- Aasritha Daisy Lam
- Kunam Day Harika
- Medapati Adi Lakshmi
- Mounika Pachhala
- Yarapa Venu Gopal
