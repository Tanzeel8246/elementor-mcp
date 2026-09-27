<?php
/**
 * WooCommerce abilities for MindCrafts AI.
 *
 * Provides MCP tools for WooCommerce product grids, category displays,
 * cart, checkout, my-account, product ratings, and full Theme Builder product templates.
 *
 * @package MindCrafts_AI
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles WooCommerce widgets and template creation for Elementor and Elementor Pro.
 *
 * @since 2.0.0
 */
class MindCrafts_AI_WooCommerce_Abilities {

	/**
	 * Data access layer.
	 *
	 * @var MindCrafts_AI_Data
	 */
	private MindCrafts_AI_Data $data;

	/**
	 * Element factory.
	 *
	 * @var MindCrafts_AI_Element_Factory
	 */
	private MindCrafts_AI_Element_Factory $factory;

	/**
	 * Constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param MindCrafts_AI_Data                 $data    The data access layer.
	 * @param MindCrafts_AI_Element_Factory|null $factory Optional element factory.
	 */
	public function __construct( MindCrafts_AI_Data $data, ?MindCrafts_AI_Element_Factory $factory = null ) {
		$this->data    = $data;
		$this->factory = $factory ? $factory : new MindCrafts_AI_Element_Factory();
	}

	/**
	 * Returns the ability names registered by this class.
	 *
	 * @since 2.0.0
	 *
	 * @return string[]
	 */
	public function get_ability_names(): array {
		$names = array(
			'mindcrafts-ai/add-woocommerce-products',
			'mindcrafts-ai/add-woocommerce-categories',
			'mindcrafts-ai/add-woo-cart',
			'mindcrafts-ai/add-woo-checkout',
			'mindcrafts-ai/add-woo-my-account',
			'mindcrafts-ai/add-woo-product-rating',
			'mindcrafts-ai/seed-demo-products',
		);

		if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			$names[] = 'mindcrafts-ai/create-woo-single-product-template';
			$names[] = 'mindcrafts-ai/create-woo-archive-template';
		}

