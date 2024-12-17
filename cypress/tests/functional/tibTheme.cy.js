describe("Enables TIB OP theme and dependencies", function () {
  it("Enables and selects the theme", function () {
    cy.login("admin", "admin", "publicknowledge");

    cy.get('a:contains("Website")').click();
    cy.get('button[id="plugins-button"]').click();

    // Find and enable the theme
    cy.get(
      'input[id^="select-cell-tibtheme-enabled"]'
    ).click();
    cy.get(
      "div:contains('The plugin \"TIB Open Publishing Theme\" has been enabled.')"
    );

    // Appearance tab does not get updated until the reload
    cy.reload();

    // Select the new theme
    cy.get('button[id="appearance-button"]').click();
    cy.get('select[id="theme-themePluginPath-control"]').select(
      "TIB Open Publishing Theme"
    );

    cy.get('#theme button:contains("Save")').click();
  });

  it("Tests the theme without any configuration", function () {
    cy.visit("/");
    cy.get(".tibop-footer-btm");
  });

  it("Enables the Partner Logos plugin", function() {
    cy.login("admin", "admin", "publicknowledge");

    cy.get('a:contains("Website")').click();
    cy.get('button[id="plugins-button"]').click();

    // Find and enable the Partner Logos plugin
    cy.get(
      'input[id^="select-cell-partnerlogosplugin-enabled"]'
    ).click();
    cy.get(
      "div:contains('The plugin \"Partner Logos\" has been enabled.')"
    );
  });

  it("Views the theme after enabling the Partner Logos plugin", function () {
    cy.visit("/");
    cy.get(".tibop-footer-btm");
  });
});

describe("Tests the core features", function() {

  it("Tests the language switcher", function() {
    cy.visit('/')
    cy.get('.tibop-header .tibop-locale:contains("Fr") abbr[title="Français (Canada)"]')
      .click()
    cy.get('.pkp_site_name:contains("Journal de la connaissance du public")')
    cy.get('.tibop-header .tibop-locale:contains("En") abbr[title="English"]')
      .click()
    cy.get('.pkp_site_name:contains("Journal of Public Knowledge")')
  })

  it("Tests the search bar", function() {
    cy.visit('/')
    cy.get('.tibop-header-search-wrapper input')
      .type('signalling{Enter}')
    cy.contains('Found one item.')
    cy.contains('The Signalling Theory Dividends')
  })

  it("Tests the user dropdown menu", function() {
    cy.visit('/')
    cy.get('#tibopUserDropdown')
      .click()
    cy.get('.tibop-header-account-wrapper .dropdown-menu a:contains("Login")')
      .click()
    cy.get('h1:contains("Login")')

    cy.login('admin', 'admin', 'publicknowledge')
    cy.visit('/')
    cy.get('#tibopUserDropdown')
      .click()
    cy.get('.tibop-header-account-wrapper .dropdown-menu a:contains("Dashboard")')
    cy.get('.tibop-header-account-wrapper:contains("Logged in as admin")')
  })

  it("Tests the custom footer", function() {
    cy.visit('/')
    cy.get('.tibop-footer-title:contains("Journal of Public Knowledge")')
    cy.get('.tibop-footer-metadata td:contains("0378-5955")')
    cy.get('.tibop-footer a:contains("Submissions")')
    cy.get('.tibop-footer-service-link')
    cy.get('.tibop-footer-partners')
  })

  it('Tests the mobile menu', function() {
    cy.viewport(360, 640)
    cy.visit('/')
    cy.get('.pkp_site_nav_toggle')
      .click()
    cy.get('.tibop-mobile-dropdown-link')
    cy.get('.tibop-mobile-dropdown .tibop-locale:contains("En") abbr[title="English"]')
  })
});

describe("Tests site-wide pages", function() {

  it('Creates a second journal', function() {
    cy.login("admin", "admin", "publicknowledge");
    cy.get('.app__navItem:contains("Administration")')
      .click()
    cy.get('a:contains("Hosted Journals")')
      .click()
    cy.get('a:contains("Create Journal")')
      .click()
    cy.wait(1000)
    cy.get('.pkp_modal_panel .header:contains("Create Journal")')
    cy.get('input[name^="name-en"]')
      .type('Second Journal')
    cy.get('input[name^="acronym-en"]')
      .type('sj')
    cy.get('input[name="urlPath"]')
      .type('sj')
    cy.get('input[name="supportedLocales"]')
      .check()
    cy.get('input[name="primaryLocale"]')
      .first()
      .check()
    cy.get('input[name="enabled"]')
      .check()
    cy.get('#editContext button:contains("Save")')
      .click()
  })

  it('Enables the theme at site level', function() {
    cy.login('admin', 'admin', 'publicknowledge')
    cy.get('.app__navItem:contains("Administration")')
      .click()
    cy.get('a:contains("Site Settings")')
      .click()
    cy.get('button[id="plugins-button"]')
      .click()
    cy.get('input[id^="select-cell-tibtheme-enabled"]')
      .click();
    cy.get("div:contains('The plugin \"TIB Open Publishing Theme\" has been enabled.')");
    cy.reload(); // Refresh appearance tab
    cy.get('button[id="appearance-button"]')
      .click();
    cy.get('select[id="theme-themePluginPath-control"]')
      .select("TIB Open Publishing Theme");
    cy.get('#theme button:contains("Save")').click();
  })

  it('Tests the theme without any configuration', function() {
    cy.visit('/')
    cy.get('.tibop-header-site')
    cy.get('.tibop-contexts h2:contains("Journals and Conference Proceedings")')
    cy.get('.tibop-footer-btm')
  })

  it('Tests the language switcher', function() {
    cy.visit('/')
    cy.get('.tibop-header .tibop-locale:contains("Fr") abbr[title="Français (Canada)"]')
      .click()
    cy.get('.tibop-context-title:contains("Journal de la connaissance du public")')
    cy.get('.tibop-header .tibop-locale:contains("En") abbr[title="English"]')
      .click()
    cy.get('.tibop-context-title:contains("Journal of Public Knowledge")')
  })

  it('Tests the user dropdown menu', function() {
    cy.visit('/')
    cy.get('#tibopUserDropdown')
      .click()
    cy.get('.tibop-header-account-wrapper .dropdown-menu a:contains("Login")')
      .click()
    cy.get('h1:contains("Login")')

    cy.login('admin', 'admin', 'publicknowledge')
    cy.visit('/')
    cy.get('#tibopUserDropdown')
      .click()
    cy.get('.tibop-header-account-wrapper .dropdown-menu a:contains("Dashboard")')
    cy.get('.tibop-header-account-wrapper:contains("Logged in as admin")')
  })

    it('Tests the about the site content', function() {
      const about = 'TIB Open Publishing is an open-access platform for publishing scientific journals and conference publications.'
      cy.login('admin', 'admin', 'publicknowledge')
      cy.get('.app__navItem:contains("Administration")')
        .click()
      cy.get('a:contains("Site Settings")')
        .click()
      cy.get('button:contains("Information")')
        .click()
      cy.setTinyMceContent('siteInfo-about-control-en_US', about);
  		cy.get('#siteInfo-about-control-en_US').click(); // Ensure blur event is fired
      cy.get('#info button:contains("Save")')
        .click();
      cy.visit('/')
      cy.get(`.tibop-about:contains("${about}")`)
    })
})