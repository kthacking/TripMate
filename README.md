# TripMate Setup & Usage Guide

## Installation
1. Ensure you have **XAMPP** (or any PHP/MySQL environment) installed.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open your browser and go to the installation page to set up the database:
   `http://localhost/project/tripmate/install.php`
   *(This will create the `tripmate_db` database and all necessary tables).*

## How to Run
Once installed, visit the landing page:
`http://localhost/project/tripmate/index.php`

## Default Admin Credentials
- **Email**: `admin@tripmate.com`
- **Password**: `admin123`
*(Note: As per requirements, simple Hashing is used).*

## Features Overview
- **Student**: Register, Browse Trips, Search by ID, Request to Join, Chat (once approved), Upload Media.
- **TripMaker**: Create Trips, Manage Requests (Approve/Reject), Post Updates, Upload Content.
- **Admin**: Full control over all trips and users.

## Troubleshooting
- If images don't upload, ensure the `uploads/` folder exists in the root directory.
- If you get a database error, check `db.php` if the password for root is empty (default XAMPP).
