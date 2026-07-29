<?php
/**
 * Footer template.
 *
 * @package GlycemicLife
 */
?>
</main><!-- /#content -->

<footer class="site-footer" role="contentinfo">
	<div class="site-footer__inner">
		<div class="site-footer__brand">
			<div class="site-footer__logo">
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/logo.png' ); ?>" alt="GlycemicLife" width="36" height="36">
				<h3>Glycemic<span style="color:var(--brand-500)">Life</span></h3>
			</div>
			<p class="site-footer__tag">Evidence-based Glycemic Load education. Free tools, honest science, one goal: stable blood sugar.</p>
			<a class="btn btn--accent" href="<?php echo esc_url( GLYCEMICLIFE_APP_URL . '?utm_source=glycemiclife&utm_medium=footer&utm_campaign=gl_funnel' ); ?>" rel="noopener" target="_blank">
				Download NutriGL Insight →
			</a>
		</div>

		<div>
			<p class="site-footer__col-title">Tools</p>
			<ul class="site-footer__list">
				<li><a href="<?php echo esc_url( home_url( '/calculator/' ) ); ?>">Glycemic Load Calculator</a></li>
				<li><a href="<?php echo esc_url( home_url( '/gi-database/' ) ); ?>">GI &amp; GL Database</a></li>
				<li><a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">All Articles</a></li>
			</ul>
		</div>

		<div>
			<p class="site-footer__col-title">Learn</p>
			<ul class="site-footer__list">
				<li><a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>">Blog</a></li>
				<li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">About</a></li>
				<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact</a></li>
			</ul>
		</div>

		<div>
			<p class="site-footer__col-title">Legal</p>
			<ul class="site-footer__list">
				<li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Privacy Policy</a></li>
				<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact</a></li>
			</ul>
		</div>
	</div>

	<div class="site-footer__bottom">
		<span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.</span>
		<span>Published by <a href="<?php echo esc_url( GLYCEMICLIFE_APP_URL ); ?>" rel="noopener" target="_blank">NutriGL Insight</a></span>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
