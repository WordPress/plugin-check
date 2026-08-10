## Escaping Issues

An escaping issue is data that is not escaped before being output.

Using the case as a reference, check the code to see if the case in question has been escaped.

Details:
- Data must be escaped as late as possible, ideally as part of the output statement.
- Escaping earlier in the code and then outputting later is not considered late escaping.
- Common escaping functions: `esc_html()`, `esc_attr()`, `esc_url()`, `esc_js()`, `esc_textarea()`, `wp_kses()`, `wp_kses_post()`, `wp_kses_data()`.
- `__()`, `_e()`, `_x()` and similar i18n functions do NOT escape data.
- `printf()` / `sprintf()` do NOT escape data by themselves.
- If the value being output is a hardcoded string with no variables, it is not an issue.
- If the value is the direct return of an escaping function, it is not an issue.
- If the value comes from a function that internally escapes its output (e.g., `get_avatar()`, `paginate_links()`, `wp_nonce_field()`), it may not be an issue depending on context.
- Check if the data flows through any escaping function before the output point.
