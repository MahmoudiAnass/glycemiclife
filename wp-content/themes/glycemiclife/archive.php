<?php
/**
 * Archive template.
 *
 * @package GlycemicLife
 */

get_header(); ?>

<section class="archive-hero">
	<h1 class="archive-hero__title"><?php the_archive_title(); ?></h1>
	<?php
	$desc = get_the_archive_description();
	if ( $desc ) {
		echo '<p class="archive-hero__tag">' . wp_kses_post( $desc ) . '</p>';
	}
	?>
</section>

<section class="section section--tight">
	<div class="container">
		<?php if ( have_posts() ) : ?>
			<div class="post-grid">
				<?php while ( have_posts() ) : the_post(); glycemiclife_post_card(); endwhile; ?>
			</div>
			<nav class="pagination"><?php the_posts_pagination(); ?></nav>
		<?php else : ?>
			<p>Nothing here yet.</p>
		<?php endif; ?>
	</div>
</section>

<?php get_footer(); ?>
