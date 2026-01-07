<?php
// =====================================================
// API - COMPATIBILITY MODE (PHP 5.6+)
// Supports: Profile Links & Member Updates
// =====================================================

// 1. Basic Setup
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, x-admin-pin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Constants
define('ADMIN_PIN', '2004');
define('DATA_FILE', dirname(__FILE__) . '/members_data.json');

// 3. Helper: Always return 200 OK
function sendResponse($success, $data = null, $error = null) {
    http_response_code(200);
    $response = array('success' => $success);
    if ($data) $response = array_merge($response, $data);
    if ($error) $response['error'] = $error;
    echo json_encode($response);
    exit;
}

// 4. Data Operations
function readData() {
    if (!file_exists(DATA_FILE)) {
        $initial = array('families' => array(), 'members' => array());
        if (@file_put_contents(DATA_FILE, json_encode($initial)) === false) {
            return $initial; 
        }
    }
    $content = @file_get_contents(DATA_FILE);
    $data = json_decode($content, true);
    return is_array($data) ? $data : array('families' => array(), 'members' => array());
}

function writeData($data) {
    $options = defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0;
    $json = json_encode($data, $options);
    if (@file_put_contents(DATA_FILE, $json) === false) {
        sendResponse(false, null, "Server Write Error: Check permissions.");
    }
}

// 5. Authentication
// 5. Authentication
function checkAuth() {
    $pin = '';
    // Try getallheaders
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'x-admin-pin') {
                $pin = $value;
                break;
            }
        }
    }
    // Try $_SERVER (Fallback for built-in server/Nginx)
    if (!$pin && isset($_SERVER['HTTP_X_ADMIN_PIN'])) {
        $pin = $_SERVER['HTTP_X_ADMIN_PIN'];
    }
    // Try Query Param
    if (!$pin && isset($_GET['pin'])) $pin = $_GET['pin'];
    
    if ($pin !== ADMIN_PIN) {
        sendResponse(false, null, "Incorrect PIN");
    }
}

// 6. Routing
$action = isset($_GET['action']) ? $_GET['action'] : '';
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) $input = array();

try {
    // --- GET DATA ---
    if ($action === 'data') {
        sendResponse(true, readData());
    }

    // --- LOGIN ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
        $pin = isset($input['pin']) ? $input['pin'] : '';
        if ($pin === ADMIN_PIN) sendResponse(true);
        else sendResponse(false, null, "Incorrect PIN");
    }

    // --- CREATE FAMILY ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'createFamily') {
        checkAuth();
        $data = readData();
        $id = uniqid('f');
        $newFamily = array(
            'id' => $id,
            'name' => isset($input['name']) ? $input['name'] : 'Unnamed',
            'type' => isset($input['type']) ? $input['type'] : 'spotify',
            'maxMembers' => isset($input['maxMembers']) ? (int)$input['maxMembers'] : 5
        );
        $data['families'][] = $newFamily;
        writeData($data);
        sendResponse(true, array('id' => $id));
    }

    // --- DELETE FAMILY ---
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'deleteFamily') {
        checkAuth();
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        $data = readData();
        
        $newFamilies = array();
        foreach ($data['families'] as $f) {
            if ((string)$f['id'] !== (string)$id) $newFamilies[] = $f;
        }
        $data['families'] = $newFamilies;

        $newMembers = array();
        foreach ($data['members'] as $m) {
            if ((string)$m['familyId'] !== (string)$id) $newMembers[] = $m;
        }
        $data['members'] = $newMembers;
        
        writeData($data);
        sendResponse(true);
    }

    // --- ADD MEMBER ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'addMember') {
        checkAuth();
        $data = readData();
        $newMember = array(
            'id' => uniqid('m'),
            'familyId' => isset($input['familyId']) ? $input['familyId'] : '',
            'name' => isset($input['name']) ? $input['name'] : '',
            'fb_link' => isset($input['fb_link']) ? $input['fb_link'] : '',
            'image_url' => isset($input['image_url']) ? $input['image_url'] : '', // New Field
            'status' => 'unpaid',
            'month' => 'January'
        );
        $data['members'][] = $newMember;
        writeData($data);
        sendResponse(true);
    }

    // --- UPDATE MEMBER ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'updateMember') {
        checkAuth();
        $data = readData();
        $found = false;
        
        foreach ($data['members'] as $key => $m) {
            if ((string)$m['id'] === (string)$input['id']) {
                if (isset($input['name'])) $data['members'][$key]['name'] = $input['name'];
                if (isset($input['fb_link'])) $data['members'][$key]['fb_link'] = $input['fb_link'];
                if (isset($input['image_url'])) $data['members'][$key]['image_url'] = $input['image_url'];
                $found = true;
                break;
            }
        }
        
        if ($found) {
            writeData($data);
            sendResponse(true);
        } else {
            sendResponse(false, null, "Member not found");
        }
    }

    // --- DELETE MEMBER ---
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $action === 'deleteMember') {
        checkAuth();
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        $data = readData();
        $newMembers = array();
        foreach ($data['members'] as $m) {
            if ((string)$m['id'] !== (string)$id) $newMembers[] = $m;
        }
        $data['members'] = $newMembers;
        writeData($data);
        sendResponse(true);
    }

    // --- TOGGLE STATUS ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'toggleStatus') {
        checkAuth();
        $data = readData();
        foreach ($data['members'] as $key => $m) {
            if ((string)$m['id'] === (string)$input['memberId']) {
                $data['members'][$key]['status'] = $input['status'];
                break;
            }
        }
        writeData($data);
        sendResponse(true);
    }

    sendResponse(false, null, "Action not found");

} catch (Exception $e) {
    sendResponse(false, null, "Error: " . $e->getMessage());
}
