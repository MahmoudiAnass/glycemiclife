<?php
/**
 * Template Name: Full-width Tool
 *
 * Wider template for the GL Calculator and GI Database pages.
 *
 * @package GlycemicLife
 */

get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>
<article <?php post_class( 'container container--wide tool-page' ); ?>>
	<header class="tool-page__header">
		<h1 class="tool-page__title"><?php the_title(); ?></h1>
	</header>
	<div class="tool-page__content">
		<?php the_content(); ?>
	</div>
</article>
<?php endwhile; ?>

<?php echo glycemiclife_cta_html( array( 'variant' => 'banner' ) ); ?>

<?php get_footer(); ?>
