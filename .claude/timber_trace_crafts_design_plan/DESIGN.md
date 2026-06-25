---
name: Artisanal Editorial
colors:
  surface: '#fff8f4'
  surface-dim: '#ebd6c5'
  surface-bright: '#fff8f4'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#fff1e7'
  surface-container: '#ffead9'
  surface-container-high: '#fae4d2'
  surface-container-highest: '#f4dfcd'
  on-surface: '#24190f'
  on-surface-variant: '#474740'
  inverse-surface: '#3a2e22'
  inverse-on-surface: '#ffeee0'
  outline: '#787770'
  outline-variant: '#c8c7be'
  surface-tint: '#5f5e59'
  primary: '#5f5e59'
  on-primary: '#ffffff'
  primary-container: '#f4f1ea'
  on-primary-container: '#6f6d68'
  inverse-primary: '#c9c6c0'
  secondary: '#5f5e5e'
  on-secondary: '#ffffff'
  secondary-container: '#e4e2e1'
  on-secondary-container: '#656464'
  tertiary: '#456553'
  on-tertiary: '#ffffff'
  tertiary-container: '#d5fae2'
  on-tertiary-container: '#547562'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#e5e2db'
  primary-fixed-dim: '#c9c6c0'
  on-primary-fixed: '#1c1c18'
  on-primary-fixed-variant: '#474742'
  secondary-fixed: '#e4e2e1'
  secondary-fixed-dim: '#c8c6c6'
  on-secondary-fixed: '#1b1c1c'
  on-secondary-fixed-variant: '#474747'
  tertiary-fixed: '#c7ebd4'
  tertiary-fixed-dim: '#abcfb8'
  on-tertiary-fixed: '#002113'
  on-tertiary-fixed-variant: '#2d4d3c'
  background: '#fff8f4'
  on-background: '#24190f'
  surface-variant: '#f4dfcd'
  oak-sand: '#F4F1EA'
  charcoal: '#333333'
  forest-green: '#2C4C3B'
  mahogany: '#4A2C11'
  pine-shadow: '#1E3529'
  walnut: '#8C7B6C'
  pure-white: '#FFFFFF'
  absolute-black: '#000000'
typography:
  headline-xxl:
    fontFamily: Playfair Display
    fontSize: 3rem
    fontWeight: '300'
    lineHeight: '1.125'
    letterSpacing: -0.1px
  headline-xxl-mobile:
    fontFamily: Playfair Display
    fontSize: 2.15rem
    fontWeight: '300'
    lineHeight: '1.125'
    letterSpacing: -0.1px
  headline-xl:
    fontFamily: Playfair Display
    fontSize: 2rem
    fontWeight: '300'
    lineHeight: '1.125'
    letterSpacing: -0.1px
  headline-xl-mobile:
    fontFamily: Playfair Display
    fontSize: 1.75rem
    fontWeight: '300'
    lineHeight: '1.125'
    letterSpacing: -0.1px
  body-lg:
    fontFamily: Montserrat
    fontSize: 1.375rem
    fontWeight: '400'
    lineHeight: '1.4'
    letterSpacing: '0px'
  body-lg-mobile:
    fontFamily: Montserrat
    fontSize: 1.125rem
    fontWeight: '400'
    lineHeight: '1.4'
    letterSpacing: '0px'
  body-md:
    fontFamily: Montserrat
    fontSize: 1rem
    fontWeight: '400'
    lineHeight: '1.4'
    letterSpacing: '0px'
  meta-text:
    fontFamily: Montserrat
    fontSize: 0.875rem
    fontWeight: '600'
    lineHeight: '1.4'
    letterSpacing: 1.4px
  code-block:
    fontFamily: Source Code Pro
    fontSize: 0.9rem
    fontWeight: '400'
    lineHeight: '1.5'
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  tiny: 10px
  x-small: 20px
  small: 30px
  regular: clamp(30px, 5vw, 50px)
  large: clamp(30px, 7vw, 70px)
  x-large: clamp(50px, 7vw, 90px)
  xx-large: clamp(70px, 10vw, 140px)
  content-max-width: 645px
  wide-max-width: 1340px
