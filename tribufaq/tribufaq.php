<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once _PS_MODULE_DIR_ . '/tribufaq/classes/ModuleClassUtility.php';
include_once _PS_MODULE_DIR_ . '/tribufaq/src/Entity/TribufaqQuestion.php';
include_once _PS_MODULE_DIR_ . '/tribufaq/src/Entity/TribufaqCategory.php';

class TribuFaq extends Module
{
    protected $queries = [];
    protected $moduleTabs = [];
    public function __construct()
    {
        $this->name = 'tribufaq';
        $this->version = '1.0';
        $this->author = 'Tribu and Co';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Tribu FAQ');
        $this->description = $this->l('Affiche une FAQ catégorisée sur la page d\'accueil');

        $this->moduleTabs = [
            [
                'name'              => $this->l('Gestion FAQ'),
                'class_name'        => 'AdminParentTribufaq',
                'parent_class_name' => 'TRIBU',
                'icon'              => 'help_outline'
            ],
            [
                'name'              => $this->l('Catégories FAQ'),
                'class_name'        => 'AdminTribufaqCategory',
                'parent_class_name' => 'AdminParentTribufaq',
            ],
            [
                'name'              => $this->l('Questions/réponses'),
                'class_name'        => 'AdminTribufaqQuestion',
                'parent_class_name' => 'AdminParentTribufaq',
            ]
        ];
        $this->queries = [
            'tribufaq_question' => 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'tribufaq_question` (
                `id_tribufaq_question` INT(10) unsigned NOT NULL AUTO_INCREMENT,
                `id_tribufaq_category` INT(10) unsigned NOT NULL,
                `date_add` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `active` int(1) unsigned DEFAULT "0",
                PRIMARY KEY (`id_tribufaq_question`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;',
            'tribufaq_question_lang' => 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'tribufaq_question_lang` (
                `id_tribufaq_question` INT(10) unsigned NOT NULL AUTO_INCREMENT,
                `id_lang` int(5) unsigned NOT NULL,
                `question` VARCHAR(255) NOT NULL,
                `response` text NOT NULL,
                PRIMARY KEY (`id_tribufaq_question`,`id_lang`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;',
            'tribufaq_category' => 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'tribufaq_category` (
                `id_tribufaq_category` INT(10) unsigned NOT NULL AUTO_INCREMENT,
                `date_add` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `active` int(1) unsigned DEFAULT "0",
                PRIMARY KEY (`id_tribufaq_category`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;',
            'tribufaq_category_lang' => 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'tribufaq_category_lang` (
                `id_tribufaq_category` INT(10) unsigned NOT NULL AUTO_INCREMENT,
                `id_lang` int(5) unsigned NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                PRIMARY KEY (`id_tribufaq_category`,`id_lang`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;'
        ];
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submit' . $this->name)) {
            $configValue = (string) Tools::getValue('MYMODULE_CONFIG');
            if (empty($configValue) || !Validate::isGenericName($configValue)) {
                $output = $this->displayError($this->l('Invalid Configuration value'));
            } else {
                Configuration::updateValue('MYMODULE_CONFIG', $configValue);
                $output = $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        return $output . $this->displayForm();
    }

    /**
     * Builds the configuration form
     * @return string HTML code
     */
    public function displayForm()
    {
        // Init Fields form array
        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Configuration'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Nombre de questions à afficher'),
                        'name' => 'MYMODULE_CONFIG',
                        'size' => 20,
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&' . http_build_query(['configure' => $this->name]);
        $helper->submit_action = 'submit' . $this->name;

        // Default language
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        // Load current value into the form
        $helper->fields_value['MYMODULE_CONFIG'] = Tools::getValue('MYMODULE_CONFIG', Configuration::get('MYMODULE_CONFIG'));

        return $helper->generateForm([$form]);
    }

    public function install()
    {
        return (
            parent::install()
            && $this->registerHook('displayHome')
            && $this->registerHook('actionFrontControllerSetMedia')
            && ModuleClassUtility::installSql($this->queries)
            && ModuleClassUtility::installModuleTabs($this->name,$this->moduleTabs)
        );
    }

    public function uninstall()
    {
        return (
            parent::uninstall()
            && ModuleClassUtility::removeTabByClassName('AdminTribufaqCategory')
            && ModuleClassUtility::removeTabByClassName('AdminTribufaqQuestion')
            && ModuleClassUtility::removeTabByClassName('AdminParentTribufaq')
            && ModuleClassUtility::uninstallsql($this->queries)
        );
    }

    public function hookActionFrontControllerSetMedia()
    {
        $this->context->controller->registerStylesheet(
            'tribufaq-style',
            $this->_path.'views/css/tribufaq.css',
            [
                'media' => 'all',
                'priority' => 1000,
            ]
        );

        $this->context->controller->registerJavascript(
            'tribufaq-javascript',
            $this->_path.'views/js/tribufaq.js',
            [
                'position' => 'bottom',
                'priority' => 1000,
            ]
        );
    }

    public function hookDisplayHome()
    {
        $faqToShowNumber = Configuration::get('MYMODULE_CONFIG');

        function getLastFaq($number)
        {
            $query = new DbQuery();
            $query->from('tribufaq_question', 'faq');
            $query->select('*');
            $query->leftJoin('tribufaq_question_lang', 'faql', 'faq.id_tribufaq_question = faql.id_tribufaq_question AND faql.id_lang=' . Context::getContext()->language->id);
            $query->leftJoin('tribufaq_category_lang', 'faqcl', 'faq.id_tribufaq_category = faqcl.id_tribufaq_category AND faqcl.id_lang=' . Context::getContext()->language->id);
            $query->where('faq.active = 1');
            $query->orderBy('date_add DESC');
            $query->limit((int)$number);
            return Db::getInstance()->executeS($query);
        }

        function groupFaq($key, $data) {
            $result = array();
            foreach($data as $val) {
                if(array_key_exists($key, $val)){
                    $result[$val[$key]][] = $val;
                }else{
                    $result[""][] = $val;
                }
            }
            return $result;
        }

        $faqs = getLastFaq($faqToShowNumber);
        $orderedFaqs = groupFaq('name', $faqs);

        $this->context->smarty->assign('faqs', $orderedFaqs);
        return $this->display(__FILE__,'displayHome.tpl');
    }
}