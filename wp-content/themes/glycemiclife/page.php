<?php
/**
 * Standard page template.
 *
 * @package GlycemicLife
 */

get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>
<article <?php post_class( 'container static-page' ); ?>>
	<header class="static-page__header">
		<h1 class="static-page__title"><?php the_title(); ?></h1>
	</header>
	<div class="static-page__content">
		<?php the_content(); ?>
	</div>
</article>
<?php endwhile; ?>

<?php get_footer(); ?>
