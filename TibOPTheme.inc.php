<?php
require __DIR__ . '/vendor/autoload.php';

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

        HookRegistry::register('TemplateManager::display', [$this, 'addTibFooter']);
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
     * Add the TIB footer by injecting the HTML into the
     * $pageFooter template variable.
     */
    public function addTibFooter (string $hookName, array $args): bool
    {
        /** @var TemplateManager */
        $templateMgr = $args[0];
        $template = $args[1];

        if (substr($template, 0, 8) !== 'frontend') {
            return false;
        }

        $tibFooter = $templateMgr->fetch('frontend/tibop-footer.tpl');

        $templateMgr->assign([
            'pageFooter' => $tibFooter
        ]);

        return false;
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
