<?php
/**
 * One-click site installer.
 *
 * Reads the HTML files under /content/, creates pages + posts, sets front
 * page, permalinks, and menus. Safe to run repeatedly — updates existing
 * items by slug instead of duplicating.
 *
 * @package NutriGL_Tools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Absolute path to the /content directory (theme root's parent = WP root).
 *
 * @return string
 */
function nutrigl_installer_content_dir() {
	return ABSPATH . 'content';
}

/**
 * Parse the leading HTML comment for Title / Slug / Template / Category / Tags.
 *
 * @param string $raw Full file contents.
 * @return array{title:string,slug:string,template:string,category:string,tags:array,body:string}
 */
function nutrigl_installer_parse( $raw ) {
	$meta = array(
		'title'    => '',
		'slug'     => '',
		'template' => '',
		'category' => '',
		'tags'     => array(),
		'body'     => $raw,
	);

	if ( preg_match( '/^\s*<!--(.*?)-->/s', $raw, $m ) ) {
		$header      = $m[1];
		$meta['body'] = trim( str_replace( $m[0], '', $raw ) );

		if ( preg_match( '/^\s*Title:\s*(.+?)\s*$/mi', $header, $mm ) ) {
			$meta['title'] = trim( $mm[1] );
		}
		if ( preg_match( '/^\s*Slug:\s*([a-z0-9\-]+)/mi', $header, $mm ) ) {
			$meta['slug'] = strtolower( trim( $mm[1] ) );
		}
		if ( preg_match( '/^\s*Template:\s*(.+?)\s*$/mi', $header, $mm ) ) {
			$meta['template'] = trim( $mm[1] );
		}
		if ( preg_match( '/^\s*Category:\s*(.+?)\s*$/mi', $header, $mm ) ) {
			$meta['category'] = trim( $mm[1] );
		}
		if ( preg_match( '/^\s*Tags:\s*(.+?)\s*$/mi', $header, $mm ) ) {
			$tags = array_map( 'trim', explode( ',', $mm[1] ) );
			$meta['tags'] = array_filter( $tags );
		}
	}

	return $meta;
}

/**
 * Map a template display name from the header to a real template file.
 *
 * @param string $name Human label ("Full-width Tool").
 * @return string Filename ("page-tool.php") or ''.
 */
function nutrigl_installer_template_file( $name ) {
	if ( '' === $name ) {
		return '';
	}
	$templates = wp_get_theme()->get_page_templates();
	foreach ( $templates as $file => $label ) {
		if ( strcasecmp( $label, $name ) === 0 ) {
			return $file;
		}
	}
	return '';
}

/**
 * Find an existing page/post by slug, in a given post type.
 *
 * @param string $slug Slug.
 * @param string $type post|page.
 * @return int Post ID or 0.
 */
function nutrigl_installer_find_by_slug( $slug, $type ) {
	$q = get_posts(
		array(
			'name'        => $slug,
			'post_type'   => $type,
			'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'numberposts' => 1,
			'fields'      => 'ids',
		)
	);
	return ! empty( $q ) ? (int) $q[0] : 0;
}

/**
 * Import a single page from a content file.
 *
 * @param string $path       Absolute file path.
 * @param bool   $overwrite  Whether to overwrite existing content.
 * @return array Result info.
 */
