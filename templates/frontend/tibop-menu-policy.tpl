{if $navigationMenu}
	<ul class="tibop-menu-policy">
		{foreach key="field" item="navigationMenuItemAssignment" from=$navigationMenu->menuTree}
			{if !$navigationMenuItemAssignment->navigationMenuItem->getIsDisplayed()}
				{continue}
			{/if}
			<li>
				<a href="{$navigationMenuItemAssignment->navigationMenuItem->getUrl()}">
					{$navigationMenuItemAssignment->navigationMenuItem->getLocalizedTitle()}
				</a>
			</li>
		{/foreach}
	</ul>
{/if}
