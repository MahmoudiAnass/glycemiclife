<?php
/**
 * Main index / blog archive.
 *
 * @package GlycemicLife
 */

get_header(); ?>

<section class="container archive-hero">
	<h1 class="archive-hero__title">
		<?php
		if ( is_home() && ! is_front_page() ) {
			single_post_title();
		} elseif ( is_search() ) {
			printf( 'Search results for: %s', '<em>' . esc_html( get_search_query() ) . '</em>' );
		} else {
			esc_html_e( 'Latest Guides on Glycemic Load & Blood Sugar', 'glycemiclife' );
		}
		?>
	</h1>
	<p class="archive-hero__tag">Learn how food raises (or steadies) your blood sugar — one honest, science-backed article at a time.</p>
</section>

<section class="container post-grid">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class( 'post-card' ); ?>>
				<?php if ( has_post_thumbnail() ) : ?>
					<a class="post-card__thumb" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
						<?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?>
					</a>
				<?php endif; ?>
				<div class="post-card__body">
					<h2 class="post-card__title">
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</h2>
					<p class="post-card__meta">
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						<span aria-hidden="true"> · </span>
						<span><?php echo esc_html( glycemiclife_reading_time() ); ?></span>
					</p>
					<p class="post-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 26, '…' ) ); ?></p>
					<a class="post-card__more" href="<?php the_permalink(); ?>">Read guide →</a>
				</div>
			</article>
		<?php endwhile; ?>

		<nav class="pagination" aria-label="Pagination">
			<?php the_posts_pagination(
				array(
					'mid_size'  => 1,
					'prev_text' => '‹ Prev',
					'next_text' => 'Next ›',
				)
			); ?>
		</nav>
	<?php else : ?>
		<p>No posts yet. Check back soon.</p>
	<?php endif; ?>
</section>

<?php echo glycemiclife_cta_html( array( 'variant' => 'banner' ) ); ?>

<?php get_footer(); ?>
