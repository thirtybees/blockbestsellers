<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    Thirty Bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class BlockBestSellers
 *
 * @since 1.0.0
 */
class BlockBestSellers extends Module
{
    protected static $cacheBestSellers;

    /**
     * BlockBestSellers constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->name = 'blockbestsellers';
        $this->tab = 'front_office_features';
        $this->version = '2.0.1';
        $this->author = 'thirty bees';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Top-sellers block');
        $this->description = $this->l('Adds a block displaying your store\'s top-selling products.');
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function install()
    {
        $this->clearCache('*');

        if (!parent::install()
            || !$this->registerHook('header')
            || !$this->registerHook('leftColumn')
            || !$this->registerHook('actionOrderStatusPostUpdate')
            || !$this->registerHook('addproduct')
            || !$this->registerHook('updateproduct')
            || !$this->registerHook('deleteproduct')
            || !$this->registerHook('displayHomeTab')
            || !$this->registerHook('displayHomeTabContent')
            || !ProductSale::fillProductSales()
        ) {
            return false;
        }

        Configuration::updateValue('PS_BLOCK_BESTSELLERS_TO_DISPLAY', 10);

        return true;
    }

    /**
     * @param string      $template
     * @param string|null $cacheId
     * @param string|null $compileId
     *
     * @since 1.0.0
     */
    public function clearCache($template, $cacheId = null, $compileId = null)
    {
        parent::_clearCache('blockbestsellers.tpl', 'blockbestsellers-col');
        parent::_clearCache('blockbestsellers-home.tpl', 'blockbestsellers-home');
        parent::_clearCache('tab.tpl', 'blockbestsellers-tab');
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     */
    public function uninstall()
    {
        $this->clearCache('*');

        return parent::uninstall();
    }

    /**
     * @param array $params
     *
     * @since 1.0.0
     */
    public function hookAddProduct($params)
    {
        $this->clearCache('*');
    }

    /**
     * @param array $params
     *
     * @since 1.0.0
     */
    public function hookUpdateProduct($params)
    {
        $this->clearCache('*');
    }

    /**
     * @param array $params
     *
     * @since 1.0.0
     */
    public function hookDeleteProduct($params)
    {
        $this->clearCache('*');
    }

    /**
     * @param array $params
     *
     * @since 1.0.0
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
        $this->clearCache('*');
    }

    /**
     * Called in administration -> module -> configure
     */
    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitBestSellers')) {
            Configuration::updateValue('PS_BLOCK_BESTSELLERS_DISPLAY', (int) Tools::getValue('PS_BLOCK_BESTSELLERS_DISPLAY'));
            Configuration::updateValue('PS_BLOCK_BESTSELLERS_TO_DISPLAY', (int) Tools::getValue('PS_BLOCK_BESTSELLERS_TO_DISPLAY'));
            $this->clearCache('*');
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        return $output.$this->renderForm();
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        $formFields = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ],
                'input'  => [
                    [
                        'type'  => 'text',
                        'label' => $this->l('Products to display'),
                        'name'  => 'PS_BLOCK_BESTSELLERS_TO_DISPLAY',
                        'desc'  => $this->l('Determine the number of product to display in this block'),
                        'class' => 'fixed-width-xs',
                    ],
                    [
                        'type'    => 'switch',
                        'label'   => $this->l('Always display this block'),
                        'name'    => 'PS_BLOCK_BESTSELLERS_DISPLAY',
                        'desc'    => $this->l('Show the block even if no best sellers are available.'),
                        'is_bool' => true,
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = [];

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitBestSellers';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$formFields]);
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getConfigFieldsValues()
    {
        return [
            'PS_BLOCK_BESTSELLERS_TO_DISPLAY' => (int) Tools::getValue('PS_BLOCK_BESTSELLERS_TO_DISPLAY', Configuration::get('PS_BLOCK_BESTSELLERS_TO_DISPLAY')),
            'PS_BLOCK_BESTSELLERS_DISPLAY'    => (int) Tools::getValue('PS_BLOCK_BESTSELLERS_DISPLAY', Configuration::get('PS_BLOCK_BESTSELLERS_DISPLAY')),
        ];
    }

    /**
     * @param $params
     *
     * @since 1.0.0
     */
    public function hookHeader($params)
    {
        if (Configuration::get('PS_CATALOG_MODE')) {
            return;
        }
        if (isset($this->context->controller->php_self) && $this->context->controller->php_self == 'index') {
            $this->context->controller->addCSS(_THEME_CSS_DIR_.'product_list.css');
        }
        $this->context->controller->addCSS($this->_path.'blockbestsellers.css', 'all');
    }

    /**
     * @param $params
     *
     * @return bool|string
     *
     * @since 1.0.0
     */
    public function hookDisplayHomeTab($params)
    {
        if (!$this->isCached('tab.tpl', $this->getCacheId('blockbestsellers-tab'))) {
            self::$cacheBestSellers = $this->getBestSellers($params);
            $this->smarty->assign('best_sellers', self::$cacheBestSellers);
        }

        if (self::$cacheBestSellers === false) {
            return false;
        }

        return $this->display(__FILE__, 'tab.tpl', $this->getCacheId('blockbestsellers-tab'));
    }

    /**
     * @param array $params
     *
     * @return array|bool
     *
     * @since 1.0.0
     */
    protected function getBestSellers($params)
    {
        if (Configuration::get('PS_CATALOG_MODE')) {
            return false;
        }

        if (!($result = ProductSale::getBestSalesLight((int) $params['cookie']->id_lang, 0, (int) Configuration::get('PS_BLOCK_BESTSELLERS_TO_DISPLAY')))) {
            return (Configuration::get('PS_BLOCK_BESTSELLERS_DISPLAY') ? [] : false);
        }

        $currency = new Currency($params['cookie']->id_currency);
        $usetax = (Product::getTaxCalculationMethod((int) $this->context->customer->id) != PS_TAX_EXC);
        foreach ($result as &$row) {
            $row['price'] = Tools::displayPrice(Product::getPriceStatic((int) $row['id_product'], $usetax), $currency);
        }

        return $result;
    }

    /**
     * @param array $params
     *
     * @return bool|string
     *
     * @since 1.0.0
     */
    public function hookDisplayHomeTabContent($params)
    {
        return $this->hookDisplayHome($params);
    }

    /**
     * @param array $params
     *
     * @return bool|string
     *
     * @since 1.0.0
     */
    public function hookDisplayHome($params)
    {
        if (!$this->isCached('blockbestsellers-home.tpl', $this->getCacheId('blockbestsellers-home'))) {
            $this->smarty->assign(
                [
                    'best_sellers' => self::$cacheBestSellers,
                    'homeSize'     => Image::getSize(ImageType::getFormatedName('home')),
                ]
            );
        }

        if (self::$cacheBestSellers === false) {
            return false;
        }

        return $this->display(__FILE__, 'blockbestsellers-home.tpl', $this->getCacheId('blockbestsellers-home'));
    }

    /**
     * @param array $params
     *
     * @return bool|string
     *
     * @since 1.0.0
     */
    public function hookLeftColumn($params)
    {
        return $this->hookRightColumn($params);
    }

    /**
     * @param array $params
     *
     * @return bool|string
     *
     * @since 1.0.0
     */
    public function hookRightColumn($params)
    {
        if (!$this->isCached('blockbestsellers.tpl', $this->getCacheId('blockbestsellers-col'))) {
            if (!isset(self::$cacheBestSellers)) {
                self::$cacheBestSellers = $this->getBestSellers($params);
            }
            $this->smarty->assign(
                [
                    'best_sellers'             => self::$cacheBestSellers,
                    'display_link_bestsellers' => Configuration::get('PS_DISPLAY_BEST_SELLERS'),
                    'mediumSize'               => Image::getSize(ImageType::getFormatedName('medium')),
                    'smallSize'                => Image::getSize(ImageType::getFormatedName('small')),
                ]
            );
        }

        if (self::$cacheBestSellers === false) {
            return false;
        }

        return $this->display(__FILE__, 'blockbestsellers.tpl', $this->getCacheId('blockbestsellers-col'));
    }
}
