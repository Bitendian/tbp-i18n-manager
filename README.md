# i18n management package
This packages **is NOT** a requirement to use i18n with TBP. TBP supports i18n
by itself.

This package add some modules to help manage i18n static contents.

## Requirements
To run this package, there is some mandatory requirements:
- A database **must** exists
- Database **must** contains a table named *Languages*
- Table *Languages* **must** contains next columns:
    - *LanguageId* INTEGER NOT NULL
    - *Locale* VARCHAR(255) NOT NULL
    - *Name* VARCHAR(255) NOT NULL
    - *Active* INTEGER NOT NULL DEFAULT 0
    - *Default* INTEGER NOT NULL DEFAULT 0
