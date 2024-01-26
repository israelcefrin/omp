<?php

/**
 * @file plugins/themes/default/DefaultThemePlugin.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DefaultThemePlugin
 *
 * @brief Default theme
 */

namespace APP\plugins\themes\default;

use APP\core\Application;
use APP\file\PublicFileManager;
use PKP\config\Config;
use PKP\session\SessionManager;

use APP\facades\Repo;
use APP\issue\Collector;
use APP\services\NavigationMenuService;
use PKP\plugins\ThemePlugin;
use PKP\plugins\Hook;

class DefaultThemePlugin extends ThemePlugin
{
    /**
     * @copydoc ThemePlugin::isActive()
     */
    public function isActive()
    {
        if (SessionManager::isDisabled()) {
            return true;
        }
        return parent::isActive();
    }

    /**
     * Initialize the theme's styles, scripts and hooks. This is run on the
     * currently active theme and it's parent themes.
     *
     */
    public function init()
    {
        // Register theme options
        $this->addOption('typography', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.default.option.typography.label'),
            'description' => __('plugins.themes.default.option.typography.description'),
            'options' => [
                [
                    'value' => 'notoSans',
                    'label' => __('plugins.themes.default.option.typography.notoSans'),
                ],
                [
                    'value' => 'notoSerif',
                    'label' => __('plugins.themes.default.option.typography.notoSerif'),
                ],
                [
                    'value' => 'notoSerif_notoSans',
                    'label' => __('plugins.themes.default.option.typography.notoSerif_notoSans'),
                ],
                [
                    'value' => 'notoSans_notoSerif',
                    'label' => __('plugins.themes.default.option.typography.notoSans_notoSerif'),
                ],
                [
                    'value' => 'lato',
                    'label' => __('plugins.themes.default.option.typography.lato'),
                ],
                [
                    'value' => 'lora',
                    'label' => __('plugins.themes.default.option.typography.lora'),
                ],
                [
                    'value' => 'lora_openSans',
                    'label' => __('plugins.themes.default.option.typography.lora_openSans'),
                ],
            ],
            'default' => 'notoSans',
        ]);
        $this->addOption('useHomepageImageAsHeader', 'FieldOptions', [
            'label' => __('plugins.themes.default.option.useHomepageImageAsHeader.label'),
            'description' => __('plugins.themes.default.option.useHomepageImageAsHeader.description'),
            'options' => [
                [
                    'value' => true,
                    'label' => __('plugins.themes.default.option.useHomepageImageAsHeader.option')
                ],
            ],
            'default' => false,
        ]);

        $this->addOption('baseColour', 'FieldColor', [
            'label' => __('plugins.themes.default.option.colour.label'),
            'description' => __('plugins.themes.default.option.colour.description'),
            'default' => '#1E6292',
        ]);

        $this->addOption('showCatalogSeriesListing', 'FieldOptions', [
            'label' => __('plugins.themes.default.option.showCatalogSeriesListing.label'),
            'options' => [
                [
                    'value' => true,
                    'label' => __('plugins.themes.default.option.showCatalogSeriesListing.option')
                ],
            ],
            'default' => false,
        ]);

