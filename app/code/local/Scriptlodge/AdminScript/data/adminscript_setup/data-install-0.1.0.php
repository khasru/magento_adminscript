<?php
// https://github.com/khasru/magento_adminscript.git   

$installer = $this;
$installer->startSetup();

//$store = Mage::app()->getStore('store_code');
$store = Mage::app()->getStore('default');

// System Configuraton 
$conf = new Mage_Core_Model_Config();
$address = 'Address Text Here';
$conf->saveConfig('general/store_information/address', $address, 'default', 0);

// ===== CMS Static Blocks =====
$data = array(
    'title' => 'Header Banner',
    'identifier' => 'header-banner-id',
    'stores' => array(0), // Array with store ID's
    'content' => '<p>Some HTML content for banner</p>',
    'is_active' => 1
);
// Check if static block already exists, if yes then update content
$block = Mage::getModel('cms/block')->load($data['identifier']);
if ($block->isObjectNew()) {
    // Create static block
    Mage::getModel('cms/block')->setData($data)->save();
}else{
    // Update Content
    $block->setData('content', $data['content'])->save();
}


/* === CMS PAGE === */

$page = Mage::getModel('cms/page');
$page->setStoreId($store->getId());
$page->load('home', 'identifier');

$content=<<<EOT
    <div>page content here</div>
EOT;

$cmsPage = Array(
    'title' => 'Home Title',
    'identifier' => 'home',
    'content' => $content,
    'is_active' => 1,
    'stores' => array($store->getId())
);
if ($page->getId()) {
    $page->setContent($cmsPage ['content']);
    $page->setTitle($cmsPage ['title']);
    $page->save();
} else {
    Mage::getModel('cms/page')->setData($cmsPage)->save();
}


// ==== Product Attributes =====
$model =  new Mage_Catalog_Model_Resource_Setup();
$model->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'my_custom_attribute_code', array(
    'group' => 'Prices',
    'type' => 'text',
    'attribute_set' =>  'Default', // Your custom Attribute set
    'backend' => '',
    'frontend' => '',
    'label' => 'My Custom Attribute',
    'input' => 'text',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible' => true,
    'required' => false,
    'user_defined' => true,
    'default' => '1',
    'searchable' => false,
    'filterable' => true,
    'comparable' => false,
    'visible_on_front' => true,
    'visible_in_advanced_search' => true,
    'used_in_product_listing' => true,
    'unique' => false,
    'apply_to' => 'simple',  // Apply to simple product type
));

// Custom Attribute for dropdown
$model->addAttribute(Mage_Catalog_Model_Product::ENTITY, 'colors', array(
    'group' => 'General',
    'type' => 'text',
    'attribute_set' =>  'Default', // Your custom Attribute set
    'backend' => '',
    'frontend' => '',
    'label' => 'Colors',
    'input' => 'select',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible' => true,
    'required' => false,
    'user_defined' => true,
    'default' => 0,
    'searchable' => false,
    'filterable' => true,
    'comparable' => true,
    'visible_on_front' => true,
    'visible_in_advanced_search' => true,
    'used_in_product_listing' => true,
    'unique' => false,
    'option' =>array (
        'values' =>
            array (
                0 => 'Red',
                1 => 'Black',
                2 => 'Green',
                3 => 'Grey',
                4 => 'White',
                5 => 'Yellow'
            )
    ),
));

// Enable wysiwyg  
$installer->addAttribute(Mage_Catalog_Model_Category::ENTITY, 'description2', array(
    'group' => 'General Information',
    'input' => 'textarea',
    'type' => 'text',
    'label' => 'Description2',
    'backend'       => '',
    'visible'       => true,    
    'required' => false,
    'wysiwyg_enabled' => true,
    'visible_on_front' => true,
    'is_html_allowed_on_front' => true,
    'default' => '',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
));

$installer->updateAttribute(Mage_Catalog_Model_Category::ENTITY, 'bottom_description', 'is_wysiwyg_enabled', 1);
$installer->updateAttribute(Mage_Catalog_Model_Category::ENTITY, 'bottom_description', 'is_html_allowed_on_front', 1);



/*=== Category Upgrade ====*/
Mage::app()->getStore()->setId(0);
$category = Mage::getModel('catalog/category')->load($category_id); // enter category id
$category->setDescription('Sample Category description'); // Update category description
$category->setIsAnchor(1); // set category "Is Anchor" yes
$category->save();


/* === Update All Category and Run Indexing ==== */

$storeIds = Mage::getModel('core/store')->getCollection()->getAllIds();
$category = Mage::getModel('catalog/category');
$tree = $category->getTreeModel();
$tree->load();
$ids = $tree->getCollection()->getAllIds();
asort($ids);
$categories = array();
$storeId = 0;
$resource = Mage::getResourceModel('catalog/category');
if ($ids) {
    foreach ($ids as $id) {
        $cat = $category->load($id);
        if ($cat->getId() && $cat->getLevel() > 1) {
            $cat->setData('is_anchor', 1);
            //$cat->save();
            $resource->saveAttribute($cat, 'is_anchor');
            unset($cat);
        }
    }
}
$process = Mage::getSingleton('index/indexer')->getProcessByCode('catalog_category_flat');
$process->reindexEverything();


/* === Email Template Upgrade ==== */

$template_code = "Email - Header";
$templateText = <<<EOT
Template Content here
EOT;

$emailTemplate = Mage::getModel('core/email_template');
$emailTemplate->loadByCode($template_code);
if ($emailTemplate->getTemplateId()) {
    $emailTemplate->setTemplateText($templateText);
    $emailTemplate->save();
}

/* === Product Upgrade ==== */

Mage::app()->getStore()->setId(0);
$descriptions = <<<EOT
<p>Product description here</p>
EOT;

$product_id = 344;
$product = Mage::getModel('catalog/product')->load($product_id);
$product->setDescription($descriptions);
$product->save();



/* === Drop table ==== */

$command = "
DROP TABLE IF EXISTS `custome_table`;
";
$installer->run($command);



$installer->endSetup();
