<?php/**   _____   _   _          _      _      _                  / ____| | \ | |        | |    | |    | |                | |  __  |  \| | ___  __| | ___| | ___| |__   _____   __ | | |_ | | . ` |/ _ \/ _` |/ _ \ |/ __| '_ \ / _ \ \ / / | |__| |_| |\  |  __/ (_| |  __/ | (__| | | |  __/\ V /   \_____(_)_| \_|\___|\__,_|\___|_|\___|_| |_|\___| \_/   * Coppyright 2024 * Author: Georgi Nedelchev * Created date: 03-July-24 * All rights reserved! */ 
class TaskController {
    private $conn;
    public function __construct() {
        $dsn = 'mysql:host=localhost;dbname=db';
        $username = '';
        $password = '';
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
	/**
	* @param $parentId
	*
	* @return array[]
	*/
	private function getSubtasks($parentId) {
        $sql = "SELECT * FROM tasks WHERE parent_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$parentId]);
        $subtasks = $stmt->fetchAll();
        foreach ($subtasks as &$subtask) {
            $subtask['tags'] = $this->getTaskTags($subtask['id']);
            $subtask['subtasks'] = $this->getSubtasks($subtask['id']);
        }
        return $subtasks;
    }
	/**
	* @param $taskId
	*
	* @return array[]
	*/
    private function getTaskTags($taskId) {
        $sql = "SELECT t.id, t.name, t.color FROM tags t INNER JOIN task_tags tt ON t.id = tt.tag_id WHERE tt.task_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$taskId]);
        return $stmt->fetchAll();
    }
	
	/**
	* @param $parentId
	*
	* @return
	*/
    private function deleteSubtasks($parentId) {
        $sql = "SELECT id FROM tasks WHERE parent_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$parentId]);
        $subtaskIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        foreach ($subtaskIds as $subtaskId) {
            $this->deleteSubtasks($subtaskId);
            $sql = "DELETE FROM tasks WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$subtaskId]);
        }
    }
	/**
	* @return string[]
	*/
    public function getTasks() {
        $sql = "SELECT * FROM tasks WHERE parent_id IS NULL";
        $stmt = $this->conn->query($sql);
        $tasks = $stmt->fetchAll();
        foreach ($tasks as &$task) {
            $task['tags'] = $this->getTaskTags($task['id']);
            $task['subtasks'] = $this->getSubtasks($task['id']);
        }
        echo json_encode($tasks);
    }
	/**
	* @param $id
	*
	* @return string[]
	*/
    public function getTask($id) {
        $sql = "SELECT * FROM tasks WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $task = $stmt->fetch();
        if ($task) {
            $task['tags'] = $this->getTaskTags($task['id']);
            $task['subtasks'] = $this->getSubtasks($task['id']);
        }
        echo json_encode($task);
    }
	/**
	* @return string[]
	* @throws \Exception
	*/
    public function createTasks() {
        $data = json_decode(file_get_contents("php://input"), true);
        // Check required fields
        foreach ($data as $task) {
            if (!isset($task['name']) || !isset($task['deadline'])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required fields: 'name' and 'deadline'"]);
                return;
            }
        }
        $this->conn->beginTransaction();
        try {
            foreach ($data as $task) {
                $sql = "INSERT INTO tasks (name, deadline, parent_id) VALUES (?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$task['name'], $task['deadline'], $task['parent_id'] ?? null]);

                $taskId = $this->conn->lastInsertId();
                if (isset($task['tags'])) {
                    foreach ($task['tags'] as $tagId) {
                        $sql = "INSERT INTO task_tags (task_id, tag_id) VALUES (?, ?)";
                        $stmt = $this->conn->prepare($sql);
                        $stmt->execute([$taskId, $tagId]);
                    }
                }
            }

            $this->conn->commit();
            echo json_encode(["message" => "Tasks created successfully"]);
        } catch (Exception $e) {
            $this->conn->rollBack();
            echo json_encode(["message" => "Error creating tasks: " . $e->getMessage()]);
        }
    }
	/**
	* @return string[]
	* @throws \Exception
	*/
    public function updateTasks() {
		$data = json_decode(file_get_contents("php://input"), true);
        // Check if ID is present
        foreach ($data as $task) {
            if (!isset($task['id'])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required field: 'id'"]);
                return;
            }
            if (!is_numeric($task['id'])) {
                http_response_code(400);
                echo json_encode(["message" => "Invalid ID: " . $task['id']]);
                return;
            }
            // Check if task exists
            $sql = "SELECT COUNT(*) FROM tasks WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$task['id']]);
            if ($stmt->fetchColumn() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Task not found: ID " . $task['id']]);
                return;
            }
            // Dynamically build the update query
            $updateFields = [];
            $updateValues = [];
            if (isset($task['name'])) {
                $updateFields[] = "name = ?";
                $updateValues[] = $task['name'];
            }
            if (isset($task['deadline'])) {
                $updateFields[] = "deadline = ?";
                $updateValues[] = $task['deadline'];
            }
            if (isset($task['parent_id'])) {
                $updateFields[] = "parent_id = ?";
                $updateValues[] = $task['parent_id'];
            }
            if (!empty($updateFields)) {
                $updateValues[] = $task['id'];
                $sql = "UPDATE tasks SET " . implode(", ", $updateFields) . " WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute($updateValues);
            }
            if (isset($task['tags'])) {
                // Delete existing tags
                $sql = "DELETE FROM task_tags WHERE task_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$task['id']]);

                // Insert new tags
                foreach ($task['tags'] as $tagId) {
                    $sql = "INSERT INTO task_tags (task_id, tag_id) VALUES (?, ?)";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute([$task['id'], $tagId]);
                }
            }
        }
        echo json_encode(["message" => "Tasks updated successfully"]);
    }
	/**
	* @return string[]
	* @throws \Exception
	*/
    public function deleteTasks() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data) || !is_array($data)) {
            http_response_code(400);
            echo json_encode(["message" => "Invalid input: IDs required"]);
            return;
        }
        foreach ($data as $taskId) {
            if (!is_numeric($taskId)) {
                http_response_code(400);
                echo json_encode(["message" => "Invalid ID: " . $taskId]);
                return;
            }
            // Check if task exists
            $sql = "SELECT COUNT(*) FROM tasks WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$taskId]);
            if ($stmt->fetchColumn() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Task not found: ID " . $taskId]);
                return;
            }
        }

        $this->conn->beginTransaction();
        try {
            foreach ($data as $taskId) {
                $this->deleteSubtasks($taskId);
                $sql = "DELETE FROM tasks WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$taskId]);
            }
            $this->conn->commit();
            echo json_encode(["message" => "Tasks deleted successfully"]);
        } catch (Exception $e) {
            $this->conn->rollBack();
            echo json_encode(["message" => "Error deleting tasks: " . $e->getMessage()]);
        }
    }
}
