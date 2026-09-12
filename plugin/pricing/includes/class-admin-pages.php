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
	 * Connection placeholder (API key UI in plan 1.3).
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
	 * Products placeholder (sync / recommendations in plans 1.5–1.7).
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
	 * Placeholder connection status (real state in plan 1.3 / 1.8).
	 *
	 * @return void
	 */
	private static function render_status_strip() {
		echo '<div class="pricing-admin__status" role="status">';
		echo '<span class="pricing-admin__status-dot" aria-hidden="true"></span>';
		echo '<span class="pricing-admin__status-label">';
		echo esc_html__( 'وضعیت اتصال: هنوز پیکربندی نشده', 'pricing' );
		echo '</span>';
		echo '<span class="pricing-admin__status-hint">';
		echo esc_html__( 'کلید API در مرحله بعد اضافه می‌شود.', 'pricing' );
		echo '</span>';
		echo '</div>';
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
	 * Connection empty state — no forms.
	 *
	 * @return void
	 */
	private static function render_connection_body() {
		echo '<section class="pricing-admin__panel pricing-admin__panel--empty">';
		echo '<h2 class="pricing-admin__panel-title">' . esc_html__( 'اتصال به سرویس', 'pricing' ) . '</h2>';
		echo '<p class="pricing-admin__empty">';
		echo esc_html__(
			'فرم کلید API و اعتبارسنجی در فازهای بعدی اضافه می‌شود. فعلاً چیزی برای ذخیره نیست.',
			'pricing'
		);
		echo '</p>';
		echo '</section>';
	}

	/**
	 * Products empty state — no forms / sync.
	 *
	 * @return void
	 */
	private static function render_products_body() {
		echo '<section class="pricing-admin__panel pricing-admin__panel--empty">';
		echo '<h2 class="pricing-admin__panel-title">' . esc_html__( 'محصولات ووکامرس', 'pricing' ) . '</h2>';
		echo '<p class="pricing-admin__empty">';
		echo esc_html__(
			'همگام‌سازی محصولات و نمایش قیمت پیشنهادی در فازهای بعدی می‌آید. در این نسخه لیست یا دکمه‌ای نیست.',
			'pricing'
		);
		echo '</p>';
		echo '</section>';
	}
}
