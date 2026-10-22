<?php
/**
 * Responsive image object.
 *
 * @package Borgodasso
 *
 * @var int|null    $attachment_id WP attachment ID.
 * @var string|null $size          Registered image size (default large).
 * @var string      $class         BEM class on wrapper/img.
 * @var string|null $alt           Alt override.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$attachment_id = isset( $attachment_id ) ? (int) $attachment_id : 0;
$size          = $size ?? 'large';
$class         = $class ?? 'media';
$alt           = $alt ?? null;

if ( $attachment_id <= 0 ) {
	return;
}

$attrs = array(
	'class' => $class . '__img',
);

if ( null !== $alt ) {
	$attrs['alt'] = $alt;
}

echo wp_get_attachment_image( $attachment_id, $size, false, $attrs );
