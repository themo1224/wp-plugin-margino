<?php
/**
 * Admin page renderers and shared RTL shell.
 *
 * @package Pricing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders Overview, Connection, and Products admin shells.
 */
class Pricing_Admin_Pages {

	/**
	 * Overview (home) page.
	 *
	 * @return void
	 */
	public static function render_overview() {
		self::render_shell(
			__( 'نمای کلی', 'pricing' ),
			'overview',
			array( __CLASS__, 'render_overview_body' )
		);
	}

	/**
	 * Connection page (API base URL + key).
	 *
	 * @return void
	 */
	public static function render_connection() {
		self::render_shell(
			__( 'اتصال', 'pricing' ),
			'connection',
			array( __CLASS__, 'render_connection_body' )
		);
	}

	/**
	 * Products page (sync catalog + recommended prices).
	 *
	 * @return void
	 */
	public static function render_products() {
		self::render_shell(
			__( 'محصولات', 'pricing' ),
			'products',
			array( __CLASS__, 'render_products_body' )
		);
	}

	/**
	 * Shared RTL chrome: header, status strip, content.
	 *
	 * @param string   $title   Page title (Farsi).
	 * @param string   $section Section slug for nav highlight.
	 * @param callable $body    Body renderer.
	 * @return void
	 */
	private static function render_shell( $title, $section, $body ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		echo '<div class="wrap pricing-admin" dir="rtl" lang="fa">';
		self::render_header( $title, $section );
		self::render_status_strip();
		echo '<div class="pricing-admin__content">';
		call_user_func( $body );
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Brand header + section nav.
	 *
	 * @param string $title   Current page title.
	 * @param string $section Active section slug.
	 * @return void
	 */
	private static function render_header( $title, $section ) {
		$nav = array(
			'overview'   => array(
				'label' => __( 'نمای کلی', 'pricing' ),
				'url'   => admin_url( 'admin.php?page=pricing' ),
			),
			'connection' => array(
				'label' => __( 'اتصال', 'pricing' ),
				'url'   => admin_url( 'admin.php?page=pricing-connection' ),
			),
			'products'   => array(
				'label' => __( 'محصولات', 'pricing' ),
				'url'   => admin_url( 'admin.php?page=pricing-products' ),
			),
		);

		echo '<header class="pricing-admin__header">';
		echo '<div class="pricing-admin__brand">';
		echo '<span class="pricing-admin__brand-mark" aria-hidden="true"></span>';
		echo '<div class="pricing-admin__brand-text">';
		echo '<p class="pricing-admin__product">' . esc_html__( 'Pricing', 'pricing' ) . '</p>';
		echo '<h1 class="pricing-admin__title">' . esc_html( $title ) . '</h1>';
		echo '</div>';
		echo '</div>';

		echo '<nav class="pricing-admin__nav" aria-label="' . esc_attr__( 'منوی Pricing', 'pricing' ) . '">';
		echo '<ul>';
		foreach ( $nav as $slug => $item ) {
			$active = ( $slug === $section ) ? ' is-active' : '';
			echo '<li>';
			echo '<a class="pricing-admin__nav-link' . esc_attr( $active ) . '" href="' . esc_url( $item['url'] ) . '">';
			echo esc_html( $item['label'] );
			echo '</a>';
			echo '</li>';
		}
		echo '</ul>';
		echo '</nav>';
		echo '</header>';
	}

	/**
	 * Live connection status from last successful validate + last sync hint.
	 *
	 * @return void
	 */
	private static function render_status_strip() {
		$status    = Pricing_Connection::status();
		$connected = ! empty( $status['connected'] );
		$modifier  = $connected ? ' is-connected' : ' is-disconnected';

		echo '<div class="pricing-admin__status' . esc_attr( $modifier ) . '" role="status">';
		echo '<span class="pricing-admin__status-dot" aria-hidden="true"></span>';
		echo '<span class="pricing-admin__status-label">';
		if ( $connected ) {
			echo esc_html__( 'وضعیت اتصال: متصل', 'pricing' );
		} else {
			echo esc_html__( 'وضعیت اتصال: متصل نیست', 'pricing' );
		}
		echo '</span>';
		echo '<span class="pricing-admin__status-hint">';
		if ( $connected ) {
			$shop = isset( $status['shop_name'] ) ? (string) $status['shop_name'] : '';
			$plan = isset( $status['plan_label'] ) ? (string) $status['plan_label'] : '';
			$hint = trim( $shop . ( '' !== $shop && '' !== $plan ? ' — ' : '' ) . $plan );
			$sync = self::last_sync_hint_text();
			if ( '' !== $hint ) {
				$hint .= ' · ';
			}
			$hint .= $sync;
			echo esc_html( $hint );
		} else {
			echo esc_html__( 'برای شروع، کلید API را در صفحه اتصال وارد کنید.', 'pricing' );
		}
		echo '</span>';
		echo '</div>';
	}

	/**
	 * Farsi last-sync fragment for strip / overview.
	 *
	 * @return string
	 */
	private static function last_sync_hint_text() {
		$last = Pricing_Product_Sync::get_last_sync();
		if ( ! is_array( $last ) || empty( $last['synced_at'] ) ) {
			return __( 'هنوز همگام‌سازی نشده', 'pricing' );
		}

		$at = (string) $last['synced_at'];

		/* translators: %s: UTC timestamp */
		return sprintf( __( 'آخرین همگام‌سازی: %s UTC', 'pricing' ), $at );
	}

	/**
	 * Flash notices from connect/disconnect redirects.
	 *
	 * @return void
	 */
	private static function render_connection_notices() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only flash query args.
		$notice = isset( $_GET['pricing_notice'] ) ? sanitize_key( wp_unslash( (string) $_GET['pricing_notice'] ) ) : '';
		if ( '' === $notice ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$error = isset( $_GET['pricing_error'] ) ? sanitize_key( wp_unslash( (string) $_GET['pricing_error'] ) ) : '';

		$class   = 'notice notice-info is-dismissible';
		$message = '';

		switch ( $notice ) {
			case 'connected':
				$class   = 'notice notice-success is-dismissible';
				$message = __( 'اتصال برقرار شد.', 'pricing' );
				break;
			case 'disconnected':
				$class   = 'notice notice-success is-dismissible';
				$message = __( 'اتصال قطع شد.', 'pricing' );
				break;
			case 'error':
				$class   = 'notice notice-error is-dismissible';
				$message = Pricing_Connection::error_message_for_code( $error );
				break;
			default:
				return;
		}

		echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Overview body.
	 *
	 * @return void
	 */
	private static function render_overview_body() {
		$status      = Pricing_Connection::status();
		$connected   = ! empty( $status['connected'] );
		$connected_at = isset( $status['connected_at'] ) ? (string) $status['connected_at'] : '';

		echo '<section class="pricing-admin__panel">';
		echo '<h2 class="pricing-admin__panel-title">' . esc_html__( 'اتصال فروشگاه به Pricing', 'pricing' ) . '</h2>';
		echo '<p class="pricing-admin__lead">';
		echo esc_html__(
			'این افزونه فقط اتصال ووکامرس است. موتور قیمت، رقبا، اعلان‌ها و گزارش‌ها در سرویس Pricing هستند.',
			'pricing'
		);
		echo '</p>';
		echo '<ul class="pricing-admin__steps">';
		echo '<li>' . esc_html__( '۱. اتصال با کلید API', 'pricing' ) . '</li>';
		echo '<li>' . esc_html__( '۲. همگام‌سازی محصولات', 'pricing' ) . '</li>';
		echo '<li>' . esc_html__( '۳. اعمال دستی قیمت پیشنهادی (نه زیر کف قیمت)', 'pricing' ) . '</li>';
		echo '</ul>';
		echo '</section>';

		echo '<section class="pricing-admin__panel pricing-admin__panel--status">';
		echo '<h2 class="pricing-admin__panel-title">' . esc_html__( 'وضعیت فعلی', 'pricing' ) . '</h2>';
		echo '<ul class="pricing-admin__status-list">';
		echo '<li>';
		echo esc_html__( 'اتصال:', 'pricing' ) . ' ';
		if ( $connected ) {
			echo esc_html__( 'متصل', 'pricing' );
			if ( '' !== $connected_at ) {
				echo ' <span class="pricing-admin__field-hint" dir="ltr">(' . esc_html( $connected_at ) . ' UTC)</span>';
			}
		} else {
			echo esc_html__( 'متصل نیست', 'pricing' );
		}
		echo '</li>';
		echo '<li>' . esc_html( self::last_sync_hint_text() ) . '</li>';
		echo '</ul>';
		echo '</section>';
	}

	/**
	 * Connection form: base URL + API key.
	 *
	 * @return void
	 */
	private static function render_connection_body() {
		self::render_connection_notices();

		$base_url    = Pricing_Connection::get_base_url();
		$has_key     = Pricing_Connection::has_api_key();
		$connect_url = admin_url( 'admin-post.php' );

		echo '<section class="pricing-admin__panel">';
		echo '<h2 class="pricing-admin__panel-title">' . esc_html__( 'اتصال به سرویس', 'pricing' ) . '</h2>';
		echo '<p class="pricing-admin__lead">';
		echo esc_html__(
			'آدرس سرویس و کلید API را وارد کنید تا فروشگاه به Pricing وصل شود.',
			'pricing'
		);
		echo '</p>';

		echo '<form class="pricing-admin__form" method="post" action="' . esc_url( $connect_url ) . '">';
		echo '<input type="hidden" name="action" value="pricing_connect" />';
		wp_nonce_field( Pricing_Connection::NONCE_ACTION );

		echo '<div class="pricing-admin__field">';
		echo '<label class="pricing-admin__label" for="pricing_api_base_url">' . esc_html__( 'آدرس سرویس', 'pricing' ) . '</label>';
		echo '<input class="pricing-admin__input regular-text" type="url" name="pricing_api_base_url" id="pricing_api_base_url" dir="ltr" value="' . esc_attr( $base_url ) . '" placeholder="http://localhost:8000/v1" autocomplete="off" required />';
		echo '</div>';

		echo '<div class="pricing-admin__field">';
		echo '<label class="pricing-admin__label" for="pricing_api_key">' . esc_html__( 'کلید API', 'pricing' ) . '</label>';
		echo '<input class="pricing-admin__input regular-text" type="password" name="pricing_api_key" id="pricing_api_key" dir="ltr" value="" placeholder="' . esc_attr( $has_key ? __( 'برای حفظ کلید فعلی خالی بگذارید', 'pricing' ) : '' ) . '" autocomplete="new-password" ' . ( $has_key ? '' : 'required ' ) . '/>';
		if ( $has_key ) {
			echo '<p class="pricing-admin__field-hint">' . esc_html__( 'کلید ذخیره شده است. برای تغییر، کلید جدید را وارد کنید.', 'pricing' ) . '</p>';
		}
		echo '</div>';

		echo '<div class="pricing-admin__actions">';
		echo '<button type="submit" class="button button-primary">' . esc_html__( 'اتصال', 'pricing' ) . '</button>';
		echo '</div>';
		echo '</form>';

		if ( $has_key ) {
			echo '<form class="pricing-admin__form pricing-admin__form--disconnect" method="post" action="' . esc_url( $connect_url ) . '">';
			echo '<input type="hidden" name="action" value="pricing_disconnect" />';
			wp_nonce_field( Pricing_Connection::NONCE_ACTION );
			echo '<div class="pricing-admin__actions">';
			echo '<button type="submit" class="button">' . esc_html__( 'قطع اتصال', 'pricing' ) . '</button>';
			echo '</div>';
			echo '</form>';
		}

		echo '</section>';
	}

	/**
	 * Products: sync gate + button + last result + recommendations table.
	 *
	 * @return void
	 */
	private static function render_products_body() {
		self::render_products_notices();

		$connected = Pricing_Connection::is_connected();
		$last      = Pricing_Product_Sync::get_last_sync();

		echo '<section class="pricing-admin__panel">';
		echo '<h2 class="pricing-admin__panel-title">' . esc_html__( 'همگام‌سازی محصولات', 'pricing' ) . '</h2>';

		if ( ! $connected ) {
			echo '<p class="pricing-admin__lead">';
			echo esc_html__( 'برای همگام‌سازی محصولات، ابتدا فروشگاه را به Pricing وصل کنید.', 'pricing' );
			echo '</p>';
			echo '<p class="pricing-admin__actions">';
			echo '<a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=pricing-connection' ) ) . '">';
			echo esc_html__( 'رفتن به اتصال', 'pricing' );
			echo '</a>';
			echo '</p>';
			echo '</section>';
			return;
		}

		echo '<p class="pricing-admin__lead">';
		echo esc_html__(
			'محصولات منتشرشده ووکامرس را به سرویس Pricing بفرستید، سپس قیمت پیشنهادی را در جدول زیر ببینید.',
			'pricing'
		);
		echo '</p>';

		echo '<form class="pricing-admin__form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="pricing_sync_products" />';
		wp_nonce_field( Pricing_Product_Sync::NONCE_ACTION );
		echo '<div class="pricing-admin__actions">';
		echo '<button type="submit" class="button button-primary">' . esc_html__( 'همگام‌سازی محصولات', 'pricing' ) . '</button>';
		echo '</div>';
		echo '</form>';

		if ( is_array( $last ) ) {
			self::render_last_sync_summary( $last );
		}

		echo '</section>';

		self::render_recommendations_panel();
	}

