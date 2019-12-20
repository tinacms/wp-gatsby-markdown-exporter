<?php
/**
 * Exporter class.
 *
 * @package    GatsbyExporter
 */

use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Gatsby_Exporter
 */
class Gatsby_Exporter {
	/**
	 * Export destination directory.
	 *
	 * @var string
	 */
	protected $directory;

	/**
	 * Array of posts to be exported.
	 *
	 * @var array
	 */
	protected $posts = array();

	/**
	 * Array of post types to include in the export, see: https://developer.wordpress.org/reference/classes/wp_query/#post-type-parameters .
	 *
	 * @var array
	 */
	protected $post_types = array( 'page', 'post' );

	/**
	 * Post status to include in the export, see see: https://developer.wordpress.org/reference/classes/wp_query/#status-parameters .
	 *
	 * @var string
	 */
	protected $post_status = 'any';

	/**
	 * Front matter fields to exclude from the exported file.
	 *
	 * @var array
	 */
	protected $excluded_front_matter = array();

	/**
	 * The format for post publish date, see: https://www.php.net/manual/en/function.date.php .
	 *
	 * @var string
	 */
	protected $post_date_format = 'c';

	/**
	 * If WordPress uploads should be copied to the Gatsby destination directory.
	 *
	 * @var bool
	 */
	protected $copy_uploads = true;

	/**
	 * If DOMDocument should use the blog_charset for encoding.
	 *
	 * @var bool
	 */
	protected $enforce_charset = true;

	/**
	 * If image references should use original file, not the WordPress resized image.
	 *
	 * @var bool
	 */
	protected $use_original_images = true;


	/**
	 * Tags to remove from the source content if they immediately wrap an image.
	 *
	 * @var array
	 */
	protected $remove_image_wrappers = array( 'figure' );

	/**
	 * Front matter fields that should be converted to markdown.
	 *
	 * @var array
	 */
	protected $fields_to_markdown = array( 'excerpt' );

	/**
	 * Directory in the export to copy WordPress uploads.
	 *
	 * @var string
	 */
	protected $destination_upload_dir = 'uploads';

	/**
	 * Front matter field names to remap.
	 *
	 * @var array
	 */
	protected $remap_fields = array();

	/**
	 * Front matter fields to convert to arrays.
	 *
	 * @var array
	 */
	protected $fields_to_array = array();

	/**
	 * Create directories for each post type.
	 *
	 * @var bool
	 */
	protected $create_type_directory = false;

	/**
	 * The Markdown to HTML converter object.
	 *
	 * @var HtmlConverter
	 */
	protected $converter;

	/**
	 * WordPress upload directory information.
	 *
	 * @var array
	 */
	protected $upload_dir;

	/**
	 * Constructor.
	 *
	 * @param string $directory destination directory for export.
	 */
	public function __construct( $directory ) {
		$this->directory  = $directory;
		$this->converter  = new HtmlConverter();
		$this->upload_dir = wp_upload_dir();
	}

	/**
	 * Populate posts array and prepare directories for export.
	 */
	public function prepare() {
		$this->posts = $this->get_posts();
		$this->prepare_directories();
		if ( $this->copy_uploads ) {
			$this->copy_media();
		}
	}

	/**
	 * Return the number of posts to be exported.
	 *
	 * @return int
	 */
	public function get_post_count() {
		return count( $this->posts );
	}

	/**
	 * Export the next post in the posts array.
	 *
	 * @return array
	 */
	public function export_next() {
		$post_id = array_shift( $this->posts );
		return $this->export_file( $post_id );
	}

	/**
	 * Prepare the destination directories.
	 */
	protected function prepare_directories() {
		global $wp_filesystem;
		WP_Filesystem();

		if ( $this->create_type_directory ) {
			foreach ( $this->post_types as $type ) {
				$wp_filesystem->mkdir( $this->directory . $type );
			}
		}

		if ( $this->copy_uploads ) {
			$wp_filesystem->mkdir( $this->directory . $this->destination_upload_dir );
		}
	}