        $this->addOption('displayStats', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.default.option.displayStats.label'),
            'options' => [
                [
                    'value' => 'none',
                    'label' => __('plugins.themes.default.option.displayStats.none'),
                ],
                [
                    'value' => 'bar',
                    'label' => __('plugins.themes.default.option.displayStats.bar'),
                ],
                [
                    'value' => 'line',
                    'label' => __('plugins.themes.default.option.displayStats.line'),
                ],
            ],
            'default' => 'none',
        ]);

        // Load primary stylesheet
        $this->addStyle('stylesheet', 'styles/index.less');

        // Store additional LESS variables to process based on options
        $additionalLessVariables = [];

        if ($this->getOption('typography') === 'notoSerif') {
            $this->addStyle('font', 'styles/fonts/notoSerif.less');
            $additionalLessVariables[] = '@font: "Noto Serif", -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", "Oxygen-Sans", "Ubuntu", "Cantarell", "Helvetica Neue", sans-serif;';
        } elseif (strpos($this->getOption('typography'), 'notoSerif') !== false) {
            $this->addStyle('font', 'styles/fonts/notoSans_notoSerif.less');
            if ($this->getOption('typography') == 'notoSerif_notoSans') {
                $additionalLessVariables[] = '@font-heading: "Noto Serif", serif;';
            } elseif ($this->getOption('typography') == 'notoSans_notoSerif') {
                $additionalLessVariables[] = '@font: "Noto Serif", serif;@font-heading: "Noto Sans", serif;';
            }
        } elseif ($this->getOption('typography') == 'lato') {
            $this->addStyle('font', 'styles/fonts/lato.less');
            $additionalLessVariables[] = '@font: Lato, sans-serif;';
        } elseif ($this->getOption('typography') == 'lora') {
            $this->addStyle('font', 'styles/fonts/lora.less');
            $additionalLessVariables[] = '@font: Lora, serif;';
        } elseif ($this->getOption('typography') == 'lora_openSans') {
            $this->addStyle('font', 'styles/fonts/lora_openSans.less');
            $additionalLessVariables[] = '@font: "Open Sans", sans-serif;@font-heading: Lora, serif;';
        } else {
            $this->addStyle('font', 'styles/fonts/notoSans.less');
        }

        // Update colour based on theme option
        if ($this->getOption('baseColour') !== '#1E6292') {
            $additionalLessVariables[] = '@bg-base:' . $this->getOption('baseColour') . ';';
            if (!$this->isColourDark($this->getOption('baseColour'))) {
                $additionalLessVariables[] = '@text-bg-base:rgba(0,0,0,0.84);';
                $additionalLessVariables[] = '@bg-base-border-color:rgba(0,0,0,0.2);';
            }
        }

        // Pass additional LESS variables based on options
        if (!empty($additionalLessVariables)) {
            $this->modifyStyle('stylesheet', ['addLessVariables' => join("\n", $additionalLessVariables)]);
        }

        $request = Application::get()->getRequest();

        // Load icon font FontAwesome - http://fontawesome.io/
        $this->addStyle(
            'fontAwesome',
            $request->getBaseUrl() . '/lib/pkp/styles/fontawesome/fontawesome.css',
            ['baseUrl' => '']
        );

        // Get homepage image and use as header background if useAsHeader is true
        $context = Application::get()->getRequest()->getContext();
        if ($context && $this->getOption('useHomepageImageAsHeader') && ($homepageImage = $context->getLocalizedData('homepageImage'))) {
            $publicFileManager = new PublicFileManager();
            $publicFilesDir = $request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($context->getId());
            $homepageImageUrl = $publicFilesDir . '/' . $homepageImage['uploadName'];

            $this->addStyle(
                'homepageImage',
                '.pkp_structure_head { background: center / cover no-repeat url("' . $homepageImageUrl . '");}',
                ['inline' => true]
            );
        }

        // Load jQuery from a CDN or, if CDNs are disabled, from a local copy.
        $min = Config::getVar('general', 'enable_minified') ? '.min' : '';
        $jquery = $request->getBaseUrl() . '/lib/pkp/lib/vendor/components/jquery/jquery' . $min . '.js';
        $jqueryUI = $request->getBaseUrl() . '/lib/pkp/lib/vendor/components/jqueryui/jquery-ui' . $min . '.js';
        // Use an empty `baseUrl` argument to prevent the theme from looking for
        // the files within the theme directory
        $this->addScript('jQuery', $jquery, ['baseUrl' => '']);
        $this->addScript('jQueryUI', $jqueryUI, ['baseUrl' => '']);

        // Load Bootsrap's dropdown
        $this->addScript('popper', 'js/lib/popper/popper.js');
        $this->addScript('bsUtil', 'js/lib/bootstrap/util.js');
        $this->addScript('bsDropdown', 'js/lib/bootstrap/dropdown.js');

        // Load custom JavaScript for this theme
        $this->addScript('default', 'js/main.js');

        // Add navigation menu areas for this theme
        $this->addMenuArea(['primary', 'user']);

        Hook::add('TemplateManager::display', array($this, 'checkCurrentPage'));
    }

    /**
     * Get the name of the settings file to be installed on new press
     * creation.
     *
     * @return string
     */
    public function getContextSpecificPluginSettingsFile()
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    /**
     * Get the name of the settings file to be installed site-wide when
     * OJS is installed.
     *
     * @return string
     */
    public function getInstallSitePluginSettingsFile()
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    /**
     * Get the display name of this plugin
     *
     * @return string
     */
    public function getDisplayName()
    {
        return __('plugins.themes.default.name');
    }

    /**
     * Get the description of this plugin
     *
     * @return string
     */
    public function getDescription()
    {
        return __('plugins.themes.default.description');
    }


	/**
	 * @param $hookname string
	 * @param $args array
	 */
	public function checkCurrentPage($hookname, $args) {
		$templateMgr = $args[0];
		// TODO check the issue with multiple calls of the hook on settings/website
		if (!isset($templateMgr->registered_plugins["function"]["default_item_active"])) {
			$templateMgr->registerPlugin('function', 'default_item_active', array($this, 'isActiveItem'));
		}

	}

	/**
	 * @param $params array
	 * @param $smarty Smarty_Internal_Template
	 * @return string
	 */
	public function isActiveItem($params, $smarty) {

		$navigationMenuItem = $params['item'];
		$emptyMarker = '';
		$activeMarker = ' active';
		
		// Get URL of the current page
		$request = $this->getRequest();
		$currentUrl = $request->getCompleteUrl();
		$currentPage = $request->getRequestedPage();

		// Do not add an active marker if it's a dropdown menu
		if ($navigationMenuItem->getIsChildVisible()) return $emptyMarker;

		// Retrieve URL and its components for a menu item
		$itemUrl = $navigationMenuItem->getUrl();

		if ($currentUrl === $itemUrl) return $activeMarker;

		return $emptyMarker;
	}

}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\themes\default\DefaultThemePlugin', '\DefaultThemePlugin');
}