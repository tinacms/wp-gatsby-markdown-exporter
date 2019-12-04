<?php
/**
 * Plugin Name:     WordPress to Gatsby Exporter
 * Plugin URI:      https://github.com/tinacms/wp-gatsby-exporter
 * Description:     Export WordPress content to Markdown for GatsbyJS
 * Author:          Mitch MacKenzie
 * Author URI:      https://www.tinacms.org
 * Text Domain:     gatsby-exporter
 * Version:         0.1.0
 *
 * @package         GatsbyExporter
 */

/**
 * Main plugin file
 */

require_once dirname( __FILE__ ) . '/vendor/autoload.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	$command_info = array(
		'shortdesc' => 'Export WordPress content to Markdown for GatsbyJS.',
		'synopsis'  => array(
			array(
				'type'        => 'assoc',
				'name'        => 'directory',
				'description' => 'The export output directory. Defaults to a random temp directory.',
				'optional'    => true,
			),
			array(
				'type'        => 'assoc',
				'name'        => 'post_types',
				'description' => 'List of post types to include in the export.',
				'optional'    => true,
			),
			array(
				'type'        => 'assoc',
				'name'        => 'post_status',
				'description' => 'Post status to include in the export, see see: https://developer.wordpress.org/reference/classes/wp_query/#status-parameters .',
				'optional'    => true,
			),
			array(
				'type'        => 'assoc',
				'name'        => 'post_date_format',
				'description' => 'Set the format for post publish date, see: https://www.php.net/manual/en/function.date.php .',
				'optional'    => true,
			),
			array(
				'type'        => 'assoc',
				'name'        => 'excluded_front_matter',
				'description' => 'List of front matter fields to exclude from the exported Markdown.',
				'optional'    => true,
			),
			array(
				'type'        => 'assoc',
				'name'        => 'fields_to_markdown',
				'description' => 'List of front matter fields that should be converted to markdown.',
				'optional'    => true,
			),
			array(
				'type'        => 'assoc',
				'name'        => 'upload_dir',
				'description' => 'Directory in the export to copy WordPress uploads.',
				'optional'    => true,
			),
			array(
				'type'        => 'assoc',
				'name'        => 'remap_fields',
				'description' => 'Front matter field names to remap.',
				'optional'    => true,
			),
			array(
				'type'        => 'assoc',
				'name'        => 'fields_to_array',
				'description' => 'Front matter fields to convert to arrays.',
				'optional'    => true,
			),
			array(
				'type'        => 'flag',
				'name'        => 'skip_original_images',
				'optional'    => true,
				'description' => 'If image references should not use original file, rather the WordPress resized image.',
			),
			array(
				'type'        => 'flag',
				'name'        => 'skip_copy_uploads',
				'optional'    => true,
				'description' => 'If WordPress uploads should not be copied to the Gatsby destination directory.',
			),
			array(
				'type'        => 'flag',
				'name'        => 'skip_enforce_charset',
				'optional'    => true,
				'description' => 'If DOMDocument should not use the blog_charset for encoding.',
			),
		),
	);
	WP_CLI::add_command( 'gatsby-export', 'Gatsby_Exporter_CLI', $command_info );
}
