<?php
/**
 * CLI class.
 *
 * @package    GatsbyMarkdownExporter
 */

/**
 * Class Gatsby_Markdown_Exporter_CLI
 */
class Gatsby_Markdown_Exporter_CLI extends WP_CLI_Command {
	/**
	 * Invoke command.
	 *
	 * @param array $args command arguments.
	 * @param array $assoc_args associative array of command arguments.
	 *
	 * @throws \WP_CLI\ExitException Exit on error.
	 */
	public function __invoke( $args, $assoc_args ) {
		global $wp_filesystem;
		WP_Filesystem();

		if ( ! isset( $assoc_args['directory'] ) ) {
			$directory = get_temp_dir() . md5( time() );
			$wp_filesystem->mkdir( $directory );
		} else {
			$directory = $assoc_args['directory'];
		}

		$directory = trailingslashit( $directory );

		$exists = is_dir( $directory );
		if ( ! $exists ) {
			WP_CLI::error( 'The target directory does not exist' );
		}

		$writable = is_writable( $directory );
		if ( ! $writable ) {
			WP_CLI::error( 'The target directory cannot be written to' );
		}

		$exporter = new Gatsby_Markdown_Exporter( $directory );

		if ( isset( $assoc_args['post_types'] ) ) {
			$exporter->set_post_types( array_map( 'trim', explode( ',', $assoc_args['post_types'] ) ) );
		}

		if ( isset( $assoc_args['post_status'] ) ) {
			$exporter->set_post_status( $assoc_args['post_status'] );
		}

		if ( isset( $assoc_args['post_date_format'] ) ) {
			$exporter->set_post_date_format( $assoc_args['post_date_format'] );
		}

		if ( isset( $assoc_args['excluded_front_matter'] ) ) {
			$exporter->set_excluded_front_matter( array_map( 'trim', explode( ',', $assoc_args['excluded_front_matter'] ) ) );
		}

		if ( isset( $assoc_args['fields_to_markdown'] ) ) {
			$exporter->set_fields_to_markdown( array_map( 'trim', explode( ',', $assoc_args['fields_to_markdown'] ) ) );
		}

		if ( isset( $assoc_args['fields_to_array'] ) ) {
			$exporter->set_fields_to_array( array_map( 'trim', explode( ',', $assoc_args['fields_to_array'] ) ) );
		}

		if ( isset( $assoc_args['remap_fields'] ) ) {
			$remap_fields = array();
			$sets         = explode( ';', $assoc_args['remap_fields'] );
			foreach ( $sets as $set ) {
				$remap                             = explode( ',', $set );
				$remap_fields[ trim( $remap[0] ) ] = trim( $remap[1] );
			}
			$exporter->set_remap_fields( $remap_fields );
		}

		if ( isset( $assoc_args['upload_dir'] ) ) {
			$exporter->set_destination_upload_dir( $assoc_args['upload_dir'] );
		}

		if ( isset( $assoc_args['skip_copy_uploads'] ) ) {
			$exporter->set_copy_uploads( false );
		}

		if ( isset( $assoc_args['skip_original_images'] ) ) {
			$exporter->set_original_images( false );
		}

		if ( isset( $assoc_args['skip_enforce_charset'] ) ) {
			$exporter->set_enforce_charset( false );
		}

		if ( isset( $assoc_args['create_type_directory'] ) ) {
			$exporter->set_create_type_directory( true );
		}

		if ( isset( $assoc_args['include_private_fields'] ) ) {
			$exporter->set_included_private_post_meta( array_map( 'trim', explode( ',', $assoc_args['include_private_fields'] ) ) );
		}

		/* translators: %s: destination directory for export */
		WP_CLI::line( sprintf( __( 'Exporting to: %s', 'gatsby-markdown-exporter' ), $directory ) );

		$exporter->prepare();
		$total_posts = $exporter->get_post_count();

		/* translators: %d: number of posts that will be exported */
		WP_CLI::line( sprintf( __( 'Total content count to export: %d', 'gatsby-markdown-exporter' ), $total_posts ) );

		$progress = \WP_CLI\Utils\make_progress_bar( __( 'Exporting content', 'gatsby-markdown-exporter' ), $total_posts );

		for ( $i = 0; $i < $total_posts; $i++ ) {

			$exported = $exporter->export_next();

			if ( ! is_null( $exported['exception'] ) && $exported['exception']->getMessage() === 'Invalid HTML was provided' ) {
				/* translators: %s: file that was not converted to Markdown  */
				WP_CLI::warning( sprintf( __( 'The Markdown converter encountered invalid HTML and could not convert the content at: %s', 'gatsby-markdown-exporter' ), $exported['destination'] ) );
			}

			$progress->tick();
		}
		$progress->finish();

	}
}
