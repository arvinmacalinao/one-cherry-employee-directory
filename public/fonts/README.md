# Brand fonts

Proxima Nova and Mint Sans are commercially licensed and are not included in this
repository. `resources/css/app.css` already declares the `@font-face` rules that
expect these exact filenames — drop the licensed `.woff2` files in here and they'll
load automatically, no other changes needed:

```
public/fonts/
  ProximaNova-Regular.woff2
  ProximaNova-Bold.woff2
  MintSans-Regular.woff2
  MintSans-Medium.woff2
```

Until then, the app falls back to a close system-font stack (`-apple-system`,
`Segoe UI`, etc.) so nothing is broken in the meantime.
