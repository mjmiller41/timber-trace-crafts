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

## Shared Components

### TopAppBar (Mobile)
- **Background:** `base`
- **Branding:** "Timber Trace Crafts" in `Playfair Display`, `Charcoal`.
- **Icons:** `menu` (leading), `shopping_bag` (trailing).

### NavigationDrawer
- **Style:** High-contrast sidebar using `Playfair Display` for primary navigation links.
- **Links:** The Workshop, Gallery, Custom Orders, Natural Philosophy, Contact.

### Footer
- **Background:** `accent-1` (Deep Forest Green)
- **Text:** `base` (Oak Sand)
- **Links:** Sustainability Statement, Care Instructions, Privacy Policy.

---

## Screen Inventory

### [{{DATA:SCREEN:SCREEN_23}}] Storefront Landing (Mobile)
A sophisticated entry point featuring high-impact hero imagery and "Latest from the Workshop" curated collections.

### [{{DATA:SCREEN:SCREEN_22}}] Shop: Jewelry (Mobile)
A category list page showcasing "Monarch Butterfly" and "Teardrop Filigree" designs with wood-species filtering.

### [{{DATA:SCREEN:SCREEN_19}}] Product: Heart Box (Mobile)
Detailed product view for the 'Forever Infinity' box, emphasizing dimensions, material provenance, and hand-etched details.

### [{{DATA:SCREEN:SCREEN_20}}] Cart & Checkout (Mobile)
A streamlined flow combining shopping bag review and a secure, single-page secure checkout.

### [{{DATA:SCREEN:SCREEN_17}}] The Workshop (Mobile)
The brand's narrative heart, detailing "The Poetry of the Grain" and ethical sourcing philosophy.

---

## Antigravity-CLI Instructions

1. **Install the CLI:**
   ```bash
   npm install -g @antigravity/cli
   ```

2. **Initialize Project:**
   ```bash
   antigravity init
   ```

3. **Sync Tokens:**
   Pass the `theme.json` to sync your "Deep Forest" palette:
   ```bash
   antigravity sync --theme ./theme.json
   ```

4. **Scaffold Components:**
   Generate the hand-crafted UI component library:
   ```bash
   antigravity scaffold --design-md ./design.md
   ```

5. **Deploy:**
   Push your changes to the environment:
   ```bash
   antigravity deploy
   ```
