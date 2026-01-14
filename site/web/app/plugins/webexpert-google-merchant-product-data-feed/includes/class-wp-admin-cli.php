<?php
class WEGoogleXML_Admin_CLI extends WP_CLI_Command {
    public function generate( $args, $assoc_args ) {
        WP_CLI::log( 'Web Expert Google Merchant XML is generating...' );
        require "scripts/google-merchant-engine.php";
        WP_CLI::log( 'Web Expert Google Merchant generation has finished.' );
    }
}