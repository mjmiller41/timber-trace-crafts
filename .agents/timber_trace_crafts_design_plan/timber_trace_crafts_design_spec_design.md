# Timber Trace Crafts: Design Specification

## Brand Identity

**Timber Trace Crafts** is a brand rooted in craftsmanship, tradition, and the natural beauty of wood. The visual identity reflects a "Deep Forest" aesthetic—sophisticated, organic, and grounded.

## Design Tokens

### Colors

| Slug | Color | Name | Usage |
| :--- | :--- | :--- | :--- |
| `base` | `#F4F1EA` | Oak Sand | Main background, parchment feel. |
| `contrast` | `#333333` | Charcoal | Primary text, high readability. |
| `accent-1` | `#2C4C3B` | Deep Forest Green | Hero sections, primary buttons. |
| `accent-2` | `#4A2C11` | Rich Mahogany | Accents, secondary branding elements. |
| `accent-3` | `#1E3529` | Pine Shadow | Deep backgrounds, subtle depth. |
| `accent-4` | `#8C7B6C` | Walnut Accent | Meta text, borders, decorative lines. |
| `accent-5` | `#FFFFFF` | White | Code blocks, clean UI components. |
| `accent-6` | `#000000` | Black | High-contrast borders and inputs. |

### Typography

- **Headings:** `Playfair Display`, Serif. Elegant, classic, and high-contrast.
- **Body:** `Montserrat`, Sans-serif. Modern, clean, and highly legible at small sizes.
- **Monospace:** `Source Code Pro`. Used for technical details or code snippets.

### Spacing (Scale)

- **Tiny:** `10px`
- **Small:** `30px`
- **Regular:** `clamp(30px, 5vw, 50px)`
- **Large:** `clamp(30px, 7vw, 70px)`

---

## Components

### Buttons
- **Primary:** Filled with `contrast` (#333333), text in `base` (#F4F1EA).
- **Outline:** Transparent background, `currentColor` border, slight darkening on hover.
- **Border Radius:** Sharp (0px) or very slight for a "hand-cut" feel.

### Cards
- **Product Card:** `base` background, thin `accent-4` border, image-heavy.
- **Swatch Card:** Minimum height 220px, `accent-4` border, used for wood species display.

### Navigation
- **Mobile Menu:** Vertical site header, high-contrast links with `Playfair Display` for top-level items.

---

## Technical Instructions: `antigravity-cli`

To use these design files with the `antigravity-cli`, follow these steps:

1. **Install the CLI:**
   ```bash
   npm install -g @antigravity/cli
   ```

2. **Initialize your project:**
   ```bash
   antigravity init
   ```

3. **Import Design Tokens:**
   Pass the `theme.json` file to the CLI to sync tokens with your project:
   ```bash
   antigravity sync --theme ./theme.json
   ```

4. **Generate Components:**
   If you have component definitions in your Design.md, you can scaffold them:
   ```bash
   antigravity scaffold --design-md ./design.md
   ```

5. **Deploy:**
   Push your changes to the environment:
   ```bash
   antigravity deploy
   ```
