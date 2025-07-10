=== ThinkPixel Search and RAG ===
Contributors: bdobrica
Donate link: https://thinkpixel.io
Tags: ai, semantic search, rag, embeddings, natural language processing
Requires at least: 4.7
Tested up to: 6.7
Stable tag: 1.2.0
Requires PHP: 7.4
License: Apache-2.0
License URI: https://www.apache.org/licenses/LICENSE-2.0

ThinkPixel Search and RAG is a plugin that enhances your WordPress search by using AI-based semantic matching for your users to find relevant content.

== Description ==

Traditional WordPress search relies on basic keyword matching, which often overlooks the true semantic meaning of queries. ThinkPixel Search and RAG revolutionizes this by leveraging an external API that converts your content into semantic embeddings. This ensures that when users search for something, they find truly relevant results, even if the exact keyword doesn’t appear in the post or page.

= How it works =

1. **Registration & Validation**  
   Upon activation, ThinkPixel Search and RAG automatically registers your site with the ThinkPixel Cloud API, securely generating an API key after validating your domain.

2. **Content Indexing**  
   The plugin sends your chosen posts and pages to the ThinkPixel API, which splits them into sentence-like segments to generate embeddings. This allows for highly accurate content matching during searches.

3. **Search Handling**  
   When a user performs a search, the plugin transforms the search term into an embedding as well. It then queries the ThinkPixel API for the closest matching embeddings, returning the most relevant (up to 20) results.

4. **Security**  
   For added protection, the plugin uses short-lived JWT tokens that auto-renew. The plugin briefly exposes a public GET endpoint for domain validation, after which it closes. 

5. **Free (for now)**  
   Currently, there are no usage fees or quotas. However, this may change in the future as the operating costs grow. We’ll do our best to keep it affordable.

= Key Benefits =

- Faster, more intuitive search results through semantic matching.  
- Easy setup and automatic indexing—no need for third-party coding.  
- Control which posts/pages get indexed to maintain privacy or relevance.  
- Future-proof AI-based approach that outperforms basic keyword searches.

= Features =

1. **AI-Powered Semantic Search**  
   Returns relevant results by analyzing the *meaning* of the query, rather than exact keyword matches.

2. **Selective Indexing**  
   Choose which pages and posts should be indexed, helping keep private or irrelevant content out of search results.

3. **Automatic Domain Validation**  
   An automated process verifies your domain to activate the service, making setup seamless.

4. **Short-Lived JWT Tokens**  
   Ensures your API key usage is protected with secure, auto-renewing tokens.

5. **20 Result Limit**  
   By design, queries are limited to the top 20 relevant matches to keep response times quick.

6. **Zero Cost (Currently)**  
   No monthly quotas or fees for a limited time, though this may change as the plugin and infrastructure evolve.

== Frequently Asked Questions ==

= Do I need an external service for this plugin to work? =  
Yes, ThinkPixel Search and RAG requires an active connection to the ThinkPixel Cloud API to perform semantic indexing and searching. The ThinkPixel API connection is done to api.thinkpixel.io:8080 using TLS.

= Can I use it on a local development environment? =  
Because a live domain validation is required, using it on `localhost` or similar local setups is not supported. You will need a public domain.

= Is there a limit to how many posts/pages I can index? =  
Currently, there is no limit on indexing or searching. However, limits may be introduced in the future to maintain performance.

= What about privacy and security? =  
We only store embeddings (numeric representations) of your content; raw content is transmitted for processing during indexing, but not fully stored. We also use short-lived JWT tokens to protect your connection and API key.

== Screenshots ==

1. **ThinkPixel Sync Settings** – A look at the simple admin settings page where you can configure and store your API key.

== Changelog ==

= 1.3.1 =
* **Bug**: Fixed the HTML2MD converter by adding a body element to the HTML fragment.

= 1.3.0 =
* **Feature**: added API object configurable timeouts based on php.ini settings;
* **Feature**: added HTML to MarkDown converter for normalizing text at machine learning model input;
* **Feature**: allows the API gateway to set the maximum batch text size so that there are no losses caused by timeouts;

= 1.2.0 =
* **Feature**: Change in how the request API Key logic: moved request from Plugin class to UI class.
* **Feature**: Allow multiple types (error, info, warning, success) of notifications in admin area.

= 1.1.2 =
* **Bug**: Normalized verification API responses.

= 1.1.1 =
* **Bug**: Removed hardcoded API Key from Settings.

= 1.1.0 =
* **Bug**: Fixes the detection of existing API key. Before this update, the API function was always returning true.
* **Bug**: Replaces the text domain constant with the actual string value to comply with Wordpress Plugins.
* **Feature**: Adds a Request New API Key button in the interface so if the key is lost or corrupted, it can be recovered.

= 1.0.0 =
* Initial release with hourly sync for posts/pages.
* Secure API key encryption using WordPress salts.
* JWT authentication with expiration handling.
* Search override to fetch curated results from ThinkPixel.

== Upgrade Notice ==

= 1.0.0 =
First stable release. Securely sync content and override search with ThinkPixel.