	/**
	 * Copy uploads out of the WordPress upload directory.
	 */
	protected function copy_media() {
		$upload_base = $this->upload_dir['basedir'];
		$this->copy_recursive( $upload_base, $this->directory . $this->destination_upload_dir );
	}

	/**
	 * Export a single post to Markdown.
	 *
	 * @param int $post_id post id for the exported post.
	 *
	 * @return array
	 */
	protected function export_file( $post_id ) {
		$doc = new DOMDocument();

		$filesystem = new Filesystem();

		$post = get_post( $post_id );

		if ( $this->create_type_directory ) {
			$destination = get_post_type( $post ) . '/' . $this->get_file_name( $post );
		} else {
			$destination = $this->get_file_name( $post );
		}

		$front_matter = $this->get_front_matter( $post, $destination );
		$yaml         = Yaml::dump( $front_matter, 2, 4, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE );

		$raw_body = $this->get_body( $post );

		// This may not be reliable; needs research.
		$xml_encoding = '';
		if ( $this->enforce_charset ) {
			$xml_encoding = '<?xml encoding="' . get_option( 'blog_charset' ) . '">';
		}

		$doc->loadHTML( $xml_encoding . $raw_body );
		$xpath = new DOMXpath( $doc );

		// What about other potential urls, like relative /wp-content/uploads, etc
		// What about other elements?
		$images = $xpath->query( '//img[starts-with(@src, "' . $this->upload_dir['baseurl'] . '")]' );

		foreach ( $images as $image ) {

			$current_src = str_replace( $this->upload_dir['baseurl'], '', $image->getAttribute( 'src' ) );
			if ( $this->use_original_images ) {
				$current_src = $this->get_original_image( $current_src, $this->upload_dir['basedir'] );
			}

			$relative_url = rtrim( $filesystem->makePathRelative( $this->destination_upload_dir . $current_src, $destination ), '/' );
			$image->setAttribute( 'src', $relative_url );

			if ( count( $this->remove_image_wrappers ) ) {
				// phpcs:disable WordPress.NamingConventions.ValidVariableName
				$parent = $image->parentNode;
				if ( in_array( $parent->nodeName, $this->remove_image_wrappers, true ) && 1 === $parent->childNodes->length ) {
					$parent->parentNode->replaceChild( $image, $parent );
				}
				// phpcs:enable
			}
		}
		$body = $doc->saveHTML();

		$body = apply_filters( 'gatsby_exporter_post_html_body', $body );

		// Crude removal of unwanted tags.
		$body      = str_replace( $xml_encoding . '<html><body>', '', $body );
		$exception = null;

		try {
			// @TODO: line breaks getting removed between img and blockquote for example.
			$converted_body = $this->converter->convert( $body );
		} catch ( Exception $e ) {
			$exception      = $e;
			$converted_body = $body;
		}

		$converted_body = apply_filters( 'gatsby_exporter_post_converted_body', $converted_body );

		$content = "---\n" . $yaml . "---\n" . $converted_body;

		$this->write_file( $content, $this->directory . $destination );
		return array(
			'destination' => $destination,
			'exception'   => $exception,
		);

	}

	/**
	 * Remove the size parameters from the image file.
	 *
	 * @param string $file image file name.
	 * @param string $path image file path.
	 *
	 * @return string
	 */
	protected function get_original_image( $file, $path ) {
		if ( preg_match( '/-\d+x\d+\./', $file ) === 1 ) {
			$original = preg_replace( '/-\d+x\d+\./', '.', $file );
			if ( file_exists( $path . $original ) ) {
				return $original;
			}
		}
		return $file;
	}

	/**
	 * Generate the file name for the exported post.
	 *
	 * @param WP_Post $post exported post.
	 *
	 * @return string
	 */
	protected function get_file_name( $post ) {
		$file_name  = str_replace( home_url(), '', rtrim( get_permalink( $post ), '/' ) );
		$file_name  = str_replace( '/index.php', '', $file_name );
		$file_name .= '.md';

		$file_name = ltrim( $file_name, '/' );

		if ( strpos( $file_name, '/' ) !== false ) {
			return dirname( $file_name ) . '/' . sanitize_file_name( basename( $file_name ) );
		}

		return sanitize_file_name( $file_name );
	}

