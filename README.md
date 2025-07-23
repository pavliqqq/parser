# PHP Parser

## 🧰 Requirements

- PHP 8.0 or higher
- MySQL or MariaDB
- Redis 7.0 or higher
- Composer 2.8 or higher

## ⚙️ Installation

### 1. Clone the Repository

```bash
git clone https://github.com/pavliqqq/conference.git
cd conference
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Environment Setup

```bash
cp .env.example .env
```
Edit the .env file

### 4. Database Setup

1) Connect to MySQL server:

```bash
mysql -u root -p
```

2) Create the database:

```bash
CREATE DATABASE parser CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3) Exit MySQL:

```bash
exit
```

4) Run the import command (for PowerShell):

```bash
Get-Content .\database\dump.sql | mysql -u root -p conference
```

If the above command didn’t work, follow these steps:

5) Open your command prompt (cmd).

6) Navigate to the project directory, for example:

```bash
cd path\to\your\project
```

7) Run the import command:

```bash
mysql -u root -p conference < database/dump.sql
```