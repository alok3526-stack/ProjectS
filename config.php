<?php
/**
 * Application & Database Configuration
 * Student Placement Management System
 */

// MongoDB Atlas Connection URI
// REPLACE the placeholder below with your MongoDB Atlas connection string:
// e.g.: mongodb+srv://myAdmin:Secret123@cluster0.abcde.mongodb.net/placement_db?retryWrites=true&w=majority
define('MONGODB_URI', getenv('MONGODB_URI') ?: 'mongodb+srv://hydrogenperoxide010_db_user:CcxQ5lkSCEClHViR@cluster0.s0nxkvo.mongodb.net/placement_db?retryWrites=true&w=majority');

// MongoDB Database Name
define('MONGODB_DB_NAME', getenv('MONGODB_DB') ?: 'placement_db');

// Application Details
define('APP_NAME', 'CampusHire | Placement Management System');
define('APP_TAGLINE', 'Connecting Top Students with World-Class Companies');
define('BASE_URL', '/');

// Security & Session Settings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
