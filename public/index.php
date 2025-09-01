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
    } catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    }


    // Find the HTTP request method that the client tries to send
    $requestMethod = $_SERVER['REQUEST_METHOD']; 

    if ($requestMethod == 'GET') {

        // If it is a GET method -> return all the pips sorted by pipId in descending order
        $statementPDOResponse = $conn->query('SELECT * FROM Pips ORDER BY pipId desc');

        // Get each database row as an associative array e.g. "pipId" -> "1"
       $result = $statementPDOResponse->fetchAll(\PDO::FETCH_ASSOC);

       // Format the array response to json
       $jsonResponse = json_encode($result);

       // Send back the json data to the client
       echo $jsonResponse;

       
        
    } elseif ($requestMethod == 'POST') {

        // The data is send as JSON from the client which means it cannot be read from the URL with the $_POST superglobal variable
        $input = (array) json_decode(file_get_contents('php://input'), TRUE);

        // Extract the username and piptext from the POST request
        $username = $input['username'];
        $pipText = $input['pipText'];
        
        // Add a pip to the database. First prepare it to avoid SQL injection
        $statement = $conn->prepare(('INSERT INTO Pips VALUES (default, ?, ?, default)'));
        // Then fill in the values and run the query
        $statement->execute([$pipText, $username]);

    
    }

?>