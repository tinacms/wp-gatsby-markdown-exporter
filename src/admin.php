<?php
/**
 * Admin form.
 *
 * @package    GatsbyExporter
 */

?>
<style>
	.gatsby-exporter label {
		font-weight: bold;
		margin-right: 2em;
		margin-bottom: .5em;
	}
	.gatsby-exporter .form-field {
		margin: 2em 0;
	}
	.gatsby-exporter .form-field input,
	.gatsby-exporter .form-field textarea {
		max-width: 400px;
	}
	.gatsby-exporter .form-block {
		display: block;
	}
	.gatsby-exporter .mr {
		margin-right: 1em;
	}
	.gatsby-exporter input[type=submit] {
		background-color: #00d1b2;
		color: #ffffff;
		padding: 1em;
		border-radius: 4px;
	}
	.gatsby-exporter input[type=submit]:hover {
		background-color: #00c6a7;
	}
	.gatsby-exporter .form-more {
		margin-top: 3em;
	}
</style>
<h1><?php print esc_html__( 'Export to Gatsby', 'gatsby-exporter' ); ?></h1>
<p><?php print esc_html__( 'Export your WordPress content to a zip file to use in a Markdown based Gatsby site like', 'gatsby-exporter' ); ?> <a target="_blank" href="https://github.com/tinacms/wp-gatsby-exporter/blob/master/README.md#working-with-gatsby">Tina Grande.</a> </p>
<p><?php print esc_html__( 'Running into issues with timeouts on a large site? Try the included WP-CLI command!', 'gatsby-exporter' ); ?></p>
<form class="gatsby-exporter" method="post">
	<?php wp_nonce_field( 'gatsby_export', 'zip_exporter' ); ?>

	<div class="form-field">
		<label class="form-block"><?php print esc_html__( 'Post types', 'gatsby-exporter' ); ?></label>
		<?php
		foreach ( $post_types as $p_type ) {
			$checked = '';
			if ( 'post' === $p_type->name || 'page' === $p_type->name ) {
				$checked = 'checked';
			}
			print '<span class="mr"><input type="checkbox" name="post_type[]" value="' . esc_attr( $p_type->name ) . '" ' . esc_attr( $checked ) . '>' . esc_html( $p_type->label ) . '</span>';
		}
		?>
	</div>

	<div class="form-field">
		<label class="form-block" for="post_status"><?php print esc_html__( 'Post status', 'gatsby-exporter' ); ?></label>
		<select name="post_status" id="post_status">
			<option value="any">any</option>
			<?php
			foreach ( $post_status as $p_status ) {
				print '<option value="' . esc_attr( $p_status ) . '">' . esc_html( $p_status ) . '</option>';
			}
			?>
		</select>
	</div>

	<div class="form-field">
		<input type="submit" value="<?php print esc_attr__( 'Download Zip File', 'gatsby-exporter' ); ?>" class="form-block">
	</div>


	<fieldset class="form-more">
		<hr>
		<h2><?php print esc_html__( 'More Export Options', 'gatsby-exporter' ); ?></h2>
		<div class="form-field">
			<label id="post_date_format_label" for="post_date_format" class="form-block"><?php print esc_html__( 'Export date format', 'gatsby-exporter' ); ?></label>
			<input type="text" name="post_date_format" id="post_date_format" value="c" aria-labelledby="post_date_format_label" aria-describedby="post_date_format_desc">
			<small id="post_date_format_desc" class="form-block"><?php print esc_html__( 'The format for the exported date, see:', 'gatsby-exporter' ); ?> <a target="_blank" href="https://www.php.net/manual/en/function.date.php"><?php print esc_html__( 'PHP reference', 'gatsby-exporter' ); ?></a></small>
		</div>

		<div class="form-field">
			<label id="fields_to_markdown_label" for="fields_to_markdown" class="form-block"><?php print esc_html__( 'Convert fields to Markdown', 'gatsby-exporter' ); ?></label>
			<textarea id="fields_to_markdown" aria-labelledby="fields_to_markdown_label" aria-describedby="fields_to_markdown_desc">excerpt</textarea>
			<small id="fields_to_markdown_desc" class="form-block"><?php print esc_html__( 'One per line. List front matter fields that should be converted to Markdown from HTML.', 'gatsby-exporter' ); ?></small>
		</div>

		<div class="form-field">
			<label id="fields_to_exclude_label" for="fields_to_exclude" class="form-block"><?php print esc_html__( 'Exclude fields', 'gatsby-exporter' ); ?></label>
			<textarea id="fields_to_exclude" name="fields_to_exclude" aria-labelledby="fields_to_exclude_label" aria-describedby="fields_to_exclude_desc"></textarea>
			<small id="fields_to_exclude_desc" class="form-block"><?php print esc_html__( 'One per line. List front matter fields that should not be exported.', 'gatsby-exporter' ); ?></small>
		</div>

		<div class="form-field">
			<label id="remap_fields_label" for="remap_fields" class="form-block"><?php print esc_html__( 'Change field name', 'gatsby-exporter' ); ?></label>
			<textarea id="remap_fields" name="remap_fields" aria-labelledby="remap_fields_label" aria-describedby="remap_fields_desc"></textarea>
			<small id="remap_fields_desc" class="form-block"><?php print esc_html__( 'One per line. Modify the field name that is exported, format: old_name,new_name', 'gatsby-exporter' ); ?></small>
		</div>

		<div class="form-field">
			<label id="fields_to_array_label" for="fields_to_array" class="form-block"><?php print esc_html__( 'Convert fields to array', 'gatsby-exporter' ); ?></label>
			<textarea id="fields_to_array" name="fields_to_array" aria-labelledby="fields_to_array_label" aria-describedby="fields_to_array_label_desc"></textarea>
			<small id="fields_to_array_label_desc" class="form-block"><?php print esc_html__( 'One per line. Front matter fields that will be converted to a single value list.', 'gatsby-exporter' ); ?></small>
		</div>

		<div class="form-field">
			<label id="create_type_directory_label" for="create_type_directory"><?php print esc_html__( 'Create post type directories', 'gatsby-exporter' ); ?></label>
			<input type="checkbox" id="create_type_directory" name="create_type_directory" aria-labelledby="create_type_directory_label" aria-describedby="create_type_directory_desc">
			<small id="create_type_directory_desc" class="form-block"><?php print esc_html__( 'Create a directory for each post type in the export', 'gatsby-exporter' ); ?></small>
		</div>

		<div class="form-field">
			<label id="skip_copy_uploads_label" for="skip_copy_uploads"><?php print esc_html__( 'Skip copying uploads', 'gatsby-exporter' ); ?></label>
			<input type="checkbox" id="skip_copy_uploads" name="skip_copy_uploads" aria-labelledby="skip_copy_uploads_label" aria-describedby="skip_copy_uploads_desc">
			<small id="skip_copy_uploads_desc" class="form-block"><?php print esc_html__( 'WordPress uploaded media will not be copied to the export.', 'gatsby-exporter' ); ?></small>
		</div>

		<div class="form-field">
			<label id="skip_original_images_label" for="skip_original_images"><?php print esc_html__( 'Skip using original image dimensions', 'gatsby-exporter' ); ?></label>
			<input type="checkbox" id="skip_original_images" name="skip_original_images" aria-labelledby="skip_original_images_label" aria-describedby="skip_original_images_desc">
			<small id="skip_original_images_desc" class="form-block"><?php print esc_html__( 'Use images that have been already resized by WordPress, rather than originals.', 'gatsby-exporter' ); ?></small>
		</div>
	</fieldset>

	<input type="hidden" value="1" name="gatsby-export">

</form>
