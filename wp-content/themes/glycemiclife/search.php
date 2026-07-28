<?php
/**
 * Search results template.
 *
 * @package GlycemicLife
 */

get_header(); ?>

<section class="container archive-hero">
	<h1 class="archive-hero__title">Search: <em><?php echo esc_html( get_search_query() ); ?></em></h1>
</section>

<section class="container post-grid">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class( 'post-card' ); ?>>
				<div class="post-card__body">
					<h2 class="post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<p class="post-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 26, '…' ) ); ?></p>
				</div>
			</article>
		<?php endwhile; ?>
		<nav class="pagination"><?php the_posts_pagination(); ?></nav>
	<?php else : ?>
		<p>No results. Try broader terms like <a href="<?php echo esc_url( home_url( '/?s=glycemic+load' ) ); ?>">glycemic load</a> or <a href="<?php echo esc_url( home_url( '/?s=banana' ) ); ?>">banana</a>.</p>
	<?php endif; ?>
</section>

<?php get_footer(); ?>
