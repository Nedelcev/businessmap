<?php/**   _____   _   _          _      _      _                  / ____| | \ | |        | |    | |    | |                | |  __  |  \| | ___  __| | ___| | ___| |__   _____   __ | | |_ | | . ` |/ _ \/ _` |/ _ \ |/ __| '_ \ / _ \ \ / / | |__| |_| |\  |  __/ (_| |  __/ | (__| | | |  __/\ V /   \_____(_)_| \_|\___|\__,_|\___|_|\___|_| |_|\___| \_/   * Coppyright 2024 * Author: Georgi Nedelchev * Created date: 03-July-24 * All rights reserved! */ 
class TagController {
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
	* @return string[]
	*/
    public function getTags() {
        $sql = "SELECT * FROM tags";
        $stmt = $this->conn->query($sql);
        $tags = $stmt->fetchAll();
        echo json_encode($tags);
    }

	/**
	* @param $id
	*
	* @return string[]
	*/
    public function getTag($id) {
        $sql = "SELECT * FROM tags WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        $tag = $stmt->fetch();
        echo json_encode($tag);
    }	
	/**
	* @return string[]
	* @throws \Exception
	*/
    public function createTags() {
        $data = json_decode(file_get_contents("php://input"), true);

        // Check required fields
        foreach ($data as $tag) {
            if (!isset($tag['name']) || !isset($tag['color'])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required fields: 'name' and 'color'"]);
                return;
            }
        }
        foreach ($data as $tag) {
            $sql = "INSERT INTO tags (name, color) VALUES (?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$tag['name'], $tag['color']]);
        }
        echo json_encode(["message" => "Tags created successfully"]);
    }
	/**
	* @return string[]
	* @throws \Exception
	*/
    public function updateTags() {
        $data = json_decode(file_get_contents("php://input"), true);
        // Check required fields
        foreach ($data as $tag) {
            if (!isset($tag['id']) || !isset($tag['name']) || !isset($tag['color'])) {
                http_response_code(400);
                echo json_encode(["message" => "Missing required fields: 'id', 'name', and 'color'"]);
                return;
            }
            if (!is_numeric($tag['id'])) {
                http_response_code(400);
                echo json_encode(["message" => "Invalid ID: " . $tag['id']]);
                return;
            }			
            // Check if tag exists
            $sql = "SELECT COUNT(*) FROM tags WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$tag['id']]);
            if ($stmt->fetchColumn() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Tag not found: ID " . $tag['id']]);
                return;
            }
        }
        foreach ($data as $tag) {
            $sql = "UPDATE tags SET name = ?, color = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$tag['name'], $tag['color'], $tag['id']]);
        }
        echo json_encode(["message" => "Tags updated successfully"]);
    }
	/**
	* @return string[]
	* @throws \Exception
	*/
    public function deleteTags() {
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data) || !is_array($data)) {
            http_response_code(400);
            echo json_encode(["message" => "Invalid input: IDs required"]);
            return;
        }
        foreach ($data as $tagId) {
            if (!is_numeric($tagId)) {
                http_response_code(400);
                echo json_encode(["message" => "Invalid ID: " . $tagId]);
                return;
            }
            // Check if tag exists
            $sql = "SELECT COUNT(*) FROM tags WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$tagId]);
            if ($stmt->fetchColumn() == 0) {
                http_response_code(404);
                echo json_encode(["message" => "Tag not found: ID " . $tagId]);
                return;
            }
        }
        $this->conn->beginTransaction();
        try {
            foreach ($data as $tagId) {
                $sql = "DELETE FROM tags WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$tagId]);
            }
            $this->conn->commit();
            echo json_encode(["message" => "Tags deleted successfully"]);
        } catch (Exception $e) {
            $this->conn->rollBack();
            echo json_encode(["message" => "Error deleting tags: " . $e->getMessage()]);
        }
    }
}