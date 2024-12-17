<?php
namespace APP\plugins\themes\tibTheme\classes;

use APP\core\Application;
use APP\template\TemplateManager;
use Exception;
use PKP\facades\Locale;
use PKP\i18n\LocaleMetadata;
use PKP\plugins\Hook;

/**
 * A helper class for building custom themes for
 * PKP's scholarly publishing software (OJS, OMP,
 * and OPS).
 *
 * Typically used to register helper functions
 * with the TemplateManager, for use in .tpl files.
 *
 * The TemplateManager class is an instance of the
 * Smarty templating library.
 *
 * @see https://www.smarty.net
 */
class ThemeHelper
{
    /**
     * Number of page buttons to display before truncating
     * the list of pages
     */
    public const DEFAULT_MAX_PAGES = 9;

    /**
     * @var TemplatePlugin[]
     */
    protected array $templatePlugins = [];

    public function __construct(
        protected TemplateManager $templateMgr
    ) {
        $this->templateMgr = $templateMgr;
        Hook::add('TemplateManager::display', [$this, 'registerTemplatePlugins']);
    }

    /**
     * Register common helper functions with the Smarty
     * template manager
     *
     * @param TemplateManager $templateMgr
     */
    public function addCommonTemplatePlugins(): void
    {
        $this->addTemplatePlugin(
            new TemplatePlugin(
                type: 'function',
                name: 'th_locales',
                callback: [$this, 'setLocales']
            )
        );
    }

    /**
     * Add a template plugin
     */
    public function addTemplatePlugin(TemplatePlugin $plugin): void
    {
        $this->templatePlugins[] = $plugin;
    }

    /**
     * Register template plugins
     *
     * This method is called with the TemplateManager::display
     * hook in order to ensure that the template plugins are
     * registered after all core plugins have been registered.
     *
     * This allows core plugins to be overridden.
     */
    public function registerTemplatePlugins(string $hookName, array $args): bool
    {
        foreach ($this->templatePlugins as $plugin) {
            $this->safeRegisterTemplatePlugin($plugin);
        }
        return false;

    }

    /**
     * Register a smarty plugin safely
     *
     * This wrapper function prevents a fatal error if a smarty plugin
     * with the same name has already been registered.
     */
    protected function safeRegisterTemplatePlugin(TemplatePlugin $plugin): void
    {
        $registered = isset($this->templateMgr->registered_plugins[$plugin->type][$plugin->name]);
        if ($registered && $plugin->override) {
            $this->templateMgr->unregisterPlugin($plugin->type, $plugin->name);
            $this->templateMgr->registerPlugin($plugin->type, $plugin->name, $plugin->callback);
        } elseif (!$registered) {
            $this->templateMgr->registerPlugin($plugin->type, $plugin->name, $plugin->callback);
        }
    }

    /**
     * Set the locales supported by the journal or site
     *
     * @example {th_locales
     *   assign="languages"
     * }
     * @param array $params
     *   @option string assign Variable to assign the result to
     */
    public function setLocales(array $params, $smarty): void
    {
        if (!$this->hasParams($params, ['assign'], 'th_locales')) {
            return;
        }

        $request = Application::get()->getRequest();
        $context = $request->getContext();

        $locales = Locale::getFormattedDisplayNames(
            isset($context)
                ? $context->getSupportedLocales()
                : $request->getSite()->getSupportedLocales(),
            Locale::getLocales(),
            LocaleMetadata::LANGUAGE_LOCALE_ONLY
        );

        $smarty->assign($params['assign'], $locales);
    }

    /**
     * Throw an exception if any of the required parameters
     * are missing from the array.
     *
     * Expects params a [key => value] map that is part
     * of all custom Smarty functions.
     *
     * @param array $params A [key => value] map. Typically c
     * @param string[] $requiredParams List of required param keys.
     * @throws Exception
     */
    public function hasParams(array $params, array $requiredParams, string $function): bool
    {
        foreach ($requiredParams as $requiredParam) {
            if (empty($params[$requiredParam])) {
                $exampleParams = join(
                  " ",
                  array_map(fn($param) => "{$param}=\"...\"", $requiredParams)
                );
                throw new Exception(
                    "Call to {{$function} without the required `{$requiredParam}` parameter. Usage: {{$function} {$exampleParams}}"
                );
            }
        }
        return true;
    }
}