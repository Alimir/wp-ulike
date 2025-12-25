# Building the WP ULike Block

## Quick Build Instructions

1. Navigate to the block directory:
   ```bash
   cd includes/blocks/wp-ulike
   ```

2. Install dependencies (if not already done):
   ```bash
   npm install
   ```

3. Build the block:
   ```bash
   npm run build
   ```

This will create:
- `build/index.js` - Compiled JavaScript
- `build/index.asset.php` - Asset dependencies file
- `build/index.css` - Compiled editor styles
- `build/style-index.css` - Compiled frontend styles

## File Structure After Build

```
wp-ulike/
├── block.json              # Block metadata (references build files)
├── render.php              # Server-side render
├── package.json            # Build dependencies
├── src/                    # Source files
│   ├── index.js           # Source JavaScript
│   ├── editor.css         # Source editor styles
│   └── style.css          # Source frontend styles
└── build/                  # Built files (generated)
    ├── index.js           # Compiled JavaScript
    ├── index.asset.php    # Asset dependencies
    ├── index.css          # Compiled editor styles
    └── style-index.css    # Compiled frontend styles
```

## Important Notes

- **block.json** now references files in the `build/` directory
- Source files are in the `src/` directory
- After building, the block should appear in the Gutenberg editor
- If the block doesn't appear, check:
  1. Build completed successfully
  2. `build/index.js` and `build/index.asset.php` exist
  3. Clear browser cache
  4. Check browser console for JavaScript errors

## Development Mode

For development with watch mode:
```bash
npm run start
```

This will watch for changes and rebuild automatically.
