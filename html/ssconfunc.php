<?php
include 'config.php';
// Authenticate User
function authenticateUser($username, $password)
{
    $conn = connectDatabase();
    $callsign = $conn->real_escape_string($username);
    $query = "SELECT int_id, callsign, psswd FROM Clients WHERE callsign = '$callsign'";
    $result = $conn->query($query);
    $int_ids = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $storedPassword = $row['psswd'];
            if (hash_pbkdf2("sha256", $password, "FreeDMR", 2000) === $storedPassword) {
                $_SESSION['user_id'] = $row['callsign'];
                $int_ids[] = $row['int_id'];
            }
        }
    }

    if (!empty($int_ids)) {
        $_SESSION['int_ids'] = $int_ids;
        return true;
    } else {
        return false;
    }
}
// Session timeout
function checkSessionTimeout()
{
    $timeout = 3600; // You have 1 hour TGTFO
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        header("Location: sslogin.php");
        exit();
    }
    $_SESSION['last_activity'] = time();
}
// Connect to the database
function connectDatabase()
{
    $dbServer = $config['SELF SERVICE']['DB_SERVER'];
    $dbUsername = $config['SELF SERVICE']['DB_USERNAME'];
    $dbPassword = $config['SELF SERVICE']['DB_PASSWORD'];
    $dbName = $config['SELF SERVICE']['DB_NAME'];
    $dbPort = $config['SELF SERVICE']['DB_PORT'];

    $conn = new mysqli($dbServer, $dbUsername, $dbPassword, $dbName, $dbPort);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}
// Get device details from db
function getDevDetails($intId)
{
    $conn = connectDatabase();
    $intId = $conn->real_escape_string($intId);

    $query = "SELECT * FROM Clients WHERE int_id = '$intId'";
    $result = $conn->query($query);

    if ($result && $result->num_rows === 1) {
        $devDetails = $result->fetch_assoc();
        return $devDetails;
    }

    return null;
}
// Update device options in db
function updateDevOptions($intId, $options)
{
    $conn = connectDatabase();
    $intId = $conn->real_escape_string($intId);
    $options = $conn->real_escape_string($options);

    $query = "UPDATE Clients SET options = '$options', modified = True WHERE int_id = '$intId'";
    $result = $conn->query($query);

    return $result;
}
?>