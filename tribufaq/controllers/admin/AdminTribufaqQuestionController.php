<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminTribufaqQuestionController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->bulk_actions = array();
        $this->context = Context::getContext();
        $this->id_lang = $this->context->language->id;
        $this->shop = $this->context->shop->id;
        $this->table = 'tribufaq_question'; //nom de la table
        $this->identifier = 'id_tribufaq_question'; //primary key de la table
        $this->default_form_language = $this->context->language->id;
        $this->bootstrap = true;
        $this->controller_name = 'AdminTribufaqQuestionController';
        $this->className = 'TribufaqQuestion'; //nom de la classe de l'objet
        $this->lang = true;

        parent::__construct();


        $this->fields_list = [


            'id_tribufaq_question' => [ //Nom du champ sql
                'title' => 'ID', //Nom afficher dans le tableau
                'align' => 'center', //Alignement
                'class' => 'fixed-width-xs', //Classe css de l'élément
            ],

            'question' => [ //Nom du champ sql
                'title' => $this->module->l('Question'), //Nom afficher dans le tableau
                'align' => 'center', //Alignement
                'lang' => true,
            ],

            'response' => [ //Nom du champ sql
                'title' => $this->module->l('Réponse à la question'), //Nom afficher dans le tableau
                'align' => 'center', //Alignement
                'lang' => true,
            ],

            'id_tribufaq_category' => [ //Nom du champ sql
                'title' => $this->module->l('Catégorie associée'), //Nom afficher dans le tableau
                'align' => 'center', //Alignement
                'class' => 'fixed-width-xs', //Classe css de l'élément
            ],


            'active' => [
                'title' => $this->module->l('Active'),
                'align' => 'center',
                'type' => 'bool',
                'active' => 'toggleActive',
                'class' => 'fixed-width-sm', //Classe css de l'élément
                'ajax' => true
            ],

            'date_add' => [
                'title' => $this->module->l('Date de création'),
                'class' => 'fixed-width-sm', //Classe css de l'élément
                'align' => 'center',
            ],

        ];
        //actions disponibles pour chaques lignes
        $this->addRowAction('edit');
        $this->addRowAction('delete');
    }

    public function initContent()
    {
        parent::initContent();
    }

    /**
     * Gestion de la toolbar
     */
    public function initPageHeaderToolbar()
    {
        //Bouton d'ajout
        $this->page_header_toolbar_btn['new'] = array(
            'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
            'desc' => $this->module->l('Ajouter une entrée à la FAQ'),
            'icon' => 'process-icon-new'
        );

        parent::initPageHeaderToolbar();
    }

    /**
     * Gestion du formulaire de création/édition
     */
    public function renderForm()
    {
        $this->loadObject(true);
        // définition du formulaire et champs
        $this->fields_form = [
            'legend' => [
                'title' => $this->module->l('Question/réponse'),
                'icon' => 'icon-cog'
            ],

            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->module->l('Question'),
                    'name' => 'question',
                    'lang' => true,
                    'required' => true,
                ],

                [
                    'type' => 'text',
                    'label' => $this->module->l('Réponse'),
                    'name' => 'response',
                    'lang' => true,
                    'required' => true,
                ],

                [
                    'type' => 'text',
                    'label' => $this->module->l('Catégorie associée'),
                    'name' => 'id_tribufaq_category',
                    'required' => true,
                ],


                [
                    'type' => 'switch',
                    'label' => $this->context->getTranslator()->trans('Active', [], 'Admin.Global'),
                    'name' => 'active',
                    'required' => false,
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => true,
                            'label' => $this->context->getTranslator()->trans('Yes', [], 'Admin.Global'),
                        ],
                        [
                            'id' => 'active_off',
                            'value' => false,
                            'label' => $this->context->getTranslator()->trans('No', [], 'Admin.Global'),
                        ],
                    ],

                ],

            ],

            'submit' => [
                'title' => $this->context->getTranslator()->trans('Save', [], 'Admin.Actions'),
            ],
        ];

        return parent::renderForm();
    }

    public function ajaxProcessToggleActiveTribufaqQuestion()
    {
        $tribufaqQuestion = new TribufaqQuestion(Tools::getValue('id_tribufaq_question'));
        $tribufaqQuestion->active = !$tribufaqQuestion->active;

        if ($tribufaqQuestion->save()) {
            die(Tools::jsonEncode([
                'success' => 1,
                'text' => $this->trans('The settings have been successfully updated.', [], 'Admin.Notifications.Success'),
            ]));
        } else {
            die(Tools::jsonEncode([
                'success' => 0,
                'text' => $this->trans('Unable to update settings.', [], 'Admin.Notifications.Error'),
            ]));
        }
    }
}