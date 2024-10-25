# TSF Snippets

A collection of well-maintained snippets for [The SEO Framework](https://theseoframework.com/) WordPress plugin.

## How to download a snippet

We created a snippet-to-ZIP plugin service. 

Use the endpoint `https://dl.theseoframework.com/get/snippet/` or `https://tsf.fyi/snippet/get/` to obtain a snippet plugin file.<br>
Append the folder and filename to the URL to obtain the snippet.

### Example

If we add `schema/tsf-image-graph` to the URL, you'll obtain a ZIP file of [schema/tsf-image-graph.php](https://github.com/sybrew/tsf-snippets/blob/main/schema/tsf-image-graph.php).<br>
Try it: https://tsf.fyi/snippet/get/schema/tsf-image-graph.

### Files may be outdated

Our [snippet-to-ZIP plugin service](https://gist.github.com/sybrew/3b2a0ef34712398105eddfc1ca25fbb5) creates a new caching hash every 4 hours. So, obtaining the latest version of a snippet can take up to 4 hours.

## How to install a snippet

1. Download the snippet ZIP file.
1. Log in to your WordPress Dashboard.
1. Go to "Plugins > Add New."
1. Click "Upload Plugin" at the top.
1. Upload the snippet ZIP file you just downloaded.
1. You can now activate the snippet plugin. Enjoy :)
