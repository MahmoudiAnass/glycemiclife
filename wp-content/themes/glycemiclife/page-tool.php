<?php
/**
 * Template Name: Full-width Tool
 *
 * @package GlycemicLife
 */

get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>
<div class="tool-page">
	<header class="tool-page__header">
		<h1 class="tool-page__title"><?php the_title(); ?></h1>
	</header>
	<div class="tool-page__body">
		<?php the_content(); ?>
	</div>
</div>
<?php endwhile; ?>

<div class="container">
	<?php echo glycemiclife_cta_html( array( 'variant' => 'banner' ) ); ?>
</div>

<?php get_footer(); ?>