		return $names;
	}

	/**
	 * Registers all WooCommerce abilities.
	 *
	 * @since 2.0.0
	 */
	public function register(): void {
		wp_register_ability(
			'mindcrafts-ai/add-woocommerce-products',
			array(
				'label'               => __( 'Add WooCommerce Products Grid', 'mindcrafts-ai' ),
				'description'         => __( 'Adds a WooCommerce products grid widget to an Elementor container.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_add_products' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'   => array( 'type' => 'integer', 'description' => __( 'The post/page ID.', 'mindcrafts-ai' ) ),
						'parent_id' => array( 'type' => 'string', 'description' => __( 'Parent container element ID.', 'mindcrafts-ai' ) ),
						'position'  => array( 'type' => 'integer', 'description' => __( 'Insert position. -1 = append.', 'mindcrafts-ai' ) ),
						'columns'   => array( 'type' => 'integer', 'description' => __( 'Number of columns to show.', 'mindcrafts-ai' ) ),
						'rows'      => array( 'type' => 'integer', 'description' => __( 'Number of rows to show.', 'mindcrafts-ai' ) ),
						'orderby'   => array( 'type' => 'string', 'description' => __( 'Order products by.', 'mindcrafts-ai' ) ),
						'order'     => array( 'type' => 'string', 'enum' => array( 'ASC', 'DESC' ), 'description' => __( 'Sort order.', 'mindcrafts-ai' ) ),
					),
					'required'   => array( 'post_id', 'parent_id' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'    => array( 'type' => 'boolean' ),
						'element_id' => array( 'type' => 'string' ),
					),
				),
			)
		);

		wp_register_ability(
			'mindcrafts-ai/add-woocommerce-categories',
			array(
				'label'               => __( 'Add WooCommerce Categories Grid', 'mindcrafts-ai' ),
				'description'         => __( 'Adds a WooCommerce product categories grid widget to an Elementor container.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_add_categories' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'   => array( 'type' => 'integer', 'description' => __( 'The post/page ID.', 'mindcrafts-ai' ) ),
						'parent_id' => array( 'type' => 'string', 'description' => __( 'Parent container element ID.', 'mindcrafts-ai' ) ),
						'position'  => array( 'type' => 'integer', 'description' => __( 'Insert position. -1 = append.', 'mindcrafts-ai' ) ),
						'columns'   => array( 'type' => 'integer', 'description' => __( 'Number of columns to show.', 'mindcrafts-ai' ) ),
						'number'    => array( 'type' => 'integer', 'description' => __( 'Total number of categories to show.', 'mindcrafts-ai' ) ),
					),
					'required'   => array( 'post_id', 'parent_id' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'    => array( 'type' => 'boolean' ),
						'element_id' => array( 'type' => 'string' ),
					),
				),
			)
		);

		wp_register_ability(
			'mindcrafts-ai/add-woo-cart',
			array(
				'label'               => __( 'Add WooCommerce Cart', 'mindcrafts-ai' ),
				'description'         => __( 'Adds an Elementor WooCommerce Cart widget to a container.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_add_cart' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'   => array( 'type' => 'integer', 'description' => __( 'The post/page ID.', 'mindcrafts-ai' ) ),
						'parent_id' => array( 'type' => 'string', 'description' => __( 'Parent container ID.', 'mindcrafts-ai' ) ),
						'position'  => array( 'type' => 'integer', 'description' => __( 'Insert position. -1 = append.', 'mindcrafts-ai' ) ),
					),
					'required'   => array( 'post_id', 'parent_id' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'    => array( 'type' => 'boolean' ),
						'element_id' => array( 'type' => 'string' ),
					),
				),
			)
		);

		wp_register_ability(
			'mindcrafts-ai/add-woo-checkout',
			array(
				'label'               => __( 'Add WooCommerce Checkout', 'mindcrafts-ai' ),
				'description'         => __( 'Adds an Elementor WooCommerce Checkout widget to a container.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_add_checkout' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'   => array( 'type' => 'integer', 'description' => __( 'The post/page ID.', 'mindcrafts-ai' ) ),
						'parent_id' => array( 'type' => 'string', 'description' => __( 'Parent container ID.', 'mindcrafts-ai' ) ),
						'position'  => array( 'type' => 'integer', 'description' => __( 'Insert position. -1 = append.', 'mindcrafts-ai' ) ),
					),
					'required'   => array( 'post_id', 'parent_id' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'    => array( 'type' => 'boolean' ),
						'element_id' => array( 'type' => 'string' ),
					),
				),
			)
		);

		wp_register_ability(
			'mindcrafts-ai/add-woo-my-account',
			array(
				'label'               => __( 'Add WooCommerce My Account', 'mindcrafts-ai' ),
				'description'         => __( 'Adds an Elementor WooCommerce My Account widget to a container.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_add_my_account' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'   => array( 'type' => 'integer', 'description' => __( 'The post/page ID.', 'mindcrafts-ai' ) ),
						'parent_id' => array( 'type' => 'string', 'description' => __( 'Parent container ID.', 'mindcrafts-ai' ) ),
						'position'  => array( 'type' => 'integer', 'description' => __( 'Insert position. -1 = append.', 'mindcrafts-ai' ) ),
					),
					'required'   => array( 'post_id', 'parent_id' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'    => array( 'type' => 'boolean' ),
						'element_id' => array( 'type' => 'string' ),
					),
				),
			)
		);

		wp_register_ability(
			'mindcrafts-ai/add-woo-product-rating',
			array(
				'label'               => __( 'Add WooCommerce Product Rating', 'mindcrafts-ai' ),
				'description'         => __( 'Adds an Elementor WooCommerce Product Rating widget to a container.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_add_product_rating' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id'   => array( 'type' => 'integer', 'description' => __( 'The post/page ID.', 'mindcrafts-ai' ) ),
						'parent_id' => array( 'type' => 'string', 'description' => __( 'Parent container ID.', 'mindcrafts-ai' ) ),
						'position'  => array( 'type' => 'integer', 'description' => __( 'Insert position. -1 = append.', 'mindcrafts-ai' ) ),
					),
					'required'   => array( 'post_id', 'parent_id' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'    => array( 'type' => 'boolean' ),
						'element_id' => array( 'type' => 'string' ),
					),
				),
			)
		);

		if ( defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			wp_register_ability(
				'mindcrafts-ai/create-woo-single-product-template',
				array(
					'label'               => __( 'Create WooCommerce Single Product Template', 'mindcrafts-ai' ),
					'description'         => __( 'Creates an Elementor Pro Single Product theme template pre-populated with standard product widgets (title, images, price, add to cart, meta).', 'mindcrafts-ai' ),
					'category'            => 'mindcrafts-ai',
					'execute_callback'    => array( $this, 'execute_create_single_product_template' ),
					'permission_callback' => array( $this, 'check_manage_permission' ),
					'input_schema'        => array(
						'type'       => 'object',
						'properties' => array(
							'title'        => array( 'type' => 'string', 'description' => __( 'Template title.', 'mindcrafts-ai' ) ),
							'apply_to_all' => array( 'type' => 'boolean', 'description' => __( 'Whether to apply condition to all products automatically.', 'mindcrafts-ai' ) ),
							'product_id'   => array( 'type' => 'integer', 'description' => __( 'Sample preview product ID.', 'mindcrafts-ai' ) ),
						),
						'required'   => array( 'title' ),
					),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'success'     => array( 'type' => 'boolean' ),
							'template_id' => array( 'type' => 'integer' ),
							'edit_url'    => array( 'type' => 'string' ),
						),
					),
				)
			);

			wp_register_ability(
				'mindcrafts-ai/create-woo-archive-template',
				array(
					'label'               => __( 'Create WooCommerce Archive Template', 'mindcrafts-ai' ),
					'description'         => __( 'Creates an Elementor Pro Product Archive / Shop template pre-populated with archive widgets (products grid, breadcrumb, description).', 'mindcrafts-ai' ),
					'category'            => 'mindcrafts-ai',
					'execute_callback'    => array( $this, 'execute_create_archive_template' ),
					'permission_callback' => array( $this, 'check_manage_permission' ),
					'input_schema'        => array(
						'type'       => 'object',
						'properties' => array(
							'title'        => array( 'type' => 'string', 'description' => __( 'Template title.', 'mindcrafts-ai' ) ),
							'apply_to_all' => array( 'type' => 'boolean', 'description' => __( 'Whether to apply condition to all product archives automatically.', 'mindcrafts-ai' ) ),
						),
						'required'   => array( 'title' ),
					),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'success'     => array( 'type' => 'boolean' ),
							'template_id' => array( 'type' => 'integer' ),
							'edit_url'    => array( 'type' => 'string' ),
						),
					),
				)
			);
		}

		// ================================================================
		// مرحلہ 4: سیڈ ڈیمو پروڈکٹس ٹول رجسٹریشن
		// ================================================================
		wp_register_ability(
			'mindcrafts-ai/seed-demo-products',
			array(
				'label'               => __( 'Seed Demo WooCommerce Products', 'mindcrafts-ai' ),
				'description'         => __( 'Creates sample WooCommerce products with placeholder images so product grids look populated during design. Safe to use on empty stores.', 'mindcrafts-ai' ),
				'category'            => 'mindcrafts-ai',
				'execute_callback'    => array( $this, 'execute_seed_demo_products' ),
				'permission_callback' => array( $this, 'check_manage_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'count'    => array(
							'type'        => 'integer',
							'description' => __( 'Number of demo products to create (1-12). Default: 4.', 'mindcrafts-ai' ),
						),
						'category' => array(
							'type'        => 'string',
							'description' => __( 'Product category name to assign. Default: "Demo Products".', 'mindcrafts-ai' ),
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'    => array( 'type' => 'boolean' ),
						'created'    => array( 'type' => 'integer' ),
						'skipped'    => array( 'type' => 'integer' ),
						'product_ids' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
					),
				),
			)
		);
	}

	/**
	 * Permission check for post editing.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $input Optional input.
	 * @return bool
	 */
	public function check_permission( $input = null ): bool {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}
		$post_id = absint( $input['post_id'] ?? 0 );
		if ( $post_id && ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Permission check for template management.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function check_manage_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Ensures WooCommerce is active.
	 *
	 * @since 2.0.0
	 *
	 * @return \WP_Error|null Error if inactive, null if active.
	 */
	private function verify_woocommerce() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new \WP_Error(
				'woocommerce_not_active',
				__( 'WooCommerce is not installed or active.', 'mindcrafts-ai' )
			);
		}
		return null;
	}

	/**
	 * Adds products grid widget.
	 *
	 * @since 2.0.0
	 */
	public function execute_add_products( $input ) {
		$error = $this->verify_woocommerce();
		if ( $error ) {
			return $error;
		}

		$post_id   = absint( $input['post_id'] ?? 0 );
		$parent_id = sanitize_text_field( $input['parent_id'] ?? '' );
		$position  = intval( $input['position'] ?? -1 );
		$columns   = absint( $input['columns'] ?? 4 );
		$rows      = absint( $input['rows'] ?? 1 );
		$orderby   = sanitize_text_field( $input['orderby'] ?? 'date' );
		$order     = sanitize_text_field( $input['order'] ?? 'DESC' );

		$settings = array(
			'columns' => $columns,
			'rows'    => $rows,
			'orderby' => $orderby,
			'order'   => $order,
		);

		return $this->insert_woo_widget( $post_id, $parent_id, 'woocommerce-products', $settings, $position );
	}

	/**
	 * Adds categories grid widget.
	 *
	 * @since 2.0.0
	 */
	public function execute_add_categories( $input ) {
		$error = $this->verify_woocommerce();
		if ( $error ) {
			return $error;
		}

		$post_id   = absint( $input['post_id'] ?? 0 );
		$parent_id = sanitize_text_field( $input['parent_id'] ?? '' );
		$position  = intval( $input['position'] ?? -1 );
		$columns   = absint( $input['columns'] ?? 4 );
		$number    = absint( $input['number'] ?? 4 );

		$settings = array(
			'columns' => $columns,
			'number'  => $number,
		);

		return $this->insert_woo_widget( $post_id, $parent_id, 'woocommerce-categories', $settings, $position );
	}

	/**
	 * Adds Cart widget.
	 *
	 * @since 2.0.0
	 */
	public function execute_add_cart( $input ) {
		$error = $this->verify_woocommerce();
		if ( $error ) {
			return $error;
		}

		$post_id   = absint( $input['post_id'] ?? 0 );
		$parent_id = sanitize_text_field( $input['parent_id'] ?? '' );
		$position  = intval( $input['position'] ?? -1 );

		return $this->insert_woo_widget( $post_id, $parent_id, 'woocommerce-cart', array(), $position );
	}

	/**
	 * Adds Checkout widget.
	 *
	 * @since 2.0.0
	 */
	public function execute_add_checkout( $input ) {
		$error = $this->verify_woocommerce();
		if ( $error ) {
			return $error;
		}

		$post_id   = absint( $input['post_id'] ?? 0 );
		$parent_id = sanitize_text_field( $input['parent_id'] ?? '' );
		$position  = intval( $input['position'] ?? -1 );

		return $this->insert_woo_widget( $post_id, $parent_id, 'woocommerce-checkout-page', array(), $position );
	}

	/**
	 * Adds My Account widget.
	 *
	 * @since 2.0.0
	 */
	public function execute_add_my_account( $input ) {
		$error = $this->verify_woocommerce();
		if ( $error ) {
			return $error;
		}

		$post_id   = absint( $input['post_id'] ?? 0 );
		$parent_id = sanitize_text_field( $input['parent_id'] ?? '' );
		$position  = intval( $input['position'] ?? -1 );

		return $this->insert_woo_widget( $post_id, $parent_id, 'woocommerce-my-account', array(), $position );
	}

	/**
	 * Adds Product Rating widget.
	 *
	 * @since 2.0.0
	 */
	public function execute_add_product_rating( $input ) {
		$error = $this->verify_woocommerce();
		if ( $error ) {
			return $error;
		}

		$post_id   = absint( $input['post_id'] ?? 0 );
		$parent_id = sanitize_text_field( $input['parent_id'] ?? '' );
		$position  = intval( $input['position'] ?? -1 );

		return $this->insert_woo_widget( $post_id, $parent_id, 'woocommerce-product-rating', array(), $position );
	}

	/**
	 * Helper to insert a WooCommerce widget into an Elementor post.
	 *
	 * @since 2.0.0
	 */
	private function insert_woo_widget( int $post_id, string $parent_id, string $widget_type, array $settings, int $position ) {
		if ( ! $post_id || empty( $parent_id ) ) {
			return new \WP_Error( 'missing_params', __( 'post_id and parent_id are required.', 'mindcrafts-ai' ) );
		}

		$page_data = $this->data->get_page_data( $post_id );
		if ( is_wp_error( $page_data ) ) {
			return $page_data;
		}

		$widget   = $this->factory->create_widget( $widget_type, $settings );
		$inserted = $this->data->insert_element( $page_data, $parent_id, $widget, $position );
		if ( ! $inserted ) {
			return new \WP_Error( 'parent_not_found', __( 'Parent container not found.', 'mindcrafts-ai' ) );
		}

		$result = $this->data->save_page_data( $post_id, $page_data );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success'    => true,
			'element_id' => $widget['id'],
		);
	}

	/**
	 * Creates a WooCommerce Single Product Theme Template.
	 *
	 * @since 2.0.0
	 */
	public function execute_create_single_product_template( $input ) {
		$error = $this->verify_woocommerce();
		if ( $error ) {
			return $error;
		}

		if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			return new \WP_Error(
				'elementor_pro_required',
				__( 'Elementor Pro is required to create WooCommerce single product theme templates.', 'mindcrafts-ai' )
			);
		}

		$title        = sanitize_text_field( $input['title'] ?? '' );
		$apply_to_all = ! empty( $input['apply_to_all'] );

		if ( empty( $title ) ) {
			return new \WP_Error( 'missing_title', __( 'Template title is required.', 'mindcrafts-ai' ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'elementor_library',
				'post_title'  => $title,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, '_elementor_template_type', 'product' );
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );

		// ================================================================
		// مرحلہ 4: جدید 2-Column ای کامرس سنگل پروڈکٹ لے آؤٹ
		// ================================================================

		// بائیں کالم: پروڈکٹ امیجز گیلری (50% چوڑائی)
		$left_column = $this->factory->create_container(
			array(
				'flex_direction'        => 'column',
				'width'                 => array( 'size' => 50, 'unit' => '%' ),
				'width_tablet'          => array( 'size' => 100, 'unit' => '%' ),
				'width_mobile'          => array( 'size' => 100, 'unit' => '%' ),
				'padding'               => array( 'unit' => 'px', 'top' => '0', 'right' => '20', 'bottom' => '0', 'left' => '0', 'isLinked' => false ),
				'padding_mobile'        => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '15', 'left' => '0', 'isLinked' => false ),
				'content_width'         => 'full',
			),
			array(
				$this->factory->create_widget( 'woocommerce-product-images', array() ),
			)
		);
		$left_column['isInner'] = true;

		// دائیں کالم: تمام خریداری سے متعلق معلومات (50% چوڑائی)
		$right_column = $this->factory->create_container(
			array(
				'flex_direction'  => 'column',
				'align_items'     => 'flex-start',
				'width'           => array( 'size' => 50, 'unit' => '%' ),
				'width_tablet'    => array( 'size' => 100, 'unit' => '%' ),
				'width_mobile'    => array( 'size' => 100, 'unit' => '%' ),
				'padding'         => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '30', 'isLinked' => false ),
				'padding_mobile'  => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => false ),
				'content_width'   => 'full',
				'gap'             => array( 'size' => 15, 'unit' => 'px' ),
			),
			array(
				$this->factory->create_widget( 'woocommerce-breadcrumb', array() ),
				$this->factory->create_widget( 'woocommerce-product-title', array() ),
				$this->factory->create_widget( 'woocommerce-product-rating', array() ),
				$this->factory->create_widget( 'woocommerce-product-price', array() ),
				$this->factory->create_widget( 'woocommerce-product-short-description', array() ),
				$this->factory->create_widget( 'woocommerce-product-add-to-cart', array() ),
				$this->factory->create_widget( 'woocommerce-product-meta', array() ),
			)
		);
		$right_column['isInner'] = true;

		// مرکزی رو کنٹینر: ڈیسک ٹاپ پر row، موبائل پر خودکار column (Element Factory)
		$main_row = $this->factory->create_container(
			array(
				'flex_direction'   => 'row',
				'align_items'      => 'flex-start',
				'padding'          => array( 'unit' => 'px', 'top' => '40', 'right' => '20', 'bottom' => '40', 'left' => '20', 'isLinked' => false ),
				'content_width'    => 'boxed',
			),
			array( $left_column, $right_column )
		);

		// نیچے فل وڈتھ: پروڈکٹ ٹیبز اور ریلیٹڈ پروڈکٹس
		$bottom_section = $this->factory->create_container(
			array(
				'flex_direction' => 'column',
				'padding'        => array( 'unit' => 'px', 'top' => '40', 'right' => '20', 'bottom' => '60', 'left' => '20', 'isLinked' => false ),
				'content_width'  => 'boxed',
			),
			array(
				$this->factory->create_widget( 'woocommerce-product-data-tabs', array() ),
				$this->factory->create_widget( 'woocommerce-product-related', array( 'posts_per_page' => 4, 'columns' => 4 ) ),
			)
		);

		$structure = array( $main_row, $bottom_section );

		$saved = $this->data->save_page_data( $post_id, $structure );
		if ( is_wp_error( $saved ) ) {
			wp_delete_post( $post_id, true );
			return $saved;
		}

		if ( $apply_to_all ) {
			update_post_meta( $post_id, '_elementor_conditions', array( 'include/singular/product' ) );
		}

		return array(
			'success'     => true,
			'template_id' => $post_id,
			'edit_url'    => admin_url( 'post.php?post=' . $post_id . '&action=elementor' ),
		);
	}


	/**
	 * Creates a WooCommerce Product Archive Theme Template.
	 *
	 * @since 2.0.0
	 */
	public function execute_create_archive_template( $input ) {
		$error = $this->verify_woocommerce();
		if ( $error ) {
			return $error;
		}

		if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			return new \WP_Error(
				'elementor_pro_required',
				__( 'Elementor Pro is required to create WooCommerce archive theme templates.', 'mindcrafts-ai' )
			);
		}

		$title        = sanitize_text_field( $input['title'] ?? '' );
		$apply_to_all = ! empty( $input['apply_to_all'] );

		if ( empty( $title ) ) {
			return new \WP_Error( 'missing_title', __( 'Template title is required.', 'mindcrafts-ai' ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'elementor_library',
				'post_title'  => $title,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, '_elementor_template_type', 'product-archive' );
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );

		// Pre-populate with archive widgets.
		$container = $this->factory->create_container(
			array(
				'flex_direction' => 'column',
				'padding'        => array( 'unit' => 'px', 'top' => '40', 'right' => '20', 'bottom' => '40', 'left' => '20' ),
			)
		);

		$widgets = array(
			$this->factory->create_widget( 'woocommerce-breadcrumb', array() ),
			$this->factory->create_widget( 'woocommerce-archive-description', array() ),
			$this->factory->create_widget( 'woocommerce-archive-products', array( 'columns' => 4, 'rows' => 3 ) ),
		);

		$container['elements'] = $widgets;
		$structure             = array( $container );

		$this->data->save_page_data( $post_id, $structure );

		if ( $apply_to_all ) {
			update_post_meta( $post_id, '_elementor_conditions', array( 'include/archive/product_archive' ) );
		}

		return array(
			'success'     => true,
			'template_id' => $post_id,
			'edit_url'    => admin_url( 'post.php?post=' . $post_id . '&action=elementor' ),
		);
	}

	/**
	 * ڈیمو WooCommerce پروڈکٹس بناتا ہے — خالی سٹورز کو فوری آباد کرتا ہے۔
	 *
	 * @since 2.3.0
	 *
	 * @param array $input ان پٹ پیرامیٹرز۔
	 * @return array|\WP_Error
	 */
	public function execute_seed_demo_products( $input ) {
		$error = $this->verify_woocommerce();
		if ( $error ) {
			return $error;
		}

		if ( ! class_exists( 'WC_Product_Simple' ) ) {
			return new \WP_Error(
				'wc_not_ready',
				__( 'WooCommerce product classes are not available.', 'mindcrafts-ai' )
			);
		}

		$count         = min( 12, max( 1, absint( $input['count'] ?? 4 ) ) );
		$category_name = sanitize_text_field( $input['category'] ?? __( 'Demo Products', 'mindcrafts-ai' ) );

		// کیٹیگری بنائیں یا ڈھونڈیں۔
		$term = get_term_by( 'name', $category_name, 'product_cat' );
		if ( ! $term ) {
			$result = wp_insert_term( $category_name, 'product_cat' );
			$cat_id = is_wp_error( $result ) ? 0 : $result['term_id'];
		} else {
			$cat_id = $term->term_id;
		}

		// نمونہ پروڈکٹس کا ڈیٹا۔
		$demo_data = array(
			array(
				'name'  => __( 'Premium Wireless Headphones', 'mindcrafts-ai' ),
				'price' => '89.99',
				'sale'  => '69.99',
				'sku'   => 'DEMO-HP-001',
			),
			array(
				'name'  => __( 'Smart Fitness Tracker', 'mindcrafts-ai' ),
				'price' => '129.99',
				'sale'  => '',
				'sku'   => 'DEMO-FT-002',
			),
			array(
				'name'  => __( 'Professional Camera Bag', 'mindcrafts-ai' ),
				'price' => '49.99',
				'sale'  => '39.99',
				'sku'   => 'DEMO-CB-003',
			),
			array(
				'name'  => __( 'Ergonomic Office Chair', 'mindcrafts-ai' ),
				'price' => '299.99',
				'sale'  => '',
				'sku'   => 'DEMO-OC-004',
			),
			array(
				'name'  => __( 'Portable Bluetooth Speaker', 'mindcrafts-ai' ),
				'price' => '59.99',
				'sale'  => '49.99',
				'sku'   => 'DEMO-BS-005',
			),
			array(
				'name'  => __( 'Mechanical Gaming Keyboard', 'mindcrafts-ai' ),
				'price' => '149.99',
				'sale'  => '',
				'sku'   => 'DEMO-GK-006',
			),
			array(
				'name'  => __( 'Minimalist Leather Wallet', 'mindcrafts-ai' ),
				'price' => '34.99',
				'sale'  => '24.99',
				'sku'   => 'DEMO-LW-007',
			),
			array(
				'name'  => __( 'Stainless Steel Water Bottle', 'mindcrafts-ai' ),
				'price' => '24.99',
				'sale'  => '',
				'sku'   => 'DEMO-WB-008',
			),
			array(
				'name'  => __( 'Yoga Mat Premium', 'mindcrafts-ai' ),
				'price' => '44.99',
				'sale'  => '34.99',
				'sku'   => 'DEMO-YM-009',
			),
			array(
				'name'  => __( 'Wireless Charging Pad', 'mindcrafts-ai' ),
				'price' => '39.99',
				'sale'  => '',
				'sku'   => 'DEMO-CP-010',
			),
			array(
				'name'  => __( 'UV Protection Sunglasses', 'mindcrafts-ai' ),
				'price' => '79.99',
				'sale'  => '59.99',
				'sku'   => 'DEMO-SG-011',
			),
			array(
				'name'  => __( 'Digital Drawing Tablet', 'mindcrafts-ai' ),
				'price' => '199.99',
				'sale'  => '169.99',
				'sku'   => 'DEMO-DT-012',
			),
		);

		$created     = 0;
		$skipped     = 0;
		$product_ids = array();

		for ( $i = 0; $i < $count && $i < count( $demo_data ); $i++ ) {
			$item = $demo_data[ $i ];

			// پہلے سے اسی SKU کا پروڈکٹ نہیں ہونا چاہیے۔
			$existing = wc_get_product_id_by_sku( $item['sku'] );
			if ( $existing ) {
				$skipped++;
				$product_ids[] = $existing;
				continue;
			}

			$product = new \WC_Product_Simple();
			$product->set_name( $item['name'] );
			$product->set_regular_price( $item['price'] );
			$product->set_sku( $item['sku'] );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			$product->set_manage_stock( false );
			$product->set_stock_status( 'instock' );
			$product->set_short_description(
				sprintf(
					/* translators: %s: product name */
					__( 'This is a demo product for design purposes. Replace with your actual %s description.', 'mindcrafts-ai' ),
					$item['name']
				)
			);

			if ( ! empty( $item['sale'] ) ) {
				$product->set_sale_price( $item['sale'] );
			}

			if ( $cat_id ) {
				$product->set_category_ids( array( $cat_id ) );
			}

			$product_id = $product->save();

			if ( $product_id ) {
				// پلیس ہولڈر تصویر لگائیں — Openverse سے ڈاؤنلوڈ۔
				if ( class_exists( 'MindCrafts_AI_Media_Resolver' ) ) {
					$resolver    = new MindCrafts_AI_Media_Resolver();
					$image_result = $resolver->resolve_image( $item['name'], 'product' );
					if ( ! empty( $image_result['id'] ) ) {
						set_post_thumbnail( $product_id, $image_result['id'] );
					}
				}

				$product_ids[] = $product_id;
				$created++;
			}
		}

		return array(
			'success'     => true,
			'created'     => $created,
			'skipped'     => $skipped,
			'product_ids' => $product_ids,
		);
	}
}
