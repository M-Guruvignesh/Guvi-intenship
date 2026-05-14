<?php

function getMySqlConnection(): mysqli {

    $conn = new mysqli(
        getenv('MYSQL_HOST') ?: 'localhost',
        getenv('MYSQL_USER') ?: 'root',
        getenv('MYSQL_PASSWORD') ?: '',
        getenv('MYSQL_DATABASE') ?: 'test',
        (int)(getenv('MYSQL_PORT') ?: 3306)
    );

    if ($conn->connect_error) {
        throw new Exception("DB connection failed");
    }

    return $conn;
}