function nutrigl_installer_import_page( $path, $overwrite = false ) {
	$raw  = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( false === $raw ) {
		return array( 'ok' => false, 'msg' => 'Could not read ' . basename( $path ) );
	}
	$meta = nutrigl_installer_parse( $raw );
	if ( '' === $meta['title'] || '' === $meta['slug'] ) {
		return array( 'ok' => false, 'msg' => basename( $path ) . ' — missing Title/Slug header' );
	}

	$existing = nutrigl_installer_find_by_slug( $meta['slug'], 'page' );

	$data = array(
		'post_title'   => $meta['title'],
		'post_name'    => $meta['slug'],
		'post_content' => $meta['body'],
		'post_status'  => 'publish',
		'post_type'    => 'page',
	);

	if ( $existing ) {
		if ( $overwrite ) {
			$data['ID'] = $existing;
			$id         = wp_update_post( $data, true );
		} else {
			$id = $existing;
		}
	} else {
		$id = wp_insert_post( $data, true );
	}

	if ( is_wp_error( $id ) || ! $id ) {
		return array( 'ok' => false, 'msg' => 'Failed: ' . $meta['title'] );
	}

	// Template.
	$tpl = nutrigl_installer_template_file( $meta['template'] );
	if ( $tpl ) {
		update_post_meta( (int) $id, '_wp_page_template', $tpl );
	}

	return array(
		'ok'       => true,
		'id'       => (int) $id,
		'slug'     => $meta['slug'],
		'title'    => $meta['title'],
		'existing' => (bool) $existing,
	);
}

/**
 * Import a single post from a content file.
 *
 * @param string $path      File path.
 * @param bool   $overwrite Overwrite existing.
 * @return array Result.
 */
function nutrigl_installer_import_post( $path, $overwrite = false ) {
	$raw = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( false === $raw ) {
		return array( 'ok' => false, 'msg' => 'Could not read ' . basename( $path ) );
	}
	$meta = nutrigl_installer_parse( $raw );
	if ( '' === $meta['title'] || '' === $meta['slug'] ) {
		return array( 'ok' => false, 'msg' => basename( $path ) . ' — missing Title/Slug header' );
	}

	$existing = nutrigl_installer_find_by_slug( $meta['slug'], 'post' );

	$data = array(
		'post_title'   => $meta['title'],
		'post_name'    => $meta['slug'],
		'post_content' => $meta['body'],
		'post_status'  => 'publish',
		'post_type'    => 'post',
	);

	if ( $existing ) {
		if ( $overwrite ) {
			$data['ID'] = $existing;
			$id         = wp_update_post( $data, true );
		} else {
			$id = $existing;
		}
	} else {
		$id = wp_insert_post( $data, true );
	}

	if ( is_wp_error( $id ) || ! $id ) {
		return array( 'ok' => false, 'msg' => 'Failed: ' . $meta['title'] );
	}

	// Category.
	if ( '' !== $meta['category'] ) {
		$term = get_term_by( 'name', $meta['category'], 'category' );
		if ( ! $term ) {
			$new = wp_insert_term( $meta['category'], 'category' );
			if ( ! is_wp_error( $new ) ) {
				$term_id = (int) $new['term_id'];
			}
		} else {
			$term_id = (int) $term->term_id;
		}
		if ( ! empty( $term_id ) ) {
			wp_set_post_categories( (int) $id, array( $term_id ) );
		}
	}

	// Tags.
	if ( ! empty( $meta['tags'] ) ) {
		wp_set_post_tags( (int) $id, $meta['tags'] );
	}

	return array(
		'ok'       => true,
		'id'       => (int) $id,
		'slug'     => $meta['slug'],
		'title'    => $meta['title'],
		'existing' => (bool) $existing,
	);
}

/**
 * Ensure a page exists with the given slug + title. Creates a stub if missing.
 *
 * @param string $slug  Slug.
 * @param string $title Title.
 * @param string $body  Body HTML.
 * @return int Page ID.
 */
function nutrigl_installer_ensure_page( $slug, $title, $body = '' ) {
	$existing = nutrigl_installer_find_by_slug( $slug, 'page' );
	if ( $existing ) {
		return $existing;
	}
	return (int) wp_insert_post(
		array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $body,
			'post_status'  => 'publish',
			'post_type'    => 'page',
		)
	);
}

/**
 * Build (or refresh) the primary & footer menus.
 *
 * @param array $pages Slug => page ID.
 */
