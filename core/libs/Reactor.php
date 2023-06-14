<?php

namespace boctulus\SW\core\libs;

/*
   	@author Pablo Bozzolo (2020-2022)
*/

abstract class Reactor
{
	protected $action;

	function __construct()
	{
		add_action('woocommerce_update_product', [$this, 'sync_on_product_update'], 10, 2 );
		add_action('added_post_meta', [$this, 'sync_on_new_post_data'], 10, 4 );
		add_action('untrash_post', [$this, 'sync_on_untrash_post'], 10, 1);
	}	

    /*
		Event Hooks
	*/
	
	protected function __onCreate($pid, $sku, $product){		
		if (!empty(get_transient('product-'. $pid))){
			return;
		}
		
		set_transient('product-'. $pid, true, 2);

		if (is_admin()){
			$sku = $_POST['_sku'] ?? null;
			if ($sku === ''){
				if (session_status() === PHP_SESSION_NONE) {
					session_start();
				}

				$_SESSION['reactor-notice'] = 'SKU ausente';
			} else {
				$_SESSION['reactor-notice'] = '';
			}
		}

        $this->onCreate($pid, $sku, $product);
	}

	protected function __onUpdate($pid, $sku, $product)
	{
		if (!empty(get_transient('product-'. $pid))){
			return;
		}

		set_transient('product-'. $pid, true, 2);	

        $this->onUpdate($pid, $sku, $product);
	}

	protected function __onDelete($pid, $sku, $product)
	{
        $this->onDelete($pid, $sku, $product);
	}

	protected function __onRestore($pid, $sku, $product)
	{	
        $this->onRestore($pid, $sku, $product);
	}


	abstract function onCreate ($pid, $sku, $product);
	abstract function onUpdate ($pid, $sku, $product);
	abstract function onDelete ($pid, $sku, $product);
	abstract function onRestore($pid, $sku, $product);


	function sync_on_product_update($pid, $product) {
		$this->action = 'edit';

		$product = wc_get_product($pid);
		$sku     = $product->get_sku();
		
		$this->__onUpdate($pid, $sku, $product);
	}

	function sync_on_untrash_post($pid){
		if (get_post_type($pid) != 'product'){
			return;
		}

		$this->action = 'untrash';
		
		$product = wc_get_product($pid);
		$sku     = $product->get_sku();

		$this->__onRestore($pid, $sku, $product);
	}

	function sync_on_new_post_data($meta_id, $post_id, $meta_key, $meta_value) {  
		if (get_post_type($post_id) == 'product') 
		{ 
			// si ya lo cogió el otro hook
			if ($this->action == 'edit'){
				return;
			}

			//  draft y otros no me interesan
			if ($meta_value != 'publish'){
				return;
			}

			$product = wc_get_product( $post_id );
			$sku     = $product->get_sku();

			switch ($meta_key){
				case '_wp_trash_meta_status': 
					$this->action = 'trash';
					$this->__onDelete($post_id, $sku, $product);
					break;
				case '_wp_old_slug':
					$this->action = 'restore';
					$this->__onRestore($post_id, $sku, $product);
					break;
				case '_stock':
					$this->action = 'edit';
					$this->__onUpdate($post_id, $sku, $product);
					break;
				// creación
				default:
					$this->action = 'create';
					$this->__onCreate($post_id, $sku, $product);
			}
		}
	
	}
}