	/**
	 * Paginated recommendations table for published WooCommerce products.
	 *
	 * @return void
	 */
	private static function render_recommendations_panel() {
		$per_page = 20;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only pagination.
		$page = isset( $_GET['pricing_paged'] ) ? max( 1, absint( wp_unslash( $_GET['pricing_paged'] ) ) ) : 1;

		$query = array(
			'status'   => 'publish',
			'limit'    => $per_page,
			'page'     => $page,
			'paginate' => true,
			'type'     => array( 'simple', 'variable', 'external', 'grouped' ),
			'return'   => 'objects',
		);

		$paged = function_exists( 'wc_get_products' ) ? wc_get_products( $query ) : null;

		$products = array();
		$total    = 0;
		$max_pages = 1;

		if ( is_object( $paged ) && isset( $paged->products ) && is_array( $paged->products ) ) {
			$products  = $paged->products;
			$total     = isset( $paged->total ) ? (int) $paged->total : count( $products );
			$max_pages = isset( $paged->max_num_pages ) ? max( 1, (int) $paged->max_num_pages ) : 1;
		} elseif ( is_array( $paged ) ) {
			// Fallback if paginate is unsupported.
			$products  = $paged;
			$total     = count( $products );
			$max_pages = 1;
		}

		echo '<section class="pricing-admin__panel pricing-admin__panel--recs">';
		echo '<h2 class="pricing-admin__panel-title">' . esc_html__( 'قیمت‌های پیشنهادی', 'pricing' ) . '</h2>';
		echo '<p class="pricing-admin__lead">';
		echo esc_html__(
			'قیمت پیشنهادی از سرویس Pricing خوانده می‌شود. برای محصولات ساده می‌توانید قیمت را دستی اعمال کنید؛ اگر قیمت فروشگاه زیر کف باشد، اعمال ممنوع است.',
			'pricing'
		);
		echo '</p>';

		if ( empty( $products ) ) {
			echo '<p class="pricing-admin__empty">';
			echo esc_html__( 'محصول منتشرشده‌ای برای نمایش نیست.', 'pricing' );
			echo '</p>';
			echo '</section>';
			return;
		}

		$ids = array();
		foreach ( $products as $product ) {
			if ( is_object( $product ) && method_exists( $product, 'get_id' ) ) {
				$ids[] = (string) $product->get_id();
			}
		}

		$fetched    = Pricing_Recommendations::fetch_for_products( $ids );
		$results    = isset( $fetched['results'] ) && is_array( $fetched['results'] ) ? $fetched['results'] : array();
		$page_error = isset( $fetched['page_error'] ) && is_string( $fetched['page_error'] ) ? $fetched['page_error'] : null;

		if ( null !== $page_error && '' !== $page_error ) {
			echo '<div class="notice notice-error inline"><p>';
			echo esc_html( Pricing_Connection::error_message_for_code( $page_error ) );
			echo '</p></div>';
		}

		echo '<div class="pricing-admin__recs-wrap">';
		echo '<table class="pricing-admin__recs-table">';
		echo '<thead><tr>';
		echo '<th scope="col">' . esc_html__( 'نام', 'pricing' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'قیمت فعلی', 'pricing' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'قیمت پیشنهادی', 'pricing' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'ارز', 'pricing' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'به‌روزرسانی', 'pricing' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'وضعیت', 'pricing' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'عملیات', 'pricing' ) . '</th>';
		echo '</tr></thead><tbody>';

		$apply_url = admin_url( 'admin-post.php' );

		foreach ( $products as $product ) {
			if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
				continue;
			}

			$id      = (string) $product->get_id();
			$name    = method_exists( $product, 'get_name' ) ? (string) $product->get_name() : $id;
			$current = self::format_store_price( $product );
			$result  = isset( $results[ $id ] ) && is_array( $results[ $id ] ) ? $results[ $id ] : null;
			$is_simple = method_exists( $product, 'is_type' ) && $product->is_type( 'simple' );

			$rec_price  = '—';
			$currency   = '—';
			$updated    = '—';
			$status_txt = __( 'نامشخص', 'pricing' );
			$below      = false;
			$rec_ok     = false;

			if ( is_array( $result ) && ! empty( $result['ok'] ) && is_array( $result['data'] ) ) {
				$data       = $result['data'];
				$rec_price  = isset( $data['recommended_price'] ) && '' !== $data['recommended_price']
					? (string) $data['recommended_price']
					: '—';
				$currency   = isset( $data['currency'] ) && '' !== $data['currency']
					? (string) $data['currency']
					: '—';
				$updated    = isset( $data['updated_at'] ) && '' !== $data['updated_at']
					? (string) $data['updated_at']
					: '—';
				$below      = ! empty( $data['below_floor'] );
				$rec_ok     = ( '—' !== $rec_price );
				$status_txt = $below ? __( 'زیر کف', 'pricing' ) : __( 'آماده', 'pricing' );
			} elseif ( is_array( $result ) && isset( $result['error_code'] ) && 'not_synced' === $result['error_code'] ) {
				$status_txt = __( 'همگام‌سازی نشده', 'pricing' );
			} elseif ( is_array( $result ) && ! empty( $result['error_code'] ) ) {
				$status_txt = __( 'خطا', 'pricing' );
			}

			echo '<tr>';
			echo '<td class="pricing-admin__recs-name">' . esc_html( $name ) . '</td>';
			echo '<td dir="ltr">' . esc_html( $current ) . '</td>';
			echo '<td dir="ltr">' . esc_html( $rec_price );
			if ( $below ) {
				echo ' <span class="pricing-admin__recs-badge">' . esc_html__( 'زیر کف', 'pricing' ) . '</span>';
			}
			echo '</td>';
			echo '<td dir="ltr">' . esc_html( $currency ) . '</td>';
			echo '<td dir="ltr">' . esc_html( $updated ) . '</td>';
			echo '<td>' . esc_html( $status_txt ) . '</td>';
			echo '<td class="pricing-admin__recs-actions">';

			if ( $is_simple && $rec_ok && ! $below ) {
				echo '<form class="pricing-admin__apply-form" method="post" action="' . esc_url( $apply_url ) . '">';
				echo '<input type="hidden" name="action" value="pricing_apply_price" />';
				echo '<input type="hidden" name="product_id" value="' . esc_attr( $id ) . '" />';
				wp_nonce_field( Pricing_Price_Apply::NONCE_ACTION );
				echo '<button type="submit" class="button button-small">' . esc_html__( 'اعمال قیمت', 'pricing' ) . '</button>';
				echo '</form>';
			} elseif ( $is_simple && $rec_ok && $below ) {
				echo '<span class="pricing-admin__field-hint">' . esc_html__( 'زیر کف — اعمال ممنوع', 'pricing' ) . '</span>';
			} elseif ( ! $is_simple ) {
				echo '<span class="pricing-admin__field-hint">' . esc_html__( 'فقط محصول ساده', 'pricing' ) . '</span>';
			} else {
				echo '<span class="pricing-admin__field-hint">—</span>';
			}

			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '</div>';

		if ( $max_pages > 1 ) {
			self::render_recs_pagination( $page, $max_pages, $total );
		}

		echo '</section>';
	}

