# Company Event Management System
A web-based event management platform designed to streamline the organization, participation, and feedback process for corporate or institutional events.

---

## Key Features

### 1. User Authentication & Roles
- **Registration**: Employees can sign up for an account. Admin accounts are created by other admins.
- **Login**: Both Admins and Employees can log in securely. Passwords are hashed for security.
- **Role-based Access**: Admins have access to management features, while Employees can view and participate in events.

### 2. Event Management (Admin)
- **Create, Edit, Delete Events**: Admins can add new events, update details, or remove events.
- **Assign Staff**: Admins can assign Employees to events with specific roles (Organizer, MC, Speaker, Attendee).
- **View Participation**: See which staff are assigned to each event and their statuses.
- **Search & Filter**: Events can be searched by name or venue.

### 3. User Management (Admin)
- **Manage Accounts**: Admins can create, update, or delete Employee and Admin accounts.
- **Search Users**: Find users by name or email.

### 4. Event Participation (Employee)
- **View Assigned Events**: Employees see a list of events they are assigned to.
- **Respond to Assignments**: Employees can accept or decline event assignments.
- **Attendance Tracking**: For ongoing events, Employees can mark themselves as present or absent.

### 5. Feedback System
- **Submit Feedback**: Employees can submit feedback (rating and comments) for events they participated in.
- **View Feedback**: Admins can view all feedback for each event, including ratings and comments, and can delete inappropriate feedback.

### 6. Dashboard & Profile
- **Personal Dashboard**: Users have a dashboard showing their assignments, statuses, and notifications.
- **Profile Management**: Users can view their profile details.

### 7. Data & Security
- **Database**: MySQL database with tables for users, events, participants, and feedback.
- **Secure Sessions**: All sensitive actions require authentication.
- **Role Checks**: Critical actions are protected by role checks to prevent unauthorized access.

### 8. User Interface
- **Modern UI**: Responsive design with a sidebar for navigation, styled with CSS and gradients.
- **Landing Page**: Public homepage with branding, video background, and links to login/signup.

---

## Database Schema

- **users**: Stores user info, roles (Admin/Employee), and hashed passwords.
- **events**: Stores event details (name, date, time, venue, description, status).
- **participants**: Links users to events with roles and participation status.
- **feedback**: Stores event feedback from users (rating, comment, timestamp).

---

## Typical User Flows

### Admin
`Logs in → Manages users/events → Assigns staff → Views feedback`

### Employee
`Signs up/logs in → Views assigned events → Accepts/declines/marks attendance → Submits feedback`

---

## Technologies Used

- **Frontend**: HTML, CSS
- **Backend**: PHP
- **Database**: MySQL
- **Session Management**: PHP sessions for authentication and role management

---

## How to Use

1. **Setup the Database**: Import `eventmanagement.sql` into your MySQL server.
2. **Configure Database Connection**: Edit `db.php` with your database credentials.
3. **Run Locally**: Place the project in your web server directory (e.g., XAMPP's `htdocs`), and access via browser.
4. **Default Admin**: The SQL file creates a default admin account (`admin@gmail.com` / password is: 123).

Or, head to the [Deployment](#deployment) to test the system online!

---

## Group Project

This project was developed as part of a Semester 4 Diploma in Computer Science group assignment for the courses **CSC264 (INTRODUCTION TO WEB AND MOBILE APPLICATION)** and **ISP250 (INFORMATION SYSTEMS DEVELOPMENT)** at **Universiti Teknologi MARA (UiTM), Tapah Campus**.  
It is a custom event management system created for **PIKATS Travel & Tours Sdn. Bhd.**  
[More about PIKATS on Facebook](https://www.facebook.com/pikatstravel/)

---

## Credits

Developed by:
- MUHAMAD DANISH AIMAN BIN SOFIAN
- MOHAMMAD AIMAN SYAHMI BIN ZAINUDDIN
- MUHAMMAD SYAHIR AFIQ BIN ROSLEE
- MUHAMAD AQIL HAFIZI BIN MOHAMAD ALI SABRI
- MUHAMMAD FARIS AMZAR BIN MOHD NAZRI

---

## Deployment
[Click Here](https://pikats-events.free.nf/) to test the system online.  
  
Admin Account info:  
**Email**: admin@gmail.com  
**Password**: 123  
