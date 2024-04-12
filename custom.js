// JavaScript to handle tab switching
document.addEventListener('DOMContentLoaded', function() {
    var tabs = document.querySelectorAll('.nav-tab');
    var tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            var target = e.target.getAttribute('href').replace('#', '');

            // Hide all tab contents
            tabContents.forEach(function(content) {
                content.style.display = 'none';
            });

            // Show the selected tab content
            document.getElementById(target).style.display = 'block';

            // Update active tab
            tabs.forEach(function(t) {
                t.classList.remove('nav-tab-active');
            });
            tab.classList.add('nav-tab-active');
        });
    });
});
jQuery(document).ready(function($) {

    $('#exportProduct').click(function() {

        $("#exportProduct").html('Processing <i class="fa fa-spinner fa-spin li_spinner"></i>');
        $(this).prop('disabled', true);
        var formData = new FormData();
        formData.append('action', 'custom_woocommerce_export_orders');
        jQuery.ajax({
         type : "post",
         url : ajax_object.ajax_url,
            contentType: false,
            processData: false,
         data : formData,
         success: function(response) {
             var res = JSON.parse(response);
             $('#exportProduct').prop('disabled', false);
           $("#upload-status").text(res.message);
           $("#exportProduct").html('Export Order');
         }
      });   
    });

     $('#search_btn').click(function() {

        $(this).prop('disabled', true);
        var formData = new FormData();
        formData.append('action', 'custom_ai_search');
        formData.append('search_question', $("#search_box").val());
        jQuery.ajax({
         type : "post",
         url : ajax_object.ajax_url,
            contentType: false,
            processData: false,
         data : formData,
         success: function(response) {
             var res = JSON.parse(response);
             $('#search_btn').prop('disabled', false);
            $(".answer-box").html('Answer - ' + res.message);
         }
      }); 

     })
    
})