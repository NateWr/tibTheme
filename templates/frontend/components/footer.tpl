{**
 * Override default theme's footer template
 *
 * Items marked with <!-- --> comments refer to elements
 * that are required to remain compatible with the default
 * theme.
 *}
  </div><!-- pkp_structure_main -->
</div><!-- pkp_structure_content -->

<div class="tibop-footer">
  {if $currentContext}
    <div class="tibop-footer-title">
      {$currentContext->getLocalizedName()|escape}
    </div>
    <div class="tibop-footer-columns">
      <div class="tibop-footer-column">
        {if $pageFooter}
          {$pageFooter|strip_unsafe_html}
        {else}
          {$currentContext->getLocalizedData('description')|strip_unsafe_html}
        {/if}
        <div class="tibop-footer-issn">
          eISSN: ...
        </div>
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
  {/if}

  {* TIB-OP, Policy and Partners *}
  <div class="tibop-footer-btm">
    {$tibopSitePolicyMenu}
  </div>
</div>

<!-- end of page -->
{load_script context="frontend"}
{call_hook name="Templates::Common::Footer::PageFooter"}
</body>
</html>