<?php
/**
 * Comments template.
 *
 * @package GlycemicLife
 */

if ( post_password_required() ) {
	return;
}
?>
<div id="comments" class="comments-area">

	<?php if ( have_comments() ) : ?>
		<h2 class="comments-title">
			<?php
			$glycemiclife_comment_count = get_comments_number();
			if ( 1 === (int) $glycemiclife_comment_count ) {
				esc_html_e( '1 Comment', 'glycemiclife' );
			} else {
				printf(
					/* translators: %s: number of comments. */
					esc_html( _n( '%s Comment', '%s Comments', $glycemiclife_comment_count, 'glycemiclife' ) ),
					esc_html( number_format_i18n( $glycemiclife_comment_count ) )
				);
			}
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 44,
				)
			);
			?>
		</ol>

		<?php
		the_comments_pagination(
			array(
				'prev_text' => '‹ Prev',
				'next_text' => 'Next ›',
			)
		);
		?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
		<p class="comments-closed"><?php esc_html_e( 'Comments are closed.', 'glycemiclife' ); ?></p>
	<?php endif; ?>

	<div class="comment-respond">
		<?php
		comment_form(
			array(
				'class_form'         => 'comment-form',
				'class_submit'       => 'btn btn--primary',
				'title_reply'        => have_comments() ? __( 'Leave a Reply', 'glycemiclife' ) : __( 'Join the Conversation', 'glycemiclife' ),
				'title_reply_before' => '<h2 id="reply-title" class="comment-reply-title">',
				'title_reply_after'  => '</h2>',
				'label_submit'       => __( 'Post Comment', 'glycemiclife' ),
			)
		);
		?>
	</div>
</div>
