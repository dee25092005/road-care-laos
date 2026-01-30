# Road_Care System(Learning Project)

A full-stack application for community issue reporting, featuring geolocation and administrative management.

## ✨ Features

* **User Reporting:** Submit issues with title, description, and image uploads.
* **Geolocation:** Integrated latitude and longitude tracking for precise issue mapping.
* **Admin Dashboard:**
    * **Search & Filter:** Advanced filtering by status (Pending/Fixed) and keyword search.
    * **Pagination:** Efficient data handling for large report volumes.
    * **Status Management:** One-click status toggling for administrators.
* **Automated Testing:** Comprehensive Feature and Unit tests covering CRUD operations and storage logic.

## 🛠️ Tech Stack

* **Backend:** Laravel 11
* **Frontend:** Vue.js 3 (Composition API) with Inertia.js
* **Styling:** Tailwind CSS
* **Database:** PostgreSQL
* **Testing:** PHPUnit

## ⚙️ Quick Start

### 1. Installation
```bash
git clone [https://github.com/your-username/report-system.git](https://github.com/your-username/report-system.git)
cd report-system
composer install
npm install
```
### 2. Environment & Database
```Bash
cp .env.example .env
php artisan key:generate
# Configure your DB_DATABASE, DB_USERNAME, etc. in .env
php artisan migrate
php artisan storage:link
```
3. Run Development Server
```Bash
# In terminal 1
php artisan serve

# In terminal 2
npm run dev
```
🧪 Testing
The project uses PHPUnit for automated testing. I have focused on Feature Testing to ensure the stability of the report submission and admin workflows.

Run the tests using:

```Bash
php artisan test
```
Current test coverage:
* Index/Dashboard Access: Ensures Inertia responses and data structures are correct.
* Store Logic: Validates data input and image storage on the public disk.
* Admin Actions: Verifies unauthorized users cannot update report statuses.

👤 Author
Phonepaseuth (Dee) - Developer (Student)

National University of Laos
