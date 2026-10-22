<?php
/**
 * Application & Database Configuration
 * Student Placement Management System
 */

// MongoDB Atlas Connection URI
// Set MONGODB_URI as an environment variable (Render: Environment tab).
// Never put real credentials in this file.
define('MONGODB_URI', getenv('MONGODB_URI') ?: 'mongodb+srv://<username>:<password>@<cluster>.mongodb.net/placement_db?retryWrites=true&w=majority');

// MongoDB Database Name
define('MONGODB_DB_NAME', getenv('MONGODB_DB_NAME') ?: (getenv('MONGODB_DB') ?: 'placement_db'));

// Application Details
define('APP_NAME', 'CampusHire | Placement Management System');
define('APP_TAGLINE', 'Connecting Top Students with World-Class Companies');
define('BASE_URL', '/');

// Security & Session Settings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
