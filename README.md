# WordPress to Gatsby Exporter
A command line WordPress plugin to export WordPress content to Markdown for use with [GatsbyJS](https://www.gatsbyjs.org/).

Want to build real-time editing into your Gatsby site? Checkout [TinaCMS](https://tinacms.org/)!

## Features

  - Move WordPress content to Gatsby-friendly Markdown.
  - Remap and restructure exported fields via CLI options.
  - Avoid PHP timeouts by using the command line with WP-CLI (included).
  - Export progress bar (great for large sites)!
  - Customize the export using the available [command line options](#Options).

## Usage

    ./vendor/bin/wp gatsby-export --directory=/example/gatsby-starter/src/content

## Installation Methods
### Git clone + Composer
Or clone this repository into wp-content/plugins, then navigate to the `wp-gatsby-exporter` directory:

    composer install

## Enable the plugin
Activate "WordPress to Gatsby Exporter" from the WordPress admin.
Or from the plugin's directory:

    ./vendor/bin/wp plugin activate wp-gatsby-exporter

## Working with Gatsby
  If you're just getting started with Gatsby, we recommend experimenting with a Markdown based Gatsby starter like [Tina Grande](https://github.com/tinacms/tina-starter-grande). Installation is as easy as:


    git clone https://github.com/tinacms/tina-starter-grande.git

    cd tina-starter-grande

    yarn install && gatsby develop

Then copy your exported WordPress Markdown into the `content` directory or point the wp gatsby-export command at the content directory.

  Want to port your WordPress or HTML theme to Gatsby? Checkout [Porting an HTML Site to Gatsby](https://www.gatsbyjs.org/docs/porting-an-html-site-to-gatsby/) for tips.
## Options

All CLI arguments are optional.

| Option | Description | Default value
|--|--|--|
|`--help`|get help
|`--directory=`|export output directory|random temp directory
|`--post_types=`|post types to export, see: https://developer.wordpress.org/reference/classes/wp_query/#post-type-parameters |page,post
|`--post_status=`|post status to export, see: https://developer.wordpress.org/reference/classes/wp_query/#status-parameters|any
|`--fields_to_markdown=`|fields to convert to Markdown|excerpt
|`--excluded_front_matter=`|fields to exclude from front matter
|`--post_date_format=`|format for post publish date, see: https://www.php.net/manual/en/function.date.php|c
|`--upload_dir=`|directory in the export to copy WordPress uploads|uploads
|`--remap_fields=`|remap front matter field names, example: find1,replace1;find2,replace2
|`--fields_to_array=`|convert single value front matter fields to arrays
|`--skip_copy_uploads`|flag, skips copying WordPress uploads to the export
|`--skip_original_images`|flag, skips the use of original dimension images
|`--skip_enforce_charset`|flag, skips use of blog_charset for the XML charset

## Hooks
  The following hooks can be implemented in a plugin to modify the behaviour of the exporter:

|Hook|Notes|
|--|--|
|`gatsby_exporter_posts`|array of the post ids to be exported
|`gatsby_exporter_query_options`|array of query options for the posts to be exported database query
|`gatsby_exporter_post_html_body`|string of the post's HTML body
|`gatsby_exporter_post_converted_body`|string of the post's body after conversion to Markdown
|`gatsby_exporter_post_meta`|array of the post's meta fields that will become front matter fields

## Roadmap

 - Add a how-to video.
 - More/better unit tests.
 - Allow for partial/resumable export.

## Troubleshooting

What does `Warning: The markdown converter encountered invalid HTML and could not convert the content at:` mean?

This means that the markdown converter couldn't convert the referenced post to markdown, so we will just copy the HTML as it is.

## Acknowledgments
The structure for this plugin was heavily influenced by Ben Balter's excellent [WordPress to Jekyll Exporter](https://github.com/benbalter/wordpress-to-jekyll-exporter).

## License
This project is licensed under the GPLv3 License - see the [LICENSE](https://github.com/tinacms/wp-gatsby-exporter/blob/master/LICENSE) file for details.