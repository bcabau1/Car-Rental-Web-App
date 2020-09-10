<?php
/* Date 5/3/2020
        ver 1.3 setting up session cookie in certain amount of time, if no activity, it will automatically log out and request to log in again
        Chi Luong   */
        
// mysqli_connect() function opens a new connection to the MySQL server.
require 'connection.php';
$conn = Connect();

session_start();// Starting Session


// Storing Session
$user_check=$_SESSION['login_customer'];

// SQL Query To Fetch Complete Information Of User
$query = "SELECT cus_username FROM customer WHERE cus_username = '$user_check'";
$ses_sql = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($ses_sql);
$login_session =$row['customer_username'];
?>