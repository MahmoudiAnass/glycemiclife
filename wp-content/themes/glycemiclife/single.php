<?php
/**
 * Single post template.
 *
 * @package GlycemicLife
 */

get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>
<article <?php post_class( 'container single-post' ); ?>>
	<header class="single-post__header">
		<?php
		$cats = get_the_category();
		if ( $cats ) :
			?>
			<p class="single-post__cat"><a href="<?php echo esc_url( get_category_link( $cats[0]->term_id ) ); ?>"><?php echo esc_html( $cats[0]->name ); ?></a></p>
		<?php endif; ?>
		<h1 class="single-post__title"><?php the_title(); ?></h1>
		<p class="single-post__meta">
			<span>By <?php the_author(); ?></span>
			<span aria-hidden="true"> · </span>
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
			<span aria-hidden="true"> · </span>
			<span><?php echo esc_html( glycemiclife_reading_time() ); ?></span>
		</p>
	</header>

	<?php if ( has_post_thumbnail() ) : ?>
		<figure class="single-post__hero">
			<?php the_post_thumbnail( 'large', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?>
		</figure>
	<?php endif; ?>

	<div class="single-post__content">
		<?php the_content(); ?>
	</div>

	<footer class="single-post__footer">
		<?php echo glycemiclife_cta_html( array( 'variant' => 'banner' ) ); ?>
	</footer>
</article>

<?php
if ( comments_open() || get_comments_number() ) {
	comments_template();
}
?>
<?php endwhile; ?>

<?php get_footer(); ?>
