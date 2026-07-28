<?php
/**
 * Footer template.
 *
 * @package GlycemicLife
 */
?>
</main><!-- /#content -->

<footer class="site-footer" role="contentinfo">
	<div class="container site-footer__inner">
		<div class="site-footer__brand">
			<p class="site-footer__title"><?php bloginfo( 'name' ); ?></p>
			<p class="site-footer__tag">Evidence-based Glycemic Load education, powered by <a href="<?php echo esc_url( GLYCEMICLIFE_APP_URL ); ?>" rel="noopener" target="_blank">NutriGL Insight</a>.</p>
		</div>
		<nav class="site-footer__nav" aria-label="Footer">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'site-footer__list',
					'fallback_cb'    => function () {
						echo '<ul class="site-footer__list">';
						echo '<li><a href="' . esc_url( home_url( '/about/' ) ) . '">About</a></li>';
						echo '<li><a href="' . esc_url( home_url( '/contact/' ) ) . '">Contact</a></li>';
						echo '<li><a href="' . esc_url( home_url( '/privacy-policy/' ) ) . '">Privacy Policy</a></li>';
						echo '</ul>';
					},
					'depth'          => 1,
				)
			);
			?>
		</nav>
		<p class="site-footer__copy">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.</p>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
