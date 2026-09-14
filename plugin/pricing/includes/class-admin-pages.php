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
	 * Products page (sync catalog; recommendations in 1.6+).
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
	 * Live connection status from last successful validate.
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
			echo esc_html( $hint );
		} else {
			echo esc_html__( 'برای شروع، کلید API را در صفحه اتصال وارد کنید.', 'pricing' );
		}
		echo '</span>';
		echo '</div>';
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
		echo '<li>' . esc_html__( '۳. اعمال دستی قیمت پیشنهادی', 'pricing' ) . '</li>';
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
	 * Products: sync gate + button + last result (no product table).
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
			'محصولات منتشرشده ووکامرس را به سرویس Pricing بفرستید. قیمت پیشنهادی و اعمال قیمت در مراحل بعد می‌آید.',
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
	}

	/**
	 * Flash notices from sync redirects.
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
	 * Farsi error copy for sync failures.
	 *
	 * @param string $code Error slug.
	 * @return string
	 */
	private static function products_error_message( $code ) {
		if ( 'not_connected' === $code ) {
			return __( 'ابتدا اتصال را برقرار کنید.', 'pricing' );
		}

		return Pricing_Connection::error_message_for_code( $code );
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