---

## Brand & Style

This design system embodies an organic, premium editorial aesthetic tailored for high-end lifestyle, botanical, or interior design publications. The brand personality is sophisticated and grounded, favoring tactile warmth over clinical digital precision.

The design style is **Minimalist Editorial**. It prioritizes heavy use of whitespace, a narrow reading column for optimal focus, and a high-contrast relationship between classical serif display faces and functional sans-serif body text. The visual language evokes the feeling of a well-crafted physical monograph, utilizing a warm, paper-inspired base to create a sense of timeless quality.

## Colors

The color palette is nature-inspired and earthy. **Oak Sand** serves as the root background color, providing a softer, more organic foundation than pure white. **Charcoal** is utilized for primary text and high-contrast UI elements like buttons to ensure legibility. 

**Deep Forest Green** acts as the primary accent for brand moments, supported by **Rich Mahogany** and **Pine Shadow** for depth. **Walnut** is specifically reserved for metadata, secondary hierarchy, and focus indicators. Pure white is used exclusively for code block backgrounds to ensure technical clarity, while absolute black is restricted to structural separators and input borders.

## Typography

This system utilizes a sophisticated typographic pairing: **Playfair Display** for high-impact editorial headings and **Montserrat** for functional body copy and UI elements.

- **Headings:** Maintain a tight line-height of 1.125 to create a dense, editorial feel. 
- **Body Text:** Set to a weight of 400 with a letter-spacing of 0 to ensure maximum accessibility and a comfortable reading experience on the Oak Sand background.
- **Meta-text:** Uses the Walnut color with a semi-bold weight and uppercase treatment to establish clear hierarchy for dates, tags, and small captions.
- **Technical Text:** Source Code Pro is used for code blocks to provide a clean, monospaced contrast to the organic tones of the system.

## Layout & Spacing

The layout follows a **Fixed-Fluid Hybrid** model. While the page containers are fluid, the primary content column is strictly constrained to 645px to maintain an ideal line length for long-form reading. 

Spacing relies on a "clamp" system that scales based on the viewport, ensuring that section headers and margins feel appropriately generous on desktop while remaining functional on mobile. Vertical rhythm is driven by the 1.2rem block gap, maintaining consistent separation between paragraphs and media elements.

## Elevation & Depth

Hierarchy is achieved through **Tonal Layering** and **Bold Outlines** rather than traditional shadows. 

- **Surfaces:** Depth is flat, with contrast derived from color blocks (e.g., Charcoal buttons against Oak Sand).
- **Outlines:** Focused elements use a 2px offset outline in Walnut to provide clear feedback without disrupting the flat aesthetic.
- **Dividers:** Use 1px absolute black lines for structural separation, echoing the precision of a printed broadsheet.
- **Overlays:** Hover states utilize opacity shifts (85% for solid buttons, 5% for ghost buttons) to provide tactile interaction without adding artificial shadows.

## Shapes

The shape language is primarily **Soft** and functional, with specific exceptions for interactive and high-profile elements:
- **UI Elements:** Textareas and inputs use a minimal 0.25rem (4px) radius for a modern, crisp feel.
- **Interactive Pills:** Search inputs and primary action buttons use a 3.125rem radius (Pill-shaped) to make them instantly recognizable as interactive touchpoints.
- **Avatars:** Strictly circular (100px) to distinguish human elements from structural UI components.

## Components

- **Buttons:** Primary buttons are solid Charcoal with White text. Secondary buttons use an outline style (1px solid current color). Hover states use an 85% opacity mix.
- **Input Fields:** Styled with a 1px Absolute Black border and 4px roundedness. Focus states must trigger the 2px Walnut offset outline.
- **Cards:** Content cards should not have shadows; instead, they rely on generous spacing and optional Pine Shadow or Mahogany borders for emphasis.
- **Meta-labels:** Chips and tags use the meta-text style (uppercase Montserrat, Walnut color) with small padding and no background to maintain an understated appearance.
- **Pullquotes:** Feature Playfair Display at XX-Large size with a 2px vertical border on the left, rendered in Mahogany.