{**
 * Site index page
 *
 * Overrides default site index page in order to use
 * distinct language for journals and conference proceedings.
 *}
{include file="frontend/components/header.tpl"}

<div class="page page_index_site tibop-page">

  {if $about}
    <div class="tibop-about-wrapper">
      <div class="tibop-about-line" aria-hidden="true">
        {include file="frontend/tibop-lines.svg"}
      </div>
      <div class="tibop-about-content">
        <div class="tibop-about">
          {$about}
        </div>
        {load_menu name="quicklinks" id="tibop-quicklinks" ulClass="tibop-quicklinks"}
      </div>
      <div class="tibop-about-line" aria-hidden="true">
        {include file="frontend/tibop-lines.svg"}
      </div>
    </div>
  {/if}

  <div class="tibop-contexts">
    <h2>
      {translate key="plugins.themes.tibOPTheme.contexts"}
    </h2>
    {if $journals|@count}
      <ul class="tibop-contexts-list">
        {foreach from=$journals item="context"}
          {capture assign="url"}{url context=$context->getPath()}{/capture}
          {assign var="description" value=$context->getLocalizedDescription()}
          <li class="tibop-context">
            {include file="frontend/tibop-lines.svg"}
            <h3 class="tibop-context-title">
              <a href="{$url}">
                {$context->getLocalizedName()|escape}
              </a>
            </h3>
            {if $context->getLocalizedDescription()}
              <div class="tibop-context-desc">
                {$context->getLocalizedDescription()|strip_unsafe_html}
              </div>
            {/if}
            <ul class="tibop-context-links">
              <li class="tibop-context-view">
                <a href="{$url}">
                  {translate key="site.journalView"}
                </a>
              </li>
              <li class="tibop-context-current">
                <a href="{url context=$context->getPath() page="issue" op="current"}">
                  {translate key="site.journalCurrent"}
                </a>
              </li>
            </ul>
          </li>
        {/foreach}
      </ul>
    {/if}
  </div>

</div><!-- .page -->

{include file="frontend/components/footer.tpl"}