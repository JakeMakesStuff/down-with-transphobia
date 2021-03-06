<?php
// Load compose.
require "vendor/autoload.php";

// Load the postgres connection.
$pgConn = pg_connect(getenv("POSTGRES_CONNECTION_STRING"));

// Defines the sendgrid client.
$sendgrid = new SendGrid(getenv("SENDGRID_API_KEY"));

// Check the key.
if (!isset($_GET["key"]) || $_GET["key"] !== getenv("INBOUND_KEY")) {
    http_response_code(400);
    echo "Invalid key.";
    exit;
}

// Get the email this is to.
$email = "";
if (preg_match("/^.+ <(.+)>$/", $_POST["to"], $matches)) {
    $email = $matches[1];
} else {
    $email = $_POST["to"];
}

// Get the identifier.
$identifier = explode("@", $email)[0];

// Select from the database.
$result = pg_query_params($pgConn, "SELECT email FROM letters WHERE id = $1", array($identifier));
if ($result) {
    $row = pg_fetch_assoc($result);
    if (!$row) {
        echo "No email.";
        exit;
    }
    $email = new SendGrid\Mail\Mail();
    $email->setFrom($identifier . "@sg.jakegealer.me", "Down With Transphobia");
    $email->setSubject("Email from GOV.UK: " . $_POST["subject"]);
    $email->addTo($row["email"], "");
    $email->addContent("text/html", $_POST["html"]);
    $sendgrid->send($email);
} else {
    http_response_code(400);
}