	/**
	 * Current store price for table display.
	 *
	 * @param object $product WC product.
	 * @return string
	 */
	private static function format_store_price( $product ) {
		$raw = '';

		if ( method_exists( $product, 'is_type' ) && $product->is_type( 'variable' ) ) {
			if ( method_exists( $product, 'get_variation_price' ) ) {
				$raw = (string) $product->get_variation_price( 'min', false );
			}
		} elseif ( method_exists( $product, 'get_price' ) ) {
			$raw = (string) $product->get_price();
		}

		$raw = trim( $raw );
		if ( '' === $raw ) {
			return '—';
		}

		if ( function_exists( 'wc_format_localized_price' ) ) {
			$formatted = wc_format_localized_price( $raw );
			if ( is_string( $formatted ) && '' !== $formatted ) {
				return $formatted;
			}
		}

		return $raw;
	}

	/**
	 * Pagination links for recommendations table.
	 *
	 * @param int $page      Current page (1-based).
	 * @param int $max_pages Total pages.
	 * @param int $total     Total products.
	 * @return void
	 */
	private static function render_recs_pagination( $page, $max_pages, $total ) {
		$base_url = admin_url( 'admin.php?page=pricing-products' );

		echo '<nav class="pricing-admin__recs-pagination" aria-label="' . esc_attr__( 'صفحه‌بندی محصولات', 'pricing' ) . '">';
		echo '<p class="pricing-admin__field-hint">';
		echo esc_html(
			sprintf(
				/* translators: 1: current page, 2: total pages, 3: total products */
				__( 'صفحه %1$d از %2$d — مجموع %3$d محصول', 'pricing' ),
				(int) $page,
				(int) $max_pages,
				(int) $total
			)
		);
		echo '</p>';
		echo '<div class="pricing-admin__actions">';

		if ( $page > 1 ) {
			$prev = add_query_arg( 'pricing_paged', (string) ( $page - 1 ), $base_url );
			echo '<a class="button" href="' . esc_url( $prev ) . '">' . esc_html__( 'قبلی', 'pricing' ) . '</a>';
		}

		if ( $page < $max_pages ) {
			$next = add_query_arg( 'pricing_paged', (string) ( $page + 1 ), $base_url );
			echo '<a class="button" href="' . esc_url( $next ) . '">' . esc_html__( 'بعدی', 'pricing' ) . '</a>';
		}

		echo '</div>';
		echo '</nav>';
	}

