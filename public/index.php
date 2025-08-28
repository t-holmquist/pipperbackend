<?php

    // Import the env file to get password later
    require "../.env";

    // Allow http requests to you PHP server
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, XRequested-With");

    // Connect to the MySQL server with the pips.
    // Handles the error in case of failed connection
    $servername = "localhost";
    $username = "root";
    $password = getenv('PASSWORD');

    try {
    $conn = new PDO("mysql:host=$servername;dbname=Pipper", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully";
    } catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    }


    // Find the HTTP request method that the client tries to send
    $requestMethod = $_SERVER['REQUEST_METHOD']; 

    if ($requestMethod == 'GET') {

        // If it is a GET method -> return all the pips
        $statementPDOResponse = $conn->query('SELECT * FROM Pips');

        // Get each database row as an associative array e.g. "pipId" -> "1"
       $result = $statementPDOResponse->fetchAll(\PDO::FETCH_ASSOC);

       // Format the array response to json
       $jsonResponse = json_encode($result);

       // Send back the json data to the client
       echo $jsonResponse;

       
        
    } elseif ($requestMethod == 'POST') {

        // If it is a POST method insert new data into the database
        echo 'This is a POST Method';
    }


    

?>