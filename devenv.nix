{ pkgs, lib, config, ... }:

let
  pcov = config.languages.php.package.buildEnv {
    extensions = { all, enabled }: with all; (builtins.filter (e: e.extensionName != "blackfire" && e.extensionName != "xdebug") enabled) ++ [config.languages.php.package.extensions.pcov];
    extraConfig = config.languages.php.ini;
  };
in {
  packages = [
    pkgs.jq
    ( pkgs.writeShellScriptBin "php-pcov" ''
      export PHP_INI_SCAN_DIR=''${PHP_INI_SCAN_DIR-'${pcov}/lib'}
      exec -a "$0" "${pcov}/bin/.php-wrapped"  "$@"
    '')
  ];

  languages.php = {
    enable = lib.mkDefault true;
    version = lib.mkDefault "8.3";

    ini = ''
      memory_limit = 2G
      realpath_cache_ttl = 3600
      display_errors = On
      error_reporting = E_ALL
      opcache.memory_consumption = 256M
      opcache.interned_strings_buffer = 20
      zend.assertions = 0
      short_open_tag = 0
      zend.detect_unicode = 0
      realpath_cache_ttl = 3600
      post_max_size = 32M
      upload_max_filesize = 32M
    '';
  };
}
