{**
 * SearchProtect - Invalid search response
 *
 * @copyright 2026 Tecnoacquisti.com
 * @license MIT
 *}
<!doctype html>
<html lang="{$searchprotect_language|escape:'html':'UTF-8'}" dir="{if $searchprotect_rtl}rtl{else}ltr{/if}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>{$searchprotect_title|escape:'html':'UTF-8'}</title>
  <style>
    {literal}
    * { box-sizing: border-box; }
    body { margin: 0; padding: 24px; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f5f6f8; color: #253247; font-family: system-ui, sans-serif; line-height: 1.6; }
    main { width: 100%; max-width: 520px; padding: 40px 28px; border: 1px solid #e1e5eb; border-radius: 16px; background: #fff; text-align: center; box-shadow: 0 8px 32px rgba(25, 40, 65, .06); }
    .status { margin: 0 0 12px; color: #596579; font-size: 14px; font-weight: 700; letter-spacing: .12em; }
    h1 { margin: 0 0 12px; font-size: clamp(24px, 5vw, 32px); line-height: 1.25; }
    .message { margin: 0 0 28px; color: #596579; }
    a { display: inline-block; max-width: 100%; padding: 12px 24px; border-radius: 8px; background: #253247; color: #fff; font-weight: 600; text-decoration: none; overflow-wrap: anywhere; }
    a:hover { background: #374962; }
    a:focus-visible { outline: 3px solid #236ac5; outline-offset: 4px; }
    {/literal}
  </style>
</head>
<body>
  <main aria-labelledby="searchprotect-title">
    <p class="status">400</p>
    <h1 id="searchprotect-title">{$searchprotect_title|escape:'html':'UTF-8'}</h1>
    <p class="message">{$searchprotect_message|escape:'html':'UTF-8'}</p>
    <a href="{$searchprotect_home_url|escape:'html':'UTF-8'}">{$searchprotect_home_label|escape:'html':'UTF-8'}</a>
  </main>
</body>
</html>
