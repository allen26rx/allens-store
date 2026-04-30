<?php
    const HOST = "localhost";
    const USERNAME = "root";
    const DATABASE = "allens_db";
    const PASSWORD = "";


    function databaseConnection()
    {
        try
        {
            $connection = new PDO( 'mysql:host=' . HOST . ';dbname=' . DATABASE , USERNAME, PASSWORD );

            $connection->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
            $connection->setAttribute( PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true );
            $connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

            //echo "connection established";
            return $connection;
            
        } 
        catch ( PDOException $e )
        {
            throw new Exception( $e->getMessage() );
            return false;
        }	 
    }

    // echo databaseConnection();

?>