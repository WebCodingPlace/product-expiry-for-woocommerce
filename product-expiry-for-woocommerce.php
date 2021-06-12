<?php
/*
 * Plugin Name: Product Expiry for WooCommerce
 * Plugin URI: https://webcodingplace.com/product-expiry-for-woocommerce/
 * Description: Provide expiry date for your products and get notified before expire
 * Version: 1.0
 * Author: WebCodingPlace
 * Author URI: https://webcodingplace.com/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: product-expiry-for-woocommerce
 * Domain Path: /languages
*/

if( ! defined('ABSPATH' ) ){
	exit;
}

define( 'WOOPE_PATH', untrailingslashit(plugin_dir_path( __FILE__ )) );
define( 'WOOPE_URL', untrailingslashit(plugin_dir_url( __FILE__ )) );

class WOO_Product_Expiry {

	function __construct(){
        add_filter( 'woocommerce_product_data_tabs', array( $this, 'create_expiry_tab' ) );
        add_action( 'woocommerce_product_data_panels', array( $this, 'display_expiry_fields' ) );
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_fields' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        add_action( 'admin_menu', array( $this, 'admin_settings_page' ) );
        add_filter( 'woocommerce_product_data_store_cpt_get_products_query', array($this, 'expiry_query_var'), 10, 2 );
        add_action( 'woo_expiry_schedule_action', array($this, 'schedule_action'), 10, 1);
	}

    function admin_settings_page(){
        add_submenu_page( 'edit.php?post_type=product', 'Expiry Status', 'Expiry Status', 'manage_options', 'products_expiry_status', array($this, 'render_status_page') );
    }

    function render_status_page(){
        include_once WOOPE_PATH. '/menu/status.php';
    }

    function schedule_action($post_id){
        $woo_expiry_action = get_post_meta( $post_id, 'woo_expiry_action', true );
        if ($woo_expiry_action != '' && $woo_expiry_action == 'draft') {
            wp_update_post(array(
                'ID'    =>  $post_id,
                'post_status'   =>  'draft'
            ));
        }
    }

	/**
	* Add the new tab to the $tabs array
	* @param   $tabs
	* @since   1.0.0
	*/
    public function create_expiry_tab( $tabs ) {
        $tabs['giftwrap'] = array(
            'label'         => __( 'Product Expiry', 'product-expiry-for-woocommerce' ),
            'target'        => 'woo_product_expiry',
        );
        return $tabs;
    }

	/**
	* Display fields for the new panel
	* @since   1.0.0
	*/
    public function display_expiry_fields() { ?>

    <div id='woo_product_expiry' class='panel woocommerce_options_panel'>
        <div class="options_group">
            <?php
                woocommerce_wp_text_input(
                    array(
                        'id'        => 'woo_expiry_date',
                        'label'     => __( 'Expiry Date', 'product-expiry-for-woocommerce' ),
                        'type'      => 'text',
                        'desc_tip'  => __( 'Provide the date of expiry', 'product-expiry-for-woocommerce' ),
                        'description'  => __( 'Provide the date of expiry', 'product-expiry-for-woocommerce' )
                    )
                );
                woocommerce_wp_select(
                    array(
                        'id'        => 'woo_expiry_action',
                        'label'     => __( 'Action', 'product-expiry-for-woocommerce' ),
                        'options'   => array(
                            '' => __( 'Nothing', 'product-expiry-for-woocommerce' ),
                            'draft' => __( 'Make it Draft', 'product-expiry-for-woocommerce' ),
                        ),
                        'desc_tip'  => __( 'What to do when this product expires?', 'product-expiry-for-woocommerce' ),
                        'description'  => __( 'What to do when this product expires?', 'product-expiry-for-woocommerce' )
                    )
                );
            ?>
        </div>
    </div>

    <?php }

    /**
     * Save the custom fields using CRUD method
     * @param $post_id
     * @since 1.0.0
     */
    public function save_fields( $post_id ) {

        $product = wc_get_product( $post_id );

        // Save the woo_expiry_date setting
        $woo_expiry_date = isset( $_POST['woo_expiry_date'] ) ? sanitize_text_field($_POST['woo_expiry_date']) : '';
        $woo_expiry_action = isset( $_POST['woo_expiry_action'] ) ? sanitize_text_field($_POST['woo_expiry_action']) : '';

        $product->update_meta_data( 'woo_expiry_date', sanitize_text_field( $woo_expiry_date ) );
        $product->update_meta_data( 'woo_expiry_action', sanitize_text_field( $woo_expiry_action ) );

        if ($woo_expiry_date != '' && $woo_expiry_action != '') {
            wp_clear_scheduled_hook( 'woo_expiry_schedule_action', array($post_id) );
            wp_schedule_single_event( strtotime($woo_expiry_date), 'woo_expiry_schedule_action', array($post_id) );
        }

        $product->save();

    }

    function admin_scripts($check){
        global $post;
        if ( $check == 'post-new.php' || $check == 'post.php' ) {
            if (isset($post->post_type) && 'product' === $post->post_type) {
				wp_enqueue_script( 'product-expiry-for-woocommerce', WOOPE_URL.'/js/trigger-date-picker.js', array('wc-admin-product-meta-boxes') );
            }
        }
    }

    function expiry_query_var($query, $query_vars){
        if ( ! empty( $query_vars['has_expiry_date'] ) ) {
            $query['meta_query'][] = array(
                'key' => 'woo_expiry_date',
                'value'   => array(''),
                'compare' => 'NOT IN'
            );
        }
        if ( ! empty( $query_vars['expire_period'] ) ) {
            if ($query_vars['expire_period'] == 'this_month') {
                $today = date('Y-m-d');
                $last_day_this_month  = date('Y-m-t');
                $query['meta_query'][] = array(
                    'key'     => 'woo_expiry_date',
                    'value'   => array( $today, $last_day_this_month ),
                    'type'    => 'DATE',
                    'compare' => 'BETWEEN',
                );
            }
            if ($query_vars['expire_period'] == 'expired') {
                $query['meta_query'][] = array(
                    'key'     => 'woo_expiry_date',
                    'value'   => date('Y-m-d'),
                    'type'    => 'DATE',
                    'compare' => '<=',
                );
            }
        }
        return $query;
    }
}
new WOO_Product_Expiry();