function nutrigl_installer_build_menus( $pages ) {
	$result = array( 'primary' => 0, 'footer' => 0 );

	// Primary menu.
	$menu_name = 'Primary Menu';
	$menu      = wp_get_nav_menu_object( $menu_name );
	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( $menu_name );
	} else {
		$menu_id = (int) $menu->term_id;
		// Clear existing items so we can rebuild deterministically.
		$items = wp_get_nav_menu_items( $menu_id );
		if ( $items ) {
			foreach ( $items as $it ) {
				wp_delete_post( $it->ID, true );
			}
		}
	}
	if ( ! is_wp_error( $menu_id ) && $menu_id ) {
		$primary_items = array(
			array( 'label' => 'Home',          'slug' => 'home' ),
			array( 'label' => 'GL Calculator',  'slug' => 'calculator' ),
			array( 'label' => 'Meal Builder',   'slug' => 'meal-builder' ),
			array( 'label' => 'Food Database',  'slug' => 'gi-database' ),
			array( 'label' => 'Articles',       'slug' => 'blog' ),
			array( 'label' => 'About',          'slug' => 'about' ),
		);
		foreach ( $primary_items as $it ) {
			if ( empty( $pages[ $it['slug'] ] ) ) {
				continue;
			}
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $it['label'],
					'menu-item-object'    => 'page',
					'menu-item-object-id' => (int) $pages[ $it['slug'] ],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}
		$locations            = get_theme_mod( 'nav_menu_locations', array() );
		$locations['primary'] = (int) $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
		$result['primary'] = (int) $menu_id;
	}

	// Footer menu.
	$menu_name = 'Footer Menu';
	$menu      = wp_get_nav_menu_object( $menu_name );
	if ( ! $menu ) {
		$menu_id = wp_create_nav_menu( $menu_name );
	} else {
		$menu_id = (int) $menu->term_id;
		$items = wp_get_nav_menu_items( $menu_id );
		if ( $items ) {
			foreach ( $items as $it ) {
				wp_delete_post( $it->ID, true );
			}
		}
	}
	if ( ! is_wp_error( $menu_id ) && $menu_id ) {
		$footer_items = array(
			array( 'label' => 'About',          'slug' => 'about' ),
			array( 'label' => 'Contact',        'slug' => 'contact' ),
			array( 'label' => 'Privacy Policy', 'slug' => 'privacy-policy' ),
		);
		foreach ( $footer_items as $it ) {
			if ( empty( $pages[ $it['slug'] ] ) ) {
				continue;
			}
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $it['label'],
					'menu-item-object'    => 'page',
					'menu-item-object-id' => (int) $pages[ $it['slug'] ],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}
		$locations           = get_theme_mod( 'nav_menu_locations', array() );
		$locations['footer'] = (int) $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
		$result['footer'] = (int) $menu_id;
	}

	return $result;
}

/**
 * Run the full setup. Idempotent.
 *
 * @param bool $overwrite Whether to overwrite existing page/post content.
 * @return array Log lines.
 */
