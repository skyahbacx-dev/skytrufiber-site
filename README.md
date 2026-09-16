## Database Setup (Local)

Requirements:
- XAMPP
- MySQL / MariaDB

Steps:
1. Start XAMPP → MySQL
2. Create database:
   CREATE DATABASE skytrufiber_db;
3. Import schema:
   mysql -u root skytrufiber_db < database/schema.sql
