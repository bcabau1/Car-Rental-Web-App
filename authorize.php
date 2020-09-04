<?php

//Modified line 23 & 39 changed transaction_paypal to payment
//Added require 'vendor/autoload.php'; to line 13

/* Date 5/4/2020
        ver 1.3 Brian Cabau: payment authorization, executes with intent = authorize,
         no money taken yet, payment captured in pay.php at return.
           */

use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;

session_start();
require 'config.php';
require 'connection.php';
require 'vendor/autoload.php';

$conn = Connect();

if(isset($_GET['approved']))    {
    $approved = $_GET['approved'] === 'true';

    if($approved)    {
        
        $payerId = $_GET['PayerID']; 

        //Get payment-id from database
        $paymentId = $conn->prepare("SELECT pm_key FROM payment WHERE pm_hash = ?");
        $paymentId->bind_param("s", $hash);
        $hash = $_SESSION['paypal_hash'];
        $paymentId->execute();
        $paymentId->bind_result($paymentId);
        $paymentId->fetch();
        //gets paypal payment
        $payment = Payment::get($paymentId, $api);
        //echo $payment;
        //$token = $payment->cart;
        //echo $token;

        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);

        //execute paypal payment (charge)
        $payment->execute($execution, $api);    //payment executed as authorization, ready for capture in pay.php
    
        $token = array();
        $token = $payment->transactions;
        $token = $token[0]->related_resources[0]->authorization->id;    //authorization token for payment capture, stored as pm_key
        echo $token;
        //var_dump($token[0]->related_resources[0]->authorization->id);
        

        $update = $conn->prepare("UPDATE payment SET pm_key = ? WHERE pm_hash = ?");
        $update->bind_param("ss", $token, $hash);
        $update->execute();

        //any other changes after transaction complete.


        header('Location: complete.php');

    } else{
        header('Location: cancelled.php');
    }

}
?>