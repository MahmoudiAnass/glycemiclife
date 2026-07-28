<?php
/**
 * 404 template.
 *
 * @package GlycemicLife
 */

get_header(); ?>

<section class="container static-page">
	<h1>Page not found</h1>
	<p>The page you were looking for isn't here. Try the tools or the blog:</p>
	<ul>
		<li><a href="<?php echo esc_url( home_url( '/calculator/' ) ); ?>">Glycemic Load Calculator</a></li>
		<li><a href="<?php echo esc_url( home_url( '/gi-database/' ) ); ?>">GI &amp; GL Database</a></li>
		<li><a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">Latest guides</a></li>
	</ul>
	<?php get_search_form(); ?>
</section>

<?php get_footer(); ?>
