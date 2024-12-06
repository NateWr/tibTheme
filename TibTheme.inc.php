<?php
import('lib.pkp.classes.plugins.ThemePlugin');
import('plugins.themes.tibTheme.classes.TibThemeHelper');
import('plugins.themes.tibTheme.classes.TibThemeTemplatePlugin');
import('plugins.themes.tibTheme.classes.TibThemeViteLoader');

class TibTheme extends ThemePlugin
{
    protected TibThemeHelper $themeHelper;

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

        if (!$request->getContext()) {
            $this->addSiteConferencesOption();
        } else {
            $this->addPartnerLogosOption();
        }

        $this->addFonts($this->getPluginUrl());
        $this->addStyle('variables', $this->getCssVariables(), ['inline' => true]);
        $this->addViteAssets(['src/main.js']);

        HookRegistry::register('TemplateManager::display', [$this, 'addTemplateData']);
        HookRegistry::register('TemplateManager::display', [$this, 'displayTemplate'], HOOK_SEQUENCE_LAST);
    }

    public function getDisplayName()
    {
        return __('plugins.themes.tibTheme.name');
    }

    public function getDescription()
    {
        return __('plugins.themes.tibTheme.description');
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
    public function addTemplateData(string $hookName, array $args): bool
    {
        /** @var TemplateManager */
        $templateMgr = $args[0];
        $template = $args[1];
        $context = Application::get()->getRequest()->getContext();

        if (substr($template, 0, 8) !== 'frontend') {
            return false;
        }

        if ($context) {
            /** @var PartnerLogosPlugin */
            $partnerLogosPlugin = PluginRegistry::getPlugin('generic', 'partnerlogosplugin');
            if ($partnerLogosPlugin) {
                $templateMgr->assign('partnerLogos', $partnerLogosPlugin->getHtml($context));
            }
        }

        if ($template === 'frontend/pages/navigationMenuItemViewContent.tpl') {
            $this->addCustomPageData($templateMgr);
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
     * Add template data to custom pages
     *
     * Overrides the `$requestedOp` template variable in order to add a custom
     * class to the body tag. This class is necessary to target styles for a
     * custom page.
     *
     * These are custom pages created as a navigation menu item.
     *
     * Warning: in the default theme, the $requestedOp is only used in the header.
     * However, if the variable is used elsewhere, this override could cause
     * problems. This override should only occur on the custom nav menu pages.
     */
    protected function addCustomPageData(TemplateManager $templateMgr): void
    {
        $op = $templateMgr->get_template_vars('requestedOp');
        $templateMgr->assign([
            'requestedOp' => "$op tibop_custom_page",
        ]);
    }

    /**
     * Insert custom HTML into the rendered templates
     *
     * This callback intercepts and modifies the HTML rendered by
     * the template engine. No further callbacks will be fired
     * after this one, so it should always be registered as the
     * last callback.
     *
     * If possible, use self::addTemplateData() instead.
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
     * Add a theme option at site level to select which
     * contexts are conferences
     *
     * This theme option is used to adapt the text of the
     * buttons in the context link, so that it reflects
     * whether the context publishes journals or proceedings.
     */
    protected function addSiteConferencesOption(): void
    {
        $options = [];
        $user = Application::get()->getRequest()->getUser();

        if ($user) {
            $options = array_map(
                function(stdClass $context) {
                    return [
                        'value' => $context->id,
                        'label' => $context->name,
                    ];
                },
                Services::get('context')->getManySummary(['userId' => $user->getId()])
            );
        }

        $this->addOption('conferenceContexts', 'FieldOptions', [
            'label' => __('plugins.themes.tibTheme.option.conferenceContexts.label'),
            'type' => 'checkbox',
            'description' => __('plugins.themes.tibTheme.option.conferenceContexts.description'),
            'options' => $options,
            'default' => [],
        ]);
    }

    /**
     * Add a theme option at context level to select whether
     * or not to display partner logos in the footer
     */
    protected function addPartnerLogosOption(): void
    {
        $this->addOption('showPartnerLogos', 'FieldOptions', [
            'label' => __('plugins.themes.tibTheme.option.showPartnerLogos.label'),
            'type' => 'radio',
            'description' => __('plugins.themes.tibTheme.option.showPartnerLogos.description'),
            'options' => [
                [
                    'value' => 'show',
                    'label' => __('plugins.themes.tibTheme.option.showPartnerLogos.show'),
                ],
                [
                    'value' => 'hide',
                    'label' => __('plugins.themes.tibTheme.option.showPartnerLogos.hide'),
                ],
            ],
            'default' => 'show',
        ]);

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
        $menu = $this->getMenu('quicklinks', $contextId);

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
        $this->themeHelper = new TibThemeHelper(TemplateManager::getManager(Application::get()->getRequest()));
        $this->themeHelper->addCommonTemplatePlugins();
        $this->themeHelper->addTemplatePlugin(
            new TibThemeTemplatePlugin(
                type: 'function',
                name: 'load_menu',
                callback: [$this, 'loadMenu'],
                override: true
            )
        );
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
        $templateMgr = TemplateManager::getManager(
            Application::get()->getRequest()
        );

        $viteLoader = new TibThemeViteLoader(
            templateManager: $templateMgr,
            manifestPath: dirname(__FILE__) . '/dist/.vite/manifest.json',
            serverPath: join('/', [dirname(__FILE__), '.vite.server.json']),
            buildUrl: join('/', [$this->getPluginUrl(), 'dist/']),
            prefix: $this->getPluginPath(),
            theme: $this,
        );

        $viteLoader->load($entryPoints);
    }

    /**
     * Get CSS variables
     *
     * Returns CSS variables that are set differently based on the
     * theme options or whether it's a context or site page.
     */
    protected function getCssVariables(): string
    {
        $context = Application::get()->getRequest()->getContext();
        $isBaseColorDark = $this->isColourDark($this->getOption('baseColour'));

        $variables = [];

        $variables['--font'] = $context
            ? "'Hanken Grotesk', system-ui, -apple-system, BlinkMacSystemFont, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'"
            : 'var(--tibop-font)';


        if ($context) {
            $variables['--header-bg'] = $this->getOption('baseColour');
            $variables['--header-text'] = $isBaseColorDark ? "#fff" : 'rgba(0, 0, 0, 0.85)';
        } else {
            $variables['--tibop-header-bg'] = 'white';
            $variables['--tibop-header-text'] = 'rgba(0, 0, 0, 0.85)';
            $variables['--header-bg'] = 'white';
            $variables['--header-text'] = 'var(--tibop-header-text)';
            $variables['--button-radius'] = '9999px';
        }

        if ($isBaseColorDark) {
            $variables['--button-bg'] = $this->getOption('baseColour');
            $variables['--button-text'] = '#fff';
        } else {
            $variables['--button-bg'] = 'rgba(0, 0, 0, 0.85)';
            $variables['--button-text'] = $this->getOption('baseColour');
        }

        $output = [];
        foreach ($variables as $var => $val) {
            $output[] = "{$var}: {$val};";
        }

        return 'body {' . join("\n", $output) . '}';
    }

    /**
     * Load custom fonts
     *
     * @font-face definitions are loaded as inline stylesheet in order
     * to make the http requests for font files as soon as possible.
     * This reduces the delay for fonts to render on page load.
     */
    protected function addFonts(string $themeBaseUrl): void
    {
        $context = Application::get()->getRequest()->getContext();

        $this->addStyle(
            'font',
            join("\n", [
                $context ? $this->getContextFontFace($themeBaseUrl) : '',
                $this->getSiteFontFace($themeBaseUrl),
            ]),
            ['inline' => true]
        );
    }

    protected function getContextFontFace(string $themeBaseUrl): string
    {
        return "
@font-face {
  font-family: 'Hanken Grotesk';
  font-style: italic;
  font-weight: 100 900;
  font-display: swap;
  src: url({$themeBaseUrl}/fonts/hankengrotesk/ieVl2YZDLWuGJpnzaiwFXS9tYtpY19-7DRs5.woff2) format('woff2');
  unicode-range: U+0460-052F, U+1C80-1C88, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
}
@font-face {
  font-family: 'Hanken Grotesk';
  font-style: italic;
  font-weight: 100 900;
  font-display: swap;
  src: url({$themeBaseUrl}/fonts/hankengrotesk/ieVl2YZDLWuGJpnzaiwFXS9tYtpY1927DRs5.woff2) format('woff2');
  unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
}
@font-face {
  font-family: 'Hanken Grotesk';
  font-style: italic;
  font-weight: 100 900;
  font-display: swap;
  src: url({$themeBaseUrl}/fonts/hankengrotesk/ieVl2YZDLWuGJpnzaiwFXS9tYtpY19y7DRs5.woff2) format('woff2');
  unicode-range: U+0100-02AF, U+0304, U+0308, U+0329, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
@font-face {
  font-family: 'Hanken Grotesk';
  font-style: italic;
  font-weight: 100 900;
  font-display: swap;
  src: url({$themeBaseUrl}/fonts/hankengrotesk/ieVl2YZDLWuGJpnzaiwFXS9tYtpY19K7DQ.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
@font-face {
  font-family: 'Hanken Grotesk';
  font-style: normal;
  font-weight: 100 900;
  font-display: swap;
  src: url({$themeBaseUrl}/fonts/hankengrotesk/ieVn2YZDLWuGJpnzaiwFXS9tYtpQ59CjCQ.woff2) format('woff2');
  unicode-range: U+0460-052F, U+1C80-1C88, U+20B4, U+2DE0-2DFF, U+A640-A69F, U+FE2E-FE2F;
}
@font-face {
  font-family: 'Hanken Grotesk';
  font-style: normal;
  font-weight: 100 900;
  font-display: swap;
  src: url({$themeBaseUrl}/fonts/hankengrotesk/ieVn2YZDLWuGJpnzaiwFXS9tYtpS59CjCQ.woff2) format('woff2');
  unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
}
@font-face {
  font-family: 'Hanken Grotesk';
  font-style: normal;
  font-weight: 100 900;
  font-display: swap;
  src: url({$themeBaseUrl}/fonts/hankengrotesk/ieVn2YZDLWuGJpnzaiwFXS9tYtpT59CjCQ.woff2) format('woff2');
  unicode-range: U+0100-02AF, U+0304, U+0308, U+0329, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
@font-face {
  font-family: 'Hanken Grotesk';
  font-style: normal;
  font-weight: 100 900;
  font-display: swap;
  src: url({$themeBaseUrl}/fonts/hankengrotesk/ieVn2YZDLWuGJpnzaiwFXS9tYtpd59A.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
        ";
    }

    protected function getSiteFontFace(string $themeBaseUrl): string
    {
        return "
@font-face {
  font-family: 'Quicksand';
  font-style: normal;
  font-weight: 300 700;
  font-display: swap;
  src: url({$themeBaseUrl}/fonts/quicksand/6xKtdSZaM9iE8KbpRA_hJFQNcOM.woff2) format('woff2');
  unicode-range: U+0102-0103, U+0110-0111, U+0128-0129, U+0168-0169, U+01A0-01A1, U+01AF-01B0, U+0300-0301, U+0303-0304, U+0308-0309, U+0323, U+0329, U+1EA0-1EF9, U+20AB;
}
@font-face {
  font-family: 'Quicksand';
  font-style: normal;
  font-weight: 300 700;
  font-display: swap;
  src: url({$themeBaseUrl}/fonts/quicksand/6xKtdSZaM9iE8KbpRA_hJVQNcOM.woff2) format('woff2');
  unicode-range: U+0100-02AF, U+0304, U+0308, U+0329, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
}
@font-face {
  font-family: 'Quicksand';
  font-style: normal;
  font-weight: 300 700;
  font-display: swap;
  src: url({$themeBaseUrl}/fonts/quicksand/6xKtdSZaM9iE8KbpRA_hK1QN.woff2) format('woff2');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
        ";
    }
}
