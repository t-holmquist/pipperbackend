<?php

    // Import the env file to get password later
    require "../.env";

    // Allow http requests to the PHP server
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

       try {
        // Læs query-parametre: limit og offset
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;

        // Rimelige grænser/validering
        if ($limit < 1) $limit = 1;
        if ($limit > 100) $limit = 100; // undgå alt for store svar
        if ($offset < 0) $offset = 0;

        // (Valgfrit) total antal rækker til metadata
        $total = (int) $conn->query("SELECT COUNT(*) FROM Pips")->fetchColumn();

        // Hent pagineret data – bind som ints!
        $stmt = $conn->prepare("SELECT * FROM Pips ORDER BY created_at desc LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Returnér data + pagination-info
        echo json_encode([
            'data' => $rows,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total,
                'next_offset' => ($offset + $limit < $total) ? $offset + $limit : null,
                'prev_offset' => ($offset - $limit >= 0) ? $offset - $limit : null
            ]
            ]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Connection failed: " . $e->getMessage()]);
        }
       
        
    } elseif ($requestMethod == 'POST') {

        // The data is send as JSON from the client which means it cannot be read from the URL with the $_POST superglobal variable
        $input = (array) json_decode(file_get_contents('php://input'), TRUE);

        // Extract the username and piptext from the POST request
        $username = $input['username'];
        $pipText = $input['pipText'];

        // Check if username is not empty. If empty return an error the the client
        if ($input['username'] != '' && $input['pipText'] != '') {

            // Add a pip to the database. First prepare it to avoid SQL injection
            $statement = $conn->prepare(('INSERT INTO Pips VALUES (default, ?, ?, default)'));
            // Then fill in the values and run the query
            $statement->execute([$pipText, $username]);

            // Return the pip details to the client so that it can temporarily add it to the DOM until page reload (To improve UX)
            // Get the id that is last inserted
            $pipId = $conn->lastInsertId();

            // Approx. current time sent to the client
            $createdAt = date('Y-m-d H:i:s');

            // Return the pip data to the client
            $response = [
                'id' => $pipId,
                'pipText' => $pipText,
                'username' => $username,
                'created_at' => $createdAt
            ];

            echo json_encode($response);

        }        
    
    } elseif ($requestMethod == 'DELETE') {

        // The data is send as JSON from the client which means it cannot be read from the URL with the $_POST superglobal variable
        $input = (array) json_decode(file_get_contents('php://input'), TRUE);

        // Extract the pip id to be able to delete it
        $pipId = $input['pipId'];

        // Prepare the sql statement and delete a row with the specific id
        $statement = $conn->prepare('DELETE FROM Pips WHERE pipId = :pipId');
        $statement->bindParam(':pipId', $pipId, PDO::PARAM_INT);
        $statement->execute();

    }

?>