	/**
	 * Flash notices from sync / apply redirects.
	 *
	 * @return void
	 */
	private static function render_products_notices() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only flash query args.
		$notice = isset( $_GET['pricing_notice'] ) ? sanitize_key( wp_unslash( (string) $_GET['pricing_notice'] ) ) : '';
		if ( '' === $notice ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$error = isset( $_GET['pricing_error'] ) ? sanitize_key( wp_unslash( (string) $_GET['pricing_error'] ) ) : '';

		$class   = 'notice notice-info is-dismissible';
		$message = '';

		switch ( $notice ) {
			case 'synced':
				$class   = 'notice notice-success is-dismissible';
				$last    = Pricing_Product_Sync::get_last_sync();
				$accepted = is_array( $last ) && isset( $last['accepted'] ) ? (int) $last['accepted'] : 0;
				$rejected = is_array( $last ) && isset( $last['rejected'] ) ? (int) $last['rejected'] : 0;
				$skipped  = is_array( $last ) && isset( $last['skipped'] ) ? (int) $last['skipped'] : 0;
				/* translators: 1: accepted count, 2: rejected count, 3: skipped count */
				$message = sprintf(
					__( 'همگام‌سازی انجام شد. پذیرفته: %1$d — ردشده: %2$d — رد محلی (بدون قیمت): %3$d', 'pricing' ),
					$accepted,
					$rejected,
					$skipped
				);
				break;
			case 'applied':
				$class = 'notice notice-success is-dismissible';
				$last  = Pricing_Price_Apply::get_last_apply();
				$price = is_array( $last ) && isset( $last['applied_price'] ) ? (string) $last['applied_price'] : '';
				if ( '' !== $price ) {
					/* translators: %s: applied price */
					$message = sprintf( __( 'قیمت اعمال شد: %s', 'pricing' ), $price );
				} else {
					$message = __( 'قیمت اعمال شد.', 'pricing' );
				}
				break;
			case 'error':
				$class   = 'notice notice-error is-dismissible';
				$message = self::products_error_message( $error );
				break;
			default:
				return;
		}

		echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Farsi error copy for sync / apply failures.
	 *
	 * @param string $code Error slug.
	 * @return string
	 */
	private static function products_error_message( $code ) {
		switch ( $code ) {
			case 'not_connected':
				return __( 'ابتدا اتصال را برقرار کنید.', 'pricing' );
			case 'unsupported_type':
				return __( 'اعمال قیمت فقط برای محصولات ساده پشتیبانی می‌شود.', 'pricing' );
			case 'invalid_product':
				return __( 'محصول نامعتبر است.', 'pricing' );
			case 'not_synced':
				return __( 'ابتدا محصول را همگام‌سازی کنید.', 'pricing' );
			case 'below_floor':
				return __( 'قیمت فعلی فروشگاه زیر کف است؛ اعمال قیمت مجاز نیست.', 'pricing' );
			case 'wc_write_failed':
				return __( 'نوشتن قیمت در ووکامرس ناموفق بود.', 'pricing' );
			case 'ack_failed':
				return __( 'قیمت در ووکامرس به‌روز شد، اما ثبت در سرویس Pricing انجام نشد. پس از رفع مشکل API دوباره اعمال کنید.', 'pricing' );
			default:
				return Pricing_Connection::error_message_for_code( $code );
		}
	}

	/**
	 * Render persisted last-sync counts.
	 *
	 * @param array $last Last sync option.
	 * @return void
	 */
	private static function render_last_sync_summary( array $last ) {
		$accepted = isset( $last['accepted'] ) ? (int) $last['accepted'] : 0;
		$rejected = isset( $last['rejected'] ) ? (int) $last['rejected'] : 0;
		$skipped  = isset( $last['skipped'] ) ? (int) $last['skipped'] : 0;
		$at       = isset( $last['synced_at'] ) ? (string) $last['synced_at'] : '';
		$partial  = ! empty( $last['partial'] );
		$failed   = isset( $last['ok'] ) && ! $last['ok'];

		echo '<div class="pricing-admin__sync-summary">';
		echo '<h3 class="pricing-admin__sync-summary-title">' . esc_html__( 'آخرین همگام‌سازی', 'pricing' ) . '</h3>';
		if ( '' !== $at ) {
			echo '<p class="pricing-admin__field-hint" dir="ltr">' . esc_html( $at ) . ' UTC</p>';
		}
		echo '<ul class="pricing-admin__sync-stats">';
		echo '<li>' . esc_html( sprintf( /* translators: %d: count */ __( 'پذیرفته‌شده: %d', 'pricing' ), $accepted ) ) . '</li>';
		echo '<li>' . esc_html( sprintf( /* translators: %d: count */ __( 'ردشده توسط API: %d', 'pricing' ), $rejected ) ) . '</li>';
		echo '<li>' . esc_html( sprintf( /* translators: %d: count */ __( 'رد محلی (بدون قیمت): %d', 'pricing' ), $skipped ) ) . '</li>';
		echo '</ul>';

		if ( $failed ) {
			echo '<p class="pricing-admin__sync-warning">';
			echo esc_html__( 'آخرین درخواست کامل نشد.', 'pricing' );
			if ( $partial ) {
				echo ' ' . esc_html__( 'برخی دسته‌ها قبل از خطا ارسال شده‌اند.', 'pricing' );
			}
			echo '</p>';
		} elseif ( $partial ) {
			echo '<p class="pricing-admin__sync-warning">';
			echo esc_html__( 'همگام‌سازی جزئی بود (برخی دسته‌ها ارسال شدند).', 'pricing' );
			echo '</p>';
		}

		if ( ! empty( $last['rejected_ids'] ) && is_array( $last['rejected_ids'] ) ) {
			$ids = array_map( 'strval', $last['rejected_ids'] );
			echo '<p class="pricing-admin__field-hint" dir="ltr">';
			echo esc_html__( 'نمونه شناسه‌های ردشده:', 'pricing' ) . ' ' . esc_html( implode( ', ', $ids ) );
			echo '</p>';
		}

		echo '</div>';
	}
}
