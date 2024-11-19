{**
 * Top header for TIB-OP
 *
 * Includes the TIB-OP header, user nav menu, language selection
 * and search input.
 *
 * Only visible on large screens.
 *}
{capture assign="userMenu"}{strip}{load_menu name="user" id="nav-desktop-user" ulClass="tibop-header-account-menu"}{/strip}{/capture}

<div
  class="
    tibop-header
    {**
     * Add flag if this is the site level
     *
     * This flag is a hack that allows us to apply CSS
     * to the default theme header at the site level.
     *}
    {if !$currentContext}
      tibop-header-site
    {/if}
  "
>
  <a
    class="tibop-header-link"
    href="{url context="index"}"
  >
    {include file="frontend/tibop-logo.svg"}
  </a>
  <div class="tibop-header-right">
    {if $currentContext}
      <form
        action="{url page="search"}"
        class="tibop-header-search"
        method="GET"
      >
        <label class="tibop-header-search-wrapper">
          <span class="pkp_screen_reader">
            {translate key="common.search"}
          </span>
          {include file="frontend/icons/manage-search.svg"}
          <input
            type="search"
            name="query"
            placeholder="{translate key="common.search"}"
          >
        </label>
        <button
          class="pkp_screen_reader"
          type="submit"
        >
          {translate key="common.search"}
        </button>
      </form>
    {/if}
    {include file="frontend/tibop-locales.tpl"}
    {if $userMenu}
      <div class="tibop-header-account-wrapper">
        <button
          aria-expanded="false"
          aria-haspopup="true"
          class="dropdown-toggle"
          data-toggle="dropdown"
          id="tibopUserDropdown"
        >
          <span class="pkp_screen_reader">
            {translate key="plugins.themes.tibOPTheme.account"}
          </span>
          {include file="frontend/icons/user.svg"}
        </button>
        <div
          aria-labelledby="tibopUserDropdown"
          class="dropdown-menu dropdown-menu-right"
        >
          {load_menu name="user" id="nav-desktop-user"}
          {if $isUserLoggedIn}
            <hr class="dropdown-divider">
            <div class="tibop-header-logged-in-as">
              {translate key="plugins.themes.tibOPTheme.loggedInAs" username=$currentUser->getData('username')}
              <a
                {if $isUserLoggedInAs}
                  href="{url page="login" op="signOutAsUser"}"
                {else}
                  href="{url page="login" op="signOut"}"
                {/if}
                class="tibop-link"
              >
                {translate key="user.logOut"}
              </a>
            </div>
          {/if}
        </div>
      </div>
    {/if}
  </div>
</div>