function nutrigl_installer_run( $overwrite = false ) {
	$log = array();
	$dir = nutrigl_installer_content_dir();

	if ( ! is_dir( $dir ) ) {
		return array( 'error' => "Content folder not found at $dir. Make sure the /content directory is deployed." );
	}

	// 1. Pages.
	$pages     = array(); // slug => id.
	$page_dir  = $dir . '/pages';
	$page_files = is_dir( $page_dir ) ? glob( $page_dir . '/*.html' ) : array();
	foreach ( $page_files as $file ) {
		$r = nutrigl_installer_import_page( $file, $overwrite );
		if ( ! empty( $r['ok'] ) ) {
			$pages[ $r['slug'] ] = $r['id'];
			$log[]              = ( $r['existing'] ? ( $overwrite ? 'Updated page: ' : 'Kept page: ' ) : 'Created page: ' ) . $r['title'];
		} else {
			$log[] = 'Skipped: ' . $r['msg'];
		}
	}

	// 2. Ensure a Blog page for the posts index.
	$blog_id = nutrigl_installer_ensure_page( 'blog', 'Articles' );
	if ( $blog_id ) {
		$pages['blog'] = $blog_id;
		$log[]         = 'Blog index page ready.';
	}

	// 3. Posts.
	$post_dir   = $dir . '/posts';
	$post_files = is_dir( $post_dir ) ? glob( $post_dir . '/*.html' ) : array();
	foreach ( $post_files as $file ) {
		$r = nutrigl_installer_import_post( $file, $overwrite );
		if ( ! empty( $r['ok'] ) ) {
			$log[] = ( $r['existing'] ? ( $overwrite ? 'Updated post: ' : 'Kept post: ' ) : 'Created post: ' ) . $r['title'];
		} else {
			$log[] = 'Skipped: ' . $r['msg'];
		}
	}

	// 4. Static front + posts page.
	if ( ! empty( $pages['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $pages['home'] );
		$log[] = 'Homepage set to: Home.';
	}
	if ( ! empty( $pages['blog'] ) ) {
		update_option( 'page_for_posts', (int) $pages['blog'] );
		$log[] = 'Posts page set to: Articles.';
	}

	// 5. Permalinks — pretty URLs.
	$current_permalink = get_option( 'permalink_structure' );
	if ( '/%postname%/' !== $current_permalink ) {
		update_option( 'permalink_structure', '/%postname%/' );
		flush_rewrite_rules( true );
		$log[] = 'Permalinks set to Post Name (/%postname%/).';
	}

	// 6. Menus.
	$menus = nutrigl_installer_build_menus( $pages );
	if ( $menus['primary'] ) {
		$log[] = 'Primary menu built + assigned.';
	}
	if ( $menus['footer'] ) {
		$log[] = 'Footer menu built + assigned.';
	}

	return array( 'log' => $log );
}

/**
 * Handle the admin form submission.
 */
function nutrigl_installer_handle_form() {
	if ( empty( $_POST['nutrigl_installer_action'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'nutrigl_installer' );

	$overwrite = ! empty( $_POST['overwrite'] );
	$result    = nutrigl_installer_run( $overwrite );

	set_transient( 'nutrigl_installer_result', $result, 60 );

	wp_safe_redirect( add_query_arg( array( 'page' => 'nutrigl-tools', 'ran' => '1' ), admin_url( 'options-general.php' ) ) );
	exit;
}
add_action( 'admin_init', 'nutrigl_installer_handle_form' );

/**
 * Render the installer UI block. Called from the admin page.
 */
function nutrigl_installer_render() {
	$dir  = nutrigl_installer_content_dir();
	$here = is_dir( $dir );
	?>
	<hr />
	<h2>One-click site setup</h2>
	<p>
		Creates every page and post from the <code>/content</code> folder in this repo, then wires up the homepage,
		blog index, permalinks, primary menu, and footer menu. Safe to run multiple times &mdash; existing pages
		matched by slug are left alone (unless you check &ldquo;Overwrite&rdquo;).
	</p>
	<p>Content folder detected: <code><?php echo esc_html( $dir ); ?></code> <?php echo $here ? '<span style="color:#22c55e;">&#9679; found</span>' : '<span style="color:#ef4444;">&#9679; missing</span>'; ?></p>

	<?php
	if ( isset( $_GET['ran'] ) ) {
		$r = get_transient( 'nutrigl_installer_result' );
		delete_transient( 'nutrigl_installer_result' );
		if ( is_array( $r ) ) {
			if ( ! empty( $r['error'] ) ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $r['error'] ) . '</p></div>';
			}
			if ( ! empty( $r['log'] ) ) {
				echo '<div class="notice notice-success"><p><strong>Setup complete.</strong></p><ul style="margin-left:20px;list-style:disc;">';
				foreach ( $r['log'] as $line ) {
					echo '<li>' . esc_html( $line ) . '</li>';
				}
				echo '</ul></div>';
			}
		}
	}
	?>

	<form method="post" action="">
		<?php wp_nonce_field( 'nutrigl_installer' ); ?>
		<input type="hidden" name="nutrigl_installer_action" value="run" />
		<p>
			<label>
				<input type="checkbox" name="overwrite" value="1" />
				<strong>Overwrite existing content</strong> (replaces the body of pages/posts that already exist with the same slug)
			</label>
		</p>
		<p>
			<button type="submit" class="button button-primary button-hero">Run one-click setup</button>
		</p>
	</form>
	<?php
}
