<?php
/**
 * Theme footer — site footer partial + document closing.
 *
 * @package Borgodasso
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
	</main>
	<?php borgodasso_template( 'components/footer' ); ?>
</div>
<?php
if ( function_exists( 'WC' ) ) {
	borgodasso_render_cart_drawer();
}
?>
<?php wp_footer(); ?>
</body>
</html>
