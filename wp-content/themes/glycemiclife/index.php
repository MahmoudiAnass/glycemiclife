<?php
/**
 * Blog index / archive fallback.
 *
 * @package GlycemicLife
 */

get_header(); ?>

<section class="archive-hero">
	<h1 class="archive-hero__title">
		<?php
		if ( is_home() && ! is_front_page() ) {
			single_post_title();
		} elseif ( is_search() ) {
			printf( 'Search results for: <em>%s</em>', esc_html( get_search_query() ) );
		} else {
			esc_html_e( 'Guides on Glycemic Load & Blood Sugar', 'glycemiclife' );
		}
		?>
	</h1>
	<p class="archive-hero__tag">Learn how food raises (or steadies) your blood sugar — one science-backed article at a time.</p>
</section>

<section class="section section--tight">
	<div class="container">
		<?php if ( have_posts() ) : ?>
			<div class="post-grid">
				<?php while ( have_posts() ) : the_post(); glycemiclife_post_card(); endwhile; ?>
			</div>
			<nav class="pagination" aria-label="Pagination">
				<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => '‹ Prev', 'next_text' => 'Next ›' ) ); ?>
			</nav>
		<?php else : ?>
			<p>No posts yet. Check back soon.</p>
		<?php endif; ?>
	</div>
</section>

<div class="container">
	<?php echo glycemiclife_cta_html( array( 'variant' => 'banner' ) ); ?>
</div>

<?php get_footer(); ?>
