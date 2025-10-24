# CourtMaster Football Court Booking System

A PHP & MySQL-based web application for managing football court bookings.  

## Features
- Admin & User Authentication
- Court Availability Management
- Maintenance Scheduling
- Booking & Cancellation System
- Revenue Tracking

## Technologies Used
- PHP
- MySQL (via XAMPP)
- Bootstrap
- JavaScript (AJAX)
- Apache Server

## Setup Instructions
1. Install XAMPP.
2. Copy this project to `C:\xampp\htdocs\courtmaster-api`.
3. Create a MySQL database named `courtmaster`.
4. Import the included SQL file `courtmaster.sql` using phpMyAdmin.
5. Update `db.php` if needed (database credentials).
6. Start Apache & MySQL from XAMPP Control Panel.
7. Visit `http://localhost/courtmaster-api` in your browser.

## Default Accounts
- **Admin Login:** admin@example.com / admin123  
- **Sample User:** mohammed@gmail.com / reaper

# Database Schema
- users – stores user details (user_id, name, email, password, role)
- admins – stores admin credentials
- availability – stores admin-defined available court times
- courts – stores court details (id, name, location, open/close times)
- bookings – stores user bookings with start/end times, prices, and statuses
- maintenance – stores blocked maintenance periods for each court
- notifications – Stores system notifications sent to users (e.g., booking confirmations, cancellations, reminders) // Half of it currently does not work.
