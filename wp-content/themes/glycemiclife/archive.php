<?php
/**
 * Archive template (categories, tags, author).
 *
 * @package GlycemicLife
 */

get_header(); ?>

<section class="container archive-hero">
	<h1 class="archive-hero__title"><?php the_archive_title(); ?></h1>
	<?php
	$desc = get_the_archive_description();
	if ( $desc ) {
		echo '<p class="archive-hero__tag">' . wp_kses_post( $desc ) . '</p>';
	}
	?>
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
					<h2 class="post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<p class="post-card__meta">
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
					</p>
					<p class="post-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 26, '…' ) ); ?></p>
				</div>
			</article>
		<?php endwhile; ?>
		<nav class="pagination"><?php the_posts_pagination(); ?></nav>
	<?php else : ?>
		<p>Nothing here yet.</p>
	<?php endif; ?>
</section>

<?php get_footer(); ?>
