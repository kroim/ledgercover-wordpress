<?php
/* Template Name: RiskAssessmentPage */
get_header();
$risk_name = "";
$risk_email = "";
$risk_domain = "";
$risk_industry = "";
$risk_employees = "";
$risk_phone = "";
$riskApiErr = "";
$reputationScore = "";
$testResults = [];
$to_report_mail = "rmathery@gmail.com";
$to_report_send_flag = 0;
$to_report_send_message = false;
if (isset($_POST['report_name'])) {
    $report_name = $_POST['report_name'];
    $report_email = $_POST['report_email'];
    $report_domain = $_POST['report_domain'];
    $report_industry = $_POST['report_industry'];
    $report_employees = $_POST['report_employees'];
    $report_phone = $_POST['report_phone'];
    // sending mail
    $reportSubject = "LETTER OF INTENT";
    $reportMessage = "<h4>From: " . $report_name ."</h4>";
    $reportMessage .= "<h4>Website: " . $report_domain ."</h4><br>";
    $reportMessage .= "<h4>Dear LedgerCover Team,</h4>";
    $reportMessage .= "<h4>I would like to be protect my assets and be an early customer to the Cyber Insurance product at a preferential rate when the product is available Fall 2020.</h4><br>";
    $reportMessage .= "<h4>Thank you</h4>";
    $reportMessage .= "<h4>Date: ". date("Y-m-d") ."</h4>";
    $reportMessage .= "<h4>Signature: ". $report_name ."</h4>";
    $reportHeaders = 'From:' . $report_email;
    if(wp_mail($to_report_mail, $reportSubject, $reportMessage, $reportHeaders))
    {
        $to_report_send_flag = 1;
        $to_report_send_message = "Mail is sent successfully!";
    }
    else
    {
        $to_report_send_flag = 2;
        $to_report_send_message = "Sending mail is failed.";
    }
} elseif (isset($_POST['risk_email'])) {
    $risk_name = $_POST['risk_name'];
    $risk_email = $_POST['risk_email'];
    $risk_domain = $_POST['risk_domain'];
    $risk_industry = $_POST['risk_industry'];
    $risk_employees = $_POST['risk_employees'];
    $risk_phone = $_POST['risk_phone'];
    $ch = curl_init();
    if (!$ch) {
        $riskApiErr = 'Could\'t initialize a cURL session';
    }
    $curl_url = "https://domain-reputation.whoisxmlapi.com/api/v1?apiKey=at_YHxeWHWBc35Ivpl3PprOXMhM78QcW&mode=full&domainName=".$risk_domain;
    curl_setopt($ch, CURLOPT_URL, $curl_url);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // send the request
    $response = curl_exec($ch);

    // close the connection
    curl_close($ch);

    $response = json_decode($response);

//    var_dump($response);
    if ($response->code) {
        $riskApiErr = $response -> messages;
    } else{
        $reputationScore = $response -> reputationScore;
        if ($reputationScore > 90) $reputationScore = "Very Secure";
        elseif ($reputationScore > 70) $reputationScore = "Secure";
        elseif ($reputationScore > 50) $reputationScore = "Risk";
        else $reputationScore = "Very Risk";
        $testResults = $response -> testResults;
    }
}
?>
<style>
    .ledger-col-6 {
        display: inline-block;
        width: 50%;
        float: left;
    }
    .ledger-input {
        display: inline-block !important;
    }
</style>
<div style="padding: 5%; min-height: calc(30vh); ">
    <form action="" method="post">
        <div class="ledger-col-6">
            <div style="padding: 0 5%; text-align: center;">
                <label>Your Name</label>
                <input type="text" name="risk_name" class="ledger-input" value="<?php echo $risk_name; ?>" required>
                <label>Your Email</label>
                <input type="email" name="risk_email" class="ledger-input" value="<?php echo $risk_email; ?>" required>
                <label>Available Domain</label>
                <input type="text" name="risk_domain" class="ledger-input" value="<?php echo $risk_domain; ?>" required>
            </div>
        </div>
        <div class="ledger-col-6">
            <div style="padding: 0 5%; text-align: center;">
                <label>Your Industry</label>
                <input type="text" name="risk_industry" class="ledger-input" value="<?php echo $risk_industry; ?>" required>
                <label>Number of Employees</label>
                <input type="text" name="risk_employees" class="ledger-input" value="<?php echo $risk_employees; ?>" required>
                <label>Phone Number</label>
                <input type="text" name="risk_phone" class="ledger-input" value="<?php echo $risk_phone; ?>" required>
            </div>
        </div>
        <div style="text-align: center;">
            <button type="submit">Detect Risk Assessment</button>
        </div>
    </form>
    <?php if ($to_report_send_flag == 1) { ?>
        <h4 style="color: green; text-align: center;"><?php echo $to_report_send_message; ?></h4>
    <?php } elseif ($to_report_send_flag == 2) { ?>
        <h4 style="color: orangered; text-align: center;"><?php echo $to_report_send_message; ?></h4>
    <?php } ?>
    <?php if ($risk_domain) { ?>
        <div style="padding: 5% 0;">
            <h3 style="text-align: center;">Risk Assessment Result</h3>
            <?php if ($riskApiErr) { ?>
                <h4 style="color: orangered; text-align: center;"><?php echo $riskApiErr; ?></h4>
            <?php } else { ?>
                <div class="container">
                    <h4 style="text-align: center; color: orange">Warning detected score: <?php echo $reputationScore; ?></h4>
                    <?php for ($i = 0; $i < count($testResults); $i++) { ?>
                        <h5><?php echo $testResults[$i]->test; ?></h5>
                        <?php for ($j = 0; $j < count($testResults[$i]->warnings); $j++) { ?>
                            <p><?php echo $testResults[$i]->warnings[$j]; ?></p>
                        <?php } ?>
                    <?php } ?>
                    <div style="text-align: center; padding: 3%;">
                        <button type="button" onclick="ledger_report_assets()">PROTECT MY ASSETS</button>
                    </div>
                    <div id="ledger_report_form" style="display: none">
                        <div style="width: 25%;"></div>
                        <div style="width: 50%;">
                            <div>
                                <label>Subject: <span>LETTER OF INTENT</span></label>
                            </div>
                            <div>
                                <label>From: <span>Name</span></label>
                                <label>Website: <span>google.com</span></label>
                                <label>Dear LedgerCover Team,</label>
                                <label>I would like to be protect my assets and be an early customer to the Cyber Insurance product at a preferential rate when the product is available Fall 2020.</label>
                                <label>Thank you</label>
                                <label>Date: <span><?php echo date("Y-m-d"); ?></span></label>
                                <label>Signature: <span>Name</span></label>
                            </div>
                            <div style="text-align: center;">
                                <form action="" method="post">
                                    <input type="hidden" name="report_name" value="<?php echo $risk_name ?>">
                                    <input type="hidden" name="report_email" value="<?php echo $risk_email ?>">
                                    <input type="hidden" name="report_domain" value="<?php echo $risk_domain ?>">
                                    <input type="hidden" name="report_industry" value="<?php echo $risk_industry ?>">
                                    <input type="hidden" name="report_employees" value="<?php echo $risk_name ?>">
                                    <input type="hidden" name="report_phone" value="<?php echo $risk_name ?>">
                                    <button style="padding-left: 100px; padding-right: 100px;">SEND</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>
<?php get_footer(); ?>
<script>
    function ledger_report_assets() {
        document.getElementById('ledger_report_form').style.display = 'inline-flex';
    }
</script>
