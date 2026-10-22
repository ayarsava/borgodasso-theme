<?php
/**
 * Casa amenities list.
 *
 * @package Borgodasso
 *
 * @var array<int, string> $amenities
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$amenities = isset( $amenities ) && is_array( $amenities ) ? $amenities : array();

if ( array() === $amenities ) {
	return;
}
?>
<ul class="casa-amenities">
	<?php foreach ( $amenities as $label ) : ?>
		<li class="casa-amenities__item text-body"><?php echo esc_html( $label ); ?></li>
	<?php endforeach; ?>
</ul>
