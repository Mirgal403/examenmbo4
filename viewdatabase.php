<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Table Viewer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
            color: #333;
        }
        h1 {
            color: #0056b3;
        }
        h2 {
            border-bottom: 2px solid #0056b3;
            padding-bottom: 5px;
            margin-top: 40px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        thead {
            background-color: #007bff;
            color: white;
        }
        tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
        }
        .error {
            color: #D8000C;
            background-color: #FFD2D2;
            padding: 10px;
            border: 1px solid #D8000C;
            border-radius: 5px;
        }
        .empty-set {
            color: #9F6000;
            background-color: #FEEFB3;
            padding: 10px;
            border: 1px solid #9F6000;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Database Content</h1>

    <?php
    $servername = "sql305.infinityfree.com";
    $username = "if0_40576696";
    $password = "3HNSgnTKq91NDp";
    $dbname = "if0_40576696_examenprojectmbo";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("<div class='error'>Connection failed: " . $conn->connect_error . "</div>");
    }

    $tables_query = $conn->query("SHOW TABLES");

    if ($tables_query->num_rows > 0) {
        while ($table_row = $tables_query->fetch_array()) {
            $table_name = $table_row[0];
            echo "<h2>Table: `" . htmlspecialchars($table_name) . "`</h2>";

            $data_query = $conn->query("SELECT * FROM `" . $table_name . "`");

            if ($data_query->num_rows > 0) {
                echo "<table>";

                // --- Table Headers ---
                echo "<thead><tr>";
                $fields = $data_query->fetch_fields();
                foreach ($fields as $field) {
                    echo "<th>" . htmlspecialchars($field->name) . "</th>";
                }
                echo "</tr></thead>";

                // --- Table Body ---
                echo "<tbody>";
                while ($row = $data_query->fetch_assoc()) {
                    echo "<tr>";
                    foreach ($row as $cell) {
                        echo "<td>" . htmlspecialchars($cell) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</tbody>";
                echo "</table>";
            } else {
                echo "<div class='empty-set'>Table is empty.</div>";
            }
        }
    } else {
        echo "<div class='empty-set'>No tables found in the database.</div>";
    }

    // Close the connection
    ?>
</div>

</body>
</html>