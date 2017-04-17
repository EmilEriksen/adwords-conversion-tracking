<?php
/**
 * Integration.
 */

namespace ACT;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integration.
 */
class Integration extends \WC_Integration {
    /**
     * Integration ID.
     *
     * @var string
     */
    public $id;

    /**
	 * Integration title.
     *
	 * @var string
	 */
	public $method_title;

	/**
	 * Integration description.
     *
	 * @var string
	 */
	public $method_description;

    /**
     * The settings fields.
     *
     * @var array
     */
    public $form_fields;

    /**
     * The AdWords conversion label.
     *
     * @var string
     */
    private $conversion_label;

    /**
     * The AdWords conversion ID.
     *
     * @var string
     */
    private $conversion_id;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id                 = 'act';
        $this->method_title       = __( 'AdWords Conversion Tracking', 'act' );
        $this->method_description = __( 'Add the AdWords conversion tracking code to the thank you page.', 'act' );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        $this->conversion_label = $this->get_option( 'conversion_label' );
        $this->conversion_id    = $this->get_option( 'conversion_id' );

        add_action( 'woocommerce_update_options_integration_act', array( $this, 'process_admin_options' ) );

        add_action( 'wp', array( $this, 'maybe_output_tracking_code' ) );
    }

    /**
     * Initialise Settings Form Fields
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable', 'act' ),
                'type'  => 'checkbox',
            ),
            'conversion_label' => array(
                'title'       => __( 'Conversion Label', 'act' ),
                'description' => __( 'The conversion label found on the conversion tag page.', 'act' ),
                'type'        => 'text',
            ),
            'conversion_id' => array(
                'title'       => __( 'Conversion ID', 'act' ),
                'description' => __( 'The conversion ID found on the conversion tag page.', 'act' ),
                'type'        => 'text',
            ),
        );
    }

    /**
     * Output the tracking code.
     */
    public function output_tracking_code() {
        global $wp;

        $order_id = 0;
        $order = null;

        // This will should really always be set since it's checked in is_order_received_page().
        if ( ! isset( $wp->query_vars['order-received'] ) ) {
            return;
        }

        $order_id  = apply_filters( 'woocommerce_thankyou_order_id', absint( $wp->query_vars['order-received'] ) );
        $order_key = apply_filters( 'woocommerce_thankyou_order_key', empty( $_GET['key'] ) ? '' : wc_clean( $_GET['key'] ) );

        if ( ! $order_id > 0 ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order || ( version_compare( WC_VERSION, '2.7', '>=' ) ? $order->get_order_key() : $order->order_key ) !== $order_key ) {
            return;
        }

        $locale = get_locale();
        $total = $order->get_total();

        if ( version_compare( WC_VERSION, '2.7', '>=' ) ) {
            $currency = $order->get_currency();
        } else {
            $currency = $order->get_order_currency();
        }

        // @codingStandardsIgnoreStart
        ?>
        <script type="text/javascript">
        /* <![CDATA[ */
        var google_conversion_id = <?php echo esc_js( absint( $this->conversion_id ) ); ?>;
        var google_conversion_language = "<?php echo esc_js( $locale ); ?>";
        var google_conversion_format = "3";
        var google_conversion_color = "ffffff";
        var google_conversion_label = "<?php echo esc_js( $this->conversion_label ); ?>";
        <?php if ( $order_id > 0 ) : ?>
            var google_conversion_order_id = "<?php echo esc_js( $order_id ); ?>";
        <?php endif; ?>
        var google_conversion_value = <?php echo esc_js( $total ); ?>;
        var google_conversion_currency = "<?php echo esc_js( $currency ); ?>";
        var google_remarketing_only = false;
        /* ]]> */
        </script>
        <script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
        </script>
        <noscript>
        <div style="display:inline;">
        <img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/<?php echo esc_js( absint( $this->conversion_id ) ); ?>/?value=<?php echo esc_js( $total ); ?>&amp;currency_code=<?php echo esc_js( $currency ); ?>&amp;label=<?php echo esc_js( $this->conversion_label ); ?><?php if ( $order_id > 0 ) echo '&amp;oid=' . esc_js( $order_id ); ?>&amp;guid=ON&amp;script=0"/>
        </div>
        </noscript>
        <?php
        // @codingStandardsIgnoreEnd
    }

    /**
     * Validate that we have everything we need to track.
     */
    private function validate_settings() {
        return 'yes' === $this->enabled && $this->conversion_label && $this->conversion_id;
    }

    /**
     * Add tracking code if on order received page.
     */
    public function maybe_output_tracking_code() {
        if ( is_order_received_page() && $this->validate_settings() ) {
            add_action( 'body_open', array( $this, 'output_tracking_code' ) );
        }
    }
}
