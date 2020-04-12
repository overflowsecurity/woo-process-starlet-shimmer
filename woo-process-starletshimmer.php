<?php
/*
    Plugin Name: Woocommerce Process Starlet Shimmer
    description: This plugin processes custom post meta to add product variations for Paparazzi Starlet Shimmer Jewelry
    Author: Justin Tharpe
    Version: 0.0.1
*/


if (!defined('ABSPATH')) die('No direct access allowed');

class woo_process_starletshimmer{
    public function __construct()
    {
        // Hook into the admin menu
        add_action('admin_menu', array($this, 'create_plugin_settings_page'));
        add_action( 'wp_ajax_process_starlet', array( $this, 'process_starlet' ));        

    }

    public function create_plugin_settings_page()
    {

        // Add the menu item and page
        $page_title = 'Woocommerce Process Starlet Shimmer';
        $menu_title = 'Process Starlet Shimmer';
        $capability = 'manage_options';
        $slug = 'process_starlet_shimmer';
        $callback = array($this, 'plugin_settings_page_content');
        $icon = 'dashicons-admin-plugins';
        $position = 100;   
        add_menu_page($page_title, $menu_title, $capability, $slug, $callback, $icon, $position);
        }

        public function plugin_settings_page_content(){
        global $content;
        global $wpdb;
        echo "<script src='" . plugins_url('/assets/js/main.js', __FILE__) . "'></script>";
        ?>
        <h1>Woocommerce Process Starlet Shimmer</h1>
        <br></br>
        <br></br>
        <br></br>
        <br></br>
        <br></br>
        <button id="send_button" type="button">Process Images</button><div id="send_message"></div>
        <div id="print_out"></div>


<?php
    }

