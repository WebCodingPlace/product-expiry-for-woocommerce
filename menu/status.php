<div class="wrap">
	<h1>Product Expiry Stats</h1>
<?php
	$this_month = wc_get_products(
		array(
		    'limit' => -1,
		    'status' => 'publish',
		    'has_expiry_date' => true,
		    'expire_period' => 'this_month'
		)
	);

	$expired = wc_get_products(
		array(
		    'limit' => -1,
		    'status' => 'publish',
		    'has_expiry_date' => true,
		    'expire_period' => 'expired'
		)
	);
?>
	<h2>Expired Products</h2>
	<table class="wp-list-table widefat fixed striped table-view-list widefat">
	    <thead>
	        <tr>
	            <th><?php _e("Product Name", "product-expiry-for-woocommerce"); ?></th>
	            <th><?php _e("Expired on", "product-expiry-for-woocommerce"); ?></th>
	            <th><?php _e("Action", "product-expiry-for-woocommerce"); ?></th>
	        </tr>
	        <?php
				foreach ($expired as $key => $product) { ?>
					<tr>
						<td><?php echo $product->get_title(); ?></td>
						<td><?php
							$exp_date = get_post_meta( $product->get_id(), 'woo_expiry_date', true );
							echo esc_html( human_time_diff( current_time('timestamp'), strtotime($exp_date) ). ' ago' );
						?></td>
						<td><?php edit_post_link( 'Edit', '', '', $product->get_id() ); ?></td>
					</tr>
				<?php }
	        ?>
	    </thead>
	    </tbody>
	</table>

	<h2>This Month Expiring</h2>
	<table class="wp-list-table widefat fixed striped table-view-list widefat">
	    <thead>
	        <tr>
	            <th><?php _e("Product Name", "product-expiry-for-woocommerce"); ?></th>
	            <th><?php _e("Expiring In", "product-expiry-for-woocommerce"); ?></th>
	            <th><?php _e("Action", "product-expiry-for-woocommerce"); ?></th>
	        </tr>
	        <?php
				foreach ($this_month as $key => $product) { ?>
					<tr>
						<td><?php echo $product->get_title(); ?></td>
						<td><?php
							$exp_date = get_post_meta( $product->get_id(), 'woo_expiry_date', true );
							echo esc_html( human_time_diff( current_time('timestamp'), strtotime($exp_date) ) );
						?></td>
						<td><?php edit_post_link( 'Edit', '', '', $product->get_id() ); ?></td>
					</tr>
				<?php }
	        ?>
	    </thead>
	    </tbody>
	</table>
</div>