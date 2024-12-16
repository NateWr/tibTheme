{**
 * Override default theme's footer template
 *
 * Items marked with <!-- --> comments refer to elements
 * that are required to remain compatible with the default
 * theme.
 *}
  </div><!-- pkp_structure_main -->
</div><!-- pkp_structure_content -->

<a id="pkp_content_footer"></a>
<footer class="tibop-footer-wrapper">

  {if $currentContext}
    <div class="tibop-footer">
      <div class="tibop-footer-title">
        {$currentContext->getLocalizedName()|escape}
      </div>
      <div class="tibop-footer-columns">
        <div class="tibop-footer-column tibop-footer-column-about">
          <div class="tibop-footer-desc">
            {if $pageFooter}
              {$pageFooter|strip_unsafe_html}
            {else}
              {$currentContext->getLocalizedData('description')|strip_unsafe_html}
            {/if}
          </div>
          {if $currentContext->getData('onlineIssn') || $currentContext->getData('printIssn')}
            <table class="tibop-footer-metadata">
              <tbody>
                {if $currentContext->getData('printIssn')}
                  <tr>
                    <th>ISSN</th>
                    <td>{$currentContext->getData('printIssn')}</td>
                  </tr>
                {/if}
                {if $currentContext->getData('onlineIssn')}
                  <tr>
                    <th>eISSN</th>
                    <td>{$currentContext->getData('onlineIssn')}</td>
                  </tr>
                {/if}
              </tbody>
            </table>
          {/if}
        </div>
        <div class="tibop-footer-column">
          {load_menu name="primary"}
        </div>
        <div class="tibop-footer-column">
          {load_menu name="user"}
        </div>
      </div>

      <!-- sidebar -->
      {capture assign="sidebarCode"}{call_hook name="Templates::Common::Sidebar"}{/capture}
      {if $sidebarCode}
        <div class="tibop-footer-columns tibop-sidebar" role="complementary" aria-label="{translate|escape key="common.navigation.sidebar"}">
          {$sidebarCode}
        </div>
      {/if}
    </div>
  {/if}

  {* TIB-OP, Policy and Partners *}
  <div class="
    tibop-footer-btm
    {if !$currentContext}
      tibop-footer-btm-site
    {/if}
  ">
    <div class="tibop-footer-service">
      <a
        class="tibop-footer-service-link"
        href="{url context="index"}"
      >
        {include file="frontend/tibop-logo.svg"}
      </a>
      {$tibopSitePolicyMenu}
    </div>
    {if $currentContext && $activeTheme->getOption('showPartnerLogos') === 'show'}
      <div class="tibop-footer-partners">
        <h2 class="pkp_screen_reader">
          {translate key="plugins.themes.tibTheme.partners"}
        </h2>
        {$partnerLogos}
      </div>
    {else}
      <a
        class="tibop-footer-ojs"
        href="{url page="about" op="aboutThisPublishingSystem"}"
      >
        <img
          alt="{translate key="about.aboutThisPublishingSystem"}"
          src="{$baseUrl}/{$brandImage}"
        >
      </a>
    {/if}
  </div>
</footer>

<!-- end of page -->
{load_script context="frontend"}
{call_hook name="Templates::Common::Footer::PageFooter"}
</body>
</html>