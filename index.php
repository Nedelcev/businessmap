<?php

/**
   _____   _   _          _      _      _                
  / ____| | \ | |        | |    | |    | |               
 | |  __  |  \| | ___  __| | ___| | ___| |__   _____   __
 | | |_ | | . ` |/ _ \/ _` |/ _ \ |/ __| '_ \ / _ \ \ / /
 | |__| |_| |\  |  __/ (_| |  __/ | (__| | | |  __/\ V / 
  \_____(_)_| \_|\___|\__,_|\___|_|\___|_| |_|\___| \_/  

 * Coppyright 2024
 * Author: Georgi Nedelchev
 * Created date: 03-July-24
 * All rights reserved!
 */
 
header("Content-Type: application/json");

function authenticate() {
    $valid_username = 'admin';
    $valid_password = 'password';

    if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="Task Management API"');
        echo json_encode(["message" => "Authentication required"]);
        exit;
    }

    $username = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];

    if ($username !== $valid_username || $password !== $valid_password) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(["message" => "Invalid credentials"]);
        exit;
    }
}

$request = $_SERVER['REQUEST_URI'];
$request = rtrim($request, '/');
$request = explode('/', $request);

$method = $_SERVER['REQUEST_METHOD'];

require 'tasks.php';
require 'tags.php';

authenticate();

switch ($request[1]) {
    case 'tasks':
        $taskController = new TaskController();
        handleTaskRequest($taskController, $method, $request);
        break;
    case 'tags':
        $tagController = new TagController();
        handleTagRequest($tagController, $method, $request);
        break;
    default:
        http_response_code(404);
        echo json_encode(["message" => "Resource not found"]);
        break;
}

function handleTaskRequest($controller, $method, $request) {
    switch ($method) {
        case 'GET':
            if (isset($request[2])) {
                $controller->getTask($request[2]);
            } else {
                $controller->getTasks();
            }
            break;
        case 'POST':
            $controller->createTasks();
            break;
        case 'PUT':
            $controller->updateTasks();
            break;
        case 'DELETE':
            $controller->deleteTasks();
            break;
        default:
            http_response_code(405);
            echo json_encode(["message" => "Method not allowed"]);
            break;
    }
}

function handleTagRequest($controller, $method, $request) {
    switch ($method) {
        case 'GET':
            if (isset($request[2])) {
                $controller->getTag($request[2]);
            } else {
                $controller->getTags();
            }
            break;
        case 'POST':
            $controller->createTags();
            break;
        case 'PUT':
            $controller->updateTags();
            break;
        case 'DELETE':
            $controller->deleteTags();
            break;
        default:
            http_response_code(405);
            echo json_encode(["message" => "Method not allowed"]);
            break;
    }
}
