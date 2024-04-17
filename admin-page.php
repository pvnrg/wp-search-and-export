<?php


// Add a menu item to the WordPress admin dashboard
function search_and_dropbox_plugin_menu() {
    add_menu_page(
        'Wp Search and Dropbox',   // Page title
        'Search and Dropbox',        // Menu title
        'manage_options',       // Capability required
        'search-and-dropbox',   // Menu slug
        'search_and_dropbox_page',   // Callback function to display content
        'dashicons-admin-plugins', // Icon URL or dashicon name
        20                      // Position in the menu
    );
}


// Callback function to display content for the custom plugin menu
function search_and_dropbox_page() {
    // Display the tabs and tab content
    ?>
    <div class="wrap">
    	 <h2>Search and Dropbox</h2>
        <h2 class="nav-tab-wrapper">
            <a href="#searchTab" class="nav-tab nav-tab-active">AI Search</a>
            <a href="#uploadTab" class="nav-tab">Upload Orders on dropbox</a>
        </h2>
        <div id="searchTab" class="tab-content">
           	<div class="card">
        		<div class="card-body">
        			<div class="row">
        				<div class="col-md-4">
				            <div class="input-group  mt-4">
							    <input type="text" class="form-control" id="search_box" placeholder="Search hereh">
							    <div class="input-group-append">
							      <button class="btn btn-secondary" type="button" id="search_btn">
							        <i class="fa fa-search"></i>
							      </button>
							    </div>
							</div>
						</div>
						<div class="col-md-12">
							<div class="answer-box"></div>
						</div>
					</div>
		        </div>
	        </div>
        </div>
        <div id="uploadTab" class="tab-content" style="display:none;">
        	<div class="card">
        		<div class="card-body">
        			<form id="uploadForm" enctype="multipart/form-data">
			            <div class="row mt-4 px-3">
			            	<div class="col-md-4">
							  <!-- <div class="form-group">
							    <label for="file" class="form-label">Upload File</label>
							    <input type="file" class="form-control" name="file" id="file" >
							  </div>
							 </div> -->
							 <button type="button" class="btn btn-primary" id="exportProduct">Export Order</button>
							 <div class="mt-2 mb-2">
							 	<div id="upload-status"></div>
							 </div>
							 
			            </div>
			        </form>
		        </div>
	        </div>
        </div>
    </div>
    <?php
}

// Hook the search_and_dropbox_plugin_menu function into the admin_menu action hook
add_action('admin_menu', 'search_and_dropbox_plugin_menu');

if( ! function_exists('custom_ai_search') ) {
    add_action('wp_ajax_custom_ai_search', 'custom_ai_search' );
    add_action('wp_ajax_nopriv_custom_ai_search', 'custom_ai_search');
    function custom_ai_search(){
    	// API endpoint
			$endpoint = 'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent?key=AIzaSyD8JXe_tWBoVozVBrpD66QDm_wN4MxjE8w';

			// Request data
			$data = [	
			    "contents" => [
			        0 => [
			            "role" => "user",
			            "parts" => [
			                0 => [
			                    "text" => $_POST['search_question']
			                ]
			            ]
			        ]
			    ]
			];

			// cURL request
			$ch = curl_init($endpoint);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			    'Content-Type: application/json'
			));

			// Execute request
			$response = curl_exec($ch);

			// Check for errors
			if ($response === false) {
			    $return['status'] = false;		
			    $return['message'] = 'cURL error: ' . curl_error($ch);
			} else {
			    $decoded_json = json_decode($response, true);	
			    $return['status'] = true;	
			    $return['data'] = $decoded_json;
			    $message = !empty($decoded_json['candidates'][0]['content']['parts'][0]['text']) ? $decoded_json['candidates'][0]['content']['parts'][0]['text'] : "";
			    $new_post = array(
                    'post_title' => $_POST['search_question'],
                    'post_content' => $message,
                    'post_status' => 'publish'
                );
                
                $post_id = wp_insert_post( $new_post );
                if( $post_id ) {
                    $message .= "<br/> <br/> Post created successfully with the post ID of ".$post_id;
                } else {
                    $message .= "<br/> <br/> Error, while creating post.";
                }
			    $return['message'] = $message;
			}

			// Close cURL session
			curl_close($ch);
			echo json_encode($return);exit;
    }
}


if( ! function_exists('custom_woocommerce_export_orders') ) {
    add_action('wp_ajax_custom_woocommerce_export_orders', 'custom_woocommerce_export_orders' );
    add_action('wp_ajax_nopriv_custom_woocommerce_export_orders', 'custom_woocommerce_export_orders');

    function custom_woocommerce_export_orders($access_token) {
        // Get orders
        $orders = wc_get_orders( array(
            'status' => array( 'processing', 'completed' ), // Specify the order statuses you want to retrieve
            'limit' => -1, // Retrieve all orders, use a positive number to limit the number of orders retrieved
        ) );
    
    	$filename = 'export_orders'.time().'.txt';
    
    	$upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['path'] . '/';
        $target_file = $target_dir . $filename;
    
    	$uniqueFileName = uniqid() . "." . $filename;
    
    	$appendVar = fopen($target_file,'w');
    
        // Loop through each order
        foreach ( $orders as $order ) {
            // Get order details
            $order_number = $order->get_order_number();
            $order_total = $order->get_total();
            $order_status = $order->get_status();
            $billing_email = $order->get_billing_email();
            $billing_phone = $order->get_billing_phone();
            // Add more details as needed
    		fwrite($appendVar, "Order Number: $order_number, Total: $order_total, Status: $order_status, Email: $billing_email, Phone: $billing_phone \n" );
        }
        fclose($appendVar);
    
        $size = filesize($target_file);
        $fp = fopen($target_file, 'rb');
        $cheaders = array('Authorization: Bearer ',$access_token,
                  'Content-Type: application/octet-stream',
                  'Dropbox-API-Arg: {"path":"/demo_files/'.$uniqueFileName.'", "mode":"add"}');
    
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
    
         
        $json_output = json_decode($response, JSON_PRETTY_PRINT);
        if(isset($json_output['error_summary'])){
      		$response = array(
                'success' => false,
                'message' => 'Expired access token',
                'response' => $response
            );
        }else{
            $response = array(
                'success' => true,
                'message' => 'Export and upload in Dropbox successfully',
                'response' => $response
            );
        }
        echo json_encode($response);exit;
    }
}
