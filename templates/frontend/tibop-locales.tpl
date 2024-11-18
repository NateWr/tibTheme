{th_locales assign="tibopLocales"}
{if $tibopLocales|count > 1}
  <div class="tibop-locales">
    {foreach key="localeKey" item="name" from=$tibopLocales}
      <a
        class="
          tibop-locale
          {if $currentLocale === $localeKey}
            tibop-locale-current
          {/if}
        "
        href="{url router=$smarty.const.ROUTE_PAGE page="user" op="setLocale" path=$localeKey source=$smarty.server.REQUEST_URI}"
      >
        <abbr title="{$name|escape}">
          {$name|substr:0:2}
        </abbr>
      </a>
    {/foreach}
  </div>
{/if}