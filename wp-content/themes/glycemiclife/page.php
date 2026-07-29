<?php
/**
 * Standard page.
 *
 * @package GlycemicLife
 */

get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>
<article <?php post_class( 'static-page' ); ?>>
	<header>
		<h1 style="margin-top:0;"><?php the_title(); ?></h1>
	</header>
	<div>
		<?php the_content(); ?>
	</div>
</article>
<?php endwhile; ?>

<?php get_footer(); ?>
