# Manga Library - User Authentication & Collection Features Setup Guide

## Phase 1 Features Implemented

### 1. Database Setup
First, run the SQL commands in `users.sql` to create the necessary tables:

```sql
-- Run these commands in your MySQL database
-- This will create tables for users, collections, reviews, and preferences
```

### 2. New Files Created
- `users.sql` - Database schema for user features
- `auth.php` - Authentication class with login/register/logout
- `user_collection.php` - User collection management
- `register.php` - User registration page
- `login_fixed.php` - User login page (use this instead of login.php)
- `dashboard_simple.php` - User dashboard
- `logout.php` - Logout script

### 3. Setup Instructions

#### Step 1: Update Database
1. Open phpMyAdmin or MySQL client
2. Select your `manga_library` database
3. Run the SQL commands from `users.sql`

#### Step 2: Test Authentication
1. Visit `register.php` to create a new account
2. Visit `login_fixed.php` to login
3. Visit `dashboard_simple.php` to see your collection

#### Step 3: Update Navigation
Add these links to your navigation in `home.php`:
```php
<?php
session_start();
require_once 'auth.php';
$auth = new Auth($conn);
?>
```

Then in your navigation:
```html
<?php if ($auth->isLoggedIn()): ?>
    <li><a href="dashboard_simple.php">My Dashboard</a></li>
    <li><a href="logout.php">Logout</a></li>
<?php else: ?>
    <li><a href="login_fixed.php">Login</a></li>
    <li><a href="register.php">Register</a></li>
<?php endif; ?>
```

### 4. Next Steps to Complete Integration

#### Add "Add to Collection" Feature
Create `add_to_collection.php`:
```php
<?php
require_once 'auth.php';
require_once 'user_collection.php';

$auth = new Auth($conn);
$auth->requireLogin();

$userCollection = new UserCollection($conn);
$userCollection->addToCollection($_SESSION['user_id'], $_POST['manga_id'], 'want to read');
header('Location: browse.php');
?>
```

#### Update Browse Page
Add buttons to manga cards:
```php
<?php if ($auth->isLoggedIn()): ?>
    <form method="POST" action="add_to_collection.php">
        <input type="hidden" name="manga_id" value="<?= $row['id'] ?>">
        <button type="submit">Add to Collection</button>
    </form>
<?php endif; ?>
```

### 5. Security Features
- Password hashing with bcrypt
- SQL injection prevention with prepared statements
- Session management
- Input validation

### 6. Testing Checklist
- [ ] Register new user
- [ ] Login with credentials
- [ ] View dashboard
- [ ] Add manga to collection
- [ ] Update reading status
- [ ] Logout functionality

### 7. Future Enhancements
- Rating system
- Reading progress tracking
- Reviews and comments
- Social features
- API integrations
- Mobile responsive design

## Quick Start Commands
1. Import database: `mysql -u root -p manga_library < users.sql`
2. Test registration: Visit `register.php`
3. Test login: Visit `login_fixed.php`
4. Test dashboard: Visit `dashboard_simple.php`

## Support
For issues or questions, check the browser console for JavaScript errors and PHP error logs for server-side issues.
