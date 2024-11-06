<?php
require __DIR__ . '/vendor/autoload.php';

use NateWr\themehelper\Plugin;
use NateWr\themehelper\ThemeHelper;
use NateWr\vite\Loader;

import('lib.pkp.classes.plugins.ThemePlugin');

class TibOPTheme extends ThemePlugin
{
    protected ThemeHelper $themeHelper;

    public function isActive()
    {
        if (defined('SESSION_DISABLE_INIT')) return true;
        return parent::isActive();
    }

    public function init()
    {
        $this->setParent('defaultthemeplugin');
        $this->useThemeHelper();

        $request = Application::get()->getRequest();
        if ($request->getContext()) {
            $this->addMenuArea(['primary', 'user', 'quicklinks']);
        } else {
            $this->addMenuArea(['primary', 'user', 'quicklinks', 'policy']);
        }

        $this->addViteAssets(['src/main.js']);

        HookRegistry::register('TemplateManager::display', [$this, 'addGlobalTemplateData']);
        HookRegistry::register('TemplateManager::display', [$this, 'displayTemplate'], HOOK_SEQUENCE_LAST);
    }

    public function getDisplayName()
    {
        return __('plugins.themes.tibOPTheme.name');
    }

    public function getDescription()
    {
        return __('plugins.themes.tibOPTheme.description');
    }

    /**
     * Get the URL to the theme's root directory
     */
    public function getPluginUrl(): string
    {
        $request = Application::get()->getRequest();
        $baseUrl = rtrim($request->getBaseUrl(), '/');
        $pluginPath = rtrim($this->getPluginPath(), '/');
        return "{$baseUrl}/{$pluginPath}";
    }

    /**
     * Add custom data to all frontend templates
     *
     * This callback always returns false, so it never blocks other
     * callbacks registered to the same hook.
     */
    public function addGlobalTemplateData(string $hookName, array $args): bool
    {
        /** @var TemplateManager */
        $templateMgr = $args[0];
        $template = $args[1];

        if (substr($template, 0, 8) !== 'frontend') {
            return false;
        }

        $templateMgr->assign([
            'tibopSitePolicyMenu' => $this->getMenu(
                'policy',
                CONTEXT_ID_NONE,
                'frontend/tibop-menu-policy.tpl',
            ),
        ]);

        return false;
    }

    /**
     * Insert custom HTML into the rendered templates
     *
     * This callback intercepts and modifies the HTML rendered by
     * the template engine. No further callbacks will be fired
     * after this one, so it should always be registered as the
     * last callback.
     *
     * If possible, use self::addGlobalTemplateData() instead.
     */
    public function displayTemplate(string $hookName, array $args): bool
    {
        /** @var TemplateManager */
        $templateMgr = $args[0];
        $template = $args[1];
        $output =& $args[2];

        if (substr($template, 0, 8) !== 'frontend') {
            return false;
        }

        if ($template === 'frontend/pages/indexJournal.tpl') {
            $contextId = Application::get()->getRequest()->getContext()?->getId() ?? CONTEXT_ID_NONE;
            $output = $templateMgr->fetch('frontend/pages/indexJournal.tpl');
            $output = $this->addQuickLinksMenu($output, $contextId);
            return true;
        }

        return false;
    }

    /**
     * Add the Quick Links menu to the about the journal section
     * on the homepage
     *
     * Uses regex to insert the navigation menu at the end of the
     * section.homepage_about element.
     */
    protected function addQuickLinksMenu(string $homepageHtml, int $contextId): string
    {
        preg_match(
            '/(\<section class=\"homepage_about\"\>[\S\s]+?(?=\<\/section\>))/',
            $homepageHtml,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        if (empty($matches)) {
            return $homepageHtml;
        }

        $offset = $matches[0][1] + strlen($matches[0][0]);
        $menu = $this->getMenu('quickLinks', $contextId);

        return substr_replace($homepageHtml, $menu, $offset, 0);
    }

    /**
     * Get a navigation menu's template
     */
    protected function getMenu(string $name, int $contextId =  CONTEXT_ID_NONE, string $path = ''): string
    {
        /** @var NavigationMenuDAO $navigationMenuDao */
        $navigationMenuDao = DAORegistry::getDAO('NavigationMenuDAO');
        $navigationMenus = $navigationMenuDao->getByArea($contextId, $name)->toArray();

        if (!isset($navigationMenus[0])) {
            return '';
        }

        $navigationMenu = $navigationMenus[0];
        Services::get('navigationMenu')->getMenuTree($navigationMenu);

        $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
        $templateMgr->assign([
            'navigationMenu' => $navigationMenu,
            'id' => '',
            'ulClass' => '',
            'liClass' => '',
        ]);

        if (!$path) {
            $path = 'frontend/components/navigationMenu.tpl';
        }

        return $templateMgr->fetch($path);
    }

    /**
     * Use functions from ThemeHelper
     *
     * These helper functions register custom template functions, add
     * useful data to templates, and provide other utilities.
     */
    protected function useThemeHelper(): void
    {
        $this->themeHelper = new ThemeHelper(TemplateManager::getManager(Application::get()->getRequest()));
        $this->themeHelper->registerDefaultPlugins();
        $this->themeHelper->addPlugin(new Plugin('function', 'load_menu', [$this, 'loadMenu'], true));
    }

    /**
     * Override the default {load_menu} template tag
     *
     * Injects the TIB OP logo and language selection to the mobile
     * dropdown menu by attaching it at the end of the user menu.
     */
    public function loadMenu(array $params, $smarty): string
    {
        $output = $smarty->smartyLoadNavigationMenuArea($params, $smarty);

        if ($params['name'] === 'user' && $params['id'] === 'navigationUser') {
            $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
            $template = $templateMgr->fetch('frontend/tibop-mobile-dropdown.tpl');
            return $output . $template;
        }

        return $output;
    }

    /**
     * Add the script, style and other assets compiled by Vite
     */
    protected function addViteAssets(array $entryPoints): void
    {
        $viteServerUrl = $this->getViteServerUrl();
        $basePath = $viteServerUrl ? $viteServerUrl : 'dist/';

        $viteLoader = new Loader(
            templateManager: TemplateManager::getManager(Application::get()->getRequest()),
            manifestPath: dirname(__FILE__) . '/dist/.vite/manifest.json',
            basePath: $basePath,
            devMode: $viteServerUrl ? true : false,
            theme: $this,
        );

        $viteLoader->load($entryPoints);
    }

    /**
     * Get the URL to the Vite server
     *
     * This checks the theme's root directory for a .vite.server.json
     * file. If found, it reads the Vite server URL from that file.
     *
     * @see ./vite.config.js
     */
    protected function getViteServerUrl(): string
    {
        $path = join('/', [dirname(__FILE__), '.vite.server.json']);
        if (!file_exists($path)) {
            return '';
        }
        $config = json_decode(file_get_contents($path), true);
        if (!$config) {
            return '';
        }
        if (empty($config['network'])) {
            return isset($config['local']) ? $config['local'][0] : 'http://localhost:5173/';
        }
        return $config['network'][0];
    }
}
