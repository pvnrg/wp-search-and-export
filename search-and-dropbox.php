<?php
/**
* Plugin Name: Wp Search and Dropbox
* Plugin URI: http://localhost/wordpressTest/plugins
* Description: wp search and dropbox.
* Version: 0.1
* Author: Pavan
* Author URI: http://localhost/wordpressTest/
**/

// Enqueue necessary scripts and styles
function search_and_dropbox_plugin_enqueue_scripts() {
	wp_enqueue_style('fontawesome','https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');
    wp_enqueue_script('search-and-dropbox-plugin-scripts', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js', array('jquery'), '1.0', true);
    wp_enqueue_script( 'search-and-dropbox-custom-script', plugin_dir_url( __FILE__ ) . 'custom.js', array( 'jquery' ), '1.0', true );
    wp_enqueue_style('search-and-dropbox-plugin-styles', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css');
    wp_enqueue_style('custom-plugin-styles', plugin_dir_url(__FILE__) . 'styles.css');

     wp_localize_script( 'search-and-dropbox-custom-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}
add_action('admin_enqueue_scripts', 'search_and_dropbox_plugin_enqueue_scripts');

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

function custom_woocommerce_export_orders( ) {
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
	    $cheaders = array('Authorization: Bearer sl.BzPbjjVsGy4j_Y4Et3-AXShqYkFOI8vYm8-T_4YlbfjWMnaSYDaD9Mx4lFSsasV6gMm3bxdAdQq-Wx-r6VTUGNdQXC837LqUtwY18Fn9jC9wafCkoZ13vw6mnpR1gqy_EIZAdoa3JSE9',
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


        $response = array(
            'success' => true,
            'message' => 'Export and upload in Dropbox successfully',
        );
        echo json_encode($response);exit;
}
}
