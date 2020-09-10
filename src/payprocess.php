
<?php
//Modified by Olajide A 4-18-2020
//Updated sql statements & moved coe blocks directed by Brian C
//Modified line 46 changed variable to $res_amount

/* Date 5/4/2020
        ver 1.3 Brian Cabau: payment created as authorization, actually authorized in authorize.php, payment captured in pay.php
         no money taken yet, payment captured in pay.php at return.
         User redirected automatically to approval url to authorize payment for capture when returned.

         Make sure your redirectUrl's match your workspace, I changed it to match my local host
            ie)you might have: \localhost:8080\ vs me: \localhost\
           */

session_start();
require 'config.php';
require 'connection.php';

$conn = Connect();

use PayPal\Api\Payer;
use PayPal\Api\Details;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Authorization;
use PayPal\Exception\PPConnectionException;
use PayPal\Exception\PayPalConnectionException;


try {
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

    $payer = new Payer();
    $details = new Details();
    $amount = new Amount();
    $transaction = new Transaction(); //amount + description
    $payment = new Payment(); //intent
    $redirectUrls = new RedirectUrls(); //success url, return url
    $subtotal = $res_amount;

    //Payer
    $payer->setPaymentMethod('paypal');

    //Details
    $details->setShipping('0.00')
        ->setTax('0.00')
        ->setSubtotal($subtotal); //depends on car type

    //Amount
    $amount->setCurrency('USD')
        ->setTotal($subtotal) //subtotal + tax + shipping
        ->setDetails($details);

    //Transaction
    $transaction->setAmount($amount)
        ->setDescription('Rental')
        ->setInvoiceNumber(uniqid());

    //Payment    
    $payment->setIntent('authorize')
        ->setPayer($payer)
        ->setTransactions([$transaction]);

    //Redirect URLs
    $redirectUrls->setReturnUrl('http://localhost/testing/authorize.php?approved=true') //redirect on success
        ->setCancelUrl('http://localhost/testing/authorize.php?approved=false'); //redirect on fail, 

    $payment->setRedirectUrls($redirectUrls); 
} catch (PayPalConnectionException $e)  {
    //could also log error
    echo $e->getCode();
    echo $e->getData();
    die($e);
}

$request = clone $payment;

try {
    $payment->create($api);
   // $pm_key = $payment->transactions->related_resources->authorization->id;
    //echo $pm_key;
    //generate and store hash
    $hash = md5($payment->getId());
    $_SESSION['paypal_hash'] = $hash;
    $pm_date = $res_date;
    $pm_cus_ID = $customer_id;
    $pm_res_ID = $res_ID;
    $pm_amount = $res_amount;
    $pm_key = $payment->getId();
    $pm_hash = $hash;
    $pm_status = 'no';
    //prepare and execute transaction storage to table in db
    $store = $conn->prepare("INSERT INTO payment (pm_date, pm_cus_ID, pm_res_ID, pm_cus_name, pm_key, pm_hash, pm_status, pm_amount ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $store->bind_param("siissssd",$pm_date, $pm_cus_ID, $pm_res_ID, $pm_cus_name, $pm_key, $pm_hash, $pm_status, $pm_amount);
    
    if($store->execute())   {
    //echo $pm_cus_name;
    //echo $pm_key;
    //echo $pm_hash;
    //echo $pm_status;
    } else  {
        echo("Error description: " . $conn->error);
    }
} catch (PayPalConnectionException $e)  {
    //could also log error
    echo $e->getCode();
    echo $e->getData();
    die($e);
    header('Location: /error.php');
}
foreach($payment->getLinks() as $link)   {
    if($link->getRel() == 'approval_url')    {
        $redirectUrl = $link->getHref();
    }
}

$approvalUrl = $payment->getApprovalLink();
//echo $payment;
//var_dump($approvalUrl);
//var_dump($payment->getLinks());
//echo("<a href='$approvalUrl' >$approvalUrl</a>");

header('Location: ' . $redirectUrl); //uncomment when done testing



?>