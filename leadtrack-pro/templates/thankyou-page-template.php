<?php
/**
 * Optional standalone template – copy to your theme as page-thank-you.php
 * and assign it to the Thank You page in the Page Attributes meta box.
 *
 * Requires the [leadtrack_thankyou] shortcode to output the content block.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>
<main id="ltp-main" class="ltp-page-main">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content(); // Renders the [leadtrack_thankyou] shortcode.
	endwhile;
	?>
</main>
<?php
get_footer();
