
Task Management API
This API allows for managing tasks and tags, providing endpoints to create, read, update, and delete tasks and tags. Each task can have multiple subtasks and multiple tags associated with it. The API is built using PHP and PDO for database interactions.

Prerequisites
PHP >= 7.0
MySQL
Installation
Clone the repository:

sh
Copy code
git clone <repository-url>
cd <repository-directory>
Set up your database:

sql
Copy code
CREATE DATABASE task_management;

USE task_management;

CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    deadline DATE,
    parent_id INT,
    FOREIGN KEY (parent_id) REFERENCES tasks(id) ON DELETE CASCADE
);

CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    color VARCHAR(7) NOT NULL
);

CREATE TABLE task_tags (
    task_id INT,
    tag_id INT,
    PRIMARY KEY (task_id, tag_id),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);
Configure your database connection in tasks.php and tags.php:

php
Copy code
private $conn;

public function __construct() {
    $dsn = 'mysql:host=localhost;dbname=task_management';
    $username = 'your_username';
    $password = 'your_password';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    try {
        $this->conn = new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        die('Connection failed: ' . $e->getMessage());
    }
}
Configure your web server to support clean URLs. For Apache, you can use the following .htaccess configuration:

plaintext
Copy code
Options +FollowSymLinks
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L,QSA]
Restart your web server to apply the changes.

API Endpoints
Tasks
Get all tasks: GET /tasks
Get a specific task: GET /tasks/{id}
Create tasks: POST /tasks
Body (JSON):
json
Copy code
[
    {
        "name": "Task Name",
        "deadline": "YYYY-MM-DD",
        "parent_id": null,
        "tags": [1, 2]
    }
]
Update tasks: PUT /tasks
Body (JSON):
json
Copy code
[
    {
        "id": 1,
        "name": "Updated Task Name",
        "deadline": "YYYY-MM-DD",
        "parent_id": null,
        "tags": [2, 3]
    }
]
Delete tasks: DELETE /tasks
Body (JSON):
json
Copy code
[1, 2]
Tags
Get all tags: GET /tags
Get a specific tag: GET /tags/{id}
Create tags: POST /tags
Body (JSON):
json
Copy code
[
    {
        "name": "Tag Name",
        "color": "#FFFFFF"
    }
]
Update tags: PUT /tags
Body (JSON):
json
Copy code
[
    {
        "id": 1,
        "name": "Updated Tag Name",
        "color": "#000000"
    }
]
Delete tags: DELETE /tags
Body (JSON):
json
Copy code
[1, 2]
License
This project is licensed under the MIT License.
