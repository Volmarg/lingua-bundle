# Lingua Bundle

Handles "language" processing. For example checking in which language is given text, or what for a **human** languages mentioned in text etc.

# Important

- If this package will be used in project which executes this bundle code via `supervisor` then this is a MUST in conf file
  - `environment=LC_ALL='en_US.UTF-8',LANG='en_US.UTF-8'`
    - **Reason:** language detecting fails, iso code is getting used instead of full language name