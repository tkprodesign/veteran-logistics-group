<?php
//setting initials
    session_start();
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    date_default_timezone_set('America/Chicago');



// PHP Mailer
if (file_exists('./PHPMailer/src/PHPMailer.php')) {
    require './PHPMailer/src/PHPMailer.php';
    require './PHPMailer/src/SMTP.php';
    require './PHPMailer/src/Exception.php';
} elseif (file_exists('../PHPMailer/src/PHPMailer.php')) {
    require '../PHPMailer/src/PHPMailer.php';
    require '../PHPMailer/src/SMTP.php';
    require '../PHPMailer/src/Exception.php';
} elseif (file_exists('../../PHPMailer/src/PHPMailer.php')) {
    require '../../PHPMailer/src/PHPMailer.php';
    require '../../PHPMailer/src/SMTP.php';
    require '../../PHPMailer/src/Exception.php'; 
} elseif (file_exists('../../../PHPMailer/src/PHPMailer.php')) {
    require '../../../PHPMailer/src/PHPMailer.php';
    require '../../../PHPMailer/src/SMTP.php';
    require '../../../PHPMailer/src/Exception.php'; 
} elseif (file_exists('../../../../PHPMailer/src/PHPMailer.php')) {
    require '../../../../PHPMailer/src/PHPMailer.php';
    require '../../../../PHPMailer/src/SMTP.php';
    require '../../../../PHPMailer/src/Exception.php'; 
} elseif (file_exists('../../../../../PHPMailer/src/PHPMailer.php')) {
    require '../../../../../PHPMailer/src/PHPMailer.php';
    require '../../../../../PHPMailer/src/SMTP.php';
    require '../../../../../PHPMailer/src/Exception.php'; 
} else {
    require '../../../../../../PHPMailer/src/PHPMailer.php';
    require '../../../../../../PHPMailer/src/SMTP.php';
    require '../../../../../../PHPMailer/src/Exception.php'; 
}





// Connecting To Database
$servername = "localhost";
$dbusername = "tyimttsm_dev2";
$dbpassword = "5Es~,+K@-&d6";
$dbname = "tyimttsm_dcs";
$dbconn = mysqli_connect($servername, $dbusername, $dbpassword, $dbname);
if (!$dbconn) {
  die("Connection failed: " . mysqli_connect_error());
}






    function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }



    if ($_SERVER["REQUEST_METHOD"] == "POST"  && isset($_POST['free-quote-button']) && !empty($_POST['free-quote-button'])) {
        // Collect form data
        $name = $_POST['name'];
        $address = $_POST['address'];
        $phone_number = $_POST['phone_number'];
        $item_name = $_POST['item_name'];
        $origin = $_POST['origin'];
        $destination = $_POST['destination'];
        $receivers_name = $_POST['receivers_name'];
        $receivers_number = $_POST['receivers_number'];
        $receivers_email = $_POST['receivers_email'];
        $receivers_address = $_POST['receivers_address'];
        $postal_code = $_POST['postal_code'];
        $method = $_POST['method'];
        $free_quote_request = $_POST['free-quote-request'];
        $time = time();

        
        $sql = "INSERT INTO quotes (name, address, phone_number, item_name, origin, destination, receivers_name, receivers_number, receivers_email, receivers_address, postal_code, method, free_quote_request, time) VALUES ('$name', '$address', '$phone_number', '$item_name', '$origin', '$destination', '$receivers_name', '$receivers_number', '$receivers_email', '$receivers_address', '$postal_code', '$method', '$free_quote_request', $time)";

        if ($dbconn->query($sql) === TRUE) {
            // Data inserted successfully, now prepare and send the email to admin
            $mail = new PHPMailer;
            $mail->isSMTP();
            $mail->Host = 'mail.upsexpressservices.us'; // SMTP server
            $mail->SMTPAuth = true; // Enable SMTP authentication
            $mail->Username = 'quotes@upsexpressservices.us'; // SMTP username
            $mail->Password = 'D0iIxWiWg}hw'; // SMTP password
            $mail->SMTPSecure = 'tls'; // Enable TLS encryption
            $mail->Port = 587; // SMTP port (use 465 for SSL)
            $mail->setFrom('quotes@upsexpressservices.us', 'Quotes | UPS Express Services ');
            $email = 'admin@upsexpressservices.us';
            $mail->addAddress($email); // Recipient's email
            $mail->isHTML(true);

            try {
                // Your mail setup (as shown in the previous code)
                $mail->Subject = 'New Free Quote Request';
                // Updated email content for admin notification
                $htmlContent = '
                <html>
                    <head>
                        <!-- Your styles -->
                    </head>
                    <body>
                        <div class="container">
                            <header>
                                <!-- Header content -->
                            </header>
                            <div class="content">
                                <h2>New Free Quote Request Details</h2>
                                <p><strong>Sender\'s Name:</strong> ' . $name . '</p>
                                <p><strong>Sender\'s Address:</strong> ' . $address . '</p>
                                <p><strong>Sender\'s Phone Number:</strong> ' . $phone_number . '</p>
                                <p><strong>Item Name:</strong> ' . $item_name . '</p>
                                <p><strong>Package Origin:</strong> ' . $origin . '</p>
                                <p><strong>Destination:</strong> ' . $destination . '</p>
                                <p><strong>Receiver\'s Name:</strong> ' . $receivers_name . '</p>
                                <p><strong>Receiver\'s Phone Number:</strong> ' . $receivers_number . '</p>
                                <p><strong>Receiver\'s Email:</strong> ' . $receivers_email . '</p>
                                <p><strong>Receiver\'s Address:</strong> ' . $receivers_address . '</p>
                                <p><strong>Postal Code:</strong> ' . $postal_code . '</p>
                                <p><strong>Method:</strong> ' . $method . '</p>
                                <p><strong>Special Note:</strong> ' . $free_quote_request . '</p>
                            </div>
                            <footer>
                                <!-- Footer content -->
                            </footer>
                        </div>
                    </body>
                </html>
                ';
                $mail->Body = $htmlContent;
                $mail->send();
            } catch (Exception $e) {
                echo 'Email could not be sent. Mailer Error: ' . $mail->ErrorInfo;
            }
        } else {
            echo "Error: " . $sql . "<br>" . $dbconn->error;
        }


    }
?>