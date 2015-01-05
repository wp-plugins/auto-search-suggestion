<?php 
/*
Plugin Name: Auto Search Suggestion
Plugin URI: https://wordpress.org/plugins/wp-global-variable/
Description: Showing suggestions on searching from wordpress admin.
Version: 1.0.0
Author: biztechc
Author URI: https://profiles.wordpress.org/biztechc/
License: GPLv2
*/
?>
<?php 
// Register style sheet.
add_action( 'init', 'register_jquery_ui' );
/**
 * Register style sheet.
 */
function register_jquery_ui() {
       wp_register_style('auto-search-suggestion', plugins_url('auto-search-suggestion/css/jquery-ui.css'), false, '1.0.0', 'all');
       wp_enqueue_style( 'auto-search-suggestion' );
}  
add_action('admin_menu', 'auto_search_menu');
function auto_search_menu()
{
    add_menu_page('Auto Search', 'Auto Search', 'administrator', 'auto-suggest', 'auto_suggest_settings_page');
    
    add_action( 'admin_init', 'register_auto_suggest_settings');
}
function register_auto_suggest_settings()
{
    register_setting( 'auto-suggest-settings-group', 'auto_post_type' );
}

function auto_suggest_settings_page()
{
    $set_post_type = get_option('auto_post_type');
    
    ?>
    
<div class="wrap">
<h2>Auto Search Suggestion Settings</h2>
<form method="post" action="options.php">
<table class="form-table">
<?php settings_fields( 'auto-suggest-settings-group' ); ?>
    <?php do_settings_sections( 'auto-suggest-settings-group' ); ?>
    
      <tr valign="top">
            <th scope="row">Post Type</th>
            <td>
                <fieldset>
                             <?php
                                $post_types = get_post_types( '', 'names' );
                                
                                unset($post_types['attachment']);
                                unset($post_types['revision']);
                                unset($post_types['nav_menu_item']);
                                 
                                foreach ( $post_types as $post_type ) 
                                {
                                    if(@in_array("$post_type",$set_post_type) == true)
                                    {
                                        $checked = 'checked=checked';
                                    }
                                    ?>
                                        <label><input type="checkbox"  value="<?php echo $post_type; ?>" name="auto_post_type[]" <?php echo  $checked; ?>> <span><?php echo $post_type; ?></span></label><br>
                                    <?php
                                    $checked = ''; 
                                }

                             ?>   
                </fieldset>
            </td>
        </tr>
        </table>
    
    <?php submit_button(); ?>

</form>
</div>    
<?php 
}
add_action( 'admin_footer', 'auto_suggest_javascript' ); // Write our JS below here

function auto_suggest_javascript() {         
    $current_screen = get_current_screen();    
    $set_post_type = get_option('auto_post_type');
    if($current_screen->post_type!='' && in_array($current_screen->post_type,$set_post_type)) {
       wp_enqueue_script( 'jquery-ui-autocomplete' );
        ?>
    <script type="text/javascript" >
    jQuery(document).ready(function($) {
        
        jQuery('#post-search-input').attr('autocomplete','off');
        jQuery( "#post-search-input" ).keyup(function() { 
            jQuery('#ui-id-1').attr( 'id', 'ui-custom-id-1');
            
        var search_key_var = jQuery('#post-search-input').val();        
         if(search_key_var.length > 2){
             jQuery("#post-search-input").addClass('ui-autocomplete-loading');
            
         }
          
        var data = {
            'action': 'auto_suggest',
            'whatever': search_key_var,
            'post_type': '<?php echo $current_screen->post_type;?>'
        };

        
        $.post(ajaxurl, data, function(response) {
            //alert('Got this from the server: ' + response);          
            var availableTags = new Array();
            availableTags = response.split("###");
            jQuery( "#post-search-input" ).autocomplete({ source: availableTags,minLength: 3});
            jQuery("#post-search-input").removeClass('ui-autocomplete-loading');
           
        });        
        });
    });
    </script>
    <style>
    .ui-autocomplete-loading {
    background: url('<?php echo plugins_url( 'ajax-loader.gif', __FILE__ );?>') right center no-repeat !important;
    }
    
    </style>
     <?php

    }
}
?>
<?php 

add_action( 'wp_ajax_auto_suggest', 'auto_suggest_callback' );

function auto_suggest_callback() {
    ob_flush();
    global $wpdb,$current_screen; // this is how you get access to the database
     
        
    
   $post_type = get_option('auto_post_type');
    $whatever =  $_POST['whatever'];
    $post_type =  $_POST['post_type'];
    if(isset($whatever) && $whatever!='') {
    $sql = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."posts WHERE post_title LIKE  '%".$whatever."%' and post_status='publish' and post_type='$post_type'");
    //$whatever += 10;  
    
    if(count($sql)>0){
         $name = array();
         foreach($sql as $res_name)
         {       //Debugbreak();
             $name[] = $res_name->post_title;
         }
         $name = implode('###',$name);
         echo $name; 
    }
  else
  {
      echo "No records found";
  }      
        
    }
    die(); // this is required to terminate immediately and return a proper response
}
add_action( 'current_screen', 'wporg_current_screen_example' );
function wporg_current_screen_example()
{        global $current_screen;
    $current_screen = get_current_screen();
}
register_uninstall_hook( __FILE__, 'auto_search_uninstall' ); // uninstall plug-in
function auto_search_uninstall()
{
   delete_option('auto_post_type');
} 
?>
