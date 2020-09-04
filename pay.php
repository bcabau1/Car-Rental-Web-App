<?php

//Modified by Olajide A 4-18-2020
//Modified line 23 & 39 changed transaction_paypal to payment
//Added require 'vendor/autoload.php'; to line 13

/* Date 5/4/2020
        ver 1.3 Brian Cabau: payment capture and void, captures payment if returned or returned late,
          money taken upon capture. Transaction voided if canceled, table updated to match.
           */

use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Amount;
use PayPal\Api\Authorization;
use PayPal\Api\Capture;
use PayPal\Exception\PayPalConnectionException;


//session_start();
require 'config.php';
//require 'connection.php';
require 'vendor/autoload.php';
include 'receipt.php';

//$conn = Connect();

$pm_cus_name = $_SESSION['login_customer'];
        //access database for key info
        $mysql0 = "SELECT cus_ID FROM customer WHERE cus_username = '$pm_cus_name'";
        $myresult0 = $conn->query($mysql0);
        if (mysqli_num_rows($myresult0) > 0) {
            while($myrow0 = mysqli_fetch_assoc($myresult0)) {
            $customer_id = $myrow0["cus_ID"];
            }
        }
        $sql0 = "SELECT * FROM reservation WHERE res_cus_ID = '$customer_id'";
        $result0 = $conn->query($sql0);
        if (mysqli_num_rows($result0) > 0) {
            while($row0 = mysqli_fetch_assoc($result0)) {
            $res_ID = $row0["res_ID"];
            $res_amount = $row0["res_amount"];
            $res_date = $row0["res_date"];
            }
        }
try {

    
    //Get payment-id from database
    $authorizationId = $conn->prepare("SELECT pm_key FROM payment WHERE pm_res_ID = ?");
    $authorizationId->bind_param("s", $res_ID);
    $authorizationId->execute();
    $authorizationId->bind_result($authorizationId);
    $authorizationId->fetch();
    //gets paypal payment
    $authorization = Authorization::get($authorizationId, $api);
    
    if($totalamount > 0)    {
        $amt = new Amount();
        $amt->setCurrency("USD")
            ->setTotal($totalamount);

        $capture = new Capture();
        $capture->setAmount($amt);

        //execute paypal payment (charge)
        $getCapture = $authorization->capture($capture, $api);

        //update transaction
        $updateTransaction = $conn->prepare("UPDATE payment SET pm_status = 'yes' WHERE pm_key = ?");
        $updateTransaction->bind_param("s", $authorizationId);
        $updateTransaction->execute();

    }   else    {
        //if rental canceled, void the authorization
        //remove row from payment table?
        $voidedAuth = $authorization->void($api);
        
        $updateTransaction = $conn->prepare("UPDATE payment SET pm_status = 'voided', pm_key = ? WHERE pm_res_ID = ?");
        $updateTransaction->bind_param("sd", $voidedAuth->id, $res_ID);
        $updateTransaction->execute();

    }

    //any other changes after transaction complete.
} catch (PayPalConnectionException $e)  {
    //could also log error
    echo $e->getCode();
    echo $e->getData();
    die($e);
}
    //unset Paypal hash
    //unset($_SESSION['paypal_hash']);

?>