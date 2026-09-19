<?php
/**
 * Lateral botanical frame used on every public surface.
 *
 * @package Casa_Kotti
 */

$foliage_left  = get_theme_file_path( 'assets/images/foliage-left.svg' );
$foliage_right = get_theme_file_path( 'assets/images/foliage-right.svg' );
?>
<div class="ck-foliage-layer" aria-hidden="true">
	<?php if ( file_exists( $foliage_left ) ) : ?>
		<div class="ck-foliage ck-foliage--left">
			<div class="ck-foliage__motion">
				<?php echo file_get_contents( $foliage_left ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized, trusted theme asset. ?>
			</div>
		</div>
	<?php endif; ?>
	<?php if ( file_exists( $foliage_right ) ) : ?>
		<div class="ck-foliage ck-foliage--right">
			<div class="ck-foliage__motion">
				<?php echo file_get_contents( $foliage_right ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized, trusted theme asset. ?>
			</div>
		</div>
	<?php endif; ?>
</div>
