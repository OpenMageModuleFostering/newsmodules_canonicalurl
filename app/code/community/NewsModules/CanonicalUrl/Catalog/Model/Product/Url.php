<?php
if (version_compare(Mage::getVersion(), '1.8', '>='))
{
    //version is 1.8 or greater
	/***/
	class NewsModules_CanonicalUrl_Catalog_Model_Product_Url extends Mage_Catalog_Model_Product_Url
	{
		/**
		 * Check product category
		 *
		 * @param Mage_Catalog_Model_Product $product
		 * @param array $params
		 *
		 * @return int|null
		 */
		protected function _getCategoryIdForUrl($product, $params)
		{
			if (isset($params['_ignore_category'])) {
				return null;
			} else {
				$category_ids = $product->getCategoryIds();
				foreach ($product->getCategoryIds() as $catalog_id)
				{
					$category = Mage::getModel('catalog/category')->load($catalog_id);
					$parent_ids = $category->getParentIds();
					$category_ids = array_diff($category_ids, $parent_ids);
				}
				return array_shift($category_ids);
				
				/*
				return $product->getCategoryId() && !$product->getDoNotUseCategoryId()
					? $product->getCategoryId() : null;
				*/
			}
		}
	}
	/***/
} 
else
{
	/***/
	class NewsModules_CanonicalUrl_Catalog_Model_Product_Url extends Mage_Catalog_Model_Product_Url
	{
		/**
		 * Retrieve Product URL using UrlDataObject
		 *
		 * @param Mage_Catalog_Model_Product $product
		 * @param array $params
		 * @return string
		 */
		public function getUrl(Mage_Catalog_Model_Product $product, $params = array())
		{
			$routePath      = '';
			$routeParams    = $params;
	
			$storeId    = $product->getStoreId();
			if (isset($params['_ignore_category'])) {
				unset($params['_ignore_category']);
				$categoryId = null;
			} else {
				/*
				$categoryId = $product->getCategoryId() && !$product->getDoNotUseCategoryId()
					? $product->getCategoryId() : null;
				*/
				$category_ids = $product->getCategoryIds();
				foreach ($product->getCategoryIds() as $catalog_id)
				{
					$category = Mage::getModel('catalog/category')->load($catalog_id);
					$parent_ids = $category->getParentIds();
					$category_ids = array_diff($category_ids, $parent_ids);
				}
				$categoryId = array_shift($category_ids);
			}
	
			if ($product->hasUrlDataObject()) {
				$requestPath = $product->getUrlDataObject()->getUrlRewrite();
				$routeParams['_store'] = $product->getUrlDataObject()->getStoreId();
			} else {
				$requestPath = $product->getRequestPath();
				if (empty($requestPath) && $requestPath !== false) {
					$idPath = sprintf('product/%d', $product->getEntityId());
					if ($categoryId) {
						$idPath = sprintf('%s/%d', $idPath, $categoryId);
					}
					$rewrite = $this->getUrlRewrite();
					$rewrite->setStoreId($storeId)
						->loadByIdPath($idPath);
					if ($rewrite->getId()) {
						$requestPath = $rewrite->getRequestPath();
						$product->setRequestPath($requestPath);
					} else {
						$product->setRequestPath(false);
					}
				}
			}
	
			if (isset($routeParams['_store'])) {
				$storeId = Mage::app()->getStore($routeParams['_store'])->getId();
			}
	
			if ($storeId != Mage::app()->getStore()->getId()) {
				$routeParams['_store_to_url'] = true;
			}
	
			if (!empty($requestPath)) {
				$routeParams['_direct'] = $requestPath;
			} else {
				$routePath = 'catalog/product/view';
				$routeParams['id']  = $product->getId();
				$routeParams['s']   = $product->getUrlKey();
				if ($categoryId) {
					$routeParams['category'] = $categoryId;
				}
			}
	
			// reset cached URL instance GET query params
			if (!isset($routeParams['_query'])) {
				$routeParams['_query'] = array();
			}
	
			return $this->getUrlInstance()->setStore($storeId)
				->getUrl($routePath, $routeParams);
		}
	}
	/***/
}
