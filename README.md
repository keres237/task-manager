# Task Manager - Cute Pastel Task Management Application

A beautiful, feature-rich task management application built with PHP and CSS, featuring a cute pastel design inspired by Trello and sticky notes.

# Features

# User Features
-User Authentication: Secure registration and login with password hashing (bcrypt)
-Task Management: Create, read, update, delete, and move tasks between categories
-Task Categories: Four main categories (Done, Doing, Macrotasks, Microtasks)
-Task History: View complete history of all your task actions
-Responsive Design: Works seamlessly on desktop and mobile devices

# Admin Features
-Admin Dashboard: Comprehensive system overview
-User Management: View all registered users and their activity
-Task Management: Monitor all tasks across the system
-System Statistics: View total users, tasks, and history entries
-Advanced Queries: Uses complex JOIN queries for data insights

# Technical Stack

- Backend: PHP 7.4+
- Database: MySQL/MariaDB
- Frontend: HTML5, CSS3, Vanilla JavaScript
- Security: Password hashing (bcrypt), prepared statements, session management

# Project Structure

\`\`\`
task-manager/
├── config/
│   ├── constants.php
│   └── database.php
├── includes/
│   ├── Auth.php
│   ├── Task.php
│   └── functions.php
├── api/
│   └── tasks/
│       ├── create.php
│       ├── update.php
│       ├── delete.php
│       └── move.php
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── admin/
│   └── dashboard.php
├── styles/
│   ├── auth.css
│   ├── dashboard.css
│   ├── history.css
│   └── admin.css
├── scripts/
│   ├── dashboard.js
│   └── admin.js
├── database/
│   └── schema.sql
├── dashboard.php
├── task-history.php
├── index.php
└── README.md
\`\`\`

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL/MariaDB 5.7 or higher
- A web server (Apache, Nginx, etc.)

### Steps

1. **Clone or download the project** to your web server directory

2. **Create a MySQL database**:
   \`\`\`sql
   CREATE DATABASE task_manager;
   \`\`\`

3. **Import the database schema**:
   - Open `database/schema.sql` in your MySQL client
   - Execute the SQL queries to create tables

4. **Update database configuration** in `config/database.php`:
   \`\`\`php
   define('DB_HOST', 'localhost');      // Your database host
   define('DB_USER', 'root');           // Your database username
   define('DB_PASS', '');               // Your database password
   define('DB_NAME', 'task_manager');   // Your database name
   \`\`\`

5. **Set up file permissions**:
   - Ensure the web server can read/write to the project directory

6. **Access the application**:
   - Navigate to `http://localhost/task-manager` (or your configured path)
   - Register a new account
   - Start managing your tasks!

## Usage

### For Regular Users

1. **Register**: Create a new account with username, email, and password
2. **Login**: Access your personalized dashboard
3. **Create Tasks**: Click the "+" button in any category column
4. **Manage Tasks**: 
   - Click the three-dot menu to update or delete
   - Use the dropdown to move tasks between categories
5. **View History**: Click "History" to see all your task actions
6. **Logout**: Use the logout button in the header

### For Admins

1. **Access Admin Dashboard**: Click "Admin" button in the header
2. **View Statistics**: See system-wide overview on the Statistics tab
3. **Manage Users**: View all users and their task counts
4. **Monitor Tasks**: See all tasks in the system
5. **Track Activity**: View system-wide task history

## Database Schema

### Users Table
- id: Primary key
- username: Unique username
- email: Unique email address
- password: Hashed password
- is_admin: Admin flag
- created_at, updated_at: Timestamps

### Categories Table
- id: Primary key
- name: Category name (Done, Doing, Macrotasks, Microtasks)
- color: Pastel color code
- description: Category description

### Tasks Table
- id: Primary key
- user_id: Foreign key to users
- category_id: Foreign key to categories
- title: Task title
- description: Task description
- position: Task position in category
- created_at, updated_at: Timestamps

### Task History Table
- id: Primary key
- task_id: Foreign key to tasks
- user_id: Foreign key to users
- action: Action type (created, updated, deleted, moved)
- old_data: Previous task data (JSON)
- new_data: New task data (JSON)
- created_at: Action timestamp

## API Endpoints

All API endpoints require user authentication via session.

### Tasks API

- **POST** `/api/tasks/create.php`: Create a new task
- **POST** `/api/tasks/update.php`: Update an existing task
- **POST** `/api/tasks/delete.php`: Delete a task
- **POST** `/api/tasks/move.php`: Move task to another category

## Color Scheme

The application uses a cute pastel color palette:
- **Pastel Blue**: #ADD8E6 (Primary)
- **Pastel Pink**: #FFB6C1 (Secondary)
- **Pastel Green**: #90EE90 (Success)
- **Pastel Yellow**: #FFFFE0 (Accent)

## Security Features

- Password hashing using bcrypt
- Prepared SQL statements to prevent SQL injection
- Session-based authentication
- Input sanitization and validation
- Admin role-based access control
- Row-level security with user_id checks

## Browser Compatibility

- Chrome/Chromium 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Troubleshooting

### Database Connection Error
- Verify database credentials in `config/database.php`
- Ensure MySQL service is running
- Check that the database exists

### Tasks Not Displaying
- Clear your browser cache
- Verify that you're logged in
- Check database permissions

### Admin Dashboard Not Accessible
- Ensure you're logged in as an admin user
- Only admin accounts can access the admin dashboard

## Future Enhancements

- Drag-and-drop task reordering
- Task filtering and search
- User profile customization
- Task sharing between users
- Email notifications
- Task priorities and labels
- Recurring tasks
- Mobile app
