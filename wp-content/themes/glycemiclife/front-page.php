<?php
/**
 * Homepage (used when a static front page is set, or as fallback).
 *
 * @package GlycemicLife
 */

get_header(); ?>

<section class="hero">
	<div class="container hero__inner">
		<p class="hero__eyebrow">Glycemic Load, made simple</p>
		<h1 class="hero__title">Eat for stable blood sugar &mdash; without the guesswork.</h1>
		<p class="hero__sub">Free tools and evidence-based guides that teach you the <em>real</em> impact of food on your blood glucose. No fluff. No fad diets. Just Glycemic Load.</p>
		<div class="hero__cta">
			<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/calculator/' ) ); ?>">Open GL Calculator</a>
			<a class="btn btn--ghost" href="<?php echo esc_url( home_url( '/gi-database/' ) ); ?>">Browse GI Database</a>
		</div>
	</div>
</section>

<section class="container features">
	<div class="feature">
		<h2 class="feature__title">Glycemic Load Calculator</h2>
		<p>Pick a food, enter grams, get your GL — instantly. No sign-up.</p>
		<a href="<?php echo esc_url( home_url( '/calculator/' ) ); ?>">Try the calculator →</a>
	</div>
	<div class="feature">
		<h2 class="feature__title">GI &amp; GL Database</h2>
		<p>Search 100+ everyday foods. Sortable, filterable, mobile-friendly.</p>
		<a href="<?php echo esc_url( home_url( '/gi-database/' ) ); ?>">Search foods →</a>
	</div>
	<div class="feature">
		<h2 class="feature__title">Deep-dive Guides</h2>
		<p>GI vs. GL, fat loss myths, meal ideas, working professional habits.</p>
		<a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">Read the blog →</a>
	</div>
</section>

<section class="container latest">
	<h2 class="section-title">Latest guides</h2>
	<div class="post-grid">
		<?php
		$latest = new WP_Query(
			array(
				'posts_per_page'      => 6,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);
		if ( $latest->have_posts() ) :
			while ( $latest->have_posts() ) :
				$latest->the_post();
				?>
				<article <?php post_class( 'post-card' ); ?>>
					<?php if ( has_post_thumbnail() ) : ?>
						<a class="post-card__thumb" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
							<?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?>
						</a>
					<?php endif; ?>
					<div class="post-card__body">
						<h3 class="post-card__title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h3>
						<p class="post-card__meta">
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
							<span aria-hidden="true"> · </span>
							<span><?php echo esc_html( glycemiclife_reading_time() ); ?></span>
						</p>
						<p class="post-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24, '…' ) ); ?></p>
					</div>
				</article>
				<?php
			endwhile;
			wp_reset_postdata();
		else :
			echo '<p>Articles coming soon.</p>';
		endif;
		?>
	</div>
</section>

<?php echo glycemiclife_cta_html( array( 'variant' => 'banner' ) ); ?>

<?php get_footer(); ?>
