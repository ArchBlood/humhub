<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\admin\models\forms;

use humhub\modules\file\validators\ImageSquareValidator;
use humhub\modules\stream\actions\Stream;
use humhub\modules\user\models\ProfileField;
use humhub\modules\web\pwa\widgets\SiteIcon;
use Yii;
use yii\base\Model;
use yii\web\UploadedFile;
use humhub\libs\LogoImage;
use humhub\libs\DynamicConfig;
use humhub\modules\ui\view\helpers\ThemeHelper;

/**
 * DesignSettingsForm
 *
 * @since 0.5
 */
class DesignSettingsForm extends Model
{

    public $theme;
    public $paginationSize;
    public $displayNameFormat;
    public $displayNameSubFormat;
    public $spaceOrder;
    public $logo;
    public $icon;
    public $dateInputDisplayFormat;
    public $horImageScrollOnMobile;
    public $defaultStreamSort;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $settingsManager = Yii::$app->settings;

        $this->theme = Yii::$app->view->theme->name;
        $this->paginationSize = $settingsManager->get('paginationSize');
        $this->displayNameFormat = $settingsManager->get('displayNameFormat');
        $this->displayNameSubFormat = $settingsManager->get('displayNameSubFormat');
        $this->spaceOrder = Yii::$app->getModule('space')->settings->get('spaceOrder');
        $this->dateInputDisplayFormat = Yii::$app->getModule('admin')->settings->get('defaultDateInputFormat');
        $this->horImageScrollOnMobile = $settingsManager->get('horImageScrollOnMobile');
        $this->defaultStreamSort = Yii::$app->getModule('stream')->settings->get('defaultSort');

    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['paginationSize', 'required'],
            ['paginationSize', 'integer', 'max' => 200, 'min' => 1],
            ['theme', 'in', 'range' => $this->getThemes()],
            [['displayNameFormat', 'displayNameSubFormat', 'spaceOrder'], 'safe'],
            [['horImageScrollOnMobile'], 'boolean'],
            ['logo', 'image', 'extensions' => 'png, jpg, jpeg', 'minWidth' => 100, 'minHeight' => 120],
            [['defaultStreamSort'], 'in', 'range' => array_keys($this->getDefaultStreamSortOptions())],
            ['icon', 'image', 'extensions' => 'png, jpg, jpeg', 'minWidth' => 256, 'minHeight' => 256],
            ['icon', ImageSquareValidator::class],
            ['dateInputDisplayFormat', 'in', 'range' => ['', 'php:d/m/Y']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'theme' => Yii::t('AdminModule.settings', 'Theme'),
            'paginationSize' => Yii::t('AdminModule.settings', 'Default pagination size (Entries per page)'),
            'displayNameFormat' => Yii::t('AdminModule.settings', 'User Display Name'),
            'displayNameSubFormat' => Yii::t('AdminModule.settings', 'User Display Name Subtitle'),
            'spaceOrder' => Yii::t('AdminModule.settings', 'Dropdown space order'),
            'logo' => Yii::t('AdminModule.settings', 'Logo upload'),
            'icon' => Yii::t('AdminModule.settings', 'Icon upload'),
            'dateInputDisplayFormat' => Yii::t('AdminModule.settings', 'Date input format'),
            'horImageScrollOnMobile' => Yii::t('AdminModule.settings', 'Horizontal scrolling images on a mobile device'),
        ];
    }


    /**
     * @inheritdoc
     */
    public function load($data, $formName = null)
    {
        $files = UploadedFile::getInstancesByName('logo');
        if (count($files) != 0) {
            $file = $files[0];
            $this->logo = $file;
        }

        $files = UploadedFile::getInstancesByName('icon');
        if (count($files) != 0) {
            $file = $files[0];
            $this->icon = $file;
        }

        return parent::load($data, $formName);
    }

    /**
     * @return array a list of available themes
     */
    public function getThemes()
    {
        $themes = [];

        foreach (ThemeHelper::getThemes() as $theme) {
            $themes[$theme->name] = $theme->name;
        }

        return $themes;
    }

    /**
     * Saves the form
     *
     * @return boolean
     */
    public function save()
    {
        $settingsManager = Yii::$app->settings;

        $theme = ThemeHelper::getThemeByName($this->theme);
        if ($theme !== null) {
            $theme->activate();
        }

        $settingsManager->set('paginationSize', $this->paginationSize);
        $settingsManager->set('displayNameFormat', $this->displayNameFormat);
        $settingsManager->set('displayNameSubFormat', $this->displayNameSubFormat);
        Yii::$app->getModule('space')->settings->set('spaceOrder', $this->spaceOrder);
        Yii::$app->getModule('admin')->settings->set('defaultDateInputFormat', $this->dateInputDisplayFormat);
        $settingsManager->set('horImageScrollOnMobile', $this->horImageScrollOnMobile);

        Yii::$app->getModule('stream')->settings->set('defaultSort', $this->defaultStreamSort);

        if ($this->logo) {
            LogoImage::set($this->logo);
        }

        if ($this->icon) {
            SiteIcon::set($this->icon);
        }

        DynamicConfig::rewrite();

        return true;
    }

    /**
     * Returns available options for defaultStreamSort attribute
     * @return array
     */
    public function getDefaultStreamSortOptions()
    {
        return [
            Stream::SORT_CREATED_AT => Yii::t('AdminModule.settings', 'Sort by creation date'),
            Stream::SORT_UPDATED_AT => Yii::t('AdminModule.settings', 'Sort by update date'),
        ];
    }

    /**
     * Returns a list of possible subtitle attribute names
     *
     * @return array
     */
    public function getDisplayNameSubAttributes()
    {
        $availableAttributes = [];
        foreach (ProfileField::find()->all() as $profileField) {
            $availableAttributes[$profileField->internal_name] = $profileField->title;
        }
        return $availableAttributes;
    }

}
