<?php
/**
 * Homepage.
 *
 * @package GlycemicLife
 */

get_header(); ?>

<section class="hero">
	<div class="hero__inner">
		<span class="hero__eyebrow">Glycemic Load, made simple</span>
		<h1 class="hero__title">Master the number that <em>actually</em> controls your blood sugar.</h1>
		<p class="hero__sub">Free tools and evidence-based guides on Glycemic Load — the metric that predicts how food affects your energy, cravings, and fat loss.</p>

		<form class="hero-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<svg class="hero-search__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
				<circle cx="11" cy="11" r="7"></circle>
				<path d="m21 21-4.3-4.3"></path>
			</svg>
			<input class="hero-search__input" type="search" name="s" placeholder="Search for any food, article, or recipe…" aria-label="Search">
			<button class="hero-search__btn" type="submit">Search</button>
		</form>

		<div class="hero-tags">
			<a class="hero-tag" href="<?php echo esc_url( home_url( '/?s=banana' ) ); ?>">Banana</a>
			<a class="hero-tag" href="<?php echo esc_url( home_url( '/?s=rice' ) ); ?>">Rice</a>
			<a class="hero-tag" href="<?php echo esc_url( home_url( '/?s=oats' ) ); ?>">Oats</a>
			<a class="hero-tag" href="<?php echo esc_url( home_url( '/?s=potato' ) ); ?>">Potato</a>
			<a class="hero-tag" href="<?php echo esc_url( home_url( '/?s=yogurt' ) ); ?>">Yogurt</a>
			<a class="hero-tag" href="<?php echo esc_url( home_url( '/?s=chocolate' ) ); ?>">Chocolate</a>
		</div>
	</div>
</section>

<div class="container">
	<section class="features-row">
		<a class="feature-card" href="<?php echo esc_url( home_url( '/calculator/' ) ); ?>">
			<div class="feature-card__icon feature-card__icon--green">🧮</div>
			<h3 class="feature-card__title">GL Calculator</h3>
			<p class="feature-card__body">Pick a food, enter grams, get an instant Glycemic Load score.</p>
			<span class="feature-card__more">Open calculator →</span>
		</a>
		<a class="feature-card" href="<?php echo esc_url( home_url( '/gi-database/' ) ); ?>">
			<div class="feature-card__icon feature-card__icon--blue">🥗</div>
			<h3 class="feature-card__title">Food Database</h3>
			<p class="feature-card__body">Search &amp; filter 100+ foods by GI, GL, and category.</p>
			<span class="feature-card__more">Browse foods →</span>
		</a>
		<a class="feature-card" href="<?php echo esc_url( home_url( '/meal-builder/' ) ); ?>">
			<div class="feature-card__icon feature-card__icon--purple">🍽️</div>
			<h3 class="feature-card__title">Meal Builder</h3>
			<p class="feature-card__body">Combine foods to see a whole meal's total Glycemic Load. Free with signup.</p>
			<span class="feature-card__more">Build a meal →</span>
		</a>
		<a class="feature-card" href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">
			<div class="feature-card__icon feature-card__icon--amber">📖</div>
			<h3 class="feature-card__title">Deep-dive Guides</h3>
			<p class="feature-card__body">Fat loss, meal templates, myths busted — all sourced.</p>
			<span class="feature-card__more">Read articles →</span>
		</a>
	</section>
</div>

<section class="section">
	<div class="container">
		<div class="section__head">
			<div>
				<h2 class="section__title">Latest guides</h2>
				<p class="section__sub">Practical, sourced articles you can act on today.</p>
			</div>
			<a class="section__link" href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">View all →</a>
		</div>

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
				while ( $latest->have_posts() ) : $latest->the_post();
					glycemiclife_post_card();
				endwhile;
				wp_reset_postdata();
			else :
				echo '<p>Articles coming soon.</p>';
			endif;
			?>
		</div>
	</div>
</section>

<div class="container">
	<?php echo glycemiclife_cta_html( array( 'variant' => 'banner' ) ); ?>
</div>

<?php get_footer(); ?>
