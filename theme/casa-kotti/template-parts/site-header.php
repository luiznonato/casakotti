<?php
/**
 * Compact brand header for inner pages.
 *
 * @package Casa_Kotti
 */

if ( is_front_page() ) {
	return;
}
?>
<header class="ck-site-header">
	<?php casa_kotti_header_logo(); ?>
</header>
