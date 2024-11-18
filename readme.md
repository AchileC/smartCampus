# Smart Campus

![Symfony Version](https://img.shields.io/badge/Symfony-6.4-green)
![PHP Version](https://img.shields.io/badge/PHP-8.1-blue)

Smart Campus is a Symfony application designed to manage campus rooms. It allows you to track room status, see the température, humidity, CO2 level, and facilitates room management through an intuitive interface. The application includes administration tools for adding, editing, and deleting rooms.

---

## 🚀 Features

- **Comprehensive Room Tracking**:
  - Add, edit, and delete rooms.
  - Filter and search rooms by name, floor, or status.
  - Track room statuses (`Ok`, `Problem`, `Critical`, etc.).
- **Intuitive and Modern Interface**
- **Extensible System**:
  - Ready for integration with real-time acquisition systems.

---

## 🛠️ Technologies Used

- **Backend**: Symfony 6.4, PHP 8.1
- **Database**: MariaDB (Doctrine ORM)
- **Frontend**: Bootstrap 5, HTML/CSS
- **Development Environment**: Docker

---

## ⚙️ Installation

### Prerequisites

- PHPStorm (or other IDE)
- PHP >= 8.1
- Composer
- MariaDB
- Docker

### Steps

1. Clone the repository:

   ```bash
   git clone https://forge.iut-larochelle.fr/2024-2025-but-info2-a-sae34/m1/m12/q-team/sae-docker-stack
   ```

2. Start the containers with Docker Compose:

   ```bash
   docker-compose up --build
   ```

3. Install backend dependencies:

   ```bash
   composer install
   ```

4. Create the database and run migrations:

   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. Access the application at: `http://localhost:8000`

---

## 📋 Usage

1. **Add a Room**:
   - Navigate to the "Rooms List" page.
   - Click on the "Add Room" button.
   - Fill in the form and submit it.

2. **Edit a Room**:
   - In the room list, click on the "Update" button of a room.
   - Modify the required fields and save.

3. **Delete a Room**:
   - In the room list, click on the "Delete" button.
   - Confirm deletion in the modal window.

4. **View Room Details**:
   - Click on "Details".

5. **Search for a Room**:
   - Filter by name, floor, or status.
   - Check the list.

---

## 🔒 Security

- CSRF compliance for forms.
- Field validation via Symfony constraints (Assert).
- Secure `.env` file.

---

## 👨‍💻 Authors

- Constantin Kylian
- Beaujouan Paul
- Lengronne Jules
- Cornilleau Achile