    public function process_starlet(){

        global $wpdb;

        $query = "SELECT DISTINCT post_id FROM wp_postmeta where meta_key = 'var_color' OR meta_key = 'var_variation'" . ";";
        $product_ids = $wpdb->get_results($query);

        //var_dump($product_ids);

        foreach($product_ids as $id){

            $id = $id->post_id;

            $query = "SELECT * FROM wp_posts WHERE ID = " . $id . ";";
            $post_data = $wpdb->get_row($query);

            $post_title = $post_data->post_title;

            $query = "SELECT meta_value FROM wp_postmeta WHERE post_id = " . $id . " AND meta_key = 'var_type'" . ";";
            $var_type = $wpdb->get_row($query);

            $var_type = $var_type->meta_value;

            $query = "SELECT * FROM wp_postmeta WHERE post_id=" . $id . ";";
            $postmeta_data = $wpdb->get_results($query);

            if($var_type == 1){
                $colors = "";
                $var_type = "Color";
                $query_color = "SELECT * FROM wp_postmeta WHERE meta_key = 'var_color' and post_id = " . $id . ";";
                $colors_array = $wpdb->get_results($query_color);

                foreach($colors_array as $meta){
                    $meta = $meta->meta_value;
                    $meta_array = explode(",", $meta);
                    $colors = $colors . $meta_array[0] . " | ";
                    
                }
            }elseif($var_type == 2){
                $variations = "";
                $var_type = "Variation";
                $query_variation = "SELECT * FROM wp_postmeta WHERE meta_key = 'var_variation' and post_id = " . $id . ";";
                $variation_array = $wpdb->get_results($query_variation);

                foreach($variation_array as $meta){
                    $meta = $meta->meta_value;
                    $meta_array = explode(",", $meta);
                    $variations = $variations . $meta_array[0] . " | ";
                    
                }
            }

            //Got to get the color options per peice 
            if($var_type == "Color"){
                $colors = substr_replace($colors ,"",-3);
                $attr_value = $colors;
            }elseif($var_type == "Variation"){
                $variations = substr_replace($variations ,"",-3);
                $attr_value = $variations;
            }

            //Create the attributes
            $attr_label = $var_type;
            $attr_slug = sanitize_title($attr_label);

            $attributes_array[$attr_slug] = array(
                'name' => $attr_label,
                'value' => $attr_value,
                'is_visible' => '1',
                'is_variation' => '1',
                'is_taxonomy' => '0' // for some reason, this is really important       
            );

            update_post_meta( $id, '_product_attributes', $attributes_array );
            unset($attributes_array);

            $parent_id = $id;

            $attr_value = str_replace(' | ', '|', $attr_value);
            $attr_value = explode("|", $attr_value);
            foreach($attr_value as $type_var){
                
    
                if($attr_label == "Color"){
                    
                    unset($meta_array);
                    $meta_array = explode(",", $meta);

                   
                    $variation = array(
                   'post_title'   => $post_title . ' - ' . $type_var,
                   'post_content' => '',
                   'post_status'  => 'publish',
                   'post_parent'  => $parent_id,
                   'post_type'    => 'product_variation'
                   );
                   
                   $query = "SELECT meta_value FROM wp_postmeta WHERE post_id = " . $parent_id .  " and meta_value LIKE '%" . $type_var . ",%'" . ";";
                   $query_result = $wpdb->get_row($query);
                   $results = $query_result->meta_value;
                   $results = explode(",", $results);

                   $variation_id = wp_insert_post( $variation );
                   update_post_meta( $variation_id, '_manage_stock', 1 );
                   update_post_meta( $variation_id, '_regular_price', 1 );
                   update_post_meta( $variation_id, '_price', 1 );     
                   update_post_meta( $variation_id, '_stock_qty', $results[1] );
                   update_post_meta( $variation_id, '_stock', $results[1] );
                   update_post_meta( $variation_id, 'attribute_' . $attr_slug, $type_var ); 
                   WC_Product_Variable::sync( $parent_id );
    
                   unset($meta_array);
                   unset($variation);
                   $colors = "";
                    

                    
    
    
                }elseif($attr_label == "Variation"){
                    unset($meta_array);
                    $meta_array = explode(",", $meta);

                    $variation = array(
                        'post_title'   => $post_title . ' - ' . $type_var,
                        'post_content' => '',
                        'post_status'  => 'publish',
                        'post_parent'  => $parent_id,
                        'post_type'    => 'product_variation'
                    );
    
                    $query = "SELECT meta_value FROM wp_postmeta WHERE post_id = " . $parent_id .  " and meta_value LIKE '%" . $type_var . ",%'" . ";";
                    $query_result = $wpdb->get_row($query);
                    $results = $query_result->meta_value;
                    $results = explode(",", $results);

                    $variation_id = wp_insert_post( $variation );
                    update_post_meta( $variation_id, '_manage_stock', 1 );
                    update_post_meta( $variation_id, '_regular_price', 1 );
                    update_post_meta( $variation_id, '_price', 1 );
                    update_post_meta( $variation_id, '_stock_qty', $results[1] );
                    update_post_meta( $variation_id, '_stock', $results[1] );
                    update_post_meta( $variation_id, 'attribute_' . $attr_slug, $type_var );
                    WC_Product_Variable::sync( $parent_id );

                    unset($meta_array);
                    unset($variation);
                    $variations = "";
                    
    
                }
            }

            update_post_meta( $id, '_stock_status', "instock");


        }
        echo "Done!";

        function cleanup(){
            //Cleanup!
            global $wpdb;
            $query = 'SELECT DISTINCT meta_id from wp_postmeta WHERE meta_key = "var_color" OR meta_key = "var_variation" OR meta_key = "var_type";';
            $meta_id = $wpdb->get_results($query);
            $table = "wp_postmeta";
        
            foreach($meta_id as $meta_ids){
                $wpdb->delete( $table, array( 'meta_id' => $meta_ids->meta_id ) );
            }
        }

        cleanup();
        

    }


    
    
}


New woo_process_starletshimmer();

?>