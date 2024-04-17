<?php

// Custom function to be called after successful order placement
function pvn_function_after_order_placement( $order_id ) {
    
    // Create a TXT file for order details
    $filename = $order_id.'.txt';
    $upload_dir = wp_upload_dir();
    $target_dir = $upload_dir['path'] . '/';
    $target_file = $target_dir . $filename;
    $uniqueFileName = uniqid() . "." . $filename;
    $appendVar = fopen($target_file,'w');
    
    $order = wc_get_order( $order_id );
    // Get order details
    $order_number = $order->get_order_number();
    $order_total = $order->get_total();
    $order_status = $order->get_status();
    $billing_email = $order->get_billing_email();
    $billing_phone = $order->get_billing_phone();
    // Add more details as needed
    fwrite($appendVar, "Order Number: $order_number, Total: $order_total, Status: $order_status, Email: $billing_email, Phone: $billing_phone \n" );
	fclose($appendVar);
	
	if( !session_id() ) {
        session_start();
    }
    $_SESSION['order_file_name'] = $uniqueFileName;
    $_SESSION['order_id'] = $order_id;
    $_SESSION['thank_you_link'] = wc_get_endpoint_url( 'order-received', $order_id, wc_get_page_permalink( 'checkout' ) );;
    
    $redirect_uri = 'https://reviewnprep.com/imp/';
    $authorization_url = "https://www.dropbox.com/oauth2/authorize?client_id=0u36zvxcpy73sat&response_type=code&redirect_uri={$redirect_uri}";
    header("Location: $authorization_url");
}
add_action( 'woocommerce_thankyou', 'pvn_function_after_order_placement', 10, 1 );

if (isset($_GET['code'])) {
        
    // Exchange authorization code for access token
    $code = $_GET['code'];

    $post_data = array(
        'code' => $code,
        'grant_type' => 'authorization_code',
        'client_id' => PVN_DROPBOX_API_KEY,
        'client_secret' => PVN_DROPBOX_SECRET_KEY,
        'redirect_uri' => PVN_DROPBOX_REDIRECT_URL
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.dropboxapi.com/oauth2/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $token_data = json_decode($response, true);
    $access_token = $token_data['access_token'];
    
    upload_order_file_to_dropbox($access_token);
}


function upload_order_file_to_dropbox($access_token) {

    if( !session_id() ) {
        session_start();
    }
    
    $upload_dir = wp_upload_dir();
    $target_dir = $upload_dir['path'] . '/';
    $target_file = $target_dir . $_SESSION['order_id'].'.txt';
	$size = filesize($target_file);
	$fp = fopen($target_file, 'rb');
	$cheaders = array('Authorization: Bearer '.$access_token,
	            'Content-Type: application/octet-stream',
	            'Dropbox-API-Arg: {"path":"/demo_files/'.$_SESSION['order_id'].'.txt'.'", "mode":"add"}');

    $ch = curl_init('https://content.dropboxapi.com/2/files/upload');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $cheaders);
    curl_setopt($ch, CURLOPT_PUT, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_INFILE, $fp);
    curl_setopt($ch, CURLOPT_INFILESIZE, $size);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    fclose($fp);

    $thank_you_link = $_SESSION['thank_you_link'];
    header("Location: $thank_you_link");
}