	/**
	 * Get the filtered body of the post.
	 *
	 * @param WP_Post $post exported post.
	 *
	 * @return string
	 */
	protected function get_body( $post ) {
		return apply_filters( 'the_content', $post->post_content );
	}

	/**
	 * Write a file to the export destination.
	 *
	 * @param string $content Markdown content to save.
	 * @param string $destination full destination path for file.
	 */
	protected function write_file( $content, $destination ) {
		global $wp_filesystem;

		wp_mkdir_p( dirname( $destination ) );
		$wp_filesystem->put_contents( $destination, $content );
	}

	/**
	 * Get the front matter fields to export.
	 *
	 * @param WP_Post $post post to export.
	 * @param string  $destination the post destination path.
	 *
	 * @return array
	 */
	protected function get_front_matter( $post, $destination ) {

		$meta = array(
			'title'     => get_the_title( $post ),
			'date'      => get_the_date( $this->post_date_format, $post ),
			'status'    => get_post_status( $post ),
			'permalink' => str_replace( home_url(), '', rtrim( get_permalink( $post ), '/' ) ),
			'author'    => get_userdata( $post->post_author )->display_name,
			'excerpt'   => $post->post_excerpt,
			'type'      => get_post_type( $post ),
			'id'        => $post->ID,
		);

		if ( has_post_thumbnail( $post ) ) {
			$thumb = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ) );
			if ( $thumb ) {
				// @TODO: make this reusable.
				$filesystem = new Filesystem();
				$thumb      = str_replace( $this->upload_dir['baseurl'], '', $thumb[0] );
				if ( $this->use_original_images ) {
					$thumb = $this->get_original_image( $thumb, $this->upload_dir['basedir'] );
				}
				$meta['thumbnail'] = rtrim( $filesystem->makePathRelative( $this->destination_upload_dir . $thumb, $destination ), '/' );
			}
		}

		$terms = $this->get_post_terms( $post );
		$meta  = array_merge( $meta, $terms );

		$custom_meta = get_post_custom( $post->ID );
		foreach ( $custom_meta as $key => $value ) {
			if ( substr( $key, 0, 1 ) === '_' ) {
				continue;
			}
			// @TODO: Should these be flattened from arrays? seems to be advanced custom fields thing?
			$meta[ $key ] = $value;
		}

		// Remove excluded fields.
		if ( count( $this->excluded_front_matter ) ) {
			$meta = array_diff_key( $meta, array_flip( $this->excluded_front_matter ) );
		}

		// Optionally convert fields to markdown.
		if ( count( $this->fields_to_markdown ) ) {
			foreach ( $this->fields_to_markdown as $key ) {
				// @TODO: These could be arrays.
				if ( ! is_array( $meta[ $key ] ) ) {
					$meta[ $key ] = $this->converter->convert( $meta[ $key ] );
				}
			}
		}

		// Convert to array.
		if ( count( $this->fields_to_array ) ) {
			foreach ( $this->fields_to_array as $array_field ) {
				if ( isset( $meta [ $array_field ] ) ) {
					$meta [ $array_field ] = array( $meta[ $array_field ] );
				}
			}
		}

		// Remap field names.
		if ( count( $this->remap_fields ) ) {
			foreach ( $this->remap_fields as $find => $replace ) {
				if ( isset( $meta[ $find ] ) ) {
					$meta[ $replace ] = $meta[ $find ];
					unset( $meta[ $find ] );
				}
			}
		}

		$meta = apply_filters( 'gatsby_exporter_post_meta', $meta );
		return $meta;
	}

	/**
	 * Get the taxonomy terms for a post.
	 *
	 * @param WP_Post $post post to export.
	 *
	 * @return array
	 */
	protected function get_post_terms( $post ) {
		$taxonomies = get_object_taxonomies( $post->post_type, 'objects' );

		$all_terms = array();

		foreach ( $taxonomies as $taxonomy_slug => $taxonomy ) {

			$terms = get_the_terms( $post->ID, $taxonomy_slug );

			if ( 'post_tag' === $taxonomy_slug ) {
				$taxonomy_slug = 'tag';
			}

			$all_terms[ $taxonomy_slug ] = array();
			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$all_terms[ $taxonomy_slug ][] = $term->name;
				}
			}
		}

		return $all_terms;
	}

	/**
	 * Set the post types to export, see: https://developer.wordpress.org/reference/classes/wp_query/#post-type-parameters .
	 *
	 * @param string $types post types to export.
	 */
	public function set_post_types( $types ) {
		$this->post_types = $types;
	}

	/**
	 * Set the post status for content to export, see: https://developer.wordpress.org/reference/classes/wp_query/#status-parameters .
	 *
	 * @param string $status post status to include in export.
	 */
	public function set_post_status( $status ) {
		$this->post_status = $status;
	}

	/**
	 * Set the format for post publish date, see: https://www.php.net/manual/en/function.date.php .
	 *
	 * @param string $format the date format to export.
	 */
	public function set_post_date_format( $format ) {
		$this->post_date_format = $format;
	}

	/**
	 * Set if WordPress uploads should be copied in the export.
	 *
	 * @param bool $copy_uploads should copy uploads.
	 */
	public function set_copy_uploads( $copy_uploads ) {
		$this->copy_uploads = $copy_uploads;
	}

	/**
	 * Set if DOMDocument should use the blog_charset for encoding.
	 *
	 * @param bool $enforce enforce database charset.
	 */
	public function set_enforce_charset( $enforce ) {
		$this->enforce_charset = $enforce;
	}

	/**
	 * Set if original uploaded images should be exported.
	 *
	 * @param bool $original_images should use original uploaded images.
	 */
	public function set_original_images( $original_images ) {
		$this->use_original_images = $original_images;
	}

	/**
	 * Set fields that should be converted to Markdown.
	 *
	 * @param array $fields front matter fields to convert.
	 */
	public function set_fields_to_markdown( $fields ) {
		$this->fields_to_markdown = $fields;
	}

	/**
	 * Front matter fields to exclude from the exported file.
	 *
	 * @param array $fields front matter fields to skip.
	 */
	public function set_excluded_front_matter( $fields ) {
		$this->excluded_front_matter = $fields;
	}

	/**
	 * Directory in the export to copy WordPress uploads.
	 *
	 * @param string $dir where to copy uploads.
	 */
	public function set_destination_upload_dir( $dir ) {
		$this->destination_upload_dir = $dir;
	}

	/**
	 * Front matter field names to remap.
	 *
	 * @param array $fields front matter fields to change names.
	 */
	public function set_remap_fields( $fields ) {
		$this->remap_fields = $fields;
	}

	/**
	 * Front matter fields to convert to arrays.
	 *
	 * @param array $fields fields to modify.
	 */
	public function set_fields_to_array( $fields ) {
		$this->fields_to_array = $fields;
	}

	/**
	 * If post type directories should be created.
	 *
	 * @param bool $create if directories should be created.
	 */
	public function set_create_type_directory( $create ) {
		$this->create_type_directory = $create;
	}

	/**
	 * Get the posts that will be exported.
	 *
	 * @return array
	 */
	protected function get_posts() {
		$query_options = array(
			'fields'      => 'ids',
			'nopaging'    => true,
			'post_type'   => $this->post_types,
			'post_status' => $this->post_status,
		);

		$query_options = apply_filters( 'gatsby_exporter_query_options', $query_options );

		$query = new WP_Query( $query_options );
		$posts = $query->posts;

		$posts = apply_filters( 'gatsby_exporter_posts', $posts );
		return $posts;
	}

	/**
	 * Recursively copy a source directory to a destination directory.
	 *
	 * @param string $source source directory.
	 * @param string $destination destination directory.
	 */
	protected function copy_recursive( $source, $destination ) {
		$filesystem = new Filesystem();
		$filesystem->mirror( $source, $destination );
	}
}
