<?php
$message = "total_amount=100,transaction_uuid=11-200-111,product_code=EPAYTEST";
$secret = "8gBm/:&EnhH.1/q";
$signature = base64_encode(hash_hmac("sha256", $message, $secret, true));
echo $signature;
