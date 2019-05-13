<?php
class ControllerExtensionPaymentPaykeeper extends Controller {

    private $fiscal_cart = array(); //fz54 cart
    private $order_total = 0; //order total sum
    private $shipping_price = 0; //shipping price
    private $use_delivery = false; //is delivery using or not
    private $order_params = NULL; //order parameters

	public function index() {
		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['current_host'] = $_SERVER['HTTP_HOST'];

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$payment_parameters = http_build_query(array(
			"orderid"=>$this->session->data['order_id'],
			"clientid"=>$order_info['email'],
			"sum"=>$this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false),
			"phone"=>$order_info['telephone']
		));

		$data['server'] = $this->config->get('paykeeperserver');
		$data['payment_parameters'] = $payment_parameters;
		
        return $this->load->view('/extension/payment/paykeeper', $data);
	}
	public function callback() {

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model('checkout/order');

			
			if(isset($this->request->post['id']) && isset($this->request->post['orderid'])){
				$secret_seed = $this->config->get('paykeepersecret');
				$id = $this->request->post['id'];
				$sum = $this->request->post['sum'];
				$clientid = $this->request->post['clientid'];
				$orderid = $this->request->post['orderid'];
				$key = $this->request->post['key'];

				if ($key != md5 ($id . sprintf ("%.2lf", $sum).$clientid.$orderid.$secret_seed))
				{
					echo "Error! Hash mismatch";
					exit;
				}
				
				$order_info = $this->model_checkout_order->getOrder($orderid);
				
				if ($orderid == "")
				{
					
				}
				else
				{
					$this->model_checkout_order->addOrderHistory($orderid, $this->config->get('paykeeper_order_status_id'));
				}
				echo "OK ".md5($id.$secret_seed);
			}
			else echo "OK";
		}
	}
	
	public function gopay() {

		$this->load->language('checkout/checkout');
		$this->load->language('extension/payment/paykeeper');
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_cart'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('checkout/checkout', '', 'SSL')
		);
		
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('paykeeper_title'),
			'href' => $this->url->link('extension/payment/paykeeper/gopay', '', 'SSL')
		);

        $data['heading_title'] = $this->language->get('paykeeper_title');
		$this->document->setTitle($data['heading_title']);
		
		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        
		$data['server'] = $this->config->get('payment_paykeeperserver');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

        $data['sum'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
        $data['orderid'] = $this->session->data['order_id'];
        $data['clientid'] = $this->session->data['payment_address']['firstname'] . " " . $this->session->data['payment_address']['lastname'];
        $data['client_email'] = $order_info['email'];
        $data['client_phone'] = $order_info['telephone'];
        $data['service_name'] = "";
		$data['secret'] = $this->config->get('payment_paykeepersecret');

        $this->setOrderParams($data["sum"],             //sum
                              $data["clientid"],        //clientid
                              $data["orderid"],         //orderid
                              $data["client_email"],    //client_email
                              $data["client_phone"],    //client_phone
                              $data["service_name"],    //service_name
                              $data["server"],          //payment form url
                              $data["secret"]           //secret key
        );

        $data["payment_form_type"] = ($this->getPaymentFormType() == "create") ? "create" : "order";

        //GENERATE FZ54 CART
        $product_cart_sum = 0;

        $cart_data = $this->cart->getProducts();

        foreach ($cart_data as $product) {
            $sum = 0;
            $tax_rate = 0;
            $taxes = array("tax" => "none", "tax_sum" => 0);

            $name = $product["name"];
            $qty = $product['quantity'];
            if ( (int) $product["tax_class_id"] != 0) {
                $tax_rate = $this->getRate($sum, $product["tax_class_id"]);
            }
            $price = $product["price"];
            $sum = number_format($price*$qty, 2, ".", "");
            $price = number_format($price, 2, ".", "");
            $taxes = $this->setTaxes($sum, $tax_rate);
            $product_cart_sum += $sum;

            $this->updateFiscalCart($this->getPaymentFormType(),
                                    $name, $price, $qty, $sum, $taxes["tax"], $taxes["tax_sum"]);
        }

        //add shipping service
        $shipping_tax_rate = 0;
        $shipping_taxes = array("tax" => "none", "tax_sum" => 0);

        if (array_key_exists('shipping_method', $this->session->data)) {
            $shipping_tax_rate = $this->getRate($this->getShippingPrice(),
                                                $this->session->data["shipping_method"]["tax_class_id"]
            );
            $this->setShippingPrice($this->session->data['shipping_method']['cost']);
            $shipping_name = $this->session->data['shipping_method']['title'];
            $shipping_taxes = $this->setTaxes($this->getShippingPrice(),
                                              $shipping_tax_rate);
            if ($this->getShippingPrice() > 0) {
                $this->setUseDelivery(); //for precision correct check
                $this->updateFiscalCart($this->getPaymentFormType(),
                                        $shipping_name,
                                        $this->getShippingPrice(),
                                        1,
                                        $this->getShippingPrice(),
                                        $shipping_taxes["tax"],
                                        $shipping_taxes["tax_sum"]);
            }
        }

        //set discounts
        $cart_sum = $this->setDiscounts($product_cart_sum);

        //handle possible precision problem
        $this->correctPrecision($product_cart_sum);

        $data["sum"] = $this->getOrderTotal(True);

        $data['cart'] = json_encode($this->getFiscalCart());

        $data["order_form"] = NULL;

        if ($this->getPaymentFormType() == "create") {
            $to_hash = $data['sum']          .
                       $data['clientid']     . 
                       $data['orderid']      .   
                       $data['service_name'] .   
                       $data['client_email'] .   
                       $data['client_phone'] .   
                       $data['secret'];
            $data['sign'] = hash ('sha256' , $to_hash);
        } else {
            //add order record to order list
            $this->confirm();

            $query_options = array("clientid"=>     $data['clientid'],
                                   "orderid"=>      $data['orderid'],
                                   "sum"=>          $data['sum'],
                                   "phone"=>        $data['client_phone'],
                                   "client_email"=> $data['client_email'],
                                   "cart"=>         $data['cart']);
            $payment_parameters = http_build_query($query_options);
            $options = array("http"=>array(
                             "method"=>"POST",
                             "header"=>"Content-type: application/x-www-form-urlencoded",
                             "content"=>$payment_parameters
                        ));
            $context = stream_context_create($options);

            //using curl
            if( function_exists( "curl_init" )) {
                $CR = curl_init();
                curl_setopt($CR, CURLOPT_URL, $data["server"]);
                curl_setopt($CR, CURLOPT_POST, 1);
                curl_setopt($CR, CURLOPT_FAILONERROR, true); 
                curl_setopt($CR, CURLOPT_POSTFIELDS, $payment_parameters);
                curl_setopt($CR, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($CR, CURLOPT_SSL_VERIFYPEER, 0);
                $result = curl_exec( $CR );
                $error = curl_error( $CR );                                                  
                if( !empty( $error )) {
                    $form = "<br/><span class=message>"."INTERNAL ERROR:".$error."</span>";
                    return false;
                }
                else {
                    $data["order_form"] = $result;
                }
                curl_close($CR);
            } else {
                //using file_get_contents
                if (!ini_get('allow_url_fopen')) {
                    $data["order_form"] = "<br/><span class=message>"."INTERNAL ERROR: Option allow_url_fopen is not set in php.ini"."</span>";
                }
                else {
                    $data["order_form"] = file_get_contents($data["server"], false, $context);
                }
            }
        }

        $this->response->setOutput($this->load->view('extension/payment/paykeeper_iframe', $data));

	}
	public function success() {
		
		$this->load->language('checkout/checkout');
		$this->load->language('extension/payment/paykeeper');

        //clear cart
        $this->cart->clear();
		
		$data['heading_title'] = $this->language->get('paykeeper_title');
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_cart'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('checkout/checkout', '', 'SSL')
		);
		
		$data['message'] = $this->language->get('paykeeper_success');

		$this->document->setTitle($data['message']);
		
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		
        $this->response->setOutput($this->load->view('extension/payment/paykeeper_feedback', $data));
	}
	public function failed() {
		
		$this->load->language('checkout/checkout');
		$this->load->language('extension/payment/paykeeper');
		
		$data['heading_title'] = $this->language->get('paykeeper_title');
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_cart'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('checkout/checkout', '', 'SSL')
		);
		
		$data['message'] = $this->language->get('paykeeper_failed');

		$this->document->setTitle($data['message']);
		
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		
        $this->response->setOutput($this->load->view('extension/payment/paykeeper_feedback', $data));
	}

    public function confirm() {
            $this->load->model('checkout/order');
            $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1);
        }
	
    protected function setOrderParams($order_total = 0, $clientid="", $orderid="", $client_email="", 
                                    $client_phone="", $service_name="", $form_url="", $secret_key="")
    {
       $this->setOrderTotal($order_total);
       $this->order_params = array(
           "sum" => $order_total,
           "clientid" => $clientid,
           "orderid" => $orderid,
           "client_email" => $client_email,
           "client_phone" => $client_phone,
           "phone" => $client_phone,
           "service_name" => $service_name,
           "form_url" => $form_url,
           "secret_key" => $secret_key,
       );
    }

    protected function getOrderParams($value)
    {
        return array_key_exists($value, $this->order_params) ? $this->order_params["$value"] : False;
    }

    protected function updateFiscalCart($ftype, $name="", $price=0, $quantity=0, $sum=0, $tax="none", $tax_sum=0)
    {
        //update fz54 cart
        if ($ftype == "create") {
            $name = str_replace("\n ", "", $name);
            $name = str_replace("\r ", "", $name);
            $name = preg_replace('/"{1,}/','\'',$name);
            $name = htmlspecialchars($name);
        }
        $this->fiscal_cart[] = array(
            "name" => $name,
            "price" => $price,
            "quantity" => $quantity,
            "sum" => $sum,
            "tax" => $tax,
            "tax_sum" => number_format($tax_sum, 2, ".", "")
        );
    }

    protected function getFiscalCart()
    {
        return $this->fiscal_cart;
    }

    protected function setDiscounts($cart_sum)
    {
        //set discounts
        if (array_key_exists("coupon", $this->session->data)) {
            $discount_modifier_value = ($this->getOrderTotal() - $this->getShippingPrice())/$cart_sum;

            if ($discount_modifier_value < 1) {
                $fiscal_cart_count = ($this->getShippingPrice() != 0) ? count($this->getFiscalCart())-1 : count($this->getFiscalCart());
                //iterate fiscal cart without shipping
                for ($pos=0; $pos<$fiscal_cart_count; $pos++) {
                    $this->fiscal_cart[$pos]["sum"] *= $discount_modifier_value;
                    $this->fiscal_cart[$pos]["price"] = $this->fiscal_cart[$pos]["sum"]/$this->fiscal_cart[$pos]["quantity"];
                    //formatting
                    $this->fiscal_cart[$pos]["price"] = number_format($this->fiscal_cart[$pos]["price"], 3, ".", "");
                    $this->fiscal_cart[$pos]["sum"] = number_format($this->fiscal_cart[$pos]["sum"], 2, ".", "");
                    //recalculate taxes
                    $this->recalculateTaxes($pos);
                }
            }
        }
    }

    protected function correctPrecision($fiscal_cart_sum)
    {
        //handle possible precision problem
        $total_sum = $this->getOrderTotal(True);
        //add shipping sum to cart sum
        if ($this->getShippingPrice() > 0)
            $fiscal_cart_sum += $this->fiscal_cart[count($this->fiscal_cart)-1]['sum'];
        //debug_info
        //echo "total: " . $total_sum . " - cart: " . $cart_sum;
        $diff_sum = $fiscal_cart_sum - $total_sum;
        if (abs($diff_sum) <= 0.01) {
            $this->setOrderTotal(number_format($total_sum+$diff_sum, 2, ".", ""));
        }
        else {
            if ($this->getUseDelivery() && ($fiscal_cart_sum < $total_sum)) {
                $this->setOrderTotal(number_format($total_sum+$diff_sum, 2, ".", ""));
                $delivery_pos = count($this->getFiscalCart())-1;
                $this->fiscal_cart[$delivery_pos]["price"] = number_format(
                                   $this->fiscal_cart[$delivery_pos]["price"]+$diff_sum, 2, ".", "");
                $this->fiscal_cart[$delivery_pos]["sum"] = number_format(
                                   $this->fiscal_cart[$delivery_pos]["sum"]+$diff_sum, 2, ".", "");
                $this->recalculateTaxes($delivery_pos);
            }
        }
    }

    protected function setOrderTotal($value)
    {
        $this->order_total = $value;
    }

    protected function getOrderTotal($format=False)
    {
        return ($format) ? number_format($this->order_total, 2, ".", "") : 
                                         $this->order_total;
    }

    protected function setShippingPrice($value)
    {
        $this->shipping_price = $value;
    }

    protected function getShippingPrice()
    {
        return $this->shipping_price;
    }

    protected function getPaymentFormType()
    {
        if (strpos($this->order_params["form_url"], "/order/inline") == True)
            return "order";
        else
            return "create";
    }

    protected function setUseDelivery()
    {
        $this->use_delivery = True;
    }

    protected function getUseDelivery()
    {
        return $this->use_delivery;
    }

    protected function recalculateTaxes($item_pos)
    {
        //recalculate taxes
        switch($this->fiscal_cart[$item_pos]['tax']) {
            case "vat10":
                $this->fiscal_cart[$item_pos]['tax_sum'] = round((float)
                    (($this->fiscal_cart[$item_pos]['sum']/110)*10), 2);
                break;
            case "vat18":
                $this->fiscal_cart[$item_pos]['tax_sum'] = round((float)
                    (($this->fiscal_cart[$item_pos]['sum']/118)*18), 2);
                break;
        }
    }

    protected function getRate($sum, $tax_class_id)
    {
        $tax_data = Null;
        foreach($this->tax->getRates($sum, $tax_class_id) as $td) {
            $tax_data = $td;
        }
        return ($tax_data) ? (int)$tax_data['rate'] : 0;

    }

    protected function setTaxes($sum, $tax_rate)
    {
        $taxes = array("tax" => "none", "tax_sum" => 0);
        switch(number_format($tax_rate, 0, ".", "")) {
            case 10:
                $taxes["tax"] = "vat10";
                $taxes["tax_sum"] = round((float)(($sum/110)*10), 2);
                break;
            case 18:
                $taxes["tax"] = "vat18";
                $taxes["tax_sum"] = round((float)(($sum/118)*18), 2);
                break;
        }
        return $taxes;
    }

    //add tax sum to item price
    protected function correctCartItemPrice($price, $tax_rate)
    {
        return ($tax_rate != 0) ? $price+($price/100*$tax_rate) : $price;
    }

    protected function showDebugInfo($obj_to_debug)
    {
        echo "<pre>";
        var_dump($obj_to_debug);
        echo "</pre>";
    }

}
