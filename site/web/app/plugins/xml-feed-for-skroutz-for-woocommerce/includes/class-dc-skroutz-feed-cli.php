<?php

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) return;

class Dicha_Skroutz_Feed_WP_Cli {

	/**
	 * CLI command to generate the Skroutz XML file.
	 *
	 * ## OPTIONS
	 *
	 * [--type=<type>]
	 * : (Optional) The type of XML file to generate, for example "skroutz".
	 * If no type provided, the default type is "skroutz".
	 *
	 * ## EXAMPLES
	 *
	 *     wp dicha-skroutz-xml generate --type=skroutz
	 *
	 * @param array $args
	 * @param array $flags
	 */
	public function generate( array $args, array $flags ) {

		$feed_type = $flags['type'] ?? 'skroutz';

		$result = $this->generate_xml( $feed_type );

		if ( $result ) {
			WP_CLI::success( "XML file generated successfully for '$feed_type'." );
		}
		else {
			WP_CLI::error( "Failed to generate XML file." );
		}
	}


	/**
	 * Handles the XML file generation.
	 *
	 * @param string $feed_type The type of XML to generate.
	 *
	 * @return bool True if the XML was generated successfully, false otherwise.
	 */
	private function generate_xml( string $feed_type ): bool {

		$xml_creator = new Dicha_Skroutz_Feed_Creator( $feed_type );

		return $xml_creator->create_feed();
	}
}


/**
 * Register the main WP CLI command for our plugin.
 */
WP_CLI::add_command( 'dicha-skroutz-xml', 'Dicha_Skroutz_Feed_WP_Cli' );