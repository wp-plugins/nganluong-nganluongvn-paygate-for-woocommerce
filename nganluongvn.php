<?php
/**
 * Plugin Name: Ngan Luong Paygate for Woocommerce
 * Plugin URI: http://sontq.net
 * Description: Add-on paygate NganLuong.VN for Woocommerce
 * Version: 0.1.2
 * Author: SonTQ
 * Author URI: http://sontq.net
 * License: GPL2
 */

/**
 * Plugin này xây dựng cơ bản theo URL http://docs.woothemes.com/document/payment-gateway-api/
 */
add_action ( 'plugins_loaded', 'woocommerce_NganLuongPaygate_init', 0 );

function woocommerce_NganLuongPaygate_init() {
	if (! class_exists ( 'WC_Payment_Gateway' ))
		return;
	class WC_NganLuongPaygate extends WC_Payment_Gateway {
		
		public function __construct() {
			$this->icon = 'https://www.nganluong.vn/images/logo-nl.png';
			$this->id = 'NganLuongPaygate';
			$this->medthod_title = 'Cổng thanh toán Ngân Lượng (NganLuong.vn)';
			$this->has_fields = false;
			
			$this->init_form_fields ();
			$this->init_settings ();
			
			$this->title = $this->settings ['title'];
			$this->description = $this->settings ['description'];
			$this->merchant_id = $this->settings ['merchant_id'];
			$this->nlcurrency = $this->settings ['nlcurrency'];
			//
			$this->redirect_page_id = $this->settings ['redirect_page_id'];
			$this->liveurl = 'https://www.nganluong.vn/advance_payment.php';
			
			$this->msg ['message'] = "";
			$this->msg ['class'] = "";
			
			$this->update_status_str = $this->settings ['update_status_str'];
			
			if (version_compare ( WOOCOMMERCE_VERSION, '2.2.10', '>=' )) {
				add_action ( 'woocommerce_update_options_payment_gateways_' . $this->id, array (
						&$this,
						'process_admin_options' 
				) );
			} else {
				add_action ( 'woocommerce_update_options_payment_gateways', array (
						&$this,
						'process_admin_options' 
				) );
			}
			add_action ( 'woocommerce_receipt_NganLuongPaygate', array (
					&$this,
					'receipt_page' 
			) );
			add_action ( 'woocommerce_thankyou', function ($order_id) {
				$order = new WC_Order ( $order_id );
				// check payment type is NganLuong.VN paygate
				if ('NganLuongPaygate' == $order->payment_method) {
					$order->update_status ( $this->settings ['update_status_str'], __('Đã thanh toán qua NganLuong.vn', 'nganluongpaygate') );
				}
			} );
		}
		function init_form_fields() {
			$this->form_fields = array (
					'enabled' => array (
							'title' => __ ( 'Bật / Tắt', 'nganluongpaygate' ),
							'type' => 'checkbox',
							'label' => __ ( 'Mở chức năng thanh toán qua Ngân Lượng', 'nganluongpaygate' ),
							'default' => 'no' 
					),
					'title' => array (
							'title' => __ ( 'Tên:', 'nganluongpaygate' ),
							'type' => 'text',
							'description' => __ ( 'Tên phương thức thanh toán (hiển thị khi khách hàng chọn phương thức thanh toán )', 'nganluongpaygate' ),
							'default' => __ ( 'Thanh toán qua Ngân Lượng (NganLuong.VN)', 'nganluongpaygate' ) 
					),
					'description' => array (
							'title' => __ ( 'Mô tả:', 'nganluongpaygate' ),
							'type' => 'textarea',
							'description' => __ ( 'Mô tả phương thức thanh toán  (hiển thị khi khách hàng chọn phương thức thanh toán). Có thể dùng HTML.', 'nganluongpaygate' ),
							'default' => __ ( '<img src="NganLuong.VN là ví điện tử chuyên ngành thương mại điện tử dùng để thanh toán trực tuyến an toàn.', 'nganluongpaygate' ) 
					),
					'merchant_id' => array (
							'title' => __ ( 'Tài khoản chủ shop', 'nganluongpaygate' ),
							'type' => 'text',
							'description' => __ ( 'Đây là tài khoản email NganLuong.vn để nhận tiền khi khách hàng thanh toán.' ) 
					),
					'nlcurrency' => array (
							'title' => __ ( 'Tiền tệ' ),
							'type' => 'select',
							'options' => array (
									'vnd' => 'VNĐ',
									'usd' => 'US Dollar'
							),
							'description' => 'Đơn vị tiền tệ khách hàng sẽ dùng khi thanh toán' 
					),
					'update_status_str' => array (
							'title' => __ ( 'Cập nhật trạng thái sau khi thanh toán' ),
							'type' => 'select',
							'options' => array (
									'completed' => 'Hoàn thành',
									'pending' => 'Đang chờ hoàn thành',
									'processing' => 'Đang xử lí',
									'on-hold' => 'Tạm giữ'
							),
							'description' => "Chọn trạng thái sau khi khách hàng thanh toán" 
					) 
			);
		}
		public function admin_options() {
			echo '<h3>' . __ ( 'Cổng thanh toán Ngân Lượng', 'nganluongpaygate' ) . '</h3>';
			echo '<p>Một trong những cổng thanh toán trực tuyến ở Việt Nam.<br /> <br /> 
					Chú ý: bản này vẫn là bản tích hợp đơn giản của Ngân Lượng. 
					Quản trị viên vẫn nên kiểm tra tài khoản ngân lượng trước khi chuyển hàng vì tính bảo mật của tích hợp đơn giản không cao. 
					<br />Ở bản premium hỗ trợ tích hợp nâng cao đảm bảo về tính xác thực và an toàn hơn. Liên hệ Email bên dưới nếu bạn muốn sử dụng.</p>';
			echo '<table class="form-table">';
			$this->generate_settings_html ();
			echo '</table>';
			
			echo '<p><i>Gửi email tới : <strong>sontq00787@gmail.com</strong> nếu bạn muốn phát triển tiếp plugin này</p></i>';
		}
		
		function payment_fields() {
			if ($this->description)
				echo wpautop ( wptexturize ( $this->description ) );
		}
		
		
		function receipt_page($order) {
			echo '<p>' . __ ( 'Đơn hàng của bạn đã được nhận. <br /><b>Nhấn nút Thanh toán bên dưới để tiến hành thanh toán qua Ngân Lượng', 'nganluongpaygate' ) . '</p>';
			echo $this->generate_NganLuongPaygate_form ( $order );
		}
		
		public function generate_NganLuongPaygate_form($order_id) {
			global $woocommerce;
			$order = new WC_Order ( $order_id );
			//--> Chuyển về trang thankyou để sử dụng chức năng cập nhật trạng thái 
			$redirect_url = $order->get_checkout_order_received_url ();
			//<--
			$productinfo = "Hóa đơn " . $order_id . " | " . $_SERVER ['SERVER_NAME'];
			
			$url = "https://www.nganluong.vn/button_payment.php?receiver=" . $this->merchant_id . "&price=" . $order->order_total . "&currency=" . $this->nlcurrency . "&product_name=" . $productinfo . "&return_url= " . $redirect_url;
			$nl = new NL_Checkout();
			$nl->runCheckoutUrl($url);
			
		}
		
		function process_payment($order_id) {
			$order = new WC_Order ( $order_id );
			
			return array (
					'result' => 'success',
					'redirect' => add_query_arg ( 'order', $order->id, add_query_arg ( 'key', $order->order_key, get_permalink ( woocommerce_get_page_id ( 'pay' ) ) ) ) 
			);
		}

	}
	function woocommerce_add_NganLuongPaygate_gateway($methods) {
		$methods [] = 'WC_NganLuongPaygate';
		return $methods;
	}
	
	add_filter ( 'woocommerce_payment_gateways', 'woocommerce_add_NganLuongPaygate_gateway' );
	
	class NL_Checkout
	{
		//Hàm xây dựng url
		public function runCheckoutUrl($return_url)
		{
			header("Location:".$return_url) ;
		}
	
